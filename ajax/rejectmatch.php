<?php
session_start();

require_once('../crypto/defuse-crypto.phar');

// set up classes
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;    

// set default status code
$statusCode = 0;

// set the default keyType to update contract
$keyType = "active";

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// pull the data
$matchID = $_POST["field1"];
$token = $_POST["field2"];
if (isset($_POST["field3"])) {
    $password = $_POST["field3"];
} 

// validation - check for html special characters
$matchID = validate($matchID);
$token = validate($token);

// validation function
function validate($message) {

	$message = htmlspecialchars($message);
	$message = stripslashes($message);
	$message = utf8_encode($message);
	$message = preg_replace('!\r\n?!', '\n', $message);
	
	return $message;
}

// validate that the field was submitted
if (!isset($matchID)) {  
    $statusCode = 2;	
}

// validate the field length
if (strlen($matchID) < 1) {  
    $statusCode = 2;	
} 

// validate the field length
if (strlen($matchID) > 200) {  
    $statusCode = 2;	
} 

// validate the field is a number
if (is_numeric($matchID)) {  
    $matchID = (int) $matchID;
} else {
    $statusCode = 2;	
}

// validate that the field was submitted
if (!isset($token)) {  
    $statusCode = 3;	
}

// validate the field length
if (strlen($token) < 1) {  
    $statusCode = 3;	
} 

// validate the field length
if (strlen($token) > 1000) {  
    $statusCode = 3;	
} 

