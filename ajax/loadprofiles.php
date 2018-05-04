<?php

// set default status code
$statusCode = 0;

// set the arrays
$userIDArray = array();
$firstNameArray = array();
$location1Array = array();
$location2Array = array();
$taglineArray = array();
$imageArray = array();
$whyMeetArray = array();

// set up the default return values
$pullCount = 0;
$userIDArrayFinal = [];
$firstNameArrayFinal = [];
$location1ArrayFinal = [];
$location2ArrayFinal = [];
$taglineArrayFinal = [];
$imageArrayFinal = [];
$whyMeetArrayFinal = [];

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// pull the data
$token = $_POST["field1"];
$offset = $_POST["field2"];

// validation - check for html special characters
$token = validate($token);
$offset = validate($offset);

// validation function
function validate($message) {

	$message = htmlspecialchars($message);
	$message = stripslashes($message);
	$message = utf8_encode($message);
	$message = preg_replace('!\r\n?!', '\n', $message);
	
	return $message;
}

// validate that the field was submitted
if (!isset($token)) {  
    $statusCode = 2;	
}

// validate the field length
if (strlen($token) < 1) {  
    $statusCode = 2;	
} 

// validate the field length
if (strlen($token) > 1000) {  
    $statusCode = 2;	
} 

// validate that the field was submitted
if (!isset($offset)) {  
    $statusCode = 3;	
}

// validate the field length
if (strlen($offset) < 1) {  
    $statusCode = 3;	
} 

// validate the field length
if (strlen($offset) > 1000) {  
    $statusCode = 3;	
} 

