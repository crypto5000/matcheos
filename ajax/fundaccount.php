<?php
session_start();
/*
*    THIS PAGE IS ONLY USED FOR EARLY VERSIONS OF SOFTWARE: TO STREAMLINE USER TESTING AND
*    EXPERIENCE. IN FINAL PRODUCTION, THE USER IS EXPECTED TO FUND FROM THEIR OWN WALLET.
*    FUNDS WILL BE VERIFIED USING VERIFYFUNDS.JS.
*/

require_once('../crypto/defuse-crypto.phar');

// set up classes
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;    

// set default status code
$statusCode = 0;

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// set the default contractID
$contractID = 0;

// set the default interact flag
$interactExists = "no";

// set the key type - active or owner depending on spot
$keyType = "active";

// pull the data
$matchID = $_POST["field1"];
$token = $_POST["field2"];
$accountName = $_POST["field3"];
if (isset($_POST["field4"])) {
    $password = $_POST["field4"];
} 

// validation - check for html special characters
$matchID = validate($matchID);
$token = validate($token);
$accountName = validate($accountName);

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

// validate that the field was submitted
if (!isset($accountName)) {  
    $statusCode = 4;	
}

// validate the field length
if (strlen($accountName) < 1) {  
    $statusCode = 4;	
} 

// validate the field length
if (strlen($accountName) > 1000) {  
    $statusCode = 4;	
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
    $form = "fundaccount";    
    
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
			$logging = "There was a hack attempt on fundaccount at: ";
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
                $subject = 'FUND ACCOUNT FAIL: NO DATA FOUND ON PAGE';
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
                    
                    // check status is active for current user
                    if ($status1Match != "active") {
                        //  error
                        $statusCode = 14;
                    }

                    // status is new or active for other user
                    if ($status2Match == "closed") {
                        //  error
                        $statusCode = 15;
                    }                        

                } else {

                    // check status is active for current user
                    if ($status2Match != "active") {
                        //  error
                        $statusCode = 16;
                    }

                    // status is new or active for other user
                    if ($status1Match == "closed") {
                        //  error
                        $statusCode = 17;
                    }
                
                }

                // close statement
                mysqli_stmt_close($stmt);
    
            }

        }
        
    }

    // if still ok,
    if ($statusCode == 0) {

        // verify account name exists in wastTable - pull data
        $sql = "SELECT wastID, wastName, accountName, contractName FROM wastTable WHERE accountName = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "ssss", $accountName, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'FUND ACCOUNT FAIL: NO DATA FOUND ON PAGE';
                $logging = "No wastID found at: ";
                $logging .= $createDate;
                $logging .= ' for matchID: '.$matchID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 18;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $wastIDBind, $wastNameBind, $accountNameBind, $contractNameBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $wastID = $wastIDBind;		
                $wastName = $wastNameBind;		
                $accountName = $accountNameBind;		
                $contractName = $contractNameBind;		
                
                // close statement
                mysqli_stmt_close($stmt);

            }

        }
                
    }

    // if still ok
    if ($statusCode == 0) {
        
        // check a contract exists for this wast, pull data
        $sql = "SELECT contractID, spotID1, spotID2, offer1, offer2, status1, status2, contractType, contractGoal, contractFormat, contractSteps, contractDonee, contractArbFee, contractFee  FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND wastID = ? AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
         
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiiisss", $userID, $userID, $wastID, $matchID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows != 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'FUND ACCOUNT FAIL: SHOULD ONLY BE 1 CONTRACTID';
                $logging = "No contractID or more than 1 contractID found at: ";
                $logging .= $createDate;
                $logging .= ' for matchID: '.$matchID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 19;

            } else {
                                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $contractIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $status1Bind, $status2Bind, $contractTypeBind, $contractGoalBind, $contractFormatBind, $contractStepsBind, $contractDoneeBind, $contractArbFeeBind, $contractFeeBind);
                                
                // fetch the results
                $stmt->fetch();

                // set variables
                $contractID = $contractIDBind;		
                $spotID1 = $spotID1Bind;		
                $spotID2 = $spotID2Bind;		
                $offer1 = $offer1Bind;		
                $offer2 = $offer2Bind;		
                $status1 = $status1Bind;		
                $status2 = $status2Bind;	
                $contractType = $contractTypeBind;
                $contractGoal = $contractGoalBind;
                $contractFormat = $contractFormatBind;
                $contractSteps = $contractStepsBind;
                $contractDonee = $contractDoneeBind;
                $contractArbFee = $contractArbFeeBind;
                $contractFee = $contractFeeBind;
                
                // determine which spot user is in
                if ($userID == $spotID1) {
                    
                    // set the offer, other user
                    $offer = $offer1;
                    $otherUserID = $spotID2;

                    // check the status is valid 
                    if ($status1 != "waiting") {
                        // not valid
                        $statusCode = 20;
                    }

                    // check the status is valid 
                    if (($status2 == "waiting") || ($status2 == "open")) {
                        // do nothing
                    } else {
                        // not valid
                        $statusCode = 21;
                    }

                } else {
                    
                    // set the offer, other user
                    $offer = $offer2;
                    $otherUserID = $spotID1;

                    // check the status is valid 
                    if ($status2 != "waiting") {
                        // not valid
                        $statusCode = 22;
                    }

                    // check the status is valid 
                    if (($status1 == "waiting") || ($status1 == "open")) {
                        // do nothing
                    } else {
                        // not valid
                        $statusCode = 23;
                    }

                }

                // close statement
                mysqli_stmt_close($stmt);

            } 

        }

    }
    
    // if still ok,
    if ($statusCode == 0) {

        // check if interact table already exists
        $sql = "SELECT interactID FROM interactTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $contractID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows > 0) {

                // set the interact variable
                $interactExists = "yes";
                
            }

        }
                    
    }

    // if still ok
    if ($statusCode == 0) {

        // pull the alpha wallet information to transfer
        $sql = "SELECT userName, activeEncodedPrivate FROM alphaTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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

               // send an email to the admin         
               $to = ADMINEMAIL;  
               $subject = 'FUND ACCOUNT FAIL: NO DATA FOUND ON PAGE';
               $logging = "No alpha information found at: ";
               $logging .= $createDate;
               $logging .= ' for matchID: '.$matchID;
               $logging .= ' for userID: '.$userID;			      
               $header = 'From: donotrespond@matcheos.com';
               if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

               // not valid
               $statusCode = 24;
               
           } else {
                               
               // bind result variables
               mysqli_stmt_bind_result($stmt, $userName2Bind, $activeEncodedPrivateBind);
                               
               // fetch the results
               $stmt->fetch();

               // set variables
               $userName2 = $userName2Bind;		
               $activeEncodedPrivate = $activeEncodedPrivateBind;		
               
               // decrypt the data 
               if (isset($password)) {

                    $protectedKey = KeyProtectedByPassword::loadFromAsciiSafeString($protectedKeyEncoded);
                    try {
                        
                        $userKey = $protectedKey->unlockKey($password);                    
                        $encryptedPrivateTemp = Crypto::decrypt($activeEncodedPrivate, $userKey);                    
                     
                    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                     
                        // no decryption                    
                        $statusCode = 25;
                    }

               } elseif (isset($_SESSION["userKeyEncoded"])) {

                    $userKey = Key::loadFromAsciiSafeString($_SESSION["userKeyEncoded"]);
                    try {
                        
                        $encryptedPrivateTemp = Crypto::decrypt($activeEncodedPrivate, $userKey);
                     
                    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                     
                        // no decryption                    
                        $statusCode = 25;
                    }

               } else {

                    // no decryption                    
                    $statusCode = 26;

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
            $statusCode = 600;
        }                    
                
    }
    
    // if still ok,
    if ($statusCode == 0) {

        // validate the fields
        $userName2 = validate($userName2);
        $offer = validate($offer);
        $tempPrivate = validate($tempPrivate);
        
        // exec to nodejs for transfer from user account to contract account
        $fundAccount = shell_exec('node '.MATCHEOSPATH.'/fundaccount.js '.escapeshellarg($accountName).' '.escapeshellarg($userName2).' '.escapeshellarg($offer).' '.escapeshellarg($tempPrivate));        

        // verify that funding transactions are greater than offer
        if (strpos(trim($fundAccount), 'successContractExists') !== false) {                    
            // funds were verified and contract has already been published - this means this is person2
            $contractExists = "yes";                    
        } elseif (strpos(trim($fundAccount), 'successNoContractExists') !== false) {            
            // funds were verified and contract has not already been published - this means this is person1
            $contractExists = "no";                    
        } else {
            // output error - funding could not be verified for this user
            $statusCode = 461;
        }        
                   
        // sleep for 2 seconds to allow for blockchain confirmation
        sleep(2);

    }
    
    // if still ok
    if ($statusCode == 0) {

        // pull the other person in the match        
        $sql = "SELECT userName FROM userTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                       
           // bind parameters for markers
           mysqli_stmt_bind_param($stmt, "isss", $otherUserID, $aliveFlag, $deleteFlag, $errorFlag);

           // execute query
           mysqli_stmt_execute($stmt);

           // store result to get num rows				
           $stmt->store_result();
                   
           // get the number of rows
           $numRows = $stmt->num_rows;
                                   
           if ($numRows < 1) {

               // send an email to the admin         
               $to = ADMINEMAIL;  
               $subject = 'FUND ACCOUNT FAIL: NO DATA FOUND ON PAGE';
               $logging = "No other userName found at: ";
               $logging .= $createDate;
               $logging .= ' for matchID: '.$matchID;
               $logging .= ' for other userID: '.$otherUserID;			      
               $header = 'From: donotrespond@matcheos.com';
               if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

               // not valid
               $statusCode = 60;

           } else {
                               
               // bind result variables
               mysqli_stmt_bind_result($stmt, $otherUserNameBind);
                               
               // fetch the results
               $stmt->fetch();

               // set variables
               $otherUserName = $otherUserNameBind;		               

               // close statement
               mysqli_stmt_close($stmt);

           } 

       }

    }

    // if still ok,
    if ($statusCode == 0) {
        
        // determine whether user is active or owner (owner is spotID1 of contract, active is spotID2 of contract)
        if ($userID == $spotID1) {

            // set the keyType
            $keyType = "owner";

            // pull owner from contractAdmin table            
            $sql = "SELECT ownerEncodedKeyID FROM contractAdminTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

        } else {

            // set the keyType
            $keyType = "active";
            
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
                $subject = 'FOUND ACCOUNT FAIL: NO DATA FOUND ON PAGE';
                $logging = "No contractAdmin data found at: ";
                $logging .= $createDate;
                $logging .= ' for contractID: '.$contractID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 151;
                
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
                $subject = 'FUND ACCOUNT FAIL: NO DATA FOUND ON PAGE';
                $logging = "No encoded key data found at: ";
                $logging .= $createDate;
                $logging .= ' for encodedKeyID: '.$encodedKeyID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 152;
                
            } else {

                // bind result variables
                mysqli_stmt_bind_result($stmt, $encodedPrivateBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $encodedPrivate2 = $encodedPrivateBind;     
                            
                // decrypt the data 
                if (isset($password)) {
                    
                    $protectedKey = KeyProtectedByPassword::loadFromAsciiSafeString($protectedKeyEncoded);
                    try {
                        
                        $userKey = $protectedKey->unlockKey($password);                    
                        $encryptedPrivateTemp2 = Crypto::decrypt($encodedPrivate2, $userKey);                    
                        
                    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                        
                        // no decryption                    
                        $statusCode = 153;
                    }

                } elseif (isset($_SESSION["userKeyEncoded"])) {

                    $userKey = Key::loadFromAsciiSafeString($_SESSION["userKeyEncoded"]);
                    try {
                        
                        $encryptedPrivateTemp2 = Crypto::decrypt($encodedPrivate2, $userKey);
                        
                    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                        
                        // no decryption                    
                        $statusCode = 153;
                    }

                } else {

                    // no decryption                    
                    $statusCode = 154;

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
            
            $tempPrivate2 = Crypto::decrypt($encryptedPrivateTemp2, $userKeyMatcheos);
            
        } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
            
            // no decryption                    
            $statusCode = 601;
        }                    
                
    }
    
    // if still ok, and contract does not exist yet
    if (($statusCode == 0) && ($contractExists == "no")) {
                        
        // set the initial contract terms in blockchain
        $feeAccount = MATCHEOS_FEE_ACCOUNT;        
    
        // exec to nodejs create the contract on the blockchain
        $createContract = shell_exec('node '.MATCHEOSPATH.'/createcontract.js '.escapeshellarg($accountName).' '.escapeshellarg($wastName).' '.escapeshellarg($contractName).' '.escapeshellarg($tempPrivate2).' '.escapeshellarg($userName).' '.escapeshellarg($offer).' '.escapeshellarg($otherUserName).' '.escapeshellarg($contractType).' '.escapeshellarg($contractGoal).' '.escapeshellarg($contractFormat).' '.escapeshellarg($contractSteps).' '.escapeshellarg($contractDonee).' '.escapeshellarg($contractArbFee).' '.escapeshellarg($contractFee).' '.escapeshellarg($feeAccount).' '.escapeshellarg($keyType));        

        // check contract was created
        if (strpos(trim($createContract), 'success') !== false) {                    
            // do nothing
        } else {        
            // contract not created
            $statusCode = 46;
        }

    }

    // if still ok, and contract exists (second user)
    if (($statusCode == 0) && ($contractExists == "yes")) {
        
        // set the initial person2start
        $person2Start = $offer;        
    
        // exec to nodejs create the intial person2start
        $updateContract = shell_exec('node '.MATCHEOSPATH.'/updatecontract.js '.escapeshellarg($accountName).' '.escapeshellarg($person2Start).' '.escapeshellarg($contractName).' '.escapeshellarg($userName).' '.escapeshellarg($tempPrivate2).' '.escapeshellarg($keyType));        

        // check contract was created
        if (trim($updateContract) == "success") {
            // do nothing
        } else {        
            // contract not updated
            $statusCode = 47;
        }

    }
        
    // if still ok - analytics already updated from new to active on acceptmatch
    if ($statusCode == 0) {

        // set the status to active for match for the user's slot - already should be active from acceptmatch
        $statusMatch = "active";

        // set the status to open for contract for the user's slot
        $statusContract = "open";

        // update the match table
        if ($userID == $spotID1) {
            $sqlUpdate = "UPDATE matchTable SET status1=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        } else {
            $sqlUpdate = "UPDATE matchTable SET status2=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        }
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sisss", $statusMatch, $matchID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }
                
        // update the contract table
        if ($userID == $spotID1) {
            $sqlUpdate = "UPDATE contractTable SET status1=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        } else {
            $sqlUpdate = "UPDATE contractTable SET status2=? WHERE contractID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        }        
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sisss", $statusContract, $contractID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }            

        // set the interact table start - if does not already exist
        if ($interactExists == "no") {

            $currentStep = 1;
            $statusInteract = "open";
                    
            // insert contract into new interaction, status of open
            $sql = "INSERT INTO interactTable (contractID, spotID1, spotID2, currentStep, status, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
                // bind parameters for markers
                mysqli_stmt_bind_param($stmt, "iiiissssss", $contractID, $spotID1, $spotID2, $currentStep, $statusInteract, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

                // execute query
                mysqli_stmt_execute($stmt);

                // close the statement
                $stmt->close();
                
            }

        }

        // set status to ok
        $statusCode = 1;

    }
    
}	

if (isset($encryptedPrivateTemp)) {unset($encryptedPrivateTemp);}        
if (isset($encryptedPrivateTemp2)) {unset($encryptedPrivateTemp2);}        
if (isset($userKey)) {unset($userKey);}        
if (isset($userKeyMatcheos)) {unset($userKeyMatcheos);}        
if (isset($tempPrivate)) {unset($tempPrivate);}        
if (isset($tempPrivate2)) {unset($tempPrivate2);}        
if (isset($password)) {unset($password);}        

/*
* 1 is ok
* 2+ is all others
*/

// close connection
mysqli_close($mysqli);
    
$ajaxResponse = array(
    "ajaxResult" => $statusCode,	    
    "contractID" => $contractID,	    
);	

echo json_encode($ajaxResponse);


?>