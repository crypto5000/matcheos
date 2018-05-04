<?php

// include parameters

include('./include/domain.php');

// set the ip
$ipAddress = $_SERVER['REMOTE_ADDR'];

// set the date

$createDate = date("Y-m-d H:i:s"); 

// check that token exists and is not greater than 6 hours from lastLogin

$loginCheck = $_GET["v"];

// validation - check for html special characters

$loginCheck = htmlspecialchars($loginCheck);

// validation - strip slashes

$loginCheck = stripslashes($loginCheck);

// validation - utf8 encode

$loginCheck = utf8_encode($loginCheck);

// replace escape character

$loginCheck = preg_replace('!\r\n?!', '\n', $loginCheck);

// validate that the field was submitted

if (!isset($loginCheck)) {
	// redirect - not valid
	header('Location: ../index.php?v=yes');	
	exit();
}

// validate that the field was submitted

if (strlen($loginCheck) < 1) {
	// redirect - not valid
	header('Location: ../index.php?v=yes');	
	exit();
}

// validate that the field is not an overflow

if (strlen($loginCheck) > 500) {
	// redirect - not valid
	header('Location: ../index.php?v=yes');	
	exit();
}

// include db parameters

include('./include/config.php');

// connect to database

$mysqli = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME, DBPORT);

// error message if connection failed

if (mysqli_connect_errno()) {
		printf("Connect failed: %s\n", mysqli_connect_error());		
		exit();
} 
 
// use prepared statement for extra security
			
$sql = "SELECT userID, email, userName, lastLogin, birthYear, langID FROM userTable WHERE token=?";
			
if ($stmt = mysqli_prepare($mysqli, $sql)) {
						
	// bind parameters for markers
	mysqli_stmt_bind_param($stmt, "s", $loginCheck);

	// execute query
	mysqli_stmt_execute($stmt);

	// store result to get num rows				
	$stmt->store_result();
				
	// get the number of rows
	$numRows = $stmt->num_rows;
								
	if ($numRows < 1) {
		
		mysqli_close($mysqli);
		
		// redirect - not valid
		header('Location: ../index.php?v=yes');	
		exit();
					
	} else {
			
		// bind result variables
		mysqli_stmt_bind_result($stmt, $userIDBind, $emailBind, $userNameBind, $loginBind, $birthYearBind, $langIDBind);
																																								
		// fetch the results
		$stmt->fetch();
						
		// set variables
		$userID = $userIDBind;		
		$email = $emailBind;
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
		
			mysqli_close($mysqli);
			// redirect - not valid
			header('Location: ../index.php?v=yes');	
			exit();
		
		}
		
    }
    
}
			    
?>