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

// pull the data
$contractID = $_POST["field1"];
$token = $_POST["field2"];
$termType = $_POST["field3"];
if (isset($_POST["field4"])) {
    $password = $_POST["field4"];
}

// validation - check for html special characters
$contractID = validate($contractID);
$token = validate($token);
$termType = validate($termType);

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

// validate the field
if (($termType == "yes") || ($termType == "no")) {
    // do nothing
} else {
    $statusCode = 211;
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
    $form = "terminatecontract";
    
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
			$logging = "There was a hack attempt on terminatecontract at: ";
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
        $sql = "SELECT wastID, matchID, spotID1, spotID2, offer1, offer2, status1, status2, rejectFlag, contractType, contractSteps FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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
                $subject = 'TERMINATE CONTRACT FAIL: NO DATA FOUND ON PAGE';
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
                mysqli_stmt_bind_result($stmt, $wastIDBind, $matchIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $status1Bind, $status2Bind, $rejectFlagBind, $contractTypeBind, $contractStepsBind);
            
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
                
                // verify status
                if ($spotID1 == $userID) {

                    if ($status1 != "open") {                    
                        //  error
                        $statusCode = 19;
                    }

                    if (($status2 == "waiting") || ($status2 == "open")) {
                        // do nothing
                    } else {
                        //  error
                        $statusCode = 20;
                    }                    

                } else {

                    if ($status2 != "open") {
                        //  error
                        $statusCode = 21;
                    }

                    if (($status1 == "waiting") || ($status1 == "open")) {
                        // do nothing
                    } else {
                        //  error
                        $statusCode = 22;
                    }                    

                }

                // verify not rejected 
                if ($rejectFlag == "yes") {
                    //  error
                    $statusCode = 700;                            
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
                $subject = 'TERMINATE CONTRACT FAIL: NO DATA FOUND ON PAGE';
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
                $currentStep = 0;     
                $statusInteract = "open";                
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $currentStepBind, $statusInteractBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $currentStep = $currentStepBind;     
                $statusInteract = $statusInteractBind;                

                // verify step is less than total steps
                if ($currentStep > $contractSteps) {
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
                $subject = 'TERMINATE CONTRACT FAIL: NO DATA FOUND ON PAGE';
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
                $subject = 'TERMINATE CONTRACT FAIL: NO DATA FOUND ON PAGE';
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
        
        // set status to open
        $termReleaseStatus = "open";
        
        // insert into table
        $sql = "INSERT INTO terminatedReleaseTable (contractID, terminatedID, termEncodedPrivate, doubleEncodeID, accountName, contractName, status, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iisissssssss", $contractID, $userID, $encryptedPrivateTemp, $doubleEncodeID, $accountName, $contractName, $termReleaseStatus, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }

        unset($userKeyMatcheos);
        unset($userKey);
        unset($password);
        unset($tempPrivate);
        unset($encryptedPrivateTemp);
        unset($protectedKey);
        unset($protectedKeyEncoded);
        unset($doubleEncodeID);

    }

    // if still ok and termType is no
    if (($statusCode == 0) && ($termType == "no")) {
        
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
                $subject = 'TERMINATE CONTRACT FAIL: NO DATA FOUND ON PAGE';
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
                $subject = 'TERMINATE CONTRACT FAIL: NO DATA FOUND ON PAGE';
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

    // if still ok and termType is no
    if (($statusCode == 0) && ($termType == "no")) {

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

    // if still ok
    if ($statusCode == 0) {
        
        // set the status to closed for both spots for match
        $status = "closed";

        // set the status to terminated for both spots for contract
        $statusContract = "terminated";

        // update match table if closing match
        if ($termType == "no") {

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
        
        // update the status and termination for contract
        $sqlUpdate = "UPDATE contractTable SET status1=?, status2=?, terminationDate=?, terminatedID=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sssiisss", $statusContract, $statusContract, $createDate, $userID, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }                

        // update interaction table = status closed        
        $sqlUpdate = "UPDATE interactTable SET status=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sisss", $status, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // set status to ok
        $statusCode = 1;

    }
    
}	
		
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