// validate the field
if (is_numeric($offset)) {
    $offset = (int) $offset;
    if ($offset < 0) {
        $statusCode = 3;	    
    }
} else {
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
    $form = "choosephoto";
    
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
			$logging = "There was a hack attempt on loadprofiles at: ";
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
                $statusCode = 10;

            }            

        }

    }

    // if still ok,
    if ($statusCode == 0) {

        // set the totalProfileCount
        $totalProfileCount = 0;

        // get the total number of profiles
        $sql = "SELECT userID FROM profileTable WHERE aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "sss", $aliveFlag, $deleteFlag, $errorFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();

            // get the number of rows
            $numRows = $stmt->num_rows;

            // update the profile count
            $totalProfileCount = $numRows;

            if ($numRows < 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'PROFILE LOAD FAIL: NO DATA FOUND';
                $logging = "No profiles found at: ";
                $logging .= $createDate;
                $logging .= ' for userID: '.$userID;
                $logging .= ' for ipAddress: '.$ipAddress;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 20;
                
            } 
                                                             
        }
        
    }
    
    // if still ok, and there is a pool of profiles
    if (($statusCode == 0) && ($totalProfileCount > 0)) {
                    
        // exclude the current user from the pool
        $availableProfiles = $totalProfileCount - 1;

        // check if offset would overflow the pool
        if (($offset + 10) >= $availableProfiles) {

            // generate a valid random integer offest
            $offset = rand(0,($availableProfiles - 10));            
        }        
        
        // validate offset
        if (($offset > $availableProfiles) || ($offset < 0)) {
            $offset = 0;
        }
        
        // set the pull count
        $pullCount = 0;
        
        // pull the next batch of users to display (chunk of 10 users, with offset, ordered by the latest created, not current user)        
        $sql = "SELECT userID, firstName, location1, location2, tagline, image, whyMeet FROM profileTable WHERE userID <> ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ? ORDER BY createDate DESC LIMIT ".$offset.", 10";

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
                $subject = 'PROFILE LOAD FAIL: NO DATA FOUND';
                $logging = "No profiles found at: ";
                $logging .= $createDate;
                $logging .= ' for userID: '.$userID;
                $logging .= ' for ipAddress: '.$ipAddress;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // not valid
                $statusCode = 20;
                
            } else {

                // set the values
                mysqli_stmt_bind_result($stmt, $userIDBind, $firstNameBind, $location1Bind, $location2Bind, $taglineBind, $imageBind, $whyMeetBind);
                
                // cycle through and get the options
                while ($stmt->fetch()) {

                    // validate the image url
                    $startPath = "https://matcheos.com/img/profiles/";
                    if (substr($imageBind, 0, strlen($startPath)) === $startPath) {                        
                        $imageArray[$pullCount] = htmlspecialchars($imageBind);    
                    } else {
                        // not valid format - set generic image
                        $imageArray[$pullCount] = "https://matcheos.com/img/alice4.jpg";
                    }

                    // validate and bind the text displayed
                    $userIDArray[$pullCount] = htmlspecialchars($userIDBind);
                    $firstNameArray[$pullCount] = htmlspecialchars($firstNameBind);
                    $location1Array[$pullCount] = htmlspecialchars($location1Bind);
                    $location2Array[$pullCount] = htmlspecialchars($location2Bind);
                    $taglineArray[$pullCount] = htmlspecialchars($taglineBind);      
                    $whyMeetArray[$pullCount] = htmlspecialchars($whyMeetBind);
                    $pullCount++;      

                    // update the profile views of the batch      
                    $sql2 = "SELECT profileViews FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

                    if ($stmt2 = mysqli_prepare($mysqli, $sql2)) {
                                
                        // bind parameters for markers
                        mysqli_stmt_bind_param($stmt2, "isss", $userIDBind, $aliveFlag, $deleteFlag, $errorFlag);

                        // execute query
                        mysqli_stmt_execute($stmt2);

                        // store result to get num rows				
                        $stmt2->store_result();

                        // get the number of rows
                        $numRows2 = $stmt2->num_rows;

                        if ($numRows2 < 1) {

                        // do nothing

                        } else {

                            // bind result variables
                            mysqli_stmt_bind_result($stmt2, $profileViewsBind2);							
                            
                            // fetch the results
                            $stmt2->fetch();
                                            
                            // set variables
                            $profileViews2 = $profileViewsBind2;

                            // increment the profileViews
                            if (is_numeric($profileViews2)) {                
                            $profileViews2 = (int) $profileViews2;            
                            $profileViews2++;
                            } else {
                            $profileViews2 = 0;
                            }
                            
                            // update the profile views            
                            $sqlUpdate = "UPDATE analyticsTable SET profileViews=? WHERE userID=? AND aliveFlag=?";
                            
                            if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                                        
                                // bind parameters for markers
                                mysqli_stmt_bind_param($stmt2, "iis", $profileViews2, $userIDBind, $aliveFlag);

                                // execute query
                                mysqli_stmt_execute($stmt2);

                                // close the statement
                                $stmt2->close();
                                
                            }
                            
                        }

                    }
        
                }

                // close statement
                mysqli_stmt_close($stmt);

            }
            
        }

        // shuffle the order of the profiles
        $j = 0;
        while ($j < $pullCount) {
            $copyArray[$j] = $j;
            $j++;
        }
        shuffle($copyArray);

        // set up return array using the shuffle
        $newCount = 0;
        while ($newCount < $pullCount) {
            $userIDArrayFinal[$newCount] = $userIDArray[$copyArray[$newCount]];
            $firstNameArrayFinal[$newCount] = $firstNameArray[$copyArray[$newCount]];
            $location1ArrayFinal[$newCount] = $location1Array[$copyArray[$newCount]];
            $location2ArrayFinal[$newCount] = $location2Array[$copyArray[$newCount]];
            $taglineArrayFinal[$newCount] = $taglineArray[$copyArray[$newCount]];
            $imageArrayFinal[$newCount] = $imageArray[$copyArray[$newCount]];
            $whyMeetArrayFinal[$newCount] = $whyMeetArray[$copyArray[$newCount]];
            $newCount++;            
        }

        // set to ok
        if ($statusCode == 0) {
            $statusCode = 1;
        }

    }

}	
		
/*
* 1 is ok
* 2 is all others
*/

// close connection
mysqli_close($mysqli);
    
$ajaxResponse = array(
    "ajaxResult" => $statusCode,
    "profileCount" => $pullCount,	    
    "userID" => $userIDArrayFinal,	    
    "firstName" => $firstNameArrayFinal,	    
    "location1" => $location1ArrayFinal,	    
    "location2" => $location2ArrayFinal,	    
    "tagline" => $taglineArrayFinal,	    
    "image" => $imageArrayFinal,	    
    "whyMeet" => $whyMeetArrayFinal,	    
);	

echo json_encode($ajaxResponse);


?>