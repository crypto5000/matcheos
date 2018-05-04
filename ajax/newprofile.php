<?php

// set default status code
$statusCode = 0;

// set the current date
$createDate = date("Y-m-d H:i:s"); 

// pull the data
$firstName = $_POST["field1"];
$location1 = $_POST["field2"];
$location2 = $_POST["field3"];
$tagline = $_POST["field4"];
$whyMeet = $_POST["field5"];
$token = $_POST["field6"];

// validation - check for html special characters
$firstName = validate($firstName);
$location1 = validate($location1);
$location2 = validate($location2);
$tagline = validate($tagline);
$whyMeet = validate($whyMeet);
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
if (!isset($firstName)) {  
    $statusCode = 2;	
}

// validate the field length
if (strlen($firstName) < 1) {  
    $statusCode = 2;	
} 

// validate the field length
if (strlen($firstName) > 100) {  
    $statusCode = 2;	
} 

// validate that the field was submitted
if (!isset($location1)) {  
    $statusCode = 3;	
}

// validate the field length
if (strlen($location1) < 1) {  
    $statusCode = 3;	
} 

// validate the field length
if (strlen($location1) > 200) {  
    $statusCode = 3;	
} 

// validate that the field was submitted
if (!isset($location2)) {  
    $statusCode = 4;	
}

// validate the field length
if (strlen($location2) < 1) {  
    $statusCode = 4;	
} 

// validate the field length
if (strlen($location2) > 200) {  
    $statusCode = 4;	
} 

// validate that the field was submitted
if (!isset($tagline)) {  
    $statusCode = 5;	
}

// validate the field length
if (strlen($tagline) < 3) {  
    $statusCode = 5;	
} 

// validate the field length
if (strlen($tagline) > 300) {  
    $statusCode = 5;	
} 

// validate that the field was submitted
if (!isset($whyMeet)) {  
    $statusCode = 6;	
}

// validate the field length
if (strlen($whyMeet) < 1) {  
    $statusCode = 6;	
} 

// validate the field length
if (strlen($whyMeet) > 400) {  
    $statusCode = 6;	
} 

// validate that the field was submitted
if (!isset($token)) {  
    $statusCode = 9;	
}

// validate the field length
if (strlen($token) < 1) {  
    $statusCode = 9;	
} 

// validate the field length
if (strlen($token) > 1000) {  
    $statusCode = 9;	
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
    $form = "newprofile";
    
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
			$logging = "There was a hack attempt on newprofile at: ";
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
    
    // if still ok
    if ($statusCode == 0) {

        $maxImages = 0;

        // get the number of images        
        $sql = "SELECT image FROM imageTable WHERE aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "sss", $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt);

            // store result to get num rows				
            $stmt->store_result();
                    
            // get the number of rows
            $numRows = $stmt->num_rows;
                                    
            $maxImages = $numRows;

        }

        // choose a random image number 
        if ($maxImages > 1) {
            $randomID = rand(1,$maxImages);
        } else {            
            $randomID = 1;
        }

        // pull a random profile image    
        $sql = "SELECT image FROM imageTable WHERE imageID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                        
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isss", $randomID, $aliveFlag, $errorFlag, $deleteFlag);

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
                mysqli_stmt_bind_result($stmt, $imageBind);
                                                                                                                                                                        
                // fetch the results
                $stmt->fetch();
                                
                // set variables
                $image = $imageBind;		

                // validate the image
                $startPath = "https://matcheos.com/img/profiles/";
                if (substr($imageBind, 0, strlen($startPath)) === $startPath) {      
                    // do nothing
                } else {
                    // invalid
                    $image = "https://matcheos.com/img/alice4.jpg";
                }

                // close statement
                mysqli_stmt_close($stmt);

            }

        }

    }    

    // if still ok
    if ($statusCode == 0) {
             
        // set new update flags
        $aliveFlagUpdate = "no";
        $deleteFlagUpdate = "yes";

        // delete any profiles that may exist - if the user went back and resubmitted
        $sqlUpdate = "UPDATE profileTable SET aliveFlag=?, deleteFlag=? WHERE userID=? AND aliveFlag=? AND errorFlag=? AND deleteFlag=?";
        
        if ($stmt2 = mysqli_prepare($mysqli, $sqlUpdate)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt2, "ssisss", $aliveFlagUpdate, $deleteFlagUpdate, $userID, $aliveFlag, $errorFlag, $deleteFlag);

            // execute query
            mysqli_stmt_execute($stmt2);

            // close the statement
            $stmt2->close();
            
        }        

        // insert new profile         
        $sql = "INSERT INTO profileTable (userID, firstName, location1, location2, tagline, image, whyMeet, aliveFlag, errorFlag, deleteFlag, ipAddress, createDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
            // bind parameters for markers
            mysqli_stmt_bind_param($stmt, "isssssssssss", $userID, $firstName, $location1, $location2, $tagline, $image, $whyMeet, $aliveFlag, $errorFlag, $deleteFlag, $ipAddress, $createDate);

            // execute query
            mysqli_stmt_execute($stmt);

            // close the statement
            $stmt->close();
            
        }

        // set to ok
        $statusCode = 1;
    }    
    
}	
		
/*
* 1 is ok
* 2 is invalid first name
* 3 is invalid location1
* 4 is invalid location2
* 5 is invalid tagline
* 6 is invalid whyMeet
* 9 is invalid token
* 10 is all others
*/

// close connection
mysqli_close($mysqli);
    
$ajaxResponse = array(
	"ajaxResult" => $statusCode,	    
);	

echo json_encode($ajaxResponse);


?>