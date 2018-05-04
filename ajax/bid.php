<?php

// set default status code
$statusCode = 0;

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// set the matchFlag, coldStart to no as default
$matchFlag = "no";
$coldStartFlag = "no";

// set the deafult ageFlag to yes - gets reset to no
$ageFlag = "yes";

// set the current values to 0 as default
$currentMaxRank = 0;
$currentRankCount = 0;
$currentMinRank = 0;
$currentMaxRank2 = 0;
$currentRankCount2 = 0;
$currentMinRank2 = 0;

// pull the data
$bidVal = $_POST["field1"];
$bidType = $_POST["field2"];
$offerID = $_POST["field3"];
$token = $_POST["field4"];

// validation - check for html special characters
$bidVal = validate($bidVal);
$bidType = validate($bidType);
$offerID = validate($offerID);
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
if (!isset($bidVal)) {  
    $statusCode = 499;	
}

// validate that the field was submitted
if (strlen($bidVal) < 1) {  
    $statusCode = 500;	
} 

// validate that the field is less than 500 characters
if (strlen($bidVal) > 50000) {
    $statusCode = 501;	
}

// validate that the field is a good type
if (($bidType == "work") || ($bidType == "love") || ($bidType == "friend")) {
    // do nothing
} else {
    $statusCode = 502;	
}

// validate that the field was submitted
if (!isset($offerID)) {  
    $statusCode = 503;	
}

// validate that the field was submitted
if (strlen($offerID) < 1) {  
    $statusCode = 504;	
} 

// validate that the field is less than 500 characters
if (strlen($offerID) > 50000) {
    $statusCode = 505;	
}

// validate the offer ID
if (is_numeric($offerID)) {
    $offerID = (int) $offerID;
} else {
    $statusCode = 506;	
}

// validate the bid
if (is_numeric($bidVal)) {
    // do nothing
} else {
    $statusCode = 507;	
}

// validate that the field was submitted
if (!isset($token)) {  
    $statusCode = 508;	
}

// validate the field length
if (strlen($token) < 1) {  
    $statusCode = 509;	
} 

