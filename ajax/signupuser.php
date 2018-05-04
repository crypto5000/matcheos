<?php
session_start();

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
$accountName = $_POST["field1"];
$email = $_POST["field2"];
$birthYear = $_POST["field3"];
$language = $_POST["field4"];
$password = $_POST["field5"];

// validation - check for html special characters
$accountName = validate($accountName);
$email = validate($email);
$birthYear = validate($birthYear);
$language = validate($language);
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
if (!isset($accountName)) {  
    $statusCode = 2;	
} 

// validate that the field was submitted
if (strlen($accountName) < 1) {  
    $statusCode = 2;	
} 

// validate that the field is less than 500 characters
if (strlen($accountName) > 500) {
    $statusCode = 2;	
}

// validate that the field was submitted
if (!isset($email)) {  
    $statusCode = 3;	
} 

// validate that the field was submitted
if (strlen($email) < 1) {  
    $statusCode = 3;	
} 

// validate that the field is less than 500 characters
if (strlen($email) > 500) {
    $statusCode = 3;	
}

// validate that the field a valid email 
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $statusCode = 3;	        
}

// validate that the field was submitted
if (!isset($birthYear)) {  
    $statusCode = 4;	
} 

// validate that the field was submitted
if (strlen($birthYear) < 1) {  
    $statusCode = 4;	
} 

// validate that the field is less than 500 characters
if (strlen($birthYear) > 500) {
    $statusCode = 4;	
}

if (is_numeric($birthYear)) {
    $birthYear = (int) $birthYear;
    
    if ($birthYear > 2003) {
        $statusCode = 4;	    
    }

    if ($birthYear < 1918) {
        $statusCode = 4;	    
    }

} else {
    $statusCode = 4;	
}
// validate that the field was submitted
if (!isset($language)) {  
    $statusCode = 5;	
} 

// validate that the field was submitted
if (($language == "english") || ($language == "chinese") || ($language == "korean") || ($language == "russian") || ($language == "spanish")) {
    // do nothing
} else {
    $statusCode = 5;	
} 

// validate that the field was submitted
if (!isset($password)) {  
    $statusCode = 6;	
} 

// validate that the field was submitted
if (strlen($password) < 1) {  
    $statusCode = 6;	
} 

