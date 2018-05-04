<?php

// set default status code
$statusCode = 0;

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// pull the data
$matchID = $_POST["field1"];
$token = $_POST["field2"];

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
    $form = "acceptmatch";
    
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
			$logging = "There was a hack attempt on acceptmatch at: ";
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
            $statusCode = 10;
                    
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
                $statusCode = 11;

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
                $subject = 'ACCEPT MATCH FAIL: NO DATA FOUND ON PAGE';
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
                $subject = 'ACCEPT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID1;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 18;
                
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
                $subject = 'ACCEPT MATCH FAIL: NO DATA FOUND ON PAGE';
                $logging = "No analytics data found at: ";
                $logging .= $createDate;                
                $logging .= ' for userID: '.$spotID2;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 19;
                
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

        // determine if spot 1 is current person or other
        if ($spotID1 == $userID) {

            // current person with have 1 less new, 1 more active (other person has the same, still active)
            if ($matchType == "love") {
                $newMatchesLove1 = $newMatchesLove1 - 1;                
                $activeMatchesLove1 = $activeMatchesLove1 + 1;                
            } elseif ($matchType == "work") {
                $newMatchesWork1 = $newMatchesWork1 - 1;                
                $activeMatchesWork1 = $activeMatchesWork1 + 1;                
            } else {
                $newMatchesFriend1 = $newMatchesFriend1 - 1;                
                $activeMatchesFriend1 = $activeMatchesFriend1 + 1;                
            }            

        } else {

            // current person with have 1 less new, 1 more active (other person has the same, still active)
            if ($matchType == "love") {
                $newMatchesLove2 = $newMatchesLove2 - 1;                
                $activeMatchesLove2 = $activeMatchesLove2 + 1;                
            } elseif ($matchType == "work") {
                $newMatchesWork2 = $newMatchesWork2 - 1;                
                $activeMatchesWork2 = $activeMatchesWork2 + 1;                
            } else {
                $newMatchesFriend2 = $newMatchesFriend2 - 1;                
                $activeMatchesFriend2 = $activeMatchesFriend2 + 1;                
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

        // set the status to active
        $status = "active";                
        
        // determine the spot for the user in contract
        if ($spotID1 == $userID) {

            // update the status1
            $sqlUpdate = "UPDATE matchTable SET status1=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";

        } else {
            
            // update the status2
            $sqlUpdate = "UPDATE matchTable SET status2=? WHERE matchID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        }

        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "sisss", $status, $matchID, $aliveFlag, $errorFlag, $deleteFlag);

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