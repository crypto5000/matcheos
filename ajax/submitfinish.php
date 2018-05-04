<?php
session_start();

require_once('../crypto/defuse-crypto.phar');

// set up classes
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;    

// set default status code
$statusCode = 0;

// set the lastStep flag - could be second to last step (user still submits goal)
$lastStepFlag = "yes";

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// set the default keyType to update contract
$keyType = "active";

// pull the data
$contractID = $_POST["field1"];
$token = $_POST["field2"];
$goal = $_POST["field3"];
if (isset($_POST["field4"])) {
    $response1 = $_POST["field4"];
}
if (isset($_POST["field5"])) {
    $password = $_POST["field5"];
}
$contractFlag = $_POST["field6"];

// validation - check for html special characters
$contractID = validate($contractID);
$token = validate($token);
$goal = validate($goal);

// validation function
function validate($message) {

	$message = htmlspecialchars($message);
	$message = stripslashes($message);
	$message = utf8_encode($message);
	$message = preg_replace('!\r\n?!', '\n', $message);
	
	return $message;
}

// validate that the field was submitted
if (!isset($contractID)) {  
    $statusCode = 2;	
}

// validate the field length
if (strlen($contractID) < 1) {  
    $statusCode = 2;	
} 

// validate the field length
if (strlen($contractID) > 200) {  
    $statusCode = 2;	
} 

