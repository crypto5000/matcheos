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

// set the default key type of user for the contract
$arbKeyType = "active";

// pull the data
$detail = $_POST["field1"];
$conduct = $_POST["field2"];
$contractID = $_POST["field3"];
$token = $_POST["field4"];
if (isset($_POST["field5"])) {
    $password = $_POST["field5"];
} 

// validation - check for html special characters
$detail = validate($detail);
$conduct = validate($conduct);
$contractID = validate($contractID);
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
if (!isset($detail)) {  
    $statusCode = 2;	
}

// validate the field length
if (strlen($detail) < 1) {  
    $statusCode = 2;	
} 

// validate the field length
if (strlen($detail) > 5000) {  
    $statusCode = 2;	
} 

// validate that the field was submitted
if (!isset($conduct)) {  
    $statusCode = 3;	
}

// validate the field length
if (strlen($conduct) < 1) {  
    $statusCode = 3;	
} 

// validate the field length
if (strlen($conduct) > 5000) {  
    $statusCode = 3;	
} 

// validate that the field was submitted
if (!isset($contractID)) {  
    $statusCode = 4;	
}

// validate the field length
if (strlen($contractID) < 1) {  
    $statusCode = 4;	
} 

// validate the field length
if (strlen($contractID) > 200) {  
    $statusCode = 4;	
} 

// validate the field is a number
if (is_numeric($contractID)) {  
    $contractID = (int) $contractID;
} else {
    $statusCode = 4;	
}

// validate that the field was submitted
if (!isset($token)) {  
    $statusCode = 5;	
}

// validate the field length
if (strlen($token) < 1) {  
    $statusCode = 5;	
} 

