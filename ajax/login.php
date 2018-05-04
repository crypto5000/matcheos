<?php
session_start();

// require crypto
require_once('../crypto/defuse-crypto.phar');

// set up classes
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;    

// set default status code
$statusCode = 0;

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// set the defaults
$authToken = "";

// pull the data
$email = $_POST["field1"];
$password = $_POST["field2"];

// validation - check for html special characters
$email = validate($email);
$password = validate($password);

// validation function
function validate($message) {

	$message = htmlspecialchars($message);
	$message = stripslashes($message);
	$message = utf8_encode($message);
	$message = preg_replace('!\r\n?!', '\n', $message);
	
	return $message;
}

// validate that the field was submitted
if (!isset($email)) {  
    $statusCode = 2;	
} 

// validate that the field was submitted
if (strlen($email) < 1) {  
    $statusCode = 2;	
} 

// validate that the field is less than 500 characters
if (strlen($email) > 50000) {
    $statusCode = 2;	
}

// validate that the field a valid email 
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $statusCode = 2;	        
}

// validate that the field was submitted
if (!isset($password)) {  
    $statusCode = 2;	
} 

// validate that the field was submitted
if (strlen($password) < 1) {  
    $statusCode = 2;	
} 

// validate that the field is less than 500 characters
if (strlen($password) > 50000) {
    $statusCode = 2;	
}

// set the ip, host and user agent
$ipAddress = $_SERVER['REMOTE_ADDR'];
$refUserAgent = $_SERVER['HTTP_USER_AGENT'];

if (isset($ipAddress)) {
	$host =  gethostbyaddr($ipAddress);  
} else {
	$host = "unknown";
}

// include db and domain parameters
include('../include/config.php');
include('../include/domain.php');

// connect to database
$mysqli = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME, DBPORT);

// error message if connection failed
if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());		
		exit();
} 

