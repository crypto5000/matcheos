<?php

require_once('../crypto/defuse-crypto.phar');

// set up classes
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;    

// set default status code
$statusCode = 0;

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// set the default accountName
$accountName = "";

// pull the data
$matchID = $_POST["field1"];
$token = $_POST["field2"];
$contractType = $_POST["field3"];
$goalID = $_POST["field4"];
$contractSteps = $_POST["field5"];
$contractRelease = $_POST["field6"];
$contractFormat = $_POST["field7"];
$contractDonee = $_POST["field8"];
$contractArbFee = $_POST["field9"];
$contractFee = $_POST["field10"];

// validation - check for html special characters
$matchID = validate($matchID);
$token = validate($token);
$contractType = validate($contractType);
$goalID = validate($goalID);
$contractSteps = validate($contractSteps);
$contractRelease = validate($contractRelease);
$contractFormat = validate($contractFormat);
$contractDonee = validate($contractDonee);
$contractArbFee = validate($contractArbFee);
$contractFee = validate($contractFee);

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

if (($contractType == "friend") || ($contractType == "love") || ($contractType == "work")) {
    // do nothing
} else {
    // invalid type
    $statusCode = 4;
}

// validate that the field was submitted
if (!isset($goalID)) {  
    $statusCode = 5;	
}

// validate the field length
if (strlen($goalID) < 1) {  
    $statusCode = 5;	
} 

// validate the field length
if (strlen($goalID) > 200) {  
    $statusCode = 5;	
} 

// validate the field is a number
if (is_numeric($goalID)) {  
    $goalID = (int) $goalID;
} else {
    $statusCode = 5;	
}

if (($contractFormat == "twosided") || ($contractFormat == "chat") || ($contractFormat == "interview")) {
    // do nothing
} else {
    // invalid format
    $statusCode = 6;
}

// validate that the field was submitted
if (!isset($contractSteps)) {  
    $statusCode = 7;	
}

// validate the field length
if (strlen($contractSteps) < 1) {  
    $statusCode = 7;	
} 

// validate the field length
if (strlen($contractSteps) > 200) {  
    $statusCode = 7;	
} 

if (is_numeric($contractSteps)) {
    $contractSteps = (int) $contractSteps;

    // validate min of 4 steps, max of 50
    if (($contractSteps < 4) || ($contractSteps > 50)) {
        $statusCode = 7;
    }

} else {
    // invalid steps
    $statusCode = 7;
}

// validate that the field was submitted
if (!isset($contractRelease)) {  
    $statusCode = 8;	
}

// validate the field length
if (strlen($contractRelease) < 1) {  
    $statusCode = 8;	
} 

// validate the field length
if (strlen($contractRelease) > 200) {  
    $statusCode = 8;	
} 

if (is_numeric($contractRelease)) {
    $contractRelease = (int) $contractRelease;
} else {
    // invalid release
    $statusCode = 8;
}

if (($contractDonee == "redcross") || ($contractDonee== "doctors")) {
    // do nothing
} else {
    // invalid donor
    $statusCode = 9;
}

// validate that the field was submitted
if (!isset($contractArbFee)) {  
    $statusCode = 10;	
}

// validate the field length
if (strlen($contractArbFee) < 1) {  
    $statusCode = 10;	
} 

// validate the field length
if (strlen($contractArbFee) > 200) {  
    $statusCode = 10;	
} 

if (is_numeric($contractArbFee)) {
    $contractArbFee = (int) $contractArbFee;
} else {
    // invalid fee
    $statusCode = 10;
}

// validate that the field was submitted
if (!isset($contractFee)) {  
    $statusCode = 11;	
}

// validate the field length
if (strlen($contractFee) < 1) {  
    $statusCode = 11;	
} 

// validate the field length
if (strlen($contractFee) > 200) {  
    $statusCode = 11;	
} 