// validate the field length
if (strlen($token) > 1000) {  
    $statusCode = 5;	
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
    $form = "enterarbitration";
    
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
			$logging = "There was a hack attempt on enterarbitration at: ";
            $logging .= $createDate;
            $logging .= ". For contractID: ".$contractID;
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

        // verify contract exists for the user, pull data
        $sql = "SELECT wastID, matchID, spotID1, spotID2, offer1, offer2, status1, status2, rejectFlag, contractType, contractSteps, contractRelease, contractDonee, contractArbFee, terminationRelease FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiisss", $userID, $userID, $contractID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'CONTRACT ARBITRATION FAIL: NO DATA FOUND ON PAGE';
                $logging = "No user contractID found at: ";
                $logging .= $createDate;
                $logging .= ' for contractID: '.$contractID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 12;
                
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $wastIDBind, $matchIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $status1Bind, $status2Bind, $rejectFlagBind, $contractTypeBind, $contractStepsBind, $contractReleaseBind, $contractDoneeBind, $contractArbFeeBind, $terminationReleaseBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $wastID = $wastIDBind;
                $matchID = $matchIDBind;
                $spotID1 = $spotID1Bind;
                $spotID2 = $spotID2Bind;
                $offer1 = $offer1Bind;
                $offer2 = $offer2Bind;       
                $status1 = $status1Bind;
                $status2 = $status2Bind;
                $rejectFlag = $rejectFlagBind;
                $contractType = $contractTypeBind;
                $contractSteps = $contractStepsBind;
                $contractRelease = $contractReleaseBind;
                $contractDonee = $contractDoneeBind;
                $contractArbFee = $contractArbFeeBind;
                $terminationRelease = $terminationReleaseBind;

                // set the terminationFlag
                $terminationFlag = "no";

                // validate the variables
                $invalidFlag = "no";

                if (($spotID1 == $userID) || ($spotID2 == $userID)) {
                    // do nothing
                } else {
                    // invalid ids
                    $invalidFlag = "yes";
                }

                if (is_numeric($offer1)) {
                    // do nothing
                } else {
                    // invalid offer
                    $invalidFlag = "yes";
                }
        
                if (is_numeric($offer2)) {
                    // do nothing
                } else {
                    // invalid offer
                    $invalidFlag = "yes";
                }
                
                if ((($status1 == "open") || ($status1 == "terminated")) && (($status2 == "open") || ($status2 == "terminated"))) {
                    // do nothing
                } else {
                    // invalid status - can only be open or terminated
                    $invalidFlag = "yes";
                }

                if (($status1 == "terminated") || ($status2 == "terminated")) {
                    if (isset($terminationRelease)) {
                        // tokens already released back - past 24 hour termination date
                        $invalidFlag = "yes";
                    }            
                    $terminationFlag = "yes";
                }
                
                if ($rejectFlag == "yes") {
                    // invalid if the contract was rejected
                    $invalidFlag = "yes";
                }

                if (($contractType == "love") || ($contractType == "friend") || ($contractType == "work")) {
                    // do nothing
                } else {
                    $invalidFlag = "yes";
                }

                if (is_numeric($contractArbFee)) {
                    $contractArbFee = (int) $contractArbFee;
                    if (($contractArbFee < 0) || ($contractArbFee > 100)) {
                        // invalid fee
                        $invalidFlag = "yes";
                    }
                } else {
                    // invalid fee
                    $invalidFlag = "yes";
                }
                
                // close statement
                mysqli_stmt_close($stmt);        
                
            }

        }

        // check if invalid
        if ($invalidFlag == "yes") {
            $to = ADMINEMAIL;  
            $subject = 'CONTRACT ARBITRATION FAIL: INVALID DATA FOUND ON PAGE';
            $logging = "Contract could not be arbitrated because of invalid data at: ";
            $logging .= $createDate;
            $logging .= ' for contractID: '.$contractID;
            $logging .= ' for userID: '.$userID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

            // not valid
            $statusCode = 13;
            
        } else {
            
            // calculate arbitration fee with 4 decimals
            if ($spotID1 == $userID) {                
                $arbitrationFee = number_format(($offer1 * ($contractArbFee / 100)),4);
                $arbitrationFee = (float) $arbitrationFee;
            } else {
                $arbitrationFee = number_format(($offer2 * ($contractArbFee / 100)),4);
                $arbitrationFee = (float) $arbitrationFee;
            }

        }

    }

    // if still ok
    if ($statusCode == 0) {
        
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
                $subject = 'ENTER ARBITRATION FAIL: NO DATA FOUND ON PAGE';
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
    
    // if still ok,
    if ($statusCode == 0) {
        
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
                $subject = 'ENTER ARBITRATION FAIL: NO DATA FOUND ON PAGE';
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

    // if still ok
    if ($statusCode == 0) {

        // pull the encoded key using the encodedID
        $sql = "SELECT encodedPrivate, doubleEncodeID FROM encodedKeyTable WHERE userID = ? AND encodedKeyID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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
                $subject = 'ENTER ARBITRATION FAIL: NO DATA FOUND ON PAGE';
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
                mysqli_stmt_bind_result($stmt, $encodedPrivateBind, $doubleEncodeIDBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $encodedPrivate = $encodedPrivateBind;     
                $doubleEncodeID = $doubleEncodeIDBind;
                                            
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
                $subject = 'ARBITRATION FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID1;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 14;
                
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
                $subject = 'ARBITRATION FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID2;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 15;
                
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
                
        if ($spotID1 == $userID) {

            // both spots get 1 less active, 1 more close - (only if not already terminated)
            if ($contractType == "love") {
                $activeMatchesLove1 = $activeMatchesLove1 - 1;                
                $closedMatchesLove1 = $closedMatchesLove1 + 1;                
            } elseif ($contractType == "work") {
                $activeMatchesWork1 = $activeMatchesWork1 - 1;                
                $closedMatchesWork1 = $closedMatchesWork1 + 1;                
            } else {
                $activeMatchesFriend1 = $activeMatchesFriend1 - 1;                
                $closedMatchesFriend1 = $closedMatchesFriend1 + 1;                
            }            

        } else {

            // both spots get 1 less active, 1 more close - (only if not already terminated)
            if ($contractType == "love") {
                $activeMatchesLove2 = $activeMatchesLove2 - 1;                
                $closedMatchesLove2 = $closedMatchesLove2 + 1;                
            } elseif ($contractType == "work") {
                $activeMatchesWork2 = $activeMatchesWork2 - 1;                
                $closedMatchesWork2 = $closedMatchesWork2 + 1;                
            } else {
                $activeMatchesFriend2 = $activeMatchesFriend2 - 1;                
                $closedMatchesFriend2 = $closedMatchesFriend2 + 1;                
            }
            
        }                

        // update the analytics database for spot1 - if was not previously terminated (arb happens after terminate)
        if ($terminationFlag == "no") {
        
            $sqlUpdate = "UPDATE analyticsTable SET newMatchesLove=?, newMatchesWork=?, newMatchesFriend=?, activeMatchesLove=?, activeMatchesWork=?, activeMatchesFriend=?, closedMatchesLove=?, closedMatchesWork=?, closedMatchesFriend=? WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
            
            if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                
                // bind parameters for markers
                mysqli_stmt_bind_param($stmt2, "iiiiiiiiiisss", $newMatchesLove1, $newMatchesWork1, $newMatchesFriend1, $activeMatchesLove1, $activeMatchesWork1, $activeMatchesFriend1, $closedMatchesLove1, $closedMatchesWork1, $closedMatchesFriend1, $spotID1, $aliveFlag, $errorFlag, $deleteFlag);

                // execute query
                mysqli_stmt_execute($stmt2);

                // close the statement
                $stmt2->close();
                
            }

            // update the analytics database for spot2 - if was not previously terminated (arb happens after terminate)
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

    }
    
    // if still ok
    if ($statusCode == 0) {

        // set userID requested, arbKeyType and userID violated
        $userIDRequested = $userID;
        if ($spotID1 == $userIDRequested) {
            $userIDViolated = $spotID2;
            $arbKeyType = "owner";
        } else {
            $userIDViolated = $spotID1;
            $arbKeyType = "active";
        }
        
        // set status to open
        $statusArb = "open";
        
        // insert the values in the table        
        $sql = "INSERT INTO arbitrationTable (contractID, userIDRequested, userIDViolated, spotID1, spotID2, offer1, offer2, contractDonee, arbitrationFee, status, detail, conduct, arbEncodedPrivate, arbKeyType, doubleEncodeID, accountName, contractName, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiiiiddsdsssssisssssss", $contractID, $userIDRequested, $userIDViolated, $spotID1, $spotID2, $offer1, $offer2, $contractDonee, $arbitrationFee, $statusArb, $detail, $conduct, $encryptedPrivateTemp, $arbKeyType, $doubleEncodeID, $accountName, $contractName, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }        

        // set the match status
        $matchStatus = "closed";

        // update the match status to closed - once enter arbitration, prevent match from happening
        $sqlUpdate = "UPDATE matchTable SET status1=?, status2=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "ssisss", $matchStatus, $matchStatus, $matchID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // set the contract status
        $newStatus = "arbitration";
        $terminationDate = NULL;

        // update the status of the contract (if terminated, prevent 24 hour release)
        $sqlUpdate = "UPDATE contractTable SET status1=?, status2=?, terminationDate=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sssisss", $newStatus, $newStatus, $terminationDate, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // set the interact status
        $interactStatus = "closed";

        // update the status of the contract (if terminated, prevent 24 hour release)
        $sqlUpdate = "UPDATE interactTable SET status=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sisss", $interactStatus, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // set status to closed
		$statusTerm = "closed";			

        // update termination table - if contract was previously terminated and now is in arbitration
        $sqlUpdate = "UPDATE terminatedReleaseTable SET status=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sisss", $statusTerm, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }
						
        // email admin about arbitration
        $to = ADMINEMAIL;  
        $subject = 'CONTRACT ARBITRATION REQUESTED';
        $logging = "A request for arbitration has been made to a contract: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // set status to ok
        $statusCode = 1;

    }
    
}	

if (isset($encryptedPrivateTemp)) {unset($encryptedPrivateTemp);}        
if (isset($protectedKeyEncoded)) {unset($protectedKeyEncoded);}        
if (isset($protectedKey)) {unset($protectedKey);}        
if (isset($userKeyMatcheos)) {unset($userKeyMatcheos);}        
if (isset($userKey)) {unset($userKey);}        
if (isset($password)) {unset($password);}        
if (isset($doubleEncodeID)) {unset($doubleEncodeID);}        

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