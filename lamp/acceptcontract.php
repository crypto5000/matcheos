<?php
session_start();
$sessionFlag = "no";

// check if session variable exists
if (isset($_SESSION["userKeyEncoded"])) {
    $sessionFlag = "yes";
}

include('./ajax/authorization.php');

// get the match
$matchID = $_GET["w"];

// validation - check for html special characters
$matchID = htmlspecialchars($matchID);

// validation - strip slashes
$matchID = stripslashes($matchID);

// validation - utf8 encode
$matchID = utf8_encode($matchID);

// replace escape character
$matchID = preg_replace('!\r\n?!', '\n', $matchID);

// validate that the field was submitted
if (!isset($matchID)) {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// validate that the field was submitted
if (strlen($matchID) < 1) {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// validate that the field is not an overflow
if (strlen($matchID) > 500) {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// validate that the field is a number
if (is_numeric($matchID)) {
    // convert to int
    $matchID = (int) $matchID;
} else {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// get any posted parameters, if new contract for match
if (isset($_POST["steps"])) {$contractSteps = $_POST["steps"];}
if (isset($_POST["format"])) {$contractFormat = $_POST["format"];}
if (isset($_POST["donee"])) {$contractDonee = $_POST["donee"];}
if (isset($_POST["goal"])) {$goalID = $_POST["goal"];}

// check if posted data
$newContractFlag = "no";
$invalidFlag = "no";

if ((isset($contractSteps)) && (isset($contractFormat)) && (isset($contractDonee)) && (isset($goalID))) {
    $newContractFlag = "yes";
}

// if new contract, validate the posted data
if ($newContractFlag == "yes") {

    // validation function
    function validate($message) {

        $message = htmlspecialchars($message);
        $message = stripslashes($message);
        $message = utf8_encode($message);
        $message = preg_replace('!\r\n?!', '\n', $message);
        
        return $message;
    }
    
    // validation - check for html special characters
    $contractSteps = validate($contractSteps);
    $contractFormat = validate($contractFormat);
    $contractDonee = validate($contractDonee);
    $goalID = validate($goalID);

    // validate the field - minimum of 3 steps
    if (is_numeric($contractSteps)) {
        $contractSteps = (int) $contractSteps;
        if (($contractSteps < 4) || ($contractSteps > 50)) {
            $invalidFlag = "yes";
        }
    } else {
        // invalid, redirect
        $invalidFlag = "yes";
    }

    // validate the field
    if (is_numeric($goalID)) {
        $goalID = (int) $goalID;
        if ($goalID < 0) {
            $invalidFlag = "yes";
        }
    } else {
        // invalid, redirect
        $invalidFlag = "yes";
    }

    // validate the field
    if (($contractDonee == "redcross") || ($contractDonee == "doctors")) {
        // do nothing
    } else {
        $invalidFlag = "yes";
    }
    
    // validate the field
    if (($contractFormat == "twosided") || ($contractFormat == "chat") || ($contractFormat == "interview")) {
        // do nothing
    } else {
        $invalidFlag = "yes";
    }    
 
    if ($invalidFlag == "yes") {
        // send an email to the admin         
        $to = ADMINEMAIL;  
        $subject = 'ACCEPT CONTRACT FAIL: INVALID POSTED DATA';
        $logging = "Invalid posted data for new matchID found at: ";
        $logging .= $createDate;
        $logging .= ' for matchID: '.$matchID;
        $logging .= ' for userID: '.$userID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect - not valid
        header('Location: ./index.php?v=yes');	
        exit();
    }

}

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        

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
      $subject = 'MATCH FAIL: NO DATA FOUND ON ACCEPT CONTRACT';
      $logging = "No user matchID found at: ";
      $logging .= $createDate;
      $logging .= ' for matchID: '.$matchID;
      $logging .= ' for userID: '.$userID;			      
      $header = 'From: donotrespond@matcheos.com';
      if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

      // redirect - not valid
	  header('Location: ./index.php?v=yes');	
	  exit();

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
        $status1 = $status1Bind;
        $status2 = $status2Bind;
        $rejectFlag = $rejectFlagBind;
        
        // validate the variables        
        $invalidFlag = "no";
        
        if (($spotID1 == $userID) || ($spotID2 == $userID)) {
            // do nothing
        } else {
            // invalid ids
            $invalidFlag = "yes";
        }

        if (is_numeric($offer1)) {
            // do nothing
        } else {
            // invalid offer
            $invalidFlag = "yes";
        }
    
        if (is_numeric($offer2)) {
            // do nothing
        } else {
            // invalid offer
            $invalidFlag = "yes";
        }
        
        if (($matchType == "friend") || ($matchType == "love") || ($matchType == "work")) {
            // do nothing
        } else {
            // invalid type
            $invalidFlag = "yes";
        }
        
        // verify status
        if ($spotID1 == $userID) {
            
            // check status is active for current user
            if ($status1 != "active") {
                //  error
                $invalidFlag = "yes";
            }

            // status is new or active for other user
            if ($status2 == "closed") {
                //  error
                $invalidFlag = "yes";
            }                        

        } else {

            // check status is active for current user
            if ($status2 != "active") {
                //  error
                $invalidFlag = "yes";
            }

            // status is new or active for other user
            if ($status1 == "closed") {
                //  error
                $invalidFlag = "yes";
            }
        
        }
        
        if ($rejectFlag == "no") {
            // do nothing
        } else {
            // invalid flag
            $invalidFlag = "yes";
        }

        if ($invalidFlag == "yes") {
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'MATCH FAIL: INVALID DATA FOUND ON ACCEPT CONTRACT';
            $logging = "No user matchID found at: ";
            $logging .= $createDate;
            $logging .= ' for matchID: '.$matchID;
            $logging .= ' for userID: '.$userID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

            // redirect - not valid
            header('Location: ./index.php?v=yes');	
            exit();
        }

        // set the other person's id, user's offer
        if ($spotID1 == $userID) {
            $offerID = $spotID2;
            $offer = $offer1;        
        } else {
            $offerID = $spotID1;
            $offer = $offer2;            
        }        

        // close statement
        mysqli_stmt_close($stmt);        
        
    }

}

// check there is a cap of only 1 open contract per match at a time
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

        // ok, this is new contract - should have posted data
        if ($newContractFlag != "yes") {

            // redirect to match page
            header('Location: ./match.php?v='.$loginCheck);	
            exit();
        }

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
            if ($currentOpen > 1) {

                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'ACCEPT CONTRACT FAIL: MORE THAN 1 OPEN CONTRACT FOR MATCH';
                $logging = "More than 1 contract for matchID found at: ";
                $logging .= $createDate;
                $logging .= ' for matchID: '.$matchID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // redirect - not valid
                header('Location: ./match.php?v='.$loginCheck);	
                exit();

            }
        
        }        

        // close statement
        mysqli_stmt_close($stmt);        
        
    }

}


