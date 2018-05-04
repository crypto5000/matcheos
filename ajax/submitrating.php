<?php

// set default status code
$statusCode = 0;

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// pull the data
$contractID = $_POST["field1"];
$token = $_POST["field2"];
$rating = $_POST["field3"];
$ratingType = $_POST["field4"];

// validation - check for html special characters
$contractID = validate($contractID);
$token = validate($token);
$rating = validate($rating);
$ratingType = validate($ratingType);

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
if (!isset($rating)) {  
    $statusCode = 4;	
}

// validate the field length
if (strlen($rating) < 1) {  
    $statusCode = 4;	
} 

// validate the field length
if (strlen($rating) > 200) {  
    $statusCode = 4;	
} 

// validate the field is a number
if (is_numeric($rating)) {  
    $rating = (int) $rating;
    if (($rating < 1) || ($rating > 5)) {
        $statusCode = 5;	    
    }
} else {
    $statusCode = 5;	
}

// validate that the field was submitted
if (!isset($ratingType)) {  
    $statusCode = 5;	
}

// validate the field length
if (strlen($ratingType) < 1) {  
    $statusCode = 5;	
} 

// validate the field length
if (strlen($ratingType) > 200) {  
    $statusCode = 5;	
} 

// validate the field 
if (($ratingType == "during") || ($ratingType == "after")) {
    // do nothing
} else {
    $statusCode = 5;	
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
    $form = "submitrating";
    
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
			$logging = "There was a hack attempt on submitrating at: ";
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

        // verify contract exists for the user, pull data
        $sql = "SELECT matchID, spotID1, spotID2, contractType FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
                $subject = 'RATING SUBMISSION FAIL: NO DATA FOUND ON PAGE';
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
                mysqli_stmt_bind_result($stmt, $matchIDBind, $spotID1Bind, $spotID2Bind, $contractTypeBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $matchID = $matchIDBind;
                $spotID1 = $spotID1Bind;
                $spotID2 = $spotID2Bind;
                $matchType = $contractTypeBind;
                
                // validate the variables
                $invalidFlag = "no";

                if (($spotID1 == $userID) || ($spotID2 == $userID)) {
                    // do nothing
                } else {
                    // invalid ids
                    $invalidFlag = "yes";
                }

                if (($matchType == "work") || ($matchType == "friend") || ($matchType == "love")) {
                    // do nothing
                } else {
                    // invalid type
                    $invalidFlag = "yes";
                }
                
                // close statement
                mysqli_stmt_close($stmt);        
                
            }

        }

        // check if invalid
        if ($invalidFlag == "yes") {
            $to = ADMINEMAIL;  
            $subject = 'RATING FAIL: INVALID DATA FOUND ON PAGE';
            $logging = "Rating error because of invalid data at: ";
            $logging .= $createDate;
            $logging .= ' for contractID: '.$contractID;
            $logging .= ' for userID: '.$userID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

            // not valid
            $statusCode = 13;
            
        } 

    }    

    // if still ok,
    if ($statusCode == 0) {

        // set the other user ID
        $userIDRated = $spotID2;
        if ($spotID1 == $userID) {
            $userIDRated = $spotID2;
        } else {
            $userIDRated = $spotID1;
        }

        // check if the recent rating exists
        $sql = "SELECT ratingID, createDate FROM ratingTable WHERE userIDRated = ? AND userIDRating = ? AND ratingType = ? AND matchType = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
            
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iisssss", $userIDRated, $userID, $ratingType, $matchType, $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            if ($numRows > 0) {
                
                // bind result variables
                mysqli_stmt_bind_result($stmt, $ratingIDBind, $createDateBind);
            
                // fetch the results
                $stmt->fetch();

                // set variables
                $ratingID = $ratingIDBind;		
                $ratingDate = $createDateBind;		

                // close statement
                mysqli_stmt_close($stmt);

                // check if bid was over x days ago
                $plusDays = " + 1 days";
                $failBid = $ratingDate.$plusDays;			
                $failBid = date("Y-m-d H:i:s",strtotime($failBid));			    			                                
                
                // check if the bid has occured within the last timeframe
                if (strtotime($createDate) < strtotime($failBid)) {

                    // do not allow recent updates to bids
                    $statusCode = 2;

                }                 

            }

        }
    }

    // if still ok
    if ($statusCode == 0) {

        // set the flag for updating old ratings
        $aliveFlagUpdate = "no";
        $deleteFlagUpdate = "yes";

        // delete any previous ratings for this ratingType - latest review becomes current        
        $sqlUpdate = "UPDATE ratingTable SET aliveFlag=?, deleteFlag=? WHERE userIDRated=? AND userIDRating=? AND ratingType=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "ssiissss", $aliveFlagUpdate, $deleteFlagUpdate, $userIDRated, $userID, $ratingType, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }

        // insert the new rating into the table
        $sql = "INSERT INTO ratingTable (userIDRated, userIDRating, rating, ratingType, matchType, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "iiisssssss", $userIDRated, $userID, $rating, $ratingType, $matchType, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
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