// validate the password, if exists
if (isset($password)) {
    
    $password = validate($password);

    // validate that the field was submitted
    if (strlen($password) < 1) {  
        $statusCode = 50;	
    } 

    // validate that the field is less than 500 characters
    if (strlen($password) > 50000) {
        $statusCode = 51;	
    }

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
    $form = "rejectmatch";    
    
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
			$logging = "There was a hack attempt on rejectmatch at: ";
            $logging .= $createDate;
            $logging .= ". For matchID: ".$matchID;
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

    // check the token is authorized - pull user info
    $sql = "SELECT userID, email, userName, hashPassword, protectedKeyEncoded, lastLogin, birthYear, langID FROM userTable WHERE token=?";
    
    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "s", $token);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
                
        // get the number of rows
        $numRows = $stmt->num_rows;
                                
        if ($numRows < 1) {

            // invalid
            $statusCode = 10;
                    
        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $userIDBind, $emailBind, $userNameBind, $hashPasswordBind, $protectedKeyEncodedBind, $loginBind, $birthYearBind, $langIDBind);
                                                                                                                                                                    
            // fetch the results
            $stmt->fetch();
                            
            // set variables
            $userID = $userIDBind;		
            $currentEmail = $emailBind;
            $userName = $userNameBind;
            $hashPassword = $hashPasswordBind;
            $protectedKeyEncoded = $protectedKeyEncodedBind;
            $lastLogin = $loginBind;
            $birthYear = $birthYearBind;
            $langID = $langIDBind;
                    
            // close statement
            mysqli_stmt_close($stmt);

            // check if lastLogin was over 6 hours ago
            $plus6Hrs = " + 6 hours";
            $lastLogin = $lastLogin.$plus6Hrs;

            $lastLogin = date("Y-m-d H:i:s",strtotime($lastLogin));
            $currentDateTime = date("Y-m-d H:i:s"); 
                    
            if (strtotime($currentDateTime) > strtotime($lastLogin)) {

                // token expired
                $statusCode = 11;

            }            

            // verify password, if exists
            if (isset($password)) {
                if (password_verify($password, $hashPassword)) {						
                    // do nothing - valid
                } else {
                    // invalid password
                    $statusCode = 52;
                }
            }

        }

    }
    
    // if still ok,
    if ($statusCode == 0) {

        // verify match exists for the user, pull data
        $sql = "SELECT spotID1, spotID2, matchType, status1, status2, rejectFlag FROM matchTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiisss", $userID, $userID, $matchID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'REJECT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No user matchID found at: ";
                $logging .= $createDate;
                $logging .= ' for matchID: '.$matchID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 12;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $spotID1Bind, $spotID2Bind, $matchTypeBind, $status1Bind, $status2Bind, $rejectFlagBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $spotID1 = $spotID1Bind;		
                $spotID2 = $spotID2Bind;		
                $matchType = $matchTypeBind;
                $status1Match = $status1Bind;		
                $status2Match = $status2Bind;		
                $rejectFlag = $rejectFlagBind;		
                
                // check not rejected
                if ($rejectFlag == "yes") {
                    // not valid
                    $statusCode = 13;
                }
                                
                // verify status
                if ($spotID1 == $userID) {
                    
                    // check status is new for current user
                    if ($status1Match != "new") {
                        //  error
                        $statusCode = 14;
                    }

                    // status is new or active for other user
                    if ($status2Match == "closed") {
                        //  error
                        $statusCode = 15;
                    }                        

                } else {

                    // check status is new for current user
                    if ($status2Match != "new") {
                        //  error
                        $statusCode = 16;
                    }

                    // status is new or active for other user
                    if ($status1Match == "closed") {
                        //  error
                        $statusCode = 17;
                    }

                    // set the keyType - spot1 is owner, spot2 is active for contract update
                    if ($userID == $spotID1) {
                        $keyType = "owner";
                    } else {
                        $keyType = "active";
                    }
                
                }

                // close statement
                mysqli_stmt_close($stmt);

            }

        }
        
    }

    // if still ok
    if ($statusCode == 0) {

        $contractExist = "no";         
        $contractCount = 0;       

        // check if any contracts for this match, status should be in open or waiting        
         $sql = "SELECT contractID, wastID, spotID1, spotID2, status1, status2 FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
         
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiisss", $userID, $userID, $matchID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;            
                                    
            if ($numRows > 0) {
                
                // set the flag
                $contractExist = "yes";

                // bind result variables
                mysqli_stmt_bind_result($stmt, $contractIDBind, $wastIDBind, $spotID1Bind, $spotID2Bind, $status1Bind, $status2Bind);
                
                // cycle through and get the values
                while ($stmt->fetch()) {

                    // set the values
                    $contractID = $contractIDBind;
                    $wastID = $wastIDBind;
                    $spotID1 = $spotID1Bind;
                    $spotID2 = $spotID2Bind;
                    $status1 = $status1Bind;
                    $status2 = $status2Bind;

                    // verify status
                    if ($spotID1 == $userID) {
                    
                        if ($status1 != "waiting") {
                            //  error
                            $statusCode = 18;
                        }

                        if ($status2 != "open") {
                            //  error
                            $statusCode = 19;
                        }                        

                    } else {

                        if ($status2 != "waiting") {
                            //  error
                            $statusCode = 20;
                        }

                        if ($status1 != "open") {
                            //  error
                            $statusCode = 21;
                        }
                    
                    }

                    $contractCount++;

                }

                // email admin if there are more than 1 contract (open or waiting) - at most 1 (since person hasn't accepted)
                if ($contractCount > 1) {

                    $to = ADMINEMAIL;  
                    $subject = 'REJECT MATCH FAIL: MORE THAN 1 OPEN CONTRACT';
                    $logging = "Should be a max of 1 open contract at: ";
                    $logging .= $createDate;
                    $logging .= ' for matchID: '.$matchID;
                    $logging .= ' for userID: '.$userID;			      
                    $header = 'From: donotrespond@matcheos.com';
                    if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                    // invalid
                    $statusCode = 22;
    
                }
                
            } 

        }

    }

    // if still ok and contract exists
    if (($statusCode == 0) && ($contractExist == "yes")) {
        
        // get the contractName from the wastTable        
        $sql = "SELECT accountName, contractName FROM wastTable WHERE wastID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $wastID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'REJECT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No user contractName found at: ";
                $logging .= $createDate;
                $logging .= ' for wastID: '.$wastID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 23;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $accountNameBind, $contractNameBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $accountName = $accountNameBind;     
                $contractName = $contractNameBind;                

                // close statement
                mysqli_stmt_close($stmt);
            }

        }        

    }
        
    // if still ok and contract exists
    if (($statusCode == 0) && ($contractExist == "yes")) {
                    
        // pull the interaction table             
        $sql = "SELECT currentStep, status FROM interactTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $contractID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // first step has not happened yet
                $currentStep = 1;     
                $statusInteract = "open";                

            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $currentStepBind, $statusInteractBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $currentStep = $currentStepBind;     
                $statusInteract = $statusInteractBind;                

                // verify step is 1 or less
                if ($currentStep > 1) {
                    // not valid
                    $statusCode = 24;
                }

                // verify status is open
                if ($statusInteract != "open") {
                    // not valid
                    $statusCode = 25;
                }

                // close statement
                mysqli_stmt_close($stmt);

            }

        }            

    }

    // if still ok and contract exists
    if (($statusCode == 0) && ($contractExist == "yes")) {
        
        // determine whether user is active or owner (owner is spotID1 of contract, active is spotID2 of contract)
        if ($userID == $spotID1) {

            // pull owner from contractAdmin table            
            $sql = "SELECT ownerEncodedKeyID FROM contractAdminTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

        } else {

            // pull active from contractAdmin table
            $sql = "SELECT activeEncodedKeyID FROM contractAdminTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        }

        // pull encoded key id for contract (either active or owner)
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
            
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $contractID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'REJECT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No contractAdmin data found at: ";
                $logging .= $createDate;
                $logging .= ' for contractID: '.$contractID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 26;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $encodedKeyIDBind);

                // fetch the results
                $stmt->fetch();

                // set variables
                $encodedKeyID = $encodedKeyIDBind;                             

                // close statement
                mysqli_stmt_close($stmt);

            }

        }        

    }
     
    // if still ok and contract exists
    if (($statusCode == 0) && ($contractExist == "yes")) {
                
        // pull the encoded key using the encodedID
        $sql = "SELECT encodedPrivate FROM encodedKeyTable WHERE userID = ? AND encodedKeyID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iisss", $userID, $encodedKeyID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'REJECT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No encoded key data found at: ";
                $logging .= $createDate;
                $logging .= ' for encodedKeyID: '.$encodedKeyID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 27;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $encodedPrivateBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $encodedPrivate = $encodedPrivateBind;     
                            
                // decrypt the data 
                if (isset($password)) {
                    
                    $protectedKey = KeyProtectedByPassword::loadFromAsciiSafeString($protectedKeyEncoded);
                    try {
                        
                        $userKey = $protectedKey->unlockKey($password);                    
                        $encryptedPrivateTemp = Crypto::decrypt($encodedPrivate, $userKey);                                        
                        
                    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                        
                        // no decryption                    
                        $statusCode = 28;
                    }

                } elseif (isset($_SESSION["userKeyEncoded"])) {

                    $userKey = Key::loadFromAsciiSafeString($_SESSION["userKeyEncoded"]);
                    try {
                        
                        $encryptedPrivateTemp = Crypto::decrypt($encodedPrivate, $userKey);
                        
                    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                        
                        // no decryption                    
                        $statusCode = 28;
                    }

                } else {

                    // no decryption                    
                    $statusCode = 29;

                }

                // close statement
                mysqli_stmt_close($stmt);

            }

        }            

    }

    // if still ok and contract exists
    if (($statusCode == 0) && ($contractExist == "yes")) {
        
        // decrypt the encrypted temp private key using matcheos        
        $userKeyMatcheos = Key::loadFromAsciiSafeString(MATCHEOSUSERKEYENCODED);
                    
        try {
            
            $tempPrivate = Crypto::decrypt($encryptedPrivateTemp, $userKeyMatcheos);
            
        } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
            
            // no decryption                    
            $statusCode = 601;
        }                    
                
    }
        
    // if still ok and contract exists
    if (($statusCode == 0) && ($contractExist == "yes")) {
        
        // execute contract query and transfer
        $rejectContract = shell_exec('node '.MATCHEOSPATH.'/rejectcontract.js '.escapeshellarg($accountName).' '.escapeshellarg($contractName).' '.escapeshellarg($tempPrivate).' '.escapeshellarg($userName).' '.escapeshellarg($keyType));        

        // check that contract was executed
        if (trim($rejectContract) == "success") {
        
            // do nothing

        } else {

            // blockchain query and transfer failed
            $statusCode = 30;
                                            
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'REJECT MATCH FAIL: ERROR IN NODEJS QUERY TRANSFER';
            $logging = "The exec function failed: ";
            $logging .= $createDate;
            $logging .= ' with error output: '.$rejectContract;
            $logging .= ' for encodedKeyID: '.$encodedKeyID;
            $logging .= ' for userID: '.$userID;
            $logging .= ' for contractID: '.$contractID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        }
        
    }            
    
    // if still ok
    if ($statusCode == 0) {
        
        // pull the analytics data for spot 1 of match
        $sql = "SELECT newMatchesLove, newMatchesWork, newMatchesFriend, activeMatchesLove, activeMatchesWork, activeMatchesFriend, closedMatchesLove, closedMatchesWork, closedMatchesFriend FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $spotID1, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'REJECT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID1;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 31;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $newMatchesLoveBind, $newMatchesWorkBind, $newMatchesFriendBind, $activeMatchesLoveBind, $activeMatchesWorkBind, $activeMatchesFriendBind, $closedMatchesLoveBind, $closedMatchesWorkBind, $closedMatchesFriendBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables                
                $newMatchesLove1 = $newMatchesLoveBind;
                $newMatchesWork1 = $newMatchesWorkBind;
                $newMatchesFriend1 = $newMatchesFriendBind;
                $activeMatchesLove1 = $activeMatchesLoveBind;
                $activeMatchesWork1 = $activeMatchesWorkBind;
                $activeMatchesFriend1 = $activeMatchesFriendBind;
                $closedMatchesLove1 = $closedMatchesLoveBind;
                $closedMatchesWork1 = $closedMatchesWorkBind;
                $closedMatchesFriend1 = $closedMatchesFriendBind;

                // close statement
                mysqli_stmt_close($stmt);

            }

        }            

        // pull the analytics data for spot2 of match
        $sql = "SELECT newMatchesLove, newMatchesWork, newMatchesFriend, activeMatchesLove, activeMatchesWork, activeMatchesFriend, closedMatchesLove, closedMatchesWork, closedMatchesFriend FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $spotID2, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'REJECT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID2;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 32;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $newMatchesLoveBind, $newMatchesWorkBind, $newMatchesFriendBind, $activeMatchesLoveBind, $activeMatchesWorkBind, $activeMatchesFriendBind, $closedMatchesLoveBind, $closedMatchesWorkBind, $closedMatchesFriendBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables                
                $newMatchesLove2 = $newMatchesLoveBind;
                $newMatchesWork2 = $newMatchesWorkBind;
                $newMatchesFriend2 = $newMatchesFriendBind;
                $activeMatchesLove2 = $activeMatchesLoveBind;
                $activeMatchesWork2 = $activeMatchesWorkBind;
                $activeMatchesFriend2 = $activeMatchesFriendBind;
                $closedMatchesLove2 = $closedMatchesLoveBind;
                $closedMatchesWork2 = $closedMatchesWorkBind;
                $closedMatchesFriend2 = $closedMatchesFriendBind;

                // close statement
                mysqli_stmt_close($stmt);

            }

        }                    

    }

    // if still ok
    if ($statusCode == 0) {

        // determine if spot 1 is new or active
        if ($status1Match == "new") {

            // person with have 1 less new, 1 more closed        
            if ($matchType == "love") {
                $newMatchesLove1 = $newMatchesLove1 - 1;                
                $closedMatchesLove1 = $closedMatchesLove1 + 1;                
            } elseif ($matchType == "work") {
                $newMatchesWork1 = $newMatchesWork1 - 1;                
                $closedMatchesWork1 = $closedMatchesWork1 + 1;                
            } else {
                $newMatchesFriend1 = $newMatchesFriend1 - 1;                
                $closedMatchesFriend1 = $closedMatchesFriend1 + 1;                
            }

        } else {

            // person will have 1 less active, 1 more closed
            if ($matchType == "love") {
                $actveMatchesLove1 = $activeMatchesLove1 - 1;                
                $closedMatchesLove1 = $closedMatchesLove1 + 1;                
            } elseif ($matchType == "work") {
                $activeMatchesWork1 = $activeMatchesWork1 - 1;                
                $closedMatchesWork1 = $closedMatchesWork1 + 1;                
            } else {
                $activeMatchesFriend1 = $activeMatchesFriend1 - 1;                
                $closedMatchesFriend1 = $closedMatchesFriend1 + 1;                
            }

        }

        // determine if spot 2 is new or active
        if ($status2Match == "new") {

            // person with have 1 less new, 1 more closed        
            if ($matchType == "love") {
                $newMatchesLove2 = $newMatchesLove2 - 1;                
                $closedMatchesLove2 = $closedMatchesLove2 + 1;                
            } elseif ($matchType == "work") {
                $newMatchesWork2 = $newMatchesWork2 - 1;                
                $closedMatchesWork2 = $closedMatchesWork2 + 1;                
            } else {
                $newMatchesFriend2 = $newMatchesFriend2 - 1;                
                $closedMatchesFriend2 = $closedMatchesFriend2 + 1;                
            }

        } else {

            // person will have 1 less active, 1 more closed
            if ($matchType == "love") {
                $actveMatchesLove2 = $activeMatchesLove2 - 1;                
                $closedMatchesLove2 = $closedMatchesLove2 + 1;                
            } elseif ($matchType == "work") {
                $activeMatchesWork2 = $activeMatchesWork2 - 1;                
                $closedMatchesWork2 = $closedMatchesWork2 + 1;                
            } else {
                $activeMatchesFriend2 = $activeMatchesFriend2 - 1;                
                $closedMatchesFriend2 = $closedMatchesFriend2 + 1;                
            }

        }

        // update the analytics database for spot1
        $sqlUpdate = "UPDATE analyticsTable SET newMatchesLove=?, newMatchesWork=?, newMatchesFriend=?, activeMatchesLove=?, activeMatchesWork=?, activeMatchesFriend=?, closedMatchesLove=?, closedMatchesWork=?, closedMatchesFriend=? WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
            
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "iiiiiiiiiisss", $newMatchesLove1, $newMatchesWork1, $newMatchesFriend1, $activeMatchesLove1, $activeMatchesWork1, $activeMatchesFriend1, $closedMatchesLove1, $closedMatchesWork1, $closedMatchesFriend1, $spotID1, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // update the analytics database for spot2
        $sqlUpdate = "UPDATE analyticsTable SET newMatchesLove=?, newMatchesWork=?, newMatchesFriend=?, activeMatchesLove=?, activeMatchesWork=?, activeMatchesFriend=?, closedMatchesLove=?, closedMatchesWork=?, closedMatchesFriend=? WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
            
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "iiiiiiiiiisss", $newMatchesLove2, $newMatchesWork2, $newMatchesFriend2, $activeMatchesLove2, $activeMatchesWork2, $activeMatchesFriend2, $closedMatchesLove2, $closedMatchesWork2, $closedMatchesFriend2, $spotID2, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

    }
            
    // if still ok
    if ($statusCode == 0) {

        // set the flag
        $rejectFlag = "yes";                

        // set the status to closed for both spots for match
        $status = "closed";

        // set the status to terminated for both spots for contract
        $statusContract = "terminated";

        // update the reject flag for match
        $sqlUpdate = "UPDATE matchTable SET status1=?, status2=?, rejectFlag=?, rejectID=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sssiisss", $status, $status, $rejectFlag, $userID, $matchID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }
  
        // update the reject flag, termination for any contracts for this match
        $sqlUpdate = "UPDATE contractTable SET status1=?, status2=?, rejectFlag=?, rejectID=?, terminationDate=?, terminationRelease=?, terminatedID=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";

        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sssissiisss", $statusContract, $statusContract, $rejectFlag, $userID, $createDate, $createDate, $userID, $matchID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }       
        
        // update the status for this match for any open interactions - if contract exists
        if ($contractExist == "yes") {
            
            // set status to closed
            $sqlUpdate = "UPDATE interactTable SET status=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
            
            if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                        
                // bind parameters for markers
                mysqli_stmt_bind_param($stmt2, "sisss", $status, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

                // execute query
                mysqli_stmt_execute($stmt2);

                // close the statement
                $stmt2->close();
                
            }            
        }

        // set status to ok
        $statusCode = 1;

    }
    
}	
        
if (isset($encryptedPrivateTemp)) {unset($encryptedPrivateTemp);}        
if (isset($userKey)) {unset($userKey);}        
if (isset($userKeyMatcheos)) {unset($userKeyMatcheos);}        
if (isset($tempPrivate)) {unset($tempPrivate);}        
if (isset($password)) {unset($password);}        

/*
* 1 is ok
* 2+ is all others
*/

// close connection
mysqli_close($mysqli);
    
$ajaxResponse = array(
	"ajaxResult" => $statusCode,	    
);	

echo json_encode($ajaxResponse);


?>