// if there is one open contract, ignore posted data - go to open contract
if (isset($openContractID)) {

    // pull first name of other user
    $sql = "SELECT wastID, spotID1, spotID2, offer1, offer2, contractType, status1, status2, rejectFlag, contractGoal, contractFormat, contractSteps, contractRelease, contractDonee, contractArbFee, contractFee, terminationDate, terminationRelease, finishedDate FROM contractTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "isss", $openContractID, $aliveFlag, $deleteFlag, $errorFlag);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
                
        // get the number of rows
        $numRows = $stmt->num_rows;
                                
        if ($numRows < 1) {

            // invalid - should exist, redirect to match page
            header('Location: ./match.php?v='.$loginCheck);	
            exit();    

        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $wastIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $contractTypeBind, $status1Bind, $status2Bind, $rejectFlagBind, $contractGoalBind, $contractFormatBind, $contractStepsBind, $contractReleaseBind, $contractDoneeBind, $contractArbFeeBind, $contractFeeBind, $terminationDateBind, $terminationReleaseBind, $finishedDateBind);
        
            // fetch the results
            $stmt->fetch();

            // set the values
            $wastID = $wastIDBind;
            $spotID1Contract = $spotID1Bind;            
            $spotID2Contract = $spotID2Bind;
            $offer1Contract = $offer1Bind;
            $offer2Contract = $offer2Bind;
            $contractType = $contractTypeBind;
            $status1Contract = $status1Bind;
            $status2Contract = $status2Bind;
            $rejectFlagContract = $rejectFlagBind;
            $contractGoal = $contractGoalBind;
            $contractFormat = $contractFormatBind;
            $contractSteps = $contractStepsBind;
            $contractRelease = $contractReleaseBind;
            $contractDonee = $contractDoneeBind;
            $contractArbFee = $contractArbFeeBind;
            $contractFee = $contractFeeBind;
            $terminationDate = $terminationDateBind;
            $terminationRelease = $terminationReleaseBind;
            $finishedDate = $finishedDateBind;

            // validate
            if (is_numeric($wastID)) {
                $wastID = (int) $wastID;
            } else {
                // invalid id           
                $invalidFlag = "yes";
            }
                    
            if (($spotID1Contract == $userID) || ($spotID2Contract == $userID)) {
                // do nothing
            } else {
                // invalid ids
                $invalidFlag = "yes";
            }

            if (is_numeric($offer1Contract)) {
                // do nothing
            } else {
                // invalid offer
                $invalidFlag = "yes";
            }
        
            if (is_numeric($offer2Contract)) {
                // do nothing
            } else {
                // invalid offer
                $invalidFlag = "yes";
            }
            
            if (($contractType == "friend") || ($contractType == "love") || ($contractType == "work")) {
                // do nothing
            } else {
                // invalid type
                $invalidFlag = "yes";
            }
            
            if (($status1Contract == "waiting") || ($status1Contract == "open")) {
                // do nothing
            } else {
                // invalid status
                $invalidFlag = "yes";
            }

            if (($status2Contract == "waiting") || ($status2Contract == "open")) {
                // do nothing
            } else {
                // invalid status
                $invalidFlag = "yes";
            }

            if ($rejectFlagContract == "no") {
                // do nothing
            } else {
                // invalid flag
                $invalidFlag = "yes";
            }

            if (($contractFormat == "twosided") || ($contractFormat == "chat") || ($contractFormat == "interview")) {
                // do nothing
            } else {
                // invalid format
                $invalidFlag = "yes";
            }

            if (is_numeric($contractSteps)) {
                $contractSteps = (int) $contractSteps;
            } else {
                // invalid steps
                $invalidFlag = "yes";
            }

            if (is_numeric($contractRelease)) {
                $contractRelease = (int) $contractRelease;
            } else {
                // invalid release
                $invalidFlag = "yes";
            }
            
            if (($contractDonee == "redcross") || ($contractDonee== "doctors")) {
                // do nothing
            } else {
                // invalid donor
                $invalidFlag = "yes";
            }

            if (is_numeric($contractArbFee)) {
                $contractArbFee = (int) $contractArbFee;
            } else {
                // invalid fee
                $invalidFlag = "yes";
            }

            if (is_numeric($contractFee)) {
                $contractFee = (int) $contractFee;
            } else {
                // invalid fee
                $invalidFlag = "yes";
            }

            if (isset($terminationDate)) {
                // invalid - should not be terminated
                $invalidFlag = "yes";
            }

            if (isset($terminationRelease)) {
                // invalid - should not be terminated
                $invalidFlag = "yes";
            }

            if (isset($finishedDate)) {
                // invalid - should not be finished
                $invalidFlag = "yes";
            }
            
            if ($invalidFlag == "yes") {
                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'ACCEPT CONTRACT FAIL: INVALID DATA FOUND ON ACCEPT CONTRACT';
                $logging = "Invalid existing contract found at: ";
                $logging .= $createDate;
                $logging .= ' for contractID: '.$openContractID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
    
                // redirect - not match
                header('Location: ./match.php?v='.$loginCheck);	
                exit();
            }
            
            // close statement
            mysqli_stmt_close($stmt);        
            
        }

    }    
    
}