if (is_numeric($contractFee)) {
    $contractFee = (int) $contractFee;
} else {
    // invalid fee
    $statusCode = 11;
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
    $form = "createaccount";
    
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
			$logging = "There was a hack attempt on createaccount at: ";
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
    $sql = "SELECT userID, email, userName, lastLogin, birthYear, langID FROM userTable WHERE token=?";
    
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
            $statusCode = 12;
                    
        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $userIDBind, $emailBind, $userNameBind, $loginBind, $birthYearBind, $langIDBind);
                                                                                                                                                                    
            // fetch the results
            $stmt->fetch();
                            
            // set variables
            $userID = $userIDBind;		
            $currentEmail = $emailBind;
            $userName = $userNameBind;
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
                $statusCode = 13;

            }            

        }

    }
    
    // if still ok,
    if ($statusCode == 0) {

        // verify match exists for the user, pull data
        $sql = "SELECT spotID1, spotID2, offer1, offer2, matchType, status1, status2, rejectFlag FROM matchTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
                $subject = 'CREATE ACCOUNT FAIL: NO DATA FOUND ON PAGE';
                $logging = "No user matchID found at: ";
                $logging .= $createDate;
                $logging .= ' for matchID: '.$matchID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 14;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $matchTypeBind, $status1Bind, $status2Bind, $rejectFlagBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $spotID1 = $spotID1Bind;		
                $spotID2 = $spotID2Bind;		
                $offer1 = $offer1Bind;		
                $offer2 = $offer2Bind;		
                $matchType = $matchTypeBind;		
                $status1Match = $status1Bind;		
                $status2Match = $status2Bind;		
                $rejectFlag = $rejectFlagBind;		

                // check not rejected
                if ($rejectFlag == "yes") {
                    // not valid
                    $statusCode = 15;
                }
                                
                // verify status
                if ($spotID1 == $userID) {
                    
                    // check status is active for current user
                    if ($status1Match != "active") {
                        //  error
                        $statusCode = 16;
                    }

                    // status is new or active for other user
                    if ($status2Match == "closed") {
                        //  error
                        $statusCode = 17;
                    }                        

                } else {

                    // check status is active for current user
                    if ($status2Match != "active") {
                        //  error
                        $statusCode = 18;
                    }

                    // status is new or active for other user
                    if ($status1Match == "closed") {
                        //  error
                        $statusCode = 19;
                    }
                
                }

                // close statement
                mysqli_stmt_close($stmt);        

            }

        }
        
    }
    
    // if still ok
    if ($statusCode == 0) {

        // check that match does not have any open contracts - only 1 open at a time        
        $sql = "SELECT contractID, spotID1, spotID2, offer1, offer2, contractType, status1, status2, rejectFlag FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

                // ok, this is new contract

            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $contractIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $contractTypeBind, $status1Bind, $status2Bind, $rejectFlag);
            
                // check for the open count
                $currentOpen = 0;

                // cycle through and get the values
                while ($stmt->fetch()) {

                    // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
                    if (($status1Bind == "waiting") && ($status2Bind == "waiting")) {
                        $currentOpen++;
                        $openContractID = $contractIDBind;
                    }

                    // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
                    if (($status1Bind == "waiting") && ($status2Bind == "open")) {
                        $currentOpen++;
                        $openContractID = $contractIDBind;
                    }

                    // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
                    if (($status1Bind == "open") && ($status2Bind == "waiting")) {
                        $currentOpen++;
                        $openContractID = $contractIDBind;
                    }

                    // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
                    if (($status1Bind == "open") && ($status2Bind == "open")) {
                        $currentOpen++;
                        $openContractID = $contractIDBind;
                    }

                    // more than 1 open contract - should not be allowed
                    if ($currentOpen > 0) {

                        // not valid
                        $statusCode = 20;
                    }
                
                }        

                // close statement
                mysqli_stmt_close($stmt);        
                
            }

        }

    }

    // if still ok
    if ($statusCode == 0) {
        
        // set the usedFlag 
        $usedFlag = "no";

        // pull next account from wast table - up to 5 tries       
        $sql = "SELECT wastID, wastName, accountName, contractName FROM wastTable WHERE usedFlag = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ? ORDER BY createDate DESC LIMIT 5";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "ssss", $usedFlag, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'CREATE ACCOUNT FAIL: NO MORE WAST ROWS LEFT FOR CONTRACTS';
                $logging = "Create more wast files. No contract name found at: ";
                $logging .= $createDate;
                $logging .= ' for matchID: '.$matchID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 21;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $wastIDBind, $wastNameBind, $accountNameBind, $contractNameBind);
            
                // set a counter for max number of wast files to check 
                $counter = 0;

                // fetch the the latest result
                while($stmt->fetch()) {
                    
                    // set variables
                    $wastID = $wastIDBind;		
                    $wastName = $wastNameBind;		
                    $accountName = $accountNameBind;		
                    $contractName = $contractNameBind;		                
                
                    // check if accountName exists on blockchain
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

                        // curl error - problem with blockchain connection (try again)                                                
                        $nameExistsFlag = "yes";

                    } else {

                        // parse the result to see if there are keys set for account
                        $arrayString = json_decode($result, true);                    
                        $permissions = $arrayString["permissions"];
                        if (isset($permissions)) {
                            // check 2 keys have been set - active/owner
                            if (count($permissions) != 2) {
                                // valid account does not already exist
                                $nameExistsFlag = "no";
                                break;
                            }
                        } else {
                            // no permissions set - invalid response format (try again)
                            $nameExistsFlag = "yes";
                        }
                        
                    }
                    curl_close($ch);

                    // if name exists, pull up to 5 times
                    if ($nameExistsFlag == "yes") {                            
                        $counter++;                        
                    } 
                    
                    // pull up to 5 names
                    if ($counter >= 4) {
                        // error with account name
                        $statusCode = 22;
                        break;
                    }

                }

                // close statement
                mysqli_stmt_close($stmt);                               

            }

        }

        // check how many names remain - alert admin when names are running low
        if ($numRows < 25) {

            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'RUNNING LOW ON WAST FILES. CREATE MORE';
            $logging = "There are only: ";
            $logging .= $numRows;
            $logging .= ' unused wast names left. Need to create more contracts.';            			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
            
        }
            
    }

    // if still ok
    if ($statusCode == 0) {

        // pull the keypair for the first user
        $sql = "SELECT encodedKeyID, public FROM encodedKeyTable WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=? ORDER BY createDate DESC LIMIT 1";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $spotID1, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // invalid
                $statusCode = 31;
                        
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $encodedKeyID1Bind, $public1Bind);
                                                                                                                                                                        
                // fetch the results
                $stmt->fetch();
                                
                // set variables
                $encodedKeyID1 = $encodedKeyID1Bind;                
                $publicKey1 = $public1Bind;
                        
                // close statement
                mysqli_stmt_close($stmt);

            }

        }

        // pull the keypair for the second user
        $sql = "SELECT encodedKeyID, public FROM encodedKeyTable WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=? ORDER BY createDate DESC LIMIT 1";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $spotID2, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // invalid
                $statusCode = 32;
                        
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $encodedKeyID2Bind, $public2Bind);
                                                                                                                                                                        
                // fetch the results
                $stmt->fetch();
                                
                // set variables
                $encodedKeyID2 = $encodedKeyID2Bind;                
                $publicKey2 = $public2Bind;
                        
                // close statement
                mysqli_stmt_close($stmt);

            }

        }

        // set the rotatedFlag
        $rotatedFlag = "no";

        // pull the matcheos creation account and private
        $sql = "SELECT encodedMatcheosID, activeMatcheosAccount, activeEncodedMatcheosPrivate FROM encodedMatcheosTable WHERE rotatedFlag=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=? ORDER BY createDate DESC LIMIT 1";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "ssss", $rotatedFlag, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // invalid
                $statusCode = 33;
                        
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $encodedMatcheosIDBind, $activeMatcheosAccountBind, $activeEncodedMatcheosPrivateBind);
                                                                                                                                                                        
                // fetch the results
                $stmt->fetch();
                                
                // set variables
                $encodedMatcheosID = $encodedMatcheosIDBind;
                $activeMatcheosAccount = $activeMatcheosAccountBind;
                $activeEncodedMatcheosPrivate = $activeEncodedMatcheosPrivateBind;
                                    
            }

            // close statement
            mysqli_stmt_close($stmt);

        }

        // decrypt the matcheos information
        $matcheosAccount = $activeMatcheosAccount;
        $userKeyMatcheos = Key::loadFromAsciiSafeString(MATCHEOSUSERKEYENCODED);
        $matcheosPrivate = Crypto::decrypt($activeEncodedMatcheosPrivate, $userKeyMatcheos);        
    
    }

    // if still ok
    if ($statusCode == 0) {
                        
        // execute create account name on blockchain
        $createAccount = shell_exec('node '.MATCHEOSPATH.'/createaccount.js '.escapeshellarg($accountName).' '.escapeshellarg($publicKey1).' '.escapeshellarg($publicKey2).' '.escapeshellarg($matcheosPrivate).' '.escapeshellarg($matcheosAccount));                

        // check that name now exists
        if (trim($createAccount) == "success") {

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
                $statusCode = 41;

            } else {

                // parse the result to see if there are keys set for account
                $arrayString = json_decode($result, true);            
                $permissions = $arrayString["permissions"];
                if (isset($permissions)) {
                    // check 2 keys have been set - active/owner
                    if (count($permissions) != 2) {
                        // valid account does not exist with correct keys
                        $statusCode = 42;
                    }
                } else {
                    // no permissions set - account does not exist
                    $statusCode = 43;
                }
                
            }
            curl_close($ch);
    
        } else {

            // error account not created
            $statusCode = 45;

        }
  
    }

    // if still ok
    if ($statusCode == 0) {
                
        // set the usedFlag
        $usedFlag = "yes";
        
        // update key used flags in encoded table for first user
        $sqlUpdate = "UPDATE encodedKeyTable SET usedFlag=? WHERE public=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "ss", $usedFlag, $publicKey1);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // update key used flags in encoded table for second user
        $sqlUpdate = "UPDATE encodedKeyTable SET usedFlag=? WHERE public=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "ss", $usedFlag, $publicKey2);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // update the used flag
        $usedFlag = "yes";

        // update the used flag in wast table
        $sqlUpdate = "UPDATE wastTable SET usedFlag=? WHERE wastID=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "si", $usedFlag, $wastID);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        $rejectFlag = "no";
        $status1 = "waiting";
        $status2 = "waiting";

        // pull the contractGoal from goalID        
        $sql = "SELECT goal FROM goalTable WHERE goalID=?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "i", $goalID);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // invalid, default to become facebook friends
                $contractGoal = "Become Facebook Friends";
                        
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $goalBind);
                                                                                                                                                                        
                // fetch the results
                $stmt->fetch();
                                
                // set variables
                $contractGoal = $goalBind;                
                        
                // close statement
                mysqli_stmt_close($stmt);

            }

        }
        
        // set the messageFlag to no at the start
        $messageFlag = "no";

        // insert into new contract, status of waiting for both users
        $sql = "INSERT INTO contractTable (wastID, matchID, spotID1, spotID2, offer1, offer2, status1, status2, rejectFlag, contractType, contractGoal, goalID, contractFormat, contractSteps, contractRelease, contractDonee, contractArbFee, contractFee, messageFlag, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiiiddsssssisiisiissssss", $wastID, $matchID, $spotID1, $spotID2, $offer1, $offer2, $status1, $status2, $rejectFlag, $contractType, $contractGoal, $goalID, $contractFormat, $contractSteps, $contractRelease, $contractDonee, $contractArbFee, $contractFee, $messageFlag, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }

        // get the contract id
        $contractID = mysqli_insert_id($mysqli);

        // determine the active and owner (owner is publicKey1, active is publicKey2 in nodejs createaccount)
        $activeEncodedKeyID = $encodedKeyID2;
        $ownerEncodedKeyID = $encodedKeyID1;

        // insert info into contractAdmin table
        $sql = "INSERT INTO contractAdminTable (contractID, activeEncodedKeyID, ownerEncodedKeyID, encodedMatcheosID, aliveFlag, errorFlag, deleteFlag, deleteDate, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiiissssss", $contractID, $activeEncodedKeyID, $ownerEncodedKeyID, $encodedMatcheosID, $aliveFlag, $errorFlag, $deleteFlag, $deleteDate, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }

        // check how many contracts exist - email admin to update account funding
        if (($contractID % 100) == 0) {
            // email admin            
            $to = ADMINEMAIL;  
			$subject = '100 MORE CONTRACTS CREATED. CHECK FUNDING ACCOUNT HAS ENOUGH BALANCE.';
			$logging = "There was another contract created at: ";
            $logging .= $createDate;
            $logging .= ". Total contracts at contractID: ".$contractID;
            $logging .= ". If there is small balance, send more funds.";
			$header = 'From: donotrespond@matcheos.com';
			if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}
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
    "accountName" => $accountName,	    
);	

echo json_encode($ajaxResponse);


?>