// validate that the field is less than 300 characters
if (strlen($password) > 300) {
    $statusCode = 6;	
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
    $form = "signupuser";
    
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
			$logging = "There was a hack attempt on signupuser at: ";
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

    // check that email does not already exist     
	$sql = "SELECT userID FROM userTable WHERE email = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
    
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "ssss", $email, $aliveFlag, $deleteFlag, $errorFlag);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
        
        // get the number of rows
        $numRows = $stmt->num_rows;
        
        if ($numRows > 0) {
                    
            // email already exists
            $statusCode = 6;			   												
        
        } 

    }
    
    // if ok,
    if ($statusCode == 0) {                        

        // verify that accountName exists on blockchain
        $data = array("account_name" => $accountName);                                                                    
        $data_string = json_encode($data);                                                                                   
                                                                                                                            
        $ch = curl_init(BLOCKCHAINURL_ACCOUNT);                                                                      
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($data_string))                                                                       
        );                                                                                                                   
                                                                                                                                    
        // if blockchain hosted on http, instead of https, remove certificate check
        if (strpos(BLOCKCHAINURL_ACCOUNT, 'http://') !== false) {            
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);        
        if (curl_error($ch)) {        

            // curl error - problem with blockchain connection            
            $statusCode = 2;

        } else {

            // parse the result to see if there are keys set for account
            $arrayString = json_decode($result, true);        

            $permissions = $arrayString["permissions"];
            if (isset($permissions)) {
                // check 2 keys have been set - active/owner
                if (count($permissions) != 2) {
                    // valid account does not exist
                    $statusCode = 2;
                }
            } else {
                // no permissions set - account does not exist
                $statusCode = 2;
            }
            
        }
        curl_close($ch);

    }

    // if ok, 
    if ($statusCode == 0) {
        
        // check that accountName does not already exist in matcheos
        $sql = "SELECT userID FROM userTable WHERE userName = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "ssss", $accountName, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
            
            // get the number of rows
            $numRows = $stmt->num_rows;
            
            if ($numRows > 0) {
                        
                // accountName already exists
                $statusCode = 6;			   												
            
            } 

        }

    }
        
    // if ok,
    if ($statusCode == 0) {

        // get the langID based on the language 
        $sql = "SELECT langID FROM langTable WHERE language = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "ssss", $language, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
            
            // get the number of rows
            $numRows = $stmt->num_rows;
            
            if ($numRows < 1) {
            
                // default to english
                $langID = 1;                
            
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $langIDBind);							
                
                // fetch the results
                $stmt->fetch();
                                
                // set variables
                $langID = $langIDBind;                
                
                // close statement
                mysqli_stmt_close($stmt);

            }

        }

        // default to english if error occurs
        if (!isset($langID)) {
            $langID = 1;
        }

    }

    // if ok, set the encoded key and authToken
    if ($statusCode == 0) {

        // set the protected key
        $protectedKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
        $protectedKeyEncoded = $protectedKey->saveToAsciiSafeString();

        // set the userKeyEncoded for the session        
        $userKey = $protectedKey->unlockKey($password);
        $userKeyEncoded = $userKey->saveToAsciiSafeString();
        
        // set the protected key for the duration of the user's session
        $_SESSION["userKeyEncoded"] = $userKeyEncoded;

        // set the authToken
        $randBytes = random_bytes(81);
        $authToken = bin2hex($randBytes);

    }
    
    // if ok, insert new user into database
    if ($statusCode == 0) {

        // set the default variables
        $hashPassword = password_hash($password, PASSWORD_DEFAULT);        
        $lastLogin = $createDate;
        $failAttempts = 0;
        $lastIpAddress = $ipAddress;
        
        // insert the field into the table
        $sql = "INSERT INTO userTable (email, userName, hashPassword, protectedKeyEncoded, birthYear, langID, lastLogin, token, failAttempts, aliveFlag, errorFlag, deleteFlag, lastIpAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "ssssiississsss", $email, $accountName, $hashPassword, $protectedKeyEncoded, $birthYear, $langID, $lastLogin, $authToken, $failAttempts, $aliveFlag, $errorFlag, $deleteFlag, $lastIpAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }

        // get the userID 
        $userID = mysqli_insert_id($mysqli);

        // set the start values
        $profileViews = 0;
        $newMatchesLove = 0;
        $newMatchesWork = 0;
        $newMatchesFriend = 0;
        $activeMatchesLove = 0;
        $activeMatchesWork = 0;
        $activeMatchesFriend = 0;
        $closedMatchesLove = 0;
        $closedMatchesWork = 0;
        $closedMatchesFriend = 0;
        $receivedOffersLove = 0;
        $receivedOffersWork = 0;
        $receivedOffersFriend = 0;
        $sentOffersLove = 0;
        $sentOffersWork = 0;
        $sentOffersFriend = 0;
        $sentOffersLoveEOS = 0.0000;
        $sentOffersWorkEOS = 0.0000;
        $sentOffersFriendEOS = 0.0000;

        // insert the start analytics        
        $sql = "INSERT INTO analyticsTable (userID, profileViews, newMatchesLove, newMatchesWork, newMatchesFriend, activeMatchesLove, activeMatchesWork, activeMatchesFriend, closedMatchesLove, closedMatchesWork, closedMatchesFriend, receivedOffersLove, receivedOffersWork, receivedOffersFriend, sentOffersLove, sentOffersWork, sentOffersFriend, sentOffersLoveEOS, sentOffersWorkEOS, sentOffersFriendEOS, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiiiiiiiiiiiiiiiidddsssss", $userID, $profileViews, $newMatchesLove, $newMatchesWork, $newMatchesFriend, $activeMatchesLove, $activeMatchesWork, $activeMatchesFriend, $closedMatchesLove, $closedMatchesWork, $closedMatchesFriend, $receivedOffersLove, $receivedOffersWork, $receivedOffersFriend, $sentOffersLove, $sentOffersWork, $sentOffersFriend, $sentOffersLoveEOS, $sentOffersWorkEOS, $sentOffersFriendEOS, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }

        // set the start values (set max to 0, min to 1 - each gets updated on first bid)
        $maxRankWork = 0;
        $rankCountWork = 0;
        $minRankWork = 1;
        $maxRankLove = 0;
        $rankCountLove = 0;
        $minRankLove = 1;
        $maxRankFriend = 0;
        $rankCountFriend = 0;
        $minRankFriend = 1;        
        
        // insert the start algorithm        
        $sql = "INSERT INTO algorithmTable (userID, maxRankWork, rankCountWork, minRankWork, maxRankLove, rankCountLove, minRankLove, maxRankFriend, rankCountFriend, minRankFriend, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "ididdiddidsssss", $userID, $maxRankWork, $rankCountWork, $minRankWork, $maxRankLove, $rankCountLove, $minRankLove, $maxRankFriend, $rankCountFriend, $minRankFriend, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }

        // set to ok
        $statusCode = 1;
        
    }
    
    // if success,
    if ($statusCode == 1) {
        
        // set the usedFlag
        $usedFlag = "no";

        // create new key
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
            $subject = 'FAILED TO GENERATE A VALID KEYPAIR ON SIGNUPUSER';
            $logging = "There was an error generating a keypair on signupuser at: ";
            $logging .= $createDate;
            $logging .= ' for email: '.$email;
            $logging .= ' for ipAddress: '.$ipAddress;			
            $logging .= ' with statusCode: '.$statusCode;			                    
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
            
        }
            
        // if still ok
        if ($statusCode == 1) {

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
        if ($statusCode == 1) {

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
        if ($statusCode == 1) {

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
            
}	

if (isset($protectedKeyEncoded)) {unset($protectedKeyEncoded);}        
if (isset($protectedKey)) {unset($protectedKey);}        
if (isset($userKeyMatcheos)) {unset($userKeyMatcheos);}        
if (isset($userKey)) {unset($userKey);}        
if (isset($password)) {unset($password);}        

/*
* 1 is ok
* 2 is invalid account name
* 3 is invalid email
* 4 is invaid birthyear
* 5 is invaid language
* 6+ is all others
*/

// close connection
mysqli_close($mysqli);
    
$ajaxResponse = array(
	"ajaxResult" => $statusCode,	
    "authToken" => $authToken,	
);	

echo json_encode($ajaxResponse);

?>