// if contract exists, validate
if (isset($openContractID)) {

    // user should have a status of waiting
    if ($spotID1Contract == $userID) {
        
        if ($status1Contract != "waiting") {
            // redirect - to contract
            header('Location: ./contract.php?v='.$loginCheck.'&w='.$matchID);	
            exit();
        }
    }

    // user should have a status of waiting
    if ($spotID2Contract == $userID) {
        
        if ($status2Contract != "waiting") {
            // redirect - to contract
            header('Location: ./contract.php?v='.$loginCheck.'&w='.$matchID);	
            exit();
        }
    }
}

// if contract exists with wastID, pull contract account name on blockchain
if (isset($wastID)) {

    // pull data
    $sql = "SELECT wastName, accountName, contractName FROM wastTable WHERE wastID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

            // invalid - should exist, redirect to match page
            header('Location: ./match.php?v='.$loginCheck);	
            exit();    

        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $wastNameBind, $accountNameBind, $contractNameBind);
        
            // fetch the results
            $stmt->fetch();

            $wastName = $wastNameBind;
            $accountName = $accountNameBind;
            $contractName = $contractNameBind;
                        
            // validate            
            if (!isset($wastName)) {
                // invalid - should be set
                $invalidFlag = "yes";
            }

            if ($wastName != $accountName) {
                // invalid - should be the same
                $invalidFlag = "yes";
            }
            
            if ($wastName != $contractName) {
                // invalid - should be the same
                $invalidFlag = "yes";
            }

            if ($invalidFlag == "yes") {
                // send an email to the admin         
                $to = ADMINEMAIL;  
                $subject = 'WAST FAIL: INVALID DATA FOUND ON ACCEPT CONTRACT';
                $logging = "Invalid wast name found at: ";
                $logging .= $createDate;
                $logging .= ' for wastID: '.$wastID;
                $logging .= ' for userID: '.$userID;			      
                $header = 'From: donotrespond@matcheos.com';
                if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

                // redirect - not match
                header('Location: ./match.php?v='.$loginCheck);	
                exit();
            }
            
            // close statement
            mysqli_stmt_close($stmt);        
            
        }

    }        
    
}