// If hack attempt, log to database and email admin (if appropriate)
if ($statusCode > 0) {
			
	// set the page being hit
    $form = "login";
    
    // check if ip is unique
	$sql = "SELECT hackID FROM hackTable WHERE ipAddress = ?";
    
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "s", $ipAddress);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
        
        // get the number of rows
        $numRows = $stmt->num_rows;
        
        if ($numRows < 1) {

            // if first time from ip, send an email to the admin	 
			$to = ADMINEMAIL;  
			$subject = 'HACK ATTEMPT';
			$logging = "There was a hack attempt on login at: ";
            $logging .= $createDate;
            $logging .= ". For email: ".$email;
			$header = 'From: donotrespond@matcheos.com';
			if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}

        }

        // close the statement        
        $stmt->close();
        
    }
    	        
    // insert the field into the table
	$sql = "INSERT INTO hackTable (formPage, host, refUserAgent, ipAddress, createDate) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "sssss", $form, $host, $refUserAgent, $ipAddress, $createDate);

        // execute query
        mysqli_stmt_execute($stmt);

        // close the statement
        $stmt->close();
        
    }

} else {
			
	// set the flags
	$aliveFlag = "yes";
	$deleteFlag = "no";
	$errorFlag = "no";        

    // query the database
	$sql = "SELECT userID, hashPassword, protectedKeyEncoded, failLogin, failAttempts FROM userTable WHERE email = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
    
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "ssss", $email, $aliveFlag, $deleteFlag, $errorFlag);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
        
        // get the number of rows
        $numRows = $stmt->num_rows;
        
        if ($numRows < 1) {
        
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'FAILED LOGIN ALERT';
            $logging = "There was a failed login on loginForm at: ";
            $logging .= $createDate;
            $logging .= ' for email: '.$email;
            $logging .= ' for ipAddress: '.$ipAddress;			
            $logging .= '. Email user to inform to change password.';
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
            
            // does not exist
            $statusCode = 2;			   												
        
        } else {

            // bind result variables
            mysqli_stmt_bind_result($stmt, $userIDBind, $hashPasswordBind, $protectedKeyEncodedBind, $failLoginBind, $failAttemptsBind);							
            
            // fetch the results
            $stmt->fetch();
                            
            // set variables
            $userID = $userIDBind;
            $failLogin = $failLoginBind;
            $failAttempts = $failAttemptsBind;
            $hashPassword = $hashPasswordBind;				
            $protectedKeyEncoded = $protectedKeyEncodedBind;
                        
            // close statement
            mysqli_stmt_close($stmt);

            // check if the login is locked for 24hours
            if (isset($failLogin)) {

                // check if lastLogin was over 6 hours ago			
			    $plus24Hrs = " + 24 hours";
			    $failLogin = $failLogin.$plus24Hrs;
			
			    $failLogin = date("Y-m-d H:i:s",strtotime($failLogin));
			    $currentDateTime = date("Y-m-d H:i:s"); 
			
                // check the lock has expired
                if (strtotime($createDate) > strtotime($failLogin)) {
                    
                    // lock has expired, clear lock
                    $failAttempts = 0;
                    $failLogin = NULL;
                    
                    // update the table
                    $sqlUpdate = "UPDATE userTable SET failLogin=?, failAttempts=? WHERE email=? AND aliveFlag=?";
                    
                    if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                                
                        // bind parameters for markers
                        mysqli_stmt_bind_param($stmt2, "siss", $failLogin, $failAttempts, $email, $aliveFlag);

                        // execute query
                        mysqli_stmt_execute($stmt2);

                        // close the statement
                        $stmt2->close();
                        
                    }
                    
                } else {

                    // error to user
                    $statusCode = 3;

                }
                    
            }

            // if still ok,
            if ($statusCode == 0) {

                // verify password
                if (password_verify($password, $hashPassword)) {						
            
                    // generate a random string for authtoken
                    $randBytes = random_bytes(81);
                    $authToken = bin2hex($randBytes);                    

                    // reset any failed logins
                    $failAttempts = 0;
                    $failLogin = NULL;                    
                                                    
                    // update the table
                    $sqlUpdate = "UPDATE userTable SET lastLogin=?, token=?, failAttempts=?, failLogin=?, lastIpAddress=? WHERE email=? AND hashPassword=? AND aliveFlag=?";
                    
                    if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                                
                        // bind parameters for markers
                        mysqli_stmt_bind_param($stmt2, "ssisssss", $createDate, $authToken, $failAttempts, $failLogin, $ipAddress, $email, $hashPassword, $aliveFlag);

                        // execute query
                        mysqli_stmt_execute($stmt2);

                        // close the statement
                        $stmt2->close();
                        
                    }                    

                    // set the userKeyEncoded for the session
                    $protectedKey = KeyProtectedByPassword::loadFromAsciiSafeString($protectedKeyEncoded);
                    try {
                                
                        $userKey = $protectedKey->unlockKey($password);
                        $userKeyEncoded = $userKey->saveToAsciiSafeString();
                        
                        // set the protected key for the duration of the user's session
                        $_SESSION["userKeyEncoded"] = $userKeyEncoded;
                    
                        // set the status code to ok			   			  
                        $statusCode = 1;			   	
                        
                    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                        
                        // no decryption                    
                        $statusCode = 500;
                    }
                    
                } else {
                    
                    // invalid password
                    $statusCode = 2;

                    // invalid password attempt, update the failAttempts
                    $failAttempts = $failAttempts + 1;
                    $failLogin = NULL;

                    // check if 3 attempts, set failLogin
                    if ($failAttempts >= 3) {

                        // max attempts
                        $statusCode = 3;

                        // set to current time
                        $failLogin = $createDate;

                    }                     

                    // update the database
                    $sqlUpdate = "UPDATE userTable SET failLogin=?, failAttempts=? WHERE email=? AND aliveFlag=?";
                    
                    if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                                
                        // bind parameters for markers
                        mysqli_stmt_bind_param($stmt2, "siss", $failLogin, $failAttempts, $email, $aliveFlag);

                        // execute query
                        mysqli_stmt_execute($stmt2);

                        // close the statement
                        $stmt2->close();
                        
                    }


                }

            }

        }
    
    }

    // if success,
    if ($statusCode == 1) {

        // set the usedFlag
        $usedFlag = "no";

        // check if any new keys need to be created        
        $sql = "SELECT encodedKeyID FROM encodedKeyTable WHERE userID = ? AND usedFlag = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "issss", $userID, $usedFlag, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
            
            // get the number of rows
            $numRows = $stmt->num_rows;
            
            if ($numRows > 0) {
            
                // key exists, do not need to create

            } else {

                // key used, create another one
                $randBytes = random_bytes(128);
                $randBytesString = bin2hex($randBytes);

                // execute create keypair using random string
                $keyPairString = shell_exec('node '.MATCHEOSPATH.'/genkey.js '.escapeshellarg($randBytesString));        
                                
                // validate keyPairsting
                if (isset($keyPairString)) {

                    // trim whitespace
                    $keyPairString = trim($keyPairString);

                    // explode - format is private + " " + public
                    $keyPairArray = explode(" ", $keyPairString);

                    // validate 2 keys exist in pair
                    if (count($keyPairArray) != 2) {

                        // error - valid keypair not created    
                        $statusCode = 15;

                    } else {

                        $tempPrivate = $keyPairArray[0];
                        $tempPublic = $keyPairArray[1];

                        // validate length
                        if ((strlen($tempPrivate) == 51) && (strlen($tempPublic) == 53)) {
                            // do nothing                
                        } else {
                            // error - valid keypair not created    
                            $statusCode = 16;
                        }
                    
                        // validate format            
                        if (substr( $tempPublic, 0, 3 ) != "EOS") {
                            // error - valid keypair not created    
                            $statusCode = 17;
                        }            

                    }

                } else {

                    // error - valid keypair not created
                    $statusCode = 18;

                }
                
                // destroy key variables
                unset($keyPairString);
                unset($keyPairArray);
                unset($randBytesString);

                // if error, email admin
                if ($statusCode != 1) {

                    // send an email to the admin         
                    $to = ADMINEMAIL;  
                    $subject = 'FAILED TO GENERATE A VALID KEYPAIR ON LOGIN';
                    $logging = "There was an error generating a keypair on loginForm at: ";
                    $logging .= $createDate;
                    $logging .= ' for email: '.$email;
                    $logging .= ' for ipAddress: '.$ipAddress;			
                    $logging .= ' with statusCode: '.$statusCode;			                    
                    $header = 'From: donotrespond@matcheos.com';
                    if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
                    
                }

            }    

        }

        // if still ok
        if (($statusCode == 1) && (isset($tempPrivate))) {

            // get the matcheos userKey
            $userKeyMatcheos = Key::loadFromAsciiSafeString(MATCHEOSUSERKEYENCODED);
            try {
                        
                // encrypt with matcheos userKey
                $encryptedPrivateTemp = Crypto::encrypt($tempPrivate, $userKeyMatcheos);            

            } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                
                // no decryption                    
                $statusCode = 28;
            }

        }

        // if still ok
        if (($statusCode == 1) && (isset($tempPrivate))) {
            
            // encrypt with userKey            
            try {
                        
                // encrypt with userKey
                $encryptedPrivate = Crypto::encrypt($encryptedPrivateTemp, $userKey);  
            
            } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                
                // no decryption                    
                $statusCode = 28;
            }
            
            // set the doubleEncodeID - used for tracking of matcheosUserKey
            $doubleEncodeID = 1;

        }

        // if still ok
        if (($statusCode == 1) && (isset($tempPrivate))) {
        
            // insert into table
            $sql = "INSERT INTO encodedKeyTable (userID, encodedPrivate, public, doubleEncodeID, doubleEncodeDate, usedFlag, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
                // bind parameters for markers
                mysqli_stmt_bind_param($stmt, "ississsssss", $userID, $encryptedPrivate, $tempPublic, $doubleEncodeID, $createDate, $usedFlag, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

                // execute query
                mysqli_stmt_execute($stmt);

                // close the statement
                $stmt->close();
                
            }

        }

    }

    // if still ok,
    if ($statusCode == 1) {

        // check if a profile exists
        $sql = "SELECT profileID FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $userID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
            
            // get the number of rows
            $numRows = $stmt->num_rows;
            
            if ($numRows < 1) {
            
                // profile does not exist
                $statusCode = 4;

            } 

        }    

    }
    
}	

if (isset($protectedKeyEncoded)) {unset($protectedKeyEncoded);}        
if (isset($protectedKey)) {unset($protectedKey);}        
if (isset($userKeyMatcheos)) {unset($userKeyMatcheos);}        
if (isset($userKey)) {unset($userKey);}        
if (isset($password)) {unset($password);}        

/*
* 1 is ok
* 2 is invalid credentials
* 3 is max failed attempts
* 4 is ok, but needs to redirect to new profile
* 5 is all others
*/

// close connection
mysqli_close($mysqli);
    
$ajaxResponse = array(
	"ajaxResult" => $statusCode,	
    "authToken" => $authToken,	
);	

echo json_encode($ajaxResponse);


?>