// validate the field is a number
if (is_numeric($contractID)) {  
    $contractID = (int) $contractID;
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

// validate that the field was submitted
if (!isset($goal)) {  
    $statusCode = 4;	
}

// validate the field length
if (strlen($goal) < 1) {  
    $statusCode = 4;	
} 

// validate the field length
if (strlen($goal) > 5000) {  
    $statusCode = 4;	
} 

// validate the response1, if exists
if (isset($response1)) {

    $response1 = validate($response1);

    // validate the field length
    if (strlen($response1) < 1) {  
        $statusCode = 5;	
    } 

    // validate the field length
    if (strlen($response1) > 5000) {  
        $statusCode = 5;	
    } 

} else {
    $response1 = "";
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

// validate the contractFlag, if exists
if (isset($contractFlag)) {
    
    $contractFlag = validate($contractFlag);

    // validate that the field was submitted
    if (($contractFlag == "yes") || ($contractFlag == "no")) {
        // do nothing
    } else {
        $statusCode = 60;	
    }

} else {
    $statusCode = 61;	
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
    $form = "submitfinish";
    
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
			$logging = "There was a hack attempt on submitfinish at: ";
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

        // verify contractID exists, status is open or waiting        
        $sql = "SELECT wastID, matchID, spotID1, spotID2, offer1, offer2, status1, status2, rejectFlag, contractType, contractSteps, terminationRelease, finishedDate FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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
                $subject = 'SUBMIT FINISH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No user contractID found at: ";
                $logging .= $createDate;
                $logging .= ' for contractID: '.$contractID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 18;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $wastIDBind, $matchIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $status1Bind, $status2Bind, $rejectFlagBind, $contractTypeBind, $contractStepsBind, $terminationReleaseBind, $finishedDateBind);
            
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
                $terminationRelease = $terminationReleaseBind;
                $finishedDate = $finishedDateBind;
                
                // verify status
                if ($spotID1 == $userID) {

                    // to submit step, user status should be open only
                    if ($status1 != "open") {
                        //  error
                        $statusCode = 19;
                    }

                    // other spot could be waiting or open
                    if ($status2 == "closed") {                        
                        //  error
                        $statusCode = 20;
                    }                    

                } else {

                    // to submit step, user status should be open only
                    if ($status2 != "open") {
                        //  error
                        $statusCode = 21;
                    }

                    // other spot could be waiting or open
                    if ($status1 == "closed") {
                        //  error
                        $statusCode = 22;
                    }                    

                }

                // verify not rejected 
                if ($rejectFlag == "yes") {
                    //  error
                    $statusCode = 700;                            
                }

                // verify not terminated with release
                if (isset($terminationRelease)) {
                    //  error
                    $statusCode = 701;                            
                }

                // verify not finished
                if (isset($finishedDate)) {
                    //  error
                    $statusCode = 702;                            
                }

                // if twosided format, verify substep exists
                if ($contractType == "twosided") {

                    if (!isset($response1)) {
                        //  error
                        $statusCode = 703;                            
                    }
                    
                }

                // set the keyType - spot1 is owner, spot2 is active for contract update
				if ($userID == $spotID1) {
					$keyType = "owner";
				} else {
					$keyType = "active";
                }

                // close statement
                mysqli_stmt_close($stmt);
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
                $subject = 'SUBMIT FINISH FAIL: NO DATA FOUND ON PAGE';
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

    // if still ok
    if ($statusCode == 0) {

        // pull the interaction table             
        $sql = "SELECT interactID, currentStep, status FROM interactTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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
                $currentStep = 0;     
                $statusInteract = "open";                
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $interactIDBind, $currentStepBind, $statusInteractBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $interactID = $interactIDBind;
                $currentStep = $currentStepBind;     
                $statusInteract = $statusInteractBind;                

                // check if step is last or next to step last
                if ($currentStep == $contractSteps) {
                    $lastStepFlag = "yes";
                } elseif ($currentStep == ($contractSteps - 1)) {
                    $lastStepFlag = "no";
                } else {
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
                $subject = 'SUBMIT FINISH FAIL: NO DATA FOUND ON PAGE';
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
                $subject = 'SUBMIT FINISH FAIL: NO DATA FOUND ON PAGE';
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

    // if still ok
    if ($statusCode == 0) {
        
        // decrypt the encrypted temp private key using matcheos        
        $userKeyMatcheos = Key::loadFromAsciiSafeString(MATCHEOSUSERKEYENCODED);
                    
        try {
            
            $tempPrivate = Crypto::decrypt($encryptedPrivateTemp, $userKeyMatcheos);
            
        } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
            
            // no decryption                    
            $statusCode = 601;
        }                    
                                
        // encrypt the goal only with matcheos userKey
        $encryptedGoal = Crypto::encrypt($goal, $userKeyMatcheos);                       

    }
     
    
    // if still ok
    if ($statusCode == 0) {
        
        // set the number of steps that have occurred in contract
        $stepCount = 0;

        // pull the last step - ordered by createDate
        $sql = "SELECT step, subStep, spotIDTo, spotIDFrom, finishFlag FROM stepTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ? ORDER BY createDate DESC";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $contractID, $aliveFlag, $deleteFlag, $errorFlag);
        
            // execute query
            mysqli_stmt_execute($stmt);
        
            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;

            // set the stepCount to the number of rows
            $stepCount = $numRows;
                                    
            if ($numRows < 1) {
        
                // do nothing - can have no steps if just started                 
                
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $stepBind, $subStepBind, $spotIDToBind, $spotIDFromBind, $finishFlagBind);                    
        
                // fetch the results
                $stmt->fetch();
                
                // just pull the latest step - ordered by createDate                
                $step = $stepBind;
                $subStep = $subStepBind;
                $spotIDTo = $spotIDToBind;
                $spotIDFrom = $spotIDFromBind;
                $finishFlag = $finishFlagBind;                                    
                
                // close statement
                mysqli_stmt_close($stmt);                
        
            }

        }
        
        // verify the user should be the one submitting this step 
        if (($stepCount > 0) && (isset($spotIDFrom))) {

            if ($spotIDFrom == $userID) {
                // invalid - user can not do two steps in a row
                $statusCode = 800;
            }

        }

        // verify the steps match the format (subStep if twosided)
        if (($stepCount > 0) && (!isset($subStep))) {

            if ($contractType == "twosided") {
                // invalid format - substep should exist
                $statusCode = 801;
            }

        }

        // check the last step + 1 matches the interact current step - interact is the next active step
        if (($stepCount + 1) != $currentStep) {
            // invalid step count - does not match
            $statusCode = 802;
        }
        
        // check that this is the last step or next to last step
        if (($stepCount + 1) == $contractSteps) {
            // do nothing
        } elseif (($stepCount + 2) == $contractSteps) {
            // do nothing
        } else {
            // invalid status
            $statusCode = 804;
        }        

    }

    // if still ok, and stepNo exists and not last step
    if (($statusCode == 0) && ($lastStepFlag != "yes")) {

        // next step to get inserted - this step as data
        $stepNo = $stepCount + 1;    
        
        // execute contract query and transfer
        $submitStep = shell_exec('node '.MATCHEOSPATH.'/submitstep.js '.escapeshellarg($accountName).' '.escapeshellarg($contractName).' '.escapeshellarg($tempPrivate).' '.escapeshellarg($userName).' '.escapeshellarg($stepNo).' '.escapeshellarg($keyType));        
        
        // check that contract was executed
        if (trim($submitStep) == "success") {
        
            // do nothing

        } else {

            // blockchain query and transfer failed
            $statusCode = 70;
                                            
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'SUBMIT FINISH FAIL: ERROR IN NODEJS QUERY TRANSFER';
            $logging = "The exec function failed: ";
            $logging .= $createDate;
            $logging .= ' with error output: '.$submitStep;
            $logging .= ' for encodedKeyID: '.$encodedKeyID;
            $logging .= ' for userID: '.$userID;
            $logging .= ' for contractID: '.$contractID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        }
        
    }          
        
    // if still ok, and stepNo exists and last step
    if (($statusCode == 0) && ($lastStepFlag == "yes")) {

        // next step to get inserted - this step as data
        $stepNo = $stepCount + 1;    
        
        // execute contract query and transfer
        $submitFinish = shell_exec('node '.MATCHEOSPATH.'/submitfinish.js '.escapeshellarg($accountName).' '.escapeshellarg($contractName).' '.escapeshellarg($tempPrivate).' '.escapeshellarg($userName).' '.escapeshellarg($stepNo).' '.escapeshellarg($keyType));        
        
        // check that contract was executed
        if (trim($submitFinish) == "success") {
        
            // do nothing

        } else {

            // blockchain query and transfer failed
            $statusCode = 70;
                                            
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'SUBMIT FINISH FAIL: ERROR IN NODEJS QUERY TRANSFER';
            $logging = "The exec function failed: ";
            $logging .= $createDate;
            $logging .= ' with error output: '.$submitFinish;
            $logging .= ' for encodedKeyID: '.$encodedKeyID;
            $logging .= ' for userID: '.$userID;
            $logging .= ' for contractID: '.$contractID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        }
        
    }          
        
    // if still ok and last step and contractFlag of no
    if (($statusCode == 0) && ($lastStepFlag == "yes") && ($contractFlag == "no")) {

        // pull the analytics data for spot 1 of contract
        $sql = "SELECT activeMatchesLove, activeMatchesWork, activeMatchesFriend, closedMatchesLove, closedMatchesWork, closedMatchesFriend FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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
                $subject = 'SUBMIT FINISH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID1;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 71;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $activeMatchesLoveBind, $activeMatchesWorkBind, $activeMatchesFriendBind, $closedMatchesLoveBind, $closedMatchesWorkBind, $closedMatchesFriendBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables                
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

        // pull the analytics data for spot2 of contract
        $sql = "SELECT activeMatchesLove, activeMatchesWork, activeMatchesFriend, closedMatchesLove, closedMatchesWork, closedMatchesFriend FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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
                $subject = 'SUBMIT FINISH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID2;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 72;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $activeMatchesLoveBind, $activeMatchesWorkBind, $activeMatchesFriendBind, $closedMatchesLoveBind, $closedMatchesWorkBind, $closedMatchesFriendBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables                
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

    // if still ok and last step and contractFlag of no
    if (($statusCode == 0) && ($lastStepFlag == "yes") && ($contractFlag == "no")) {

        // set the new values based on type
        if ($contractType == "love") {
            $activeMatchesLove1 = $activeMatchesLove1 - 1;
            $activeMatchesLove2 = $activeMatchesLove2 - 1;
            $closedMatchesLove1 = $closedMatchesLove1 + 1;
            $closedMatchesLove2 = $closedMatchesLove2 + 1;
        } elseif ($contractType == "work") {
            $activeMatchesWork1 = $activeMatchesWork1 - 1;
            $activeMatchesWork2 = $activeMatchesWork2 - 1;
            $closedMatchesWork1 = $closedMatchesWork1 + 1;
            $closedMatchesWork2 = $closedMatchesWork2 + 1;
        } else {
            $activeMatchesFriend1 = $activeMatchesFriend1 - 1;
            $activeMatchesFriend2 = $activeMatchesFriend2 - 1;
            $closedMatchesFriend1 = $closedMatchesFriend1 + 1;
            $closedMatchesFriend2 = $closedMatchesFriend2 + 1;
        }

        // update the analytics database - 1 less active, 1 more closed                
        $sqlUpdate = "UPDATE analyticsTable SET activeMatchesLove=?, activeMatchesWork=?, activeMatchesFriend=?, closedMatchesLove=?, closedMatchesWork=?, closedMatchesFriend=? WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
            
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "iiiiiiisss", $activeMatchesLove1, $activeMatchesWork1, $activeMatchesFriend1, $closedMatchesLove1, $closedMatchesWork1, $closedMatchesFriend1, $spotID1, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // update the analytics database - 1 less active, 1 more closed                
        $sqlUpdate = "UPDATE analyticsTable SET activeMatchesLove=?, activeMatchesWork=?, activeMatchesFriend=?, closedMatchesLove=?, closedMatchesWork=?, closedMatchesFriend=? WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
            
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "iiiiiiisss", $activeMatchesLove2, $activeMatchesWork2, $activeMatchesFriend2, $closedMatchesLove2, $closedMatchesWork2, $closedMatchesFriend2, $spotID2, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

    }

    // if still ok and last step
    if (($statusCode == 0) && ($lastStepFlag == "yes")) {
        
        // set the spotIDTo
        if ($userID == $spotID1) {
            $spotIDTo = $spotID2;
        } else {
            $spotIDTo = $spotID1;
        }

        // close match if contractFlag of no
        if ($contractFlag == "no") {
        
            // set the status to closed for both spots for match
            $status = "closed";
                            
            // update the match table status        
            $sqlUpdate = "UPDATE matchTable SET status1=?, status2=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
            
            if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                        
                // bind parameters for markers
                mysqli_stmt_bind_param($stmt2, "ssisss", $status, $status, $matchID, $aliveFlag, $errorFlag, $deleteFlag);

                // execute query
                mysqli_stmt_execute($stmt2);

                // close the statement
                $stmt2->close();
                
            }
        
        }
        
        // set the finishFlag
        $finishFlag = "yes";

        // insert the step into database        
        $sql = "INSERT INTO stepTable (contractID, interactID, step, subStep, spotIDTo, spotIDFrom, finishFlag, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iissiissssss", $contractID, $interactID, $encryptedGoal, $response1, $spotIDTo, $userID, $finishFlag, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }        

        // update the current step for interaction table - becomes the next step
        $currentStep = $currentStep + 1;

        // set the interact status
        $statusInteract = "closed";
        
        // update interaction table
        $sqlUpdate = "UPDATE interactTable SET currentStep=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "iisss", $currentStep, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // set the status to finished for contract table
        $statusContract = "finished";

        // update contract table
        $sqlUpdate = "UPDATE contractTable SET status1=?, status2=?, finishedDate=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sssisss", $statusContract, $statusContract, $createDate, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // set status to ok
        $statusCode = 1;

    }
            
    // if still ok and not last step
    if (($statusCode == 0) && ($lastStepFlag != "yes")) {

        // set the spotIDTo
        if ($userID == $spotID1) {
            $spotIDTo = $spotID2;
        } else {
            $spotIDTo = $spotID1;
        }

        // set the finishFlag
        $finishFlag = "yes";

        // check if response1 exists - set to nothing otherwise
        if (!isset($response1)) {
            $response1 = "";
        }

        // insert the step into database        
        $sql = "INSERT INTO stepTable (contractID, interactID, step, subStep, spotIDTo, spotIDFrom, finishFlag, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iissiissssss", $contractID, $interactID, $encryptedGoal, $response1, $spotIDTo, $userID, $finishFlag, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }        

        // update the current step for interaction table - becomes the next step
        $currentStep = $currentStep + 1;
        
        // update interaction table
        $sqlUpdate = "UPDATE interactTable SET currentStep=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "iisss", $currentStep, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
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