// set the matcheos fees
if (!isset($contractFee)) {
            
    // cap the matcheos fee to 1 step
    if (isset($contractRelease)) {
        $contractFee = min(MATCHEOS_FEEPER,$contractRelease);
    } else {
        $contractFee = MATCHEOS_FEEPER;
    }
    
}

// calculate the total funding for user, if no arbitration
if ($spotID1 == $userID) {
    $offerContract = $offer1;
    $offerBack = $offer1 - ($offer1 * ($contractFee/100));
} else {
    $offerContract = $offer2;
    $offerBack = $offer2 - ($offer2 * ($contractFee/100));
}

// if contract steps not set, possible page refresh
if (!isset($contractSteps)) {
    // redirect - not match
    header('Location: ./match.php?v='.$loginCheck);	
    exit();
}

// calculate the contract release
if ((!isset($contractRelease)) && ($contractSteps > 0)) {
    $contractRelease = round((1/$contractSteps)*100);
}

// set the arbitration fees
if (!isset($contractArbFee)) {
    $contractArbFee = ARBITRATION_FEEPER;
}

// set the donee display
if (($contractDonee == "redcross") || ($contractDonee == "Red Cross")) {
    $doneeDisplay = "Red Cross";
} else {
    $doneeDisplay = "Doctors without Borders";
}

// set the format
if ($contractFormat == "twosided") {
    $formatDisplay = "Two Sided Convo";
} elseif ($contractFormat == "interview") {
    $formatDisplay = "An Interview";
} else {
    $formatDisplay = "Simple Chat";
}

// if no contract and goalID was posted, pull contractGoal
if ((isset($goalID)) && (!isset($contractGoal))) {

    // pull data
    $sql = "SELECT goal FROM goalTable WHERE goalID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "isss", $goalID, $aliveFlag, $deleteFlag, $errorFlag);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
                
        // get the number of rows
        $numRows = $stmt->num_rows;
                                
        if ($numRows < 1) {

            // invalid - should exist, default to facebook friends
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

}

// pull profile names
if (isset($offerID)) {

    // pull first name of other user
    $sql = "SELECT firstName FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

            // invalid - should exist, default to nothing
            $pairName1 = "";            

        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $firstNameBind);
        
            // fetch the results
            $stmt->fetch();

            // set variables
            $pairName1 = $firstNameBind;                
            
            // close statement
            mysqli_stmt_close($stmt);        
            
        }

    }
    
    // pull firstName of user
    $sql = "SELECT firstName FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

            // invalid - should exist, default to nothing
            $pairName2 = "";            

        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $firstNameBind);
        
            // fetch the results
            $stmt->fetch();

            // set variables
            $pairName2 = $firstNameBind;                
            
            // close statement
            mysqli_stmt_close($stmt);        
            
        }

    }

}    