// validate the field length
if (strlen($token) > 1000) {  
    $statusCode = 510;	
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

// validate the minimum EOS bid
if ($bidVal < MINIMUM_BID) {
    $statusCode = 511;	
}

// validate the maximum EOS bid
if ($bidVal > MAXIMUM_BID) {
    $statusCode = 512;	
}

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
    $form = "bid";
    
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
			$logging = "There was a hack attempt on bid at: ";
			$logging .= $createDate;
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
    $sql = "SELECT userID, userName, lastLogin, birthYear, langID FROM userTable WHERE token=?";
    
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
            $statusCode = 513;
                    
        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $userIDBind, $userNameBind, $loginBind, $birthYearBind, $langIDBind);
                                                                                                                                                                    
            // fetch the results
            $stmt->fetch();
                            
            // set variables
            $userID = $userIDBind;		
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
                $statusCode = 514;

            }

            // check that user is different from offer (don't bid on oneself)
            if ($userID == $offerID) {
                // invalid
                $statusCode = 515;
            }

        }

    }

    // if still ok
    if ($statusCode == 0) {
        // check the offerID is valid - pull birth and language for matching criteria
        $sql = "SELECT userName, birthYear, langID FROM userTable WHERE userID=?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "i", $offerID);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // invalid
                $statusCode = 516;
                        
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $offerNameBind, $offerBirthYearBind, $offerLangIDBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $offerName = $offerNameBind;
                $birthYearOffer = $offerBirthYearBind;		
                $offerLangID = $offerLangIDBind;
                
                // close statement
                mysqli_stmt_close($stmt);

            }

        }

    }
    
    // if still ok,
    if ($statusCode == 0) {

        // matchTable has lower ID in spot 1 and higher ID in spot 2
        if ($userID > $offerID) {
            $spotID1 = $offerID;
            $spotID2 = $userID;
        } else {
            $spotID1 = $userID;
            $spotID2 = $offerID;
        }

        // check if person already matched (new/active/closed)        
        $sql = "SELECT matchID FROM matchTable WHERE spotID1=? AND spotID2=? AND matchType=? AND aliveFlag=? AND deleteFlag=? AND errorFlag=?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iissss", $spotID1, $spotID2, $bidType, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows > 0) {

                // already a match
                $statusCode = 4;                        

            } 
                
        }

    }

    // if still ok,
    if ($statusCode == 0) {

        // set the currency token, currency contract code, blockchain url for balance
        $code = CURRENCYCODE;
        $token = CURRENCYTOKEN;
        $blockchainUrl = BLOCKCHAINURL_BALANCE;

        // check that account has enough balance by querying blockchain        
        $accountBalance = shell_exec('node '.MATCHEOSPATH.'/getbalance.js '.escapeshellarg($code).' '.escapeshellarg($userName).' '.escapeshellarg($token).' '.escapeshellarg($blockchainUrl));                        

        if (is_numeric(trim($accountBalance))) {
            // convert to float
            $accountBalance = trim($accountBalance);
            $accountBalance = (float) $accountBalance;
            // check balance is above minimum - used for nothing at stake
            if ($accountBalance < MINIMUM_BALANCE) {
                // balance error
                $statusCode = 517;
            }
            // check bid is below balance
            if ($bidVal > $accountBalance) {
                // balance error
                $statusCode = 3;
            }            
        } else {
            // result is an error
            $statusCode = 518;
        }                
        
    }

    // if still ok, insert bid into bid table
    if ($statusCode == 0) {
        
        // check if the bid exists
        $sql = "SELECT bidID, createDate FROM bidTable WHERE userID = ? AND offerID = ? AND bidType = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iissss", $userID, $offerID, $bidType, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows > 0) {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $bidIDBind, $createDateBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $bidID = $bidIDBind;		
                $bidDate = $createDateBind;		

                // close statement
                mysqli_stmt_close($stmt);

                // check if bid was over x days ago
			    $plusDays = " + ".BID_DAYS." days";
			    $failBid = $bidDate.$plusDays;			
			    $failBid = date("Y-m-d H:i:s",strtotime($failBid));			    			                                
                
                // check if the bid has occured within the last timeframe
                if (strtotime($createDate) < strtotime($failBid)) {

                    // do not allow recent updates to bids
                    $statusCode = 5;

                } else {
                    
                    // delete the old bid
                    $aliveUpdateFlag = "no";
                    $deleteUpdateFlag = "yes";
                    $deleteDate = $createDate;

                    // update the database
                    $sqlUpdate = "UPDATE bidTable SET aliveFlag=?, deleteFlag=?, deleteDate=? WHERE bidID=?";
                    
                    if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                                
                        // bind parameters for markers
                        mysqli_stmt_bind_param($stmt2, "sssi", $aliveUpdateFlag, $deleteUpdateFlag, $deleteDate, $bidID);

                        // execute query
                        mysqli_stmt_execute($stmt2);

                        // close the statement
                        $stmt2->close();
                        
                    }

                }

            }

        }

        // if still ok, 
        if ($statusCode == 0) {
        
            // insert new bid         
            $sql = "INSERT INTO bidTable (userID, offerID, bidType, bidVal, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
                // bind parameters for markers
                mysqli_stmt_bind_param($stmt, "iisdsssss", $userID, $offerID, $bidType, $bidVal, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

                // execute query
                mysqli_stmt_execute($stmt);

                // close the statement
                $stmt->close();
                
            }
        
            // set status to ok
            $statusCode = 1;

        }
        
    }        
    
    // if still ok, 
    if ($statusCode == 1) {

        // pull the maxRank, rankCount, minRank - based on matchType
        $sql = "SELECT maxRankWork, rankCountWork, minRankWork, maxRankLove, rankCountLove, minRankLove, maxRankFriend, rankCountFriend, minRankFriend FROM algorithmTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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

                // invalid - this shouldn't happen
                $statusCode = 520;
                $to = ADMINEMAIL;  
                $subject = 'ALGORITHM ERROR: NO RESULT FOUND FOR USER';
                $logging = "No analytics were found for userID: ";
                $logging .= $userID;
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}
                        
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $maxRankWorkBind, $rankCountWorkBind, $minRankWorkBind, $maxRankLoveBind, $rankCountLoveBind, $minRankLoveBind, $maxRankFriendBind, $rankCountFriendBind, $minRankFriendBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables                
                $maxRankWork = $maxRankWorkBind;
                $rankCountWork = $rankCountWorkBind;
                $minRankWork = $minRankWorkBind;
                $maxRankLove = $maxRankLoveBind;
                $rankCountLove = $rankCountLoveBind;
                $minRankLove = $minRankLoveBind;
                $maxRankFriend = $maxRankFriendBind;
                $rankCountFriend = $rankCountFriendBind;
                $minRankFriend = $minRankFriendBind;
                
                // close statement
                mysqli_stmt_close($stmt);

                // set the current values based on type
                if ($bidType == "work") {
                    $currentMaxRank = $maxRankWork;
                    $currentRankCount = $rankCountWork;
                    $currentMinRank = $minRankWork;
                } elseif ($bidType == "love") {
                    $currentMaxRank = $maxRankLove;
                    $currentRankCount = $rankCountLove;
                    $currentMinRank = $minRankLove;
                } else {
                    $currentMaxRank = $maxRankFriend;
                    $currentRankCount = $rankCountFriend;
                    $currentMinRank = $minRankFriend;
                }

                // validate the current values
                if (is_numeric($currentMaxRank)) {
                    // do nothing
                } else {
                    $currentMaxRank = 0;
                }

                // validate the current values
                if (is_numeric($currentRankCount)) {
                    $currentRankCount = (int) $currentRankCount;
                } else {
                    $currentRankCount = 0;
                }

                // validate the current values
                if (is_numeric($currentMinRank)) {
                    // do nothing
                } else {
                    $currentMinRank = 0;
                }                

            }

        }

    }

    // if still ok
    if ($statusCode == 1) {

        // calculate the rank of the offer
        if ($accountBalance > 0) {
            $rank = $bidVal / $accountBalance;
        } else {
            $rank = 0;
        }

        // update the current values
        $currentRankCount = $currentRankCount + 1;

        // check if new max
        if ($rank > $currentMaxRank) {
            $currentMaxRank = $rank;
        }

        // check if new min
        if ($rank < $currentMinRank) {
            $currentMinRank = $rank;
        }        

        // update the algorithm table        
        if ($bidType == "work") {
            $sqlUpdate = "UPDATE algorithmTable SET maxRankWork=?, rankCountWork=?, minRankWork=? WHERE userID=? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        } elseif ($bidType == "love") {
            $sqlUpdate = "UPDATE algorithmTable SET maxRankLove=?, rankCountLove=?, minRankLove=? WHERE userID=? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        } else {
            $sqlUpdate = "UPDATE algorithmTable SET maxRankFriend=?, rankCountFriend=?, minRankFriend=? WHERE userID=? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        }
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "didisss", $currentMaxRank, $currentRankCount, $currentMinRank, $userID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // check if coldstart
        if ($currentRankCount < COLDSTART) {

            // do not match
            $coldStartFlag = "yes";

        } else {

            // calculate the score
            if ($currentMaxRank > 0) {
                $score = $rank / $currentMaxRank;
            } else {
                $score = 0;
            }

            // adjust the score for range
            $range = $currentMaxRank - $currentMinRank;
            if ($range < RANGE_THRESHOLD) {
                $score = $score * RANGE_PENALTY;
            }

        }

    }
    
    // if still ok and not cold start
    if (($statusCode == 1) && ($coldStartFlag == "no"))  {

        // check if the other person has bid on the user for bidType
        $sql = "SELECT bidVal FROM bidTable WHERE userID = ? AND offerID = ? AND bidType = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iissss", $offerID, $userID, $bidType, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // no match - person did not bid on user                

            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $bidVal2Bind);
            
                // fetch the results
                $stmt->fetch();

                // set variables                
                $bidVal2 = $bidVal2Bind;

                // close statement
                mysqli_stmt_close($stmt);
                
                // check for age filter (if love or friend, must be same bracket)
                $currentYear = date('Y');
                $currentYear = (int) $currentYear;
                $ageOffer = $currentYear - $birthYearOffer;
                $ageUser = $currentYear - $birthYear;

                // reset the default ageFlag to no
                $ageFlag = "no";

                //  if love or friend, must be same bracket
                if (($bidType == "love") || ($bidType == "friend")) {

                    // check for age mismatch
                    if (($ageOffer < 18) && ($ageUser > 18)) {

                        // no match
                        $ageFlag = "yes";

                    }

                    // check for age mismatch
                    if (($ageUser < 18) && ($ageOffer > 18)) {

                        // no match
                        $ageFlag = "yes";

                    }

                }
                
            }

        }

    }

    // if still ok and ageFlag is no
    if (($statusCode == 1) && ($ageFlag == "no"))  {
        
        // set the currency token, currency contract code, blockchain url for balance
        $code = CURRENCYCODE;
        $token = CURRENCYTOKEN;
        $blockchainUrl = BLOCKCHAINURL_BALANCE;

        // check that account has enough balance by querying blockchain        
        $accountBalance2 = shell_exec('node '.MATCHEOSPATH.'/getbalance.js '.escapeshellarg($code).' '.escapeshellarg($offerName).' '.escapeshellarg($token).' '.escapeshellarg($blockchainUrl));                

        if (is_numeric(trim($accountBalance2))) {
            // convert to float
            $accountBalance2 = trim($accountBalance2);
            $accountBalance2 = (float) $accountBalance2;
            // check balance is above minimum - used for nothing at stake
            if ($accountBalance2 < MINIMUM_BALANCE) {
                // balance error
                $statusCode = 1;
            }
            // check bid is below balance
            if ($bidVal2 > $accountBalance2) {
                // balance error
                $statusCode = 1;
            }            
        } else {
            // result is an error
            $statusCode = 1;
        }

        // pull the maxRank, rankCount, minRank for offer person - based on matchType
        $sql = "SELECT maxRankWork, rankCountWork, minRankWork, maxRankLove, rankCountLove, minRankLove, maxRankFriend, rankCountFriend, minRankFriend FROM algorithmTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $offerID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // invalid - this shouldn't happen
                $statusCode = 521;
                $to = ADMINEMAIL;  
                $subject = 'ALGORITHM ERROR: NO RESULT FOUND FOR OFFERED USER';
                $logging = "No analytics were found for userID: ";
                $logging .= $userID;
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}
                        
            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $maxRankWorkBind2, $rankCountWorkBind2, $minRankWorkBind2, $maxRankLoveBind2, $rankCountLoveBind2, $minRankLoveBind2, $maxRankFriendBind2, $rankCountFriendBind2, $minRankFriendBind2);
            
                // fetch the results
                $stmt->fetch();

                // set variables                
                $maxRankWork2 = $maxRankWorkBind2;
                $rankCountWork2 = $rankCountWorkBind2;
                $minRankWork2 = $minRankWorkBind2;
                $maxRankLove2 = $maxRankLoveBind2;
                $rankCountLove2 = $rankCountLoveBind2;
                $minRankLove2 = $minRankLoveBind2;
                $maxRankFriend2 = $maxRankFriendBind2;
                $rankCountFriend2 = $rankCountFriendBind2;
                $minRankFriend2 = $minRankFriendBind2;
                
                // close statement
                mysqli_stmt_close($stmt);

                // set the current values based on type
                if ($bidType == "work") {
                    $currentMaxRank2 = $maxRankWork2;
                    $currentRankCount2 = $rankCountWork2;
                    $currentMinRank2 = $minRankWork2;
                } elseif ($bidType == "love") {
                    $currentMaxRank2 = $maxRankLove2;
                    $currentRankCount2 = $rankCountLove2;
                    $currentMinRank2 = $minRankLove2;
                } else {
                    $currentMaxRank2 = $maxRankFriend2;
                    $currentRankCount2 = $rankCountFriend2;
                    $currentMinRank2 = $minRankFriend2;
                }

                // validate the current values
                if (is_numeric($currentMaxRank2)) {
                    // do nothing
                } else {
                    $currentMaxRank2 = 0;
                }

                // validate the current values
                if (is_numeric($currentRankCount2)) {
                    $currentRankCount2 = (int) $currentRankCount2;
                } else {
                    $currentRankCount2 = 0;
                }

                // validate the current values
                if (is_numeric($currentMinRank2)) {
                    // do nothing
                } else {
                    $currentMinRank2 = 0;
                }                

                // check for cold start
                if ($currentRankCount2 < COLDSTART) {

                    // do not match
                    $matchFlag = "no";

                } else {

                    // calculate the rank of the offer
                    if ($accountBalance2 > 0) {
                        $rank2 = $bidVal2 / $accountBalance2;
                    } else {
                        $rank2 = 0;
                    }

                    // calculate the score
                    if ($currentMaxRank2 > 0) {
                        $score2 = $rank2 / $currentMaxRank2;
                    } else {
                        $score2 = 0;
                    }

                    // adjust the score for range
                    $range2 = $currentMaxRank2 - $currentMinRank2;
                    if ($range2 < RANGE_THRESHOLD) {
                        $score2 = $score2 * RANGE_PENALTY;
                    }

                    // check if both scores are above threshold
                    if ($score + $score2 >= TOTAL_THRESHOLD) {                        
                        // match exists
                        $statusCode = 2;
                        $matchFlag = "yes";
                    }

                    // check individual score above threshold
                    if ($score < INDSCORE_THRESHOLD) {
                        // no match
                        $statusCode = 1;
                        $matchFlag = "no";
                    }

                    // check individual score above threshold
                    if ($score2 < INDSCORE_THRESHOLD) {     
                        // no match
                        $statusCode = 1;
                        $matchFlag = "no";
                    }

                }

            }
        
        }

    }

    // if still ok and match,
    if ($statusCode == 2) {
        
        // matchTable has lower ID in spot 1 and higher ID in spot 2
        if ($userID > $offerID) {
            $spotID1 = $offerID;
            $spotID2 = $userID;
        } else {
            $spotID1 = $userID;
            $spotID2 = $offerID;
        }

        // put the bids in the appropriate slots
        if ($userID == $spotID1) {
            $bid1 = $bidVal;
            $bid2 = $bidVal2;
        } else {
            $bid1 = $bidVal2;
            $bid2 = $bidVal;
        }

        // set the status
        $status1 = "new";
        $status2 = "new";

        // set the rejectFlag
        $rejectFlag = "no";

        // set the messageFlag
        $messageFlag = "no";

        // insert the field into the table
        $sql = "INSERT INTO matchTable (spotID1, spotID2, offer1, offer2, matchType, status1, status2, rejectFlag, messageFlag, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
              
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiddssssssssss", $spotID1, $spotID2, $bid1, $bid2, $bidType, $status1, $status2, $rejectFlag, $messageFlag, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }        

    }

    // if still ok, update analytics table for offers
    if (($statusCode == 1) || ($statusCode == 2))  {

        // pull the current analytics
        $sql = "SELECT newMatchesLove, newMatchesWork, newMatchesFriend, sentOffersLove, sentOffersWork, sentOffersFriend, sentOffersLoveEOS, sentOffersWorkEOS, sentOffersFriendEOS FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
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

                // email admin, analytics should exist
                $to = ADMINEMAIL;  
                $subject = 'ANALYTICS ERROR: NO RESULT FOUND ON BID INSERT';
                $logging = "No analytics were found for userID: ";
                $logging .= $userID;
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}

            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $newMatchesLoveBind, $newMatchesWorkBind, $newMatchesFriendBind, $sentOffersLoveBind, $sentOffersWorkBind, $sentOffersFriendBind, $sentOffersLoveEOSBind, $sentOffersWorkEOSBind, $sentOffersFriendEOSBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $newMatchesLove = $newMatchesLoveBind;
                $newMatchesWork = $newMatchesWorkBind;
                $newMatchesFriend = $newMatchesFriendBind;
                $sentOffersLove = $sentOffersLoveBind;
                $sentOffersWork = $sentOffersWorkBind;
                $sentOffersFriend = $sentOffersFriendBind;
                $sentOffersLoveEOS = $sentOffersLoveEOSBind;
                $sentOffersWorkEOS = $sentOffersWorkEOSBind;
                $sentOffersFriendEOS = $sentOffersFriendEOSBind;

                // validate the variables 
                if (is_numeric($newMatchesLove)) {
                    $newMatchesLove = (int) $newMatchesLove;
                } else {
                    $newMatchesLove = 0;
                }

                // validate the variables 
                if (is_numeric($newMatchesWork)) {
                    $newMatchesWork = (int) $newMatchesWork;
                } else {
                    $newMatchesWork = 0;
                }

                // validate the variables 
                if (is_numeric($newMatchesFriend)) {
                    $newMatchesFriend = (int) $newMatchesFriend;
                } else {
                    $newMatchesFriend = 0;
                }

                // validate the variables 
                if (is_numeric($sentOffersLove)) {
                    $sentOffersLove = (int) $sentOffersLove;
                } else {
                    $sentOffersLove = 0;
                }

                // validate the variables 
                if (is_numeric($sentOffersWork)) {
                    $sentOffersWork = (int) $sentOffersWork;
                } else {
                    $sentOffersWork = 0;
                }

                // validate the variables 
                if (is_numeric($sentOffersFriend)) {
                    $sentOffersFriend = (int) $sentOffersFriend;
                } else {
                    $sentOffersFriend = 0;
                }

                // validate the variables 
                if (is_numeric($sentOffersLoveEOS)) {
                    // do nothing
                } else {
                    $sentOffersLoveEOS = 0;
                }

                // validate the variables 
                if (is_numeric($sentOffersWorkEOS)) {
                    // do nothing
                } else {
                    $sentOffersWorkEOS = 0;
                }

                // validate the variables 
                if (is_numeric($sentOffersFriendEOS)) {
                    // do nothing
                } else {
                    $sentOffersFriendEOS = 0;
                }

                // update the analytics based on bidType
                if ($bidType == "love") {
                    $sentOffersLove = $sentOffersLove + 1;
                    $sentOffersLoveEOS = $sentOffersLoveEOS + $bidVal;
                    if ($matchFlag == "yes") {
                        $newMatchesLove = $newMatchesLove + 1;
                    }
                } elseif ($bidType == "work") {
                    $sentOffersWork = $sentOffersWork + 1;
                    $sentOffersWorkEOS = $sentOffersWorkEOS + $bidVal;
                    if ($matchFlag == "yes") {
                        $newMatchesWork = $newMatchesWork + 1;
                    }
                } else {
                    $sentOffersFriend = $sentOffersFriend + 1;
                    $sentOffersFriendEOS = $sentOffersFriendEOS + $bidVal;
                    if ($matchFlag == "yes") {
                        $newMatchesFriend = $newMatchesFriend + 1;
                    }
                }

                // close statement
                mysqli_stmt_close($stmt);
                
                // update the database
                $sqlUpdate = "UPDATE analyticsTable SET newMatchesLove=?, newMatchesWork=?, newMatchesFriend=?, sentOffersLove=?, sentOffersWork=?, sentOffersFriend=?, sentOffersLoveEOS=?, sentOffersWorkEOS=?, sentOffersFriendEOS=? WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
                
                if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                            
                    // bind parameters for markers
                    mysqli_stmt_bind_param($stmt2, "iiiiiidddisss", $newMatchesLove, $newMatchesWork, $newMatchesFriend, $sentOffersLove, $sentOffersWork, $sentOffersFriend, $sentOffersLoveEOS, $sentOffersWorkEOS, $sentOffersFriendEOS, $userID, $aliveFlag, $deleteFlag, $errorFlag);

                    // execute query
                    mysqli_stmt_execute($stmt2);

                    // close the statement
                    $stmt2->close();
                    
                }

            }

        }

    }

    // if still ok, update the analytics for the offered person
    if (($statusCode == 1) || ($statusCode == 2))  {

        // pull the current analytics for offered person
        $sql = "SELECT newMatchesLove, newMatchesWork, newMatchesFriend, receivedOffersLove, receivedOffersWork, receivedOffersFriend FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $offerID, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows < 1) {

                // email admin, analytics should exist
                $to = ADMINEMAIL;  
                $subject = 'ANALYTICS ERROR: NO RESULT FOUND ON BID INSERT FOR OFFERED PERSON';
                $logging = "No analytics were found for offerID: ";
                $logging .= $offerID;
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}

            } else {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $newMatchesLoveBind2, $newMatchesWorkBind2, $newMatchesFriendBind2, $receivedOffersLoveBind2, $receivedOffersWorkBind2, $receivedOffersFriendBind2);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $newMatchesLove2 = $newMatchesLoveBind2;
                $newMatchesWork2 = $newMatchesWorkBind2;
                $newMatchesFriend2 = $newMatchesFriendBind2;
                $receivedOffersLove2 = $receivedOffersLoveBind2;
                $receivedOffersWork2 = $receivedOffersWorkBind2;
                $receivedOffersFriend2 = $receivedOffersFriendBind2;
                
                // validate the variables 
                if (is_numeric($newMatchesLove2)) {
                    $newMatchesLove2 = (int) $newMatchesLove2;
                } else {
                    $newMatchesLove2 = 0;
                }

                // validate the variables 
                if (is_numeric($newMatchesWork2)) {
                    $newMatchesWork2 = (int) $newMatchesWork2;
                } else {
                    $newMatchesWork2 = 0;
                }

                // validate the variables 
                if (is_numeric($newMatchesFriend2)) {
                    $newMatchesFriend2 = (int) $newMatchesFriend2;
                } else {
                    $newMatchesFriend2 = 0;
                }

                // validate the variables 
                if (is_numeric($receivedOffersLove2)) {
                    $receivedOffersLove2 = (int) $receivedOffersLove2;
                } else {
                    $receivedOffersLove2 = 0;
                }

                // validate the variables 
                if (is_numeric($receivedOffersWork2)) {
                    $receivedOffersWork2 = (int) $receivedOffersWork2;
                } else {
                    $receivedOffersWork2 = 0;
                }

                // validate the variables 
                if (is_numeric($receivedOffersFriend2)) {
                    $receivedOffersFriend2 = (int) $receivedOffersFriend2;
                } else {
                    $receivedOffersFriend2 = 0;
                }                

                // update the analytics based on bidType
                if ($bidType == "love") {
                    $receivedOffersLove2 = $receivedOffersLove2 + 1;                    
                    if ($matchFlag == "yes") {
                        $newMatchesLove2 = $newMatchesLove2 + 1;
                    }
                } elseif ($bidType == "work") {
                    $receivedOffersWork2 = $receivedOffersWork2 + 1;                    
                    if ($matchFlag == "yes") {
                        $newMatchesWork2 = $newMatchesWork2 + 1;
                    }
                } else {
                    $receivedOffersFriend2 = $receivedOffersFriend2 + 1;                    
                    if ($matchFlag == "yes") {
                        $newMatchesFriend2 = $newMatchesFriend2 + 1;
                    }
                }

                // close statement
                mysqli_stmt_close($stmt);
                
                // update the database
                $sqlUpdate = "UPDATE analyticsTable SET newMatchesLove=?, newMatchesWork=?, newMatchesFriend=?, receivedOffersLove=?, receivedOffersWork=?, receivedOffersFriend=? WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
                
                if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                            
                    // bind parameters for markers
                    mysqli_stmt_bind_param($stmt2, "iiiiiiisss", $newMatchesLove2, $newMatchesWork2, $newMatchesFriend2, $receivedOffersLove2, $receivedOffersWork2, $receivedOffersFriend2, $offerID, $aliveFlag, $deleteFlag, $errorFlag);

                    // execute query
                    mysqli_stmt_execute($stmt2);

                    // close the statement
                    $stmt2->close();
                    
                }

            }

        }
        
    }
                
}	
		
/*
* 1 is ok
* 2 is ok with match
* 3 is not enough eos balance
* 4 is already match
* 5 is all others
*/

// close connection
mysqli_close($mysqli);
    
$ajaxResponse = array(
	"ajaxResult" => $statusCode,	    
);	

echo json_encode($ajaxResponse);


?>