// if existing contract, set invalid goalID
if (!isset($goalID)) {
    $goalID = -1;
}

?>

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - Accept Contract</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">

    <!-- Custom styles for this template -->
    <link href="css/landing-page.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/portfolio-item.css" rel="stylesheet">
    <link href="css/modern-business.css" rel="stylesheet">
    <link href="css/2-col-portfolio.css" rel="stylesheet">      
    <link rel="icon" href="./img/favicon.ico" />	

    <!-- Global site tag (gtag.js) - Google Analytics -->    
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-114902364-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-114902364-1');
    </script>    
  </head>

  <body style="background-color:#007bff">

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
      <a class="navbar-brand" href="./index.php">Matcheos</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="./profile.php?v=<?=htmlspecialchars($loginCheck)?>">Browse</a>
            </li>          
            <li class="nav-item">
                <a class="nav-link" href="./match.php?v=<?=htmlspecialchars($loginCheck)?>">Matches</a>
            </li>          
            <li class="nav-item">
                <a class="nav-link" href="./analytics.php?v=<?=htmlspecialchars($loginCheck)?>">Analytics</a>
            </li>          
        </ul>
      </div>
    </nav>

    <!-- Header -->
    <header class="intro-header">
        <div class="container">        
            <div class="intro-message">                            
                <h2 style="padding-bottom:10px;margin-top:-50px">Matcheos Smart Contract</h2>                    
                <h3 style="margin-top:5px;" id="sendMessage">You will send <?=number_format(htmlspecialchars($offerContract),4)?> EOS and receive back <?=number_format(htmlspecialchars($offerBack),4)?> EOS if no arbitration.</h3>
                <div id="warningMessage" class='alert alert-error' style="display:none;color:red">WARNING: EOS TESTNET TOKENS ONLY!! DO NOT SEND ACTUAL EOS.</div>
                <h3 id="fundingMessage" style="margin-top:5px;display:none"></h3>
                <h3 id="rejectMessage" style="margin-top:5px;display:none"></h3>
                <hr class="intro-divider">          
                <div class="form-group row pGroup" id="nGroup3">                                                    
                  <div class="col-lg-4">                 
                      <input class="form-control" id="matchType" type="text" value="Type: <?=ucfirst(htmlspecialchars($matchType))?> Smart Contract" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                 
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractGoal" type="text" value="Goal: <?=ucfirst(htmlspecialchars($contractGoal))?>" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                 
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractPeople" type="text" value="People: <?=htmlspecialchars($pairName2)?> and <?=htmlspecialchars($pairName1)?>" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                                            
                </div>                                                           
                <hr class="intro-divider">          
                <div class="form-group row pGroup" id="nGroup4">                                                    
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractFormat" type="text" value="Format: <?=htmlspecialchars($formatDisplay)?>" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                          
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractSteps" type="text" value="Contract Steps: <?=htmlspecialchars($contractSteps)?>" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                 
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractRelease" type="text" value="EOS Step Release: Equal per step" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                                            
                </div>                                                           
                <hr class="intro-divider">          
                <div class="form-group row pGroup" id="nGroup5">                                                    
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractDonee" type="text" value="Arbitration Donee: <?=htmlspecialchars($doneeDisplay)?>" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                          
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractArbFee" type="text" value="Arbitration Fees: <?=htmlspecialchars($contractArbFee)?>%" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                          
                  <div class="col-lg-4">                 
                      <input class="form-control" id="contractFee" type="text" value="Matcheos Fees: <?=htmlspecialchars($contractFee)?>%" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                  </div>                                          
                </div>                                                           
                <div class="col-lg-12">                                           
                    <input type="checkbox" id="terms" name="terms" value="terms" style="display:inline"><h5 style="display:inline;margin-left:10px" id="termsText">I agree to contract Terms and Matcheos <a href="./code.html" target="_blank" style="color:white">Code</a> of Conduct.</h5><br>
                    <input type="password" name="password" id="password" placeholder="Enter Password to Confirm" style="display:none"/>
                    <button type="submit" class="btn btn-primary" id="acceptContract" style="margin-top:10px;margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Accept</h4></button>
                    <?php if ($newContractFlag == "yes") { ?>
                    <button type="submit" class="btn btn-primary" id="cancelContract" style="margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Cancel</h4></button>           
                    <?php } else { ?>                      
                    <button type="submit" class="btn btn-primary" id="rejectContract" style="margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Reject</h4></button>           
                    <?php } ?>
                    <button type="submit" class="btn btn-primary" id="fundContract" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Send EOS from Wallet</h4></button>           
                    <button type="submit" class="btn btn-primary" id="verifyContract" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Verify EOS Sent</h4></button>           
                    <button type="submit" class="btn btn-primary" id="rejectYes" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">New Contract</h4></button>           
                    <button type="submit" class="btn btn-primary" id="rejectNo" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Reject Permanently</h4></button>           
                    <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending.</div>                    
                    <div id="loading2" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending. This may take a few seconds...</div>                    
                    <div id="message" class='alert alert-info' style="display:none"></div>
                </div>                                                              
            </div>                        
        </div>
    </header>    
        
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/popper/popper.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Module JavaScript -->
    
    <!-- Page-Level Demo Scripts -->
    <script>
    $(document).ready(function() {                        

        // redirect to https if not using it
        if (location.protocol != 'https:') {
            location.href = 'https:' + window.location.href.substring(window.location.protocol.length);
        }                  

        // set the account name
        var accountName = "";

        // set the default to contract does not exist already, then check if exists
        var contractExists = "no";        
        <?php if (isset($openContractID)) { ?>
            contractExists = "yes";
            accountName = "<?=htmlspecialchars($accountName)?>";
        <?php } ?>

        // handle the cancel click
        $("#cancelContract").on( "click", function() {		
            
            // go back to selectcontract
            window.location.href = "./selectcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>";                                                                    

        });  	                                                  

        // handle the accept click
        $("#acceptContract").on( "click", function() {		

            var isTerms = $('#terms').is(":checked");

            // check terms is clicked
            if (isTerms != true) {                            
                alert("Please agree to the terms of the contract.");
                return false;
            }

            // hide the cancel button
            <?php if ($newContractFlag == "yes") { ?>
            $("#cancelContract").fadeOut("slow");
            <?php } ?>

            // if contract doesn't exist, 
            if (contractExists == "no") {
                
                // accept creates new account for contract - show funding message with verify
                createNewAccount();
            
            } else {
            
                // if contract exists - show funding message with verify (same as case1 success)              
                showFundingMessage();
            }

        });  	                                                  

        // function to show funding message (for fund/verify) and hide accept/reject
        function showFundingMessage() {

            // if contract exists - show funding message with verify (same as case1 success)              
            $( ".pGroup" ).fadeOut("slow");			
            $( "#terms" ).fadeOut("slow");			
            $( "#termsText" ).fadeOut("slow");			
            $( "#sendMessage" ).fadeOut("slow");
            $( ".intro-divider" ).fadeOut("slow");			
            $( "#warningMessage" ).fadeIn("slow");
            $( "#fundingMessage" ).text("Send testnet EOS from your wallet to Smart Contract: " + accountName + ". For the alpha version, Matcheos has access to your testnet wallet. Just click the send button below.");
            $( "#fundingMessage" ).fadeIn("slow");

            // show fund button, hide accept contract                                                        
            $( "#acceptContract" ).fadeOut("slow");			
            $( "#rejectContract" ).fadeOut("slow");			
            $( "#verifyContract" ).fadeOut("slow");			
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
            $( "#fundContract" ).fadeIn("slow");			
                
        }

        function createNewAccount() {

            // get the values
            var matchID = <?=htmlspecialchars($matchID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";
            var matchType = "<?=htmlspecialchars($matchType)?>";
            var goalID = "<?=htmlspecialchars($goalID)?>";
            var contractSteps = <?=htmlspecialchars($contractSteps)?>;
            var contractRelease = <?=htmlspecialchars($contractRelease)?>;
            var contractFormat = "<?=htmlspecialchars($contractFormat)?>";
            var contractDonee = "<?=htmlspecialchars($contractDonee)?>";
            var contractArbFee = <?=htmlspecialchars($contractArbFee)?>;
            var contractFee = <?=htmlspecialchars($contractFee)?>;
            
            // validate the values 
            if (matchID == null || matchID === undefined) {
                alert('There is a problem with this match. Try again later.');
                return false;
            }

            if (!token) {
                alert('There is a problem with this match. Try again later.');
                return false;
            }

            if (!matchType) {
                alert('There is a problem with this match. Try again later.');
                return false;
            }

            if ((matchType == "friend") || (matchType == "work") || (matchType == "love")) {
                // do nothing
            } else {
                alert('There is a problem with this match. Try again later.');
                return false;
            }

            if (goalID == null || goalID === undefined) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if (contractSteps == null || contractSteps === undefined) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if ((contractSteps < 4) || (contractSteps > 100)) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if (contractRelease == null || contractRelease === undefined) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if ((contractRelease < 1) || (contractRelease > 100)) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if (!contractFormat) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if ((contractFormat == "twosided") || (contractFormat == "chat") || (contractFormat == "interview")) {
                // do nothing
            } else {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if (!contractDonee) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if ((contractDonee == "redcross") || (contractDonee == "doctors")) {
                // do nothing
            } else {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if (contractArbFee == null || contractArbFee === undefined) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if ((contractArbFee < 0) || (contractArbFee > 100)) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if (contractFee == null || contractFee === undefined) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            if ((contractFee < 0) || (contractFee > 100)) {
                alert('There is a problem with this contract. Try again later.');
                return false;
            }

            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                 
            // set the post url
            var url = "./ajax/createaccount.php";
                                    
            // validate the token exists
            if (token) {

                // hide the button
                $( "#acceptContract" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                
                // Send the data using post 
                var posting = $.post( url, { field1: matchID, field2: token, field3: matchType, field4: goalID, field5: contractSteps, field6: contractRelease, field7: contractFormat, field8: contractDonee, field9: contractArbFee, field10: contractFee} );

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    accountName = jsonData.accountName;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		
                    $( "#acceptContract" ).fadeIn("slow");			                    

                    // if ok, validate accountName exists
                    if (!accountName)  {
                        ajaxResult = 10;
                    } 

                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:                                                
                            // set funding message with instructions, hide accept contract                                
                            showFundingMessage();
                            break;                        
                        default:
                            $( "#message" ).fadeIn("slow");
                            $( '#message' ).text("There was a problem accepting the contract. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#acceptContract" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			

            } else {
            
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem accepting the contract. Please try later.");                                                                    
            }
            
            return false;

        }

        // handle the fund contract click (will be deleted in final production)
        $("#fundContract").on( "click", function() {		            

            // get the values
            var matchID = <?=htmlspecialchars($matchID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                    
            var password = $("#password").val();

            // validate the match
            if (matchID == null || matchID === undefined) {
                alert('There is a problem with this contract. Try again later.')
                return false;
            }

            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit send again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                 
            // set the post url
            var url = "./ajax/fundaccount.php";

            // validate the accountName exists
            if (accountName) {

                // hide the button
                $( "#fundContract" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading2" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: matchID, field2: token, field3: accountName, field4: password} );

                <?php } else { ?>
                var posting = $.post( url, { field1: matchID, field2: token, field3: accountName} );
                <?php }  ?>
      
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                        
                    var contractID = jsonData.contractID;		                                        
                    ajaxResult = Number(ajaxResult);
                    contractID = Number(contractID);

                    // if ok, 
                    if (ajaxResult == 1) {

                        // wait 3 seconds to allow blockchain confirmaation
                        setTimeout(function(){ 
                            // go to viewsteps
                            window.location.href = "./viewsteps.php?v=<?=htmlspecialchars($loginCheck)?>&w=" + contractID; 
                        3000});

                    } else {

                        $( "#loading2" ).fadeOut("slow");		
                        $( "#fundContract" ).fadeIn("slow");			                    
                        
                        // If errors, 
                        switch(ajaxResult) {
                            case 1:                                                
                                // go to viewsteps
                                window.location.href = "./viewsteps.php?v=<?=htmlspecialchars($loginCheck)?>&w=" + contractID;                                                                    
                                break;                        
                            case 2:                                                
                                // wallet did not recieve full amount
                                $( "#message" ).fadeIn("slow");
                                $( '#message' ).text("The EOS could not be verified in the blockchain. Please try later.");                                                    
                                break;                        
                            default:
                                $( "#message" ).fadeIn("slow");
                                $( '#message' ).text("There was a problem sending the EOS. Please try later.");                                                                        
                        }							

                    }
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading2" ).fadeOut("slow");
                    $( "#fundContract" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem sending the EOS. Please try later.");                                                                    
            }
            
            return false;

        });  	                                                                          
       
        // handle the reject click
        $("#rejectContract").on( "click", function() {		

            // if contract doesn't exist,
            if (contractExists == "no") {
                      
                // reject does not create contract - just goes back to match.php
                window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                    
            
            } else {
            
                // contract exists - reject has option to counteroffer new contract
                showRejectMessage();
            }

        });  	                                                  


        // function to show reject counteroffer message and hide accept/reject
        function showRejectMessage() {
            
            // if contract exists - show funding message with verify (same as case1 success)              
            $( ".pGroup" ).fadeOut("slow");			
            $( "#terms" ).fadeOut("slow");			
            $( "#termsText" ).fadeOut("slow");			
            $( "#sendMessage" ).fadeOut("slow");
            $( ".intro-divider" ).fadeOut("slow");			            
            $( "#rejectMessage" ).text("Do you want to enter into a different contract with this match? Or reject permanently?");
            $( "#rejectMessage" ).fadeIn("slow");

            // show fund button, hide accept contract                                                        
            $( "#acceptContract" ).fadeOut("slow");			
            $( "#rejectContract" ).fadeOut("slow");			
            $( "#verifyContract" ).fadeOut("slow");			
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
            $( "#rejectYes" ).fadeIn("slow");			
            $( "#rejectNo" ).fadeIn("slow");			
                
        }

        // handle the contractID, for reject buttons (may not be set for non-reject buttons, if no contract exists)
        var rejectContractID = "";
        <?php
        // if set
        if (isset($openContractID)) {            
        ?>
            rejectContractID = <?=htmlspecialchars($openContractID)?>;            
        <?php
        }
        ?>

        // handle the reject yes click
        $("#rejectYes").on( "click", function() {		
                        
            // get the values
            var matchID = <?=htmlspecialchars($matchID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";            
            var password = $("#password").val();
            var rejectFlag = "yes";
            
            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                 
            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit send again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // set the post url
            var url = "./ajax/rejectcontract.php";
                                    
            // validate token
            if (token) {

                // hide the button
                $( "#rejectNo" ).fadeOut("slow");			
                $( "#rejectYes" ).fadeOut("slow");			                
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: matchID, field2: token, field3: rejectContractID, field4: rejectFlag, field5: password} );
                <?php } else { ?>
                var posting = $.post( url, { field1: matchID, field2: token, field3: rejectContractID, field4: rejectFlag} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		                    
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // go to select new contract
                            window.location.href = "./selectcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>";                                                                    
                            break;                        
                        default:
                            $( "#rejectYes" ).fadeIn("slow");			                    
                            $( "#message" ).fadeIn("slow");
                            $( '#message' ).text("There was a problem with the submission. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#rejectYes" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem with the submission. Please try later.");                                                                    
            }
            
            return false;

        });  	                                                  

        // handle the reject no click
        $("#rejectNo").on( "click", function() {		                        

            // get the values
            var matchID = <?=htmlspecialchars($matchID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";            
            var password = $("#password").val();
            var rejectFlag = "no";
            
            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                 
            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit send again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // set the post url
            var url = "./ajax/rejectcontract.php";

            // validate token
            if (token) {

                // hide the button
                $( "#rejectNo" ).fadeOut("slow");			
                $( "#rejectYes" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: matchID, field2: token, field3: rejectContractID, field4: rejectFlag, field5: password} );
                <?php } else { ?>
                var posting = $.post( url, { field1: matchID, field2: token, field3: rejectContractID, field4: rejectFlag} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		                    
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // go to match page
                            window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                    
                            break;                        
                        default:
                            $( "#rejectNo" ).fadeIn("slow");			                    
                            $( "#message" ).fadeIn("slow");
                            $( '#message' ).text("There was a problem with the submission. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#rejectNo" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {

                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem with the submission. Please try later.");                                                                    
            }
            
            return false;

        });  	                                                  

    });	

    </script>
	
  </body>
  
</html>
<?php
	
mysqli_close($mysqli);

?>