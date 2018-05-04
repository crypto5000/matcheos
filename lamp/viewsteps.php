<?php
session_start();

require_once('./crypto/defuse-crypto.phar');

// set up classes
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;    

$sessionFlag = "no";
$lastStep = "no";

// check if session variable exists
if (isset($_SESSION["userKeyEncoded"])) {
    $sessionFlag = "yes";
}

include('./ajax/authorization.php');

// get the contract
$contractID = $_GET["w"];

// validation - check for html special characters
$contractID = htmlspecialchars($contractID);

// validation - strip slashes
$contractID = stripslashes($contractID);

// validation - utf8 encode
$contractID = utf8_encode($contractID);

// replace escape character
$contractID = preg_replace('!\r\n?!', '\n', $contractID);

// validate that the field was submitted
if (!isset($contractID)) {
	// redirect - not valid
	header('Location: ./index.php?v=yes1');	
	exit();
}

// validate that the field was submitted
if (strlen($contractID) < 1) {
	// redirect - not valid
	header('Location: ./index.php?v=yes2');	
	exit();
}

// validate that the field is not an overflow
if (strlen($contractID) > 500) {
	// redirect - not valid
	header('Location: ./index.php?v=yes3');	
	exit();
}

// validate that the field is a number
if (is_numeric($contractID)) {
    // convert to int
    $contractID = (int) $contractID;
} else {
	// redirect - not valid
	header('Location: ./index.php?v=yes4');	
	exit();
}

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        

// verify the contract exists - pull data
$sql = "SELECT wastID, matchID, spotID1, spotID2, offer1, offer2, status1, status2, rejectFlag, contractType, contractGoal, goalID, contractFormat, contractSteps, contractDonee, contractArbFee, contractFee, terminationDate, terminationRelease, terminatedID, finishedDate FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
        $subject = 'CONTRACT FAIL: NO CONTRACT FOUND ON VIEWSTEPS';
        $logging = "There was no data found for contractID at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect to match page
        header('Location: ./match.php?v='.$loginCheck);	
        exit();        

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $wastIDBind, $matchIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $status1Bind, $status2Bind, $rejectFlagBind, $contractTypeBind, $contractGoalBind, $goalIDBind, $contractFormatBind, $contractStepsBind, $contractDoneeBind, $contractArbFeeBind, $contractFeeBind, $terminationDateBind, $terminationReleaseBind, $terminatedIDBind, $finishedDateBind);                    

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
        $contractGoal = $contractGoalBind;
        $goalID = $goalIDBind;
        $contractFormat = $contractFormatBind;
        $contractSteps = $contractStepsBind;
        $contractDonee = $contractDoneeBind;
        $contractArbFee = $contractArbFeeBind;
        $contractFee = $contractFeeBind;
        $terminationDate = $terminationDateBind;
        $terminationRelease = $terminationReleaseBind;
        $terminatedID = $terminatedIDBind;
        $finishedDate = $finishedDateBind;
        
        // validate the variables        
        $invalidFlag = "no";
        
        if (is_numeric($wastID)) {
            $wastID = (int) $wastID;
        } else {
            // invalid id
            $invalidFlag = "yes";
        }

        if (is_numeric($matchID)) {
            $matchID = (int) $matchID;
        } else {
            // invalid id
            $invalidFlag = "yes";
        }

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
                
        // verify status
        if (($status1 == "waiting") || ($status1 == "open") || ($status1 == "terminated") || ($status1 == "finished") || ($status1 == "arbitration")) {
            // do nothing
        } else {
            // invalid status
            $invalidFlag = "yes";
        }

        // verify status
        if (($status2 == "waiting") || ($status2 == "open") || ($status2 == "terminated") || ($status2 == "finished") || ($status2 == "arbitration")) {
            // do nothing
        } else {
            // invalid status
            $invalidFlag = "yes";
        }
        
        if (($contractType == "friend") || ($contractType == "love") || ($contractType == "work")) {
            // do nothing
        } else {
            // invalid type
            $invalidFlag = "yes";
        }
        
        if (isset($contractGoal)) {
            // do nothing
        } else {
            // invalid goal
            $invalidFlag = "yes";
        }

        if (is_numeric($goalID)) {
            $goalID = (int) $goalID;
        } else {
            // invalid id
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
            if ($contractSteps < 1) {
                // invalid steps
                $invalidFlag = "yes";
            }
        } else {
            // invalid steps
            $invalidFlag = "yes";
        }

        if (($contractDonee == "redcross") || ($contractDonee == "doctors")) {
            // do nothing
        } else {
            // invalid donee
            $invalidFlag = "yes";
        }

        if (is_numeric($contractArbFee)) {
            $contractArbFee = (int) $contractArbFee;
            if (($contractArbFee < 0) || ($contractArbFee > 100)) {
                // invalid fee
                $invalidFlag = "yes";
            }            
        } else {
            // invalid fee
            $invalidFlag = "yes";
        }

        if (is_numeric($contractFee)) {
            $contractFee = (int) $contractFee;
            if (($contractFee < 0) || ($contractFee > 100)) {
                // invalid fee
                $invalidFlag = "yes";
            }            
        } else {
            // invalid fee
            $invalidFlag = "yes";
        }

        if ($invalidFlag == "yes") {
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'CONTRACT FAIL: INVALID DATA FOUND ON VIEW STEPS';
            $logging = "Some invalid data found at: ";
            $logging .= $createDate;
            $logging .= ' for contractID: '.$contractID;
            $logging .= ' for userID: '.$userID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

            // redirect - not valid
            header('Location: ./index.php?v=yes5');	
            exit();
        }
        
        // close statement
        mysqli_stmt_close($stmt);                
        
    }

}

// verify wastTable, pull data
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
        $subject = 'CONTRACT FAIL: NO WAST DATA FOUND ON VIEWSTEPS';
        $logging = "There was no data found for wastID at: ";
        $logging .= $createDate;
        $logging .= ' for wastID: '.$wastID;
        $logging .= ' for userID: '.$userID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect to match page
        header('Location: ./match.php?v='.$loginCheck);	
        exit();        

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

// verify interactStep, pull data
$sql = "SELECT spotID1, spotID2, currentStep, status FROM interactTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
        $subject = 'CONTRACT FAIL: NO INTERACT DATA FOUND ON VIEWSTEPS';
        $logging = "There was no data found for contractID at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect to match page
        header('Location: ./match.php?v='.$loginCheck);	
        exit();        

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $spotID1Bind, $spotID2Bind, $currentStepBind, $statusInteractBind);                    

        // fetch the results
        $stmt->fetch();
        
        // set variables
        $spotID1Interact = $spotID1Bind;
        $spotID2Interact = $spotID2Bind;
        $currentStep = $currentStepBind;
        $statusInteract = $statusInteractBind;

        // reset invalid flag
        $invalidFlag = "no";

        // validate
        if ($spotID1 != $spotID1Interact) {
            $invalidFlag = "yes";
        }

        if ($spotID2 != $spotID2Interact) {
            $invalidFlag = "yes";
        }

        if (is_numeric($currentStep)) {
            $currentStep = (int) $currentStep;
            if ($currentStep < 1) {
                // invalid step
                $invalidFlag = "yes";
            }
        } else {
            // invalid steps
            $invalidFlag = "yes";
        }

        if (($statusInteract == "open") || ($statusInteract == "closed")) {
            // do nothing
        } else {
            // invalid status
            $invalidFlag = "yes";
        }
    
        if ($invalidFlag == "yes") {            
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'CONTRACT FAIL: INVALID INTERACT DATA FOUND ON VIEWSTEPS';
            $logging = "There was invalid data found for contractID at: ";
            $logging .= $createDate;
            $logging .= ' for contractID: '.$contractID;
            $logging .= ' for userID: '.$userID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
    
            // redirect to match page
            header('Location: ./match.php?v='.$loginCheck);	
            exit();        
        }

        // close statement
        mysqli_stmt_close($stmt);                

    }

}

// set up step array
$stepCount = 0;
$counter = 0;
$stepArray = array();
$interactIDArray = array();
$stepArray = array();
$subStepArray = array();
$spotIDToArray = array();
$spotIDFromArray = array();
$finishFlagArray = array();

// verify stepTable, pull data
$sql = "SELECT interactID, step, subStep, spotIDTo, spotIDFrom, finishFlag FROM stepTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

        // do nothing - can have no steps if just started 
        
    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $interactIDBind, $stepBind, $subStepBind, $spotIDToBind, $spotIDFromBind, $finishFlagBind);                    

        // fetch the results
        while ($stmt->fetch()) {
        
            // set variables
            $interactIDArray[$stepCount] = $interactIDBind;
            $stepArray[$stepCount] = $stepBind;
            $subStepArray[$stepCount] = $subStepBind;
            $spotIDToArray[$stepCount] = $spotIDToBind;
            $spotIDFromArray[$stepCount] = $spotIDFromBind;
            $finishFlagArray[$stepCount] = $finishFlagBind;

            // decrypt any goal steps
            if ($finishFlagArray[$stepCount] == "yes") {

                $userKeyMatcheos = Key::loadFromAsciiSafeString(MATCHEOSUSERKEYENCODED);
                try {
                    
                    $stepArray[$stepCount] = Crypto::decrypt($stepBind, $userKeyMatcheos);
                    
                } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
                    
                    // no decryption                    
                    $stepArray[$stepCount] = "Goal: Error";

                }

            }

            $stepCount++;

        }
        
        // close statement
        mysqli_stmt_close($stmt);                

    }

}


// contract status should not be waiting for current user (funding turns contract open)
if (($spotID1 == $userID) && ($status1 == "waiting")) {

    // send an email to the admin         
    $to = ADMINEMAIL;  
    $subject = 'STATUS FAIL: USER HAS NOT ACCEPTED BUT ON VIEWSTEPS';
    $logging = "The user has waiting status for contractID at: ";
    $logging .= $createDate;
    $logging .= ' for contractID: '.$contractID;
    $logging .= ' for userID: '.$userID;			      
    $header = 'From: donotrespond@matcheos.com';
    if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

    // redirect to accept contract page
    header('Location: ./acceptcontract.php?v='.$loginCheck.'&w='.$matchID);	
    exit();        

}

// contract status should not be waiting for current user (funding turns contract open)
if (($spotID2 == $userID) && ($status2 == "waiting")) {
    
    // send an email to the admin         
    $to = ADMINEMAIL;  
    $subject = 'STATUS FAIL: USER HAS NOT ACCEPTED BUT ON VIEWSTEPS';
    $logging = "The user has waiting status for contractID at: ";
    $logging .= $createDate;
    $logging .= ' for contractID: '.$contractID;
    $logging .= ' for userID: '.$userID;			      
    $header = 'From: donotrespond@matcheos.com';
    if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

    // redirect to accept contract page
    header('Location: ./acceptcontract.php?v='.$loginCheck.'&w='.$matchID);	
    exit();        
    
}

// set the status for both users (person1/person2 can be different from contract spots, one is timebased, the other is increasing numbers)
if ($spotID1 == $userID) {
    $statusPersonA = $status1;
    $statusPersonB = $status2;
    $offerA = $offer1;
    $offerB = $offer2;
    $otherUserID = $spotID2;
} else {
    $statusPersonA = $status2;
    $statusPersonB = $status1;
    $offerA = $offer2;
    $offerB = $offer1;
    $otherUserID = $spotID1;
}

// pull the other person's userName
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
        $subject = 'CONTRACT FAIL: NO USERNAME FOUND ON VIEWSTEPS';
        $logging = "There was no user found for contractID at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$otherUserID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect to match page
        header('Location: ./match.php?v='.$loginCheck);	
        exit();        

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

// pull profile data for other person
$sql = "SELECT firstName, location1, location2, tagline, image, whyMeet FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

        // no profile found            
        $to = ADMINEMAIL;  
        $subject = 'CONTRACT FAIL: NO PROFILE DATA FOUND ON VIEWSTEPS';
        $logging = "No user profile for other user found at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$otherUserID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect - not valid
        header('Location: ./index.php?v=yes6');	
        exit();

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $firstNameBind, $location1Bind, $location2Bind, $taglineBind, $imageBind, $whyMeetBind);

        // fetch the results
        $stmt->fetch();
        
        // set variables
        $firstNameOther = $firstNameBind;
        $location1 = $location1Bind;
        $location2 = $location2Bind;
        $tagline = $taglineBind;
        $image = $imageBind;
        $whyMeet = $whyMeetBind;

        // validate the image
        $startPath = "https://matcheos.com/img/profiles/";
        if (substr($imageBind, 0, strlen($startPath)) === $startPath) {      
            $image = htmlspecialchars($imageBind);    
        } else {
          // not valid format - set generic image
          $image = "https://matcheos.com/img/alice4.jpg";
        }

        // close statement
        mysqli_stmt_close($stmt);        

    }

}

// pull profile data for user
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

        // no profile found            
        $to = ADMINEMAIL;  
        $subject = 'CONTRACT FAIL: NO PROFILE DATA FOUND ON VIEWSTEPS';
        $logging = "No user profile for user found at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect - not valid
        header('Location: ./index.php?v=yes7');	
        exit();

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $firstNameBind);

        // fetch the results
        $stmt->fetch();
        
        // set variables
        $firstName = $firstNameBind;
        
        // close statement
        mysqli_stmt_close($stmt);        

    }

}

// set the display flags    
$displayRejected = "no";
$displayTerminated = "no";
$displayTermRelease = "no";
$displayFinished = "no";
$displayArbitration = "no";
$displayOpen = "no";   

// set the default display values
$statusHeader = "";
$displayStaked = 0;
$displayReleased = 0;
$displayRemaining = 0;

// check if rejected before contract published and before person2 funds
if ($rejectFlag == "yes") {

    // set the rejected display
    $displayRejected = "yes";
    $statusHeader = "Rejected";

// check if person1 terminates without any steps     
} elseif ((($status1 == "terminated") || ($status2 == "terminated")) && ($stepCount == 0)) {

    // set the terminate display
    $displayTerminated = "yes";
    $statusHeader = "Terminated";

} elseif ((($status1 == "terminated") || ($status2 == "terminated")) && (!isset($terminationRelease))) {

    // if waiting for 24 hour termination
    $displayTermRelease = "yes";

} else {

    // exec to nodejs to verify that contract exists on blockchain, check database against blockchain truth
    $verifyContract = shell_exec('node '.MATCHEOSPATH.'/verifycontract.js '.escapeshellarg($accountName).' '.escapeshellarg($contractName).' '.escapeshellarg($userName).' '.escapeshellarg($otherUserName).' '.escapeshellarg($offerA).' '.escapeshellarg($offerB).' '.escapeshellarg($contractType).' '.escapeshellarg($contractGoal).' '.escapeshellarg($contractFormat).' '.escapeshellarg($contractSteps).' '.escapeshellarg($contractDonee).' '.escapeshellarg($contractArbFee).' '.escapeshellarg($contractFee).' '.escapeshellarg(MATCHEOS_FEE_ACCOUNT).' '.escapeshellarg($statusPersonA).' '.escapeshellarg($statusPersonB).' '.escapeshellarg($stepCount));        

    // verify that funding transactions are greater than offer, blockchain matches database
    if (trim($verifyContract) == "success") {
        
        // do nothing - contract exists, database matches blockchain truth, wallet has correct balance

    } else {
        
        // output error in verifycontract
        $to = ADMINEMAIL;  
        $subject = 'CONTRACT FAIL: VERIFYCONTRACT FAILED ON VIEWSTEPS';
        $logging = "There was a nodejs error for contractID at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			      
        $logging .= ' with errorCode: '.$verifyContract;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect to match page
        header('Location: ./match.php?v='.$loginCheck);	
        exit();        
    
    }
    
}

// handle status: display if contract was terminated - not rejected
if (($rejectFlag == "no") && (($status1 == "terminated") || ($status2 == "terminated"))) {
    
    // set the whether the arbWindow is open - 24 hours after terminated
    $arbWindow = "open";

    if (isset($terminationRelease)) {
        // arb window has now closed - wallet has been released
        $arbWindow = "closed";
    }
    
    $invalidFlag = "no";

    // validate that the field exists
    if (!isset($terminationDate)) {
        $invalidFlag = "yes";
    }

    // validate that the field was submitted
    if (strlen($terminationDate) < 1) {
        $invalidFlag = "yes";
    }

    // validate that the field is not an overflow
    if (strlen($terminationDate) > 500) {
        $invalidFlag = "yes";
    }

    if ($invalidFlag == "yes") {

        // email admin
        $to = ADMINEMAIL;  
        $subject = 'CONTRACT FAIL: TERMINATED ERROR ON VIEWSTEPS';
        $logging = "There was an invalid termination date at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			              
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
        
        // redirect - not valid
        header('Location: ./index.php?v=yes8');	
        exit();

    }

    // set terminated display
    $displayTerminated = "yes";
    $statusHeader = "Terminated";

}

// handle status: display if contract is finished
if (($status1 == "finished") || ($status2 == "finished")) {

    // validate
    $finishedDate = $finishedDateBind;
        
    $invalidFlag = "no";
    
    // validate that the field exists
    if (!isset($finishedDate)) {
        $invalidFlag = "yes";
    }

    // validate that the field was submitted
    if (strlen($finishedDate) < 1) {
        $invalidFlag = "yes";
    }

    // validate that the field is not an overflow
    if (strlen($finishedDate) > 500) {
        $invalidFlag = "yes";
    }

    if ($invalidFlag == "yes") {

        // email admin
        $to = ADMINEMAIL;  
        $subject = 'CONTRACT FAIL: FINISHED ERROR ON VIEWSTEPS';
        $logging = "There was an invalid finished date at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			              
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
        
        // redirect - not valid
        header('Location: ./index.php?v=yes9');	
        exit();

    }
    
    // set finished display
    $displayFinished = "yes";
    $statusHeader = "Finished";
       
}

// handle status: display if contract is in arbitration
if (($status1 == "arbitration") || ($status2 == "arbitration")) {

    // set arbitration display
    $displayArbitration = "yes";
    $statusHeader = "Arbitration";

    // check for arbitration status
    $sql = "SELECT status FROM arbitrationTable WHERE contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
    
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
            $subject = 'CONTRACT FAIL: NO ARBITRATIONID FOUND ON VIEWSTEPS';
            $logging = "The contract status is in arbitration but no datat: ";
            $logging .= $createDate;
            $logging .= ' for contractID: '.$contractID;
            $logging .= ' for userID: '.$otherUserID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			
    
            // redirect to match page
            header('Location: ./match.php?v='.$loginCheck);	
            exit();        
    
        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $statusArbBind);                    
    
            // fetch the results
            $stmt->fetch();
            
            // set variables
            $statusArb = $statusArbBind;
            
            // close statement
            mysqli_stmt_close($stmt);                
    
        }
    
    }        
   
}

// handle status: display if contract is in open
if ((($status1 == "waiting") || ($status1 == "open")) && (($status2 == "waiting") || ($status2 == "open"))) {
    
    // set open display
    $displayOpen = "yes";
    $statusHeader = "Step ".$stepCount." of ".$contractSteps;
    
}    

// set the staked display
if ($spotID1 == $userID) {
    $displayStaked = $offer1;
} else {
    $displayStaked = $offer2;
}

// set the remaining display to 0, released to offer - if rejected/finished/terminated-released/arbitration-closed
if (($displayRejected == "yes") || ($displayFinished == "yes")) {
    $displayRemaining = 0;
    $displayReleased = $displayStaked;    
}

// set the remaining display to 0, released to offer - if terminated-released/arbitration-closed
if (($displayTerminated == "yes") || ($displayArbitration == "yes")) {
    
    // check for termination release
    if (isset($terminationRelease)) {
        $displayRemaining = 0;
        $displayReleased = $displayStaked;
    }

    // check for arbitration
    if (isset($statusArb)) {

        // check if closed
        if ($statusArb == "closed") {
            $displayRemaining = 0;
            $displayReleased = $displayStaked;
        }
    }

}

// handle the display arbitration button
$displayArbButton = "yes";
if (isset($terminationRelease)) {
    $displayArbButton = "no";
}

if (($displayFinished == "yes") || ($displayArbitration == "yes")) {
    $displayArbButton = "no";
}

// set the first person and second person based on steps
if ($stepCount > 0) {

    $personID1 = $spotIDFromArray[0];
    $personID2 = $spotIDToArray[0];

    if ($spotID1 == $personID1) {
        $offerPerson1 = $offer1;
        $offerPerson2 = $offer2;
    } else {
        $offerPerson1 = $offer2;
        $offerPerson2 = $offer1;
    }

} else {

    // no steps yet - current user is person1, other is person2    
    if ($spotID1 == $userID ) {
        $personID1 = $spotID1;
        $personID2 = $spotID2;
        $offerPerson1 = $offer1;
        $offerPerson2 = $offer2;
    } else {
        $personID1 = $spotID2;
        $personID2 = $spotID1;
        $offerPerson1 = $offer2;
        $offerPerson2 = $offer1;
    }

}

// set the remaining display per released steps and spot for terminated-notreleased
if (($displayTerminated == "yes") && (!isset($terminationRelease))) {
   
    // check if user is person1 or person2
    if ($userID == $personID1) {

        // calculate the balances for person1 if open (has 1 more step than person2)
        if ($contractSteps > 0) {
            $displayReleasedString = number_format(($offerPerson1/$contractSteps),4);
            $displayReleasedInt = (float) $displayReleasedString;
            $displayReleased = ($displayReleasedInt * $stepCount);
            $displayRemaining = $offerPerson1 - $displayReleased;
        } else {
            $displayRemaining = 0;
        }

    } else {

        // calculate the remaining balance for person2 if open (has 1 less step than person1)        
        if (($contractSteps > 1) && ($stepCount > 1)) {
            $displayReleasedString = number_format(($offerPerson2/($contractSteps - 1)),4);
            $displayReleasedInt = (float) $displayReleasedString;
            $displayReleased = ($displayReleasedInt * ($stepCount - 1));
            $displayRemaining = $offerPerson2 - $displayReleased;
        } else {
            $displayRemaining = 0;
        }

    }
  
}

// set the remaining display per released steps and spot for arbitration-open
if (($displayArbitration == "yes") && (isset($statusArb))) {
    
    // check if open
    if ($statusArb == "open") {

        // check if user is person1 or person2
        if ($userID == $personID1) {
            
            // calculate the balances for person1 if open (has 1 more step than person2)
            if ($contractSteps > 0) {
                $displayReleasedString = number_format(($offerPerson1/$contractSteps),4);
                $displayReleasedInt = (float) $displayReleasedString;
                $displayReleased = ($displayReleasedInt * $stepCount);
                $displayRemaining = $offerPerson1 - $displayReleased;
            } else {
                $displayRemaining = 0;
            }

        } else {

            // calculate the remaining balance for person2 if open (has 1 less step than person1)        
            if (($contractSteps > 1) && ($stepCount > 1)) {
                $displayReleasedString = number_format(($offerPerson2/($contractSteps - 1)),4);
                $displayReleasedInt = (float) $displayReleasedString;
                $displayReleased = ($displayReleasedInt * ($stepCount - 1));
                $displayRemaining = $offerPerson2 - $displayReleased;
            } else {
                $displayRemaining = 0;
            }
    
        }

    }
     
}

// set the remaining display per released steps and spot for open
if ($displayOpen == "yes") {

    // check if user is person1 or person2
    if ($userID == $personID1) {
        
        // calculate the balances for person1 if open (has 1 more step than person2)
        if ($contractSteps > 0) {
            $displayReleasedString = number_format(($offerPerson1/$contractSteps),4);
            $displayReleasedInt = (float) $displayReleasedString;
            $displayReleased = ($displayReleasedInt * $stepCount);
            $displayRemaining = $offerPerson1 - $displayReleased;
        } else {
            $displayRemaining = 0;
        }

    } else {

        // calculate the remaining balance for person2 if open (has 1 less step than person1)        
        if (($contractSteps > 1) && ($stepCount > 1)) {
            $displayReleasedString = number_format(($offerPerson2/($contractSteps - 1)),4);
            $displayReleasedInt = (float) $displayReleasedString;
            $displayReleased = ($displayReleasedInt * ($stepCount - 1));
            $displayRemaining = $offerPerson2 - $displayReleased;
        } else {
            $displayRemaining = 0;
        }

    }

}

// get the names of person1 and person2
if ($personID1 == $userID) {
    $person1Name = $firstName;
    $person2Name = $firstNameOther;
} else {
    $person1Name = $firstNameOther;
    $person2Name = $firstName;
}

// if still open, check who has the next spot
$currentTurn = 1;
if ($displayOpen == "yes") {    
    // if no steps, person 1 is up
    if ($stepCount == 0) {
        $currentTurn = 1;
    // if current step is even, person1 is up
    } elseif ($stepCount % 2 == 0) {
        $currentTurn = 1;
    } else {
        // if odd, person2 is up
        $currentTurn = 2;
    }    
}

// handle the submit step display
$displaySubmitStep = "waiting";

// check if the user is up
$userUp = "no";
$submitName = $person1Name;
if (($currentTurn == 1) && ($userID == $personID1)) {
    $userUp = "yes";    
    $submitName = $person2Name;
} 

if (($currentTurn == 2) && ($userID == $personID2)) {
    $userUp = "yes";
    $submitName = $person1Name;
    
}

// if the user is not up = handle the submitName
if (($userUp == "no") && ($userID == $personID1)) {
    $submitName = $person2Name;
}

if (($userUp == "no") && ($userID == $personID2)) {
    $submitName = $person1Name;
}

// show submit box if open and not last step and person's turn
if (($displayOpen == "yes") && ($stepCount < $contractSteps - 1) && ($userUp == "yes")) {
    $displaySubmitStep = "open";
}

// show submit finish box if open and last step and person's turn
if (($displayOpen == "yes") && ($stepCount == $contractSteps - 1) && ($userUp == "yes")) {
    $displaySubmitStep = "finish";
    $lastStep = "yes";
}

// handle second to last step - show submit finish box
if (($displayOpen == "yes") && ($stepCount == $contractSteps - 2) && ($userUp == "yes")) {
     $displaySubmitStep = "finish";    
}

?>

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - Match Interaction</title>

    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom fonts for this template -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">

    <!-- Custom styles for this template -->
    <link href="css/landing-page.css" rel="stylesheet">
    <link href="css/speechbubble.css" rel="stylesheet">

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
            <h2 style="padding-bottom:10px;margin-top:10px">Matcheos Smart Contract: <?=htmlspecialchars($statusHeader)?></h2>                       
            <!-- Item Row -->            
            <div class="form-group row pGroup" id="nGroup3">                                                    
                <div class="col-lg-4">
                    <input class="form-control" id="staked" type="text" value="Staked: <?=number_format(htmlspecialchars($displayStaked),4)?> EOS" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                </div>                                                
                <div class="col-lg-4">                 
                    <input class="form-control" id="released" type="text" value="Released: <?=number_format(htmlspecialchars($displayReleased),4)?> EOS" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                </div>                        
                <div class="col-lg-4">
                    <input class="form-control" id="remaining" type="text" value="Remaining: <?=number_format(htmlspecialchars($displayRemaining),4)?> EOS" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                </div>                                                
            </div>                                                                                           
            <?php if ($displayTermRelease == "yes") { ?>
            <div class="row">                           
                <div class="col-12"><h5 style="color:red">24 Hour Termination Release to Prevent Arbitration Frontrunning.</div>
            </div>
            <?php } ?>
            <div class="row">                           
                <div class="col-12">
                    <h3><?=htmlspecialchars($firstNameOther)?> from <?=htmlspecialchars($location1)?>, <?=htmlspecialchars($location2)?></h3>                        
                    <img class="img-responsive whyMe" src="<?=htmlspecialchars($image)?>" style="width:50%;box-shadow: 3px 3px 5px #888888;">                      
                    <h4 style="margin-top:5px;" class="tagline"><?=htmlspecialchars($tagline)?></h4>
                    <hr class="intro-divider">          
                    <input type="password" name="password" id="password" placeholder="Enter Password to Confirm" style="display:none"/>
                    <?php
                    if ($displayOpen == "yes") {
                    ?>
                    <button type="submit" class="btn btn-primary" id="terminate" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;margin-bottom:5px"><h4 style="margin-top:5px">Terminate Contract</h4></button>
                    <?php
                    }
                    if ($displayArbButton == "yes") {
                    ?>
                    <button type="submit" class="btn btn-primary" id="arbitrate" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;margin-bottom:5px"><h4 style="margin-top:5px">Arbitrate Dispute</h4></button>           
                    <?php
                    }                    
                    ?>             
                    <h5 id="termMessage" style="display:none">Do you want to enter into a new contract with this person?</h5>                                
                    <button type="submit" class="btn btn-primary" id="contractYesTerm" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">New Contract</h4></button>           
                    <button type="submit" class="btn btn-primary" id="contractNoTerm" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Close Match</h4></button>                       
                    <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="message" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>              
            <div class="row">                           
                <div class="col-12">
                    <h4 style="margin-top:20px;" class="tagline">Goal: <?=htmlspecialchars($contractGoal)?></h4>
                    <hr class="intro-divider">          
                </div>                                                     
            </div>                          
            <div class="row">                           
                <div class="col-12">
                    <h5 style="text-align:right;margin-bottom:-5px">Autogenerated</h5>
                </div>                                                     
            </div>                          
            <div class="row">                           
                <div class="col-12">                                        
                    <div class="content">                                                    
                        <p class="triangle-right right" style="color:black">Why did you want to meet me?</p>                        
                    </div>
                </div>                                                     
            </div>                          
            <?php
            $counter = 0;
            while ($counter < $stepCount) {
            ?>
            <div class="row">                           
                <div class="col-12">
                    <h5 style="text-align:left;margin-bottom:-5px"><?=htmlspecialchars($person1Name)?>: Step <?=htmlspecialchars($counter + 1)?></h5>
                </div>                                                     
            </div>                          
            <div class="row">                           
                <div class="col-12">                                        
                    <div class="content">                        
                        <p class="triangle-right left"><?=htmlspecialchars($stepArray[$counter])?></p>                         
                    </div>
                    <?php
                    if ((isset($subStepArray[$counter])) && ($contractFormat == "twosided")) {
                    ?>
                    <div class="content">                        
                        <p class="triangle-right left"><?=htmlspecialchars($subStepArray[$counter])?></p>                         
                    </div>
                    <?php
                    }
                    ?>
                </div>                                                     
            </div>                          
            <?php
            if ($counter + 1 < $stepCount) {
            ?>
            <div class="row">                           
                <div class="col-12">
                    <h5 style="text-align:right;margin-bottom:-5px"><?=htmlspecialchars($person2Name)?>: Step <?=htmlspecialchars($counter + 2)?></h5>
                </div>                                                     
            </div>                          
            <div class="row">                           
                <div class="col-12">                                        
                    <div class="content">                                                    
                        <p class="triangle-right right" style="color:black"><?=htmlspecialchars($stepArray[$counter + 1])?></p>                        
                    </div>
                </div>                                                     
            </div>                          
            <?php
                if ((isset($subStepArray[$counter + 1])) && ($contractFormat == "twosided")) {
            ?>
            <div class="row">                           
                <div class="col-12">                                        
                    <div class="content">                                                    
                        <p class="triangle-right right" style="color:black"><?=htmlspecialchars($subStepArray[$counter + 1])?></p>                        
                    </div>
                </div>                                                     
            </div>    
            <?php
                }                
            }
            $counter = $counter + 2;
            }            
            if (($displaySubmitStep == "open") && ($contractFormat == "twosided")) {
            ?>
            <div class="row">                           
                <div class="col-lg-12">                 
                    <h5 style="text-align:right;">Enter Response to <?=htmlspecialchars($submitName)?>'s Question:</h5>
                    <textarea id="response1" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
            </div>        
            <div class="row">                           
                <div class="col-lg-12">                 
                    <h5 style="text-align:right;">Enter a Question for <?=htmlspecialchars($submitName)?>:</h5>
                    <textarea id="response2" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
            </div>        
            <div class="row">                           
                <div class="col-12">
                    <hr class="intro-divider">                           
                    <button type="submit" class="btn btn-primary" id="submitStep" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Submit Step</h4></button>           
                    <div id="loadingStep" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="messageStep" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>              
            <?php
            }
            if (($displaySubmitStep == "open") && ($contractFormat == "chat")) {
            ?>
            <div class="row">                           
                <div class="col-lg-12">                 
                    <h5 style="text-align:right;">Enter Response to <?=htmlspecialchars($submitName)?>:</h5>
                    <textarea id="response1" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
            </div>              
            <div class="row">                           
                <div class="col-12">
                    <hr class="intro-divider">                           
                    <button type="submit" class="btn btn-primary" id="submitStep" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Submit Step</h4></button>           
                    <div id="loadingStep" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="messageStep" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>                                
            <?php
            }
            if (($displaySubmitStep == "open") && ($contractFormat == "interview") && ($userID == $personID1)) {
            ?>
            <div class="row">                           
                <div class="col-lg-12">                 
                    <h5 style="text-align:right;">Enter Next Question for <?=htmlspecialchars($submitName)?>:</h5>
                    <textarea id="response1" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
            </div>              
            <div class="row">                           
                <div class="col-12">
                    <hr class="intro-divider">                           
                    <button type="submit" class="btn btn-primary" id="submitStep" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Submit Step</h4></button>           
                    <div id="loadingStep" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="messageStep" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>                                
            <?php
            }
            if (($displaySubmitStep == "open") && ($contractFormat == "interview") && ($userID == $personID2)) {
            ?>
            <div class="row">                           
                <div class="col-lg-12">                 
                    <h5 style="text-align:right;">Enter Answer to <?=htmlspecialchars($submitName)?>'s Question:</h5>
                    <textarea id="response1" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
            </div>                                
            <div class="row">                           
                <div class="col-12">
                    <hr class="intro-divider">                           
                    <button type="submit" class="btn btn-primary" id="submitStep" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Submit Step</h4></button>           
                    <div id="loadingStep" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="messageStep" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>              
            <?php
            } 
            if (($displaySubmitStep == "finish") && ($contractFormat == "twosided")) {            
            ?>
            <div class="row">                           
                <div class="col-lg-12" id="contractText1">                 
                    <h5 style="text-align:right;">Enter Response to <?=htmlspecialchars($submitName)?>'s Question:</h5>
                    <textarea id="response1" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
            </div>        
            <div class="row">                           
                <div class="col-lg-12" id="contractTex2">                 
                    <?php
                    if ($contractGoal == "A Contract with No Goal") {
                    ?>
                    <h5 style="text-align:right;">Enter the Contract Goal:</h5>
                    <?php
                    } else {
                    ?>
                     <h5 style="text-align:right;">Enter the Final Interaction:</h5>   
                    <?php
                    } 
                    ?>
                    <textarea id="goal" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
                <div class="col-lg-12" id="goalText" style="display:none">                 
                    <h5 style="text-align:right;">Do you want to enter into a new contract with this person?</h5>                    
                </div>
            </div>        
            <div class="row">                           
                <div class="col-12">
                    <hr class="intro-divider">                                               
                    <button type="submit" class="btn btn-primary" id="submitFinish" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Submit Goal</h4></button>           
                    <button type="submit" class="btn btn-primary" id="contractYes" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">New Contract</h4></button>           
                    <button type="submit" class="btn btn-primary" id="contractNo" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Close Match</h4></button>   
                    <div id="loadingFinish" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="messageFinish" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>              
            <?php
            }
            if (($displaySubmitStep == "finish") && ($contractFormat == "chat")) {            
            ?>            
            <div class="row">                           
                <div class="col-lg-12" id="contractText1">                 
                    <?php
                    if ($contractGoal == "A Contract with No Goal") {
                    ?>
                    <h5 style="text-align:right;">Enter the Contract Goal:</h5>
                    <?php
                    } else {
                    ?>
                     <h5 style="text-align:right;">Enter the Final Interaction:</h5>   
                    <?php
                    } 
                    ?>
                    <textarea id="goal" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
                <div class="col-lg-12" id="goalText" style="display:none">                 
                    <h5 style="text-align:right;">Do you want to enter into a new contract with this person?</h5>                    
                </div>
            </div>        
            <div class="row">                           
                <div class="col-12">
                    <hr class="intro-divider">                           
                    <button type="submit" class="btn btn-primary" id="submitFinish" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Submit Goal</h4></button>           
                    <button type="submit" class="btn btn-primary" id="contractYes" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">New Contract</h4></button>           
                    <button type="submit" class="btn btn-primary" id="contractNo" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Close Match</h4></button>   
                    <div id="loadingFinish" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="messageFinish" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>              
            <?php
            }
            if (($displaySubmitStep == "finish") && ($contractFormat == "interview")) {            
            ?>            
            <div class="row">                           
                <div class="col-lg-12" id="contractText1">                 
                    <?php
                    if ($contractGoal == "A Contract with No Goal") {
                    ?>
                    <h5 style="text-align:right;">Enter the Contract Goal:</h5>
                    <?php
                    } else {
                    ?>
                     <h5 style="text-align:right;">Enter the Final Interaction:</h5>   
                    <?php
                    } 
                    ?> 
                    <textarea id="goal" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                </div>
                <div class="col-lg-12" id="goalText" style="display:none">                 
                    <h5 style="text-align:right;">Do you want to enter into a new contract with this person?</h5>                    
                </div>
            </div>        
            <div class="row">                           
                <div class="col-12">
                    <hr class="intro-divider">                           
                    <button type="submit" class="btn btn-primary" id="submitFinish" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Submit Goal</h4></button>           
                    <button type="submit" class="btn btn-primary" id="contractYes" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">New Contract</h4></button>           
                    <button type="submit" class="btn btn-primary" id="contractNo" style="display:none;margin-top:10px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Close Match</h4></button>   
                    <div id="loadingFinish" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="messageFinish" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>              
            <?php
            } 
            if ($displaySubmitStep == "waiting") {
            ?>            
            <div class="row">                           
                <div class="col-lg-12">                 
                    <h5 style="text-align:right;">Waiting for <?=htmlspecialchars($submitName)?> to Respond...</h5>                    
                </div>
            </div>        
            <?php
            } 
            ?>
            <div class="row">                           
                <div class="col-12">
                    <br><br>
                    <h4 style="margin-bottom:-10px;" class="tagline">Update Your Review of <?=htmlspecialchars($firstNameOther)?>:</h4>                    
                </div>                                                     
            </div>                          
            <div class="form-group row pGroup" id="review" style="margin-top:20px;">                                                                
                <div class="col-lg-3"></div>
                <div class="col-lg-3">
                    <ul class="starList">
                        <li class="starElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw"></i></li>
                    </ul>
                    <div id="starMessage" name="starMessage">During Contract</div>                                    
                    <input id="duringRating" name="duringRating" type="hidden" />
                </div>                                                
                <div class="col-lg-3">
                    <ul class="afterList">
                        <li class="afterElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="afterElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="afterElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="afterElement"><i class="fa fa-star fa-fw"></i></li>
                        <li class="afterElement"><i class="fa fa-star fa-fw"></i></li>
                    </ul>
                    <div id="afterMessage" name="afterMessage">After Contract</div>                                    
                    <input id="afterRating" name="afterRating" type="hidden" />
                </div>                      
                <div class="col-lg-3"></div>                                          
            </div>
            
            <!-- /.row -->
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
                   
        // handle arbitration button 
        $("#arbitrate").on( "click", function() {		
            // go to arbitration page
            location.href = './arbitration.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($contractID)?>';            
        });  	                                     

        // handle terminate button 
        $("#terminate").on( "click", function() {		

            // hide terminate and arbitrate buttons            
            $( "#terminate" ).fadeOut("slow");			
            $( "#arbitrate" ).fadeOut("slow");			

            // show message about new contract or close match
            $( "#termMessage" ).fadeIn("slow");			
            $( "#contractYesTerm" ).fadeIn("slow");			
            $( "#contractNoTerm" ).fadeIn("slow");			                

        }); 

        // handle terminate button with new contract
        $("#contractYesTerm").on( "click", function() {		

            // get the values
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                                       
            var password = $("#password").val();
            var termType = "yes";

            // validate the contract
            if (!contractID) {
                alert('There is a problem with this contract. Try again later.')
                return false;
            }

            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit the button again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                
            // set the post url
            var url = "./ajax/terminatecontract.php";
                                    
            // validate the token exists
            if (token) {

                // hide the button
                $( "#contractYesTerm" ).fadeOut("slow");			                
                $( "#contractNoTerm" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: termType, field4: password} );
                <?php } else { ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: termType} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                                            
                    ajaxResult = Number(ajaxResult);                    
                    $( "#loading" ).fadeOut("slow");		
                    $( "#contractNoTerm" ).fadeIn("slow");			
                    $( "#contractYesTerm" ).fadeOut("slow");			                
                    $( "#message" ).fadeIn("slow");
                    
                    // If no errors, 
                    switch(ajaxResult) {
                        case 1:                                                
                            // go to select new contract
                            window.location.href = "./selectcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>";
                            break;                                                
                        default:
                            $( '#message' ).text("There was a problem terminating the contract. Please try later.");                                                                        
                    }							
                });

                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#contractNoTerm" ).fadeIn("slow");			
                    $( "#contractYesTerm" ).fadeOut("slow");			                
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem terminating the contract. Please try later.");                                                                    
            }

            return false;

            });  	                       
        
        // handle terminate button with close match
        $("#contractNoTerm").on( "click", function() {		

            // get the values
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                                       
            var password = $("#password").val();
            var termType = "no";

            // validate the contract
            if (!contractID) {
                alert('There is a problem with this contract. Try again later.')
                return false;
            }

            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit the button again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                 
            // set the post url
            var url = "./ajax/terminatecontract.php";
                                    
            // validate the token exists
            if (token) {

                // hide the buttons
                $( "#contractYesTerm" ).fadeOut("slow");			                
                $( "#contractNoTerm" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: termType, field4: password} );
                <?php } else { ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: termType} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                                            
                    ajaxResult = Number(ajaxResult);                    
                    $( "#loading" ).fadeOut("slow");		
                    $( "#contractNoTerm" ).fadeIn("slow");			
                    $( "#contractYesTerm" ).fadeOut("slow");			                
                    $( "#message" ).fadeIn("slow");
                    
                    // If no errors, 
                    switch(ajaxResult) {
                        case 1:                                                
                            // go to match page
                            window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                    
                            break;                                                
                        default:
                            $( '#message' ).text("There was a problem terminating the contract. Please try later.");                                                                        
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#contractNoTerm" ).fadeIn("slow");			
                    $( "#contractYesTerm" ).fadeOut("slow");			                
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem terminating the contract. Please try later.");                                                                    
            }
            
            return false;

        });  	                                     

        // handle submit step 
        $("#submitStep").on( "click", function() {		
            
            // get the values
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                                
            var response1 = $("#response1").val();
            var response2 = $("#response2").val();
            var password = $("#password").val();

            // validate the contract
            if (!contractID) {
                alert('There is a problem with this contract. Try again later.')
                return false;
            }

            // validate the first response exists
            if (!response1) {
                alert('You have not provided text for a valid step. Please enter some text.')
                return false;
            }

            // validate the first response
            if (response1.length < 1) {
                alert('You have not provided enough text for a valid step. Please enter more text.')
                return false;
            }

            // validate the first response
            if (response1.length > 1000) {
                alert('You have provided too much text for a valid step. Please enter less than 1000 characters.')
                return false;
            }

            <?php
            if ($contractFormat == "twosided") {
            ?>
            
            if (response2.length < 1) {
                alert('You have not provided enough text for a valid step in the second box. Please enter more text.')
                return false;
            }

            if (response2.length > 1000) {
                alert('You have provided too much text for a valid step in the second box. Please enter less than 1000 characters.')
                return false;
            }
        
            <?php
            } else {
            ?>

            // if second response exists, validate length
            if (response2) {

                if (response2.length < 1) {
                    alert('You have not provided enough text for a valid step in the second box. Please enter more text.')
                    return false;
                }

                if (response2.length > 1000) {
                    alert('You have provided too much text for a valid step in the second box. Please enter less than 1000 characters.')
                    return false;
                }
            }

            <?php } ?>

            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit the submit button again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // clear message, hide
            $( "#messageStep" ).fadeOut("slow");
            $( "#messageStep" ).text("");
                                 
            // set the post url
            var url = "./ajax/submitstep.php";
                                    
            // validate the first response exists
            if (response1) {

                // hide the button
                $( "#submitStep" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loadingStep" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: response1, field4: response2, field5: password} );
                <?php } else { ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: response1, field4: response2} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                                            
                    ajaxResult = Number(ajaxResult);                    
                    $( "#loadingStep" ).fadeOut("slow");		
                    $( "#submitStep" ).fadeIn("slow");			
                    $( "#messageStep" ).fadeIn("slow");
                    
                    // If no errors, 
                    switch(ajaxResult) {
                        case 1:                                                
                            $( "#submitStep" ).fadeOut("slow");	
                            $( '#messageStep' ).text("Success. Your step was submitted and now is awaiting a response!");
                            // go to match page                            
                            setTimeout(function(){ 
                                window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                    
                             }, 3000);                            
                            break;                                                
                        default:
                            $( '#messageStep' ).text("There was a problem submitting the step. Please try later.");                                                                        
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loadingStep" ).fadeOut("slow");
                    $( "#submitStep" ).fadeIn("slow");			
                    $( "#messageStep" ).fadeIn("slow");
                    $( '#messageStep' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#messageStep" ).fadeIn("slow");
                $( '#messageStep' ).text("There was a problem submitting the step. Please try later.");                                                                    
            }
            
            return false;

        });  	                                     

        // handle submit goal 
        $("#submitFinish").on( "click", function() {		
            
            // get the values
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                                
            var response1 = $("#response1").val();
            var goal = $("#goal").val();
            var password = $("#password").val();
            
            // set the contract flag to yes as default - only matters with very last step
            var contractFlag = "yes";

            // validate the contract
            if (!contractID) {
                alert('There is a problem with this contract. Try again later.')
                return false;
            }

            // validate the goal exists
            if (!goal) {
                alert('You have not provided text for a valid goal. Please enter some text.')
                return false;
            }

            // validate the goal
            if (goal.length < 1) {
                alert('You have not provided enough text for a valid goal. Please enter more text.')
                return false;
            }

            // validate the goal
            if (goal.length > 1000) {
                alert('You have provided too much text for a valid goal. Please enter less than 1000 characters.')
                return false;
            }

            <?php
            if ($contractType == "twosided") {
            ?>            
            if (!response1) {
                alert('You have not provided text for a valid step in the response box. Please enter more text.')
                return false;
            }

            if (response1.length < 1) {
                alert('You have not provided enough text for a valid step in the response box. Please enter more text.')
                return false;
            }

            if (response1.length > 1000) {
                alert('You have provided too much text for a valid step in the response box. Please enter less than 1000 characters.')
                return false;
            }
            <?php
            }
            ?>            
            
            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit the submit button again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // set the post url
            var url = "./ajax/submitfinish.php";
            
            // clear message, hide
            $( "#messageFinish" ).fadeOut("slow");
            $( "#messageFinish" ).text("");
                                                                                 
            <?php 
            if ($lastStep == "yes") {
            ?>
            // hide inputs, show button
            $( "#submitFinish" ).fadeOut("slow");			
            $( "#messageFinish" ).fadeOut("slow");
            $( "#contractText1" ).fadeOut("slow");
            $( "#contractText2" ).fadeOut("slow");
            $( "#goalText" ).fadeIn("slow");
            $( "#contractYes" ).fadeIn("slow");
            $( "#contractNo" ).fadeIn("slow");
            <?php
            } else {
            ?>                
                // hide the button
                $( "#submitFinish" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loadingFinish" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: goal, field4: response1, field5: password, field6: contractFlag} );
                <?php } else { ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: goal, field4: response1, field6: contractFlag} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                                            
                    ajaxResult = Number(ajaxResult);                    
                    $( "#loadingFinish" ).fadeOut("slow");		
                    $( "#submitFinish" ).fadeIn("slow");			
                    $( "#messageFinish" ).fadeIn("slow");
                    
                    // If no errors, 
                    switch(ajaxResult) {
                        case 1:                                                
                            $( "#submitFinish" ).fadeOut("slow");			
                            $( "#messageFinish" ).text("Success. Your goal was submitted!");                            
                            setTimeout(function(){ 
                                window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                    
                             }, 3000);                            
                            break;                                                
                        default:
                            $( '#messageFinish' ).text("There was a problem submitting the goal. Please try later.");                                                                        
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loadingFinish" ).fadeOut("slow");
                    $( "#submitFinish" ).fadeIn("slow");			
                    $( "#messageFinish" ).fadeIn("slow");
                    $( '#messageFinish' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			

            <?php   
            } 
            ?>
            return false;

        });  	                                     
        
        // handle submit goal for last step - takes either start new contract or close match 
        function submitFinishLast(contractFlag) {		
            
            // get the values
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                                
            var response1 = $("#response1").val();
            var goal = $("#goal").val();
            var password = $("#password").val();

            // validate the contract
            if (!contractID) {
                alert('There is a problem with this contract. Try again later.')
                return false;
            }

            // validate the goal exists
            if (!goal) {
                alert('You have not provided text for a valid goal. Please enter some text.')
                return false;
            }

            // validate the goal
            if (goal.length < 1) {
                alert('You have not provided enough text for a valid goal. Please enter more text.')
                return false;
            }

            // validate the goal
            if (goal.length > 1000) {
                alert('You have provided too much text for a valid goal. Please enter less than 1000 characters.')
                return false;
            }

            <?php
            if ($contractType == "twosided") {
            ?>            
            if (!response1) {
                alert('You have not provided text for a valid step in the response box. Please enter more text.')
                return false;
            }

            if (response1.length < 1) {
                alert('You have not provided enough text for a valid step in the response box. Please enter more text.')
                return false;
            }

            if (response1.length > 1000) {
                alert('You have provided too much text for a valid step in the response box. Please enter less than 1000 characters.')
                return false;
            }
            <?php
            }
            ?>            
            
            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit the submit button again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // clear message, hide
            $( "#messageFinish" ).fadeOut("slow");
            $( "#messageFinish" ).text("");
                                 
            // set the post url
            var url = "./ajax/submitfinish.php";
                                    
            // validate the goal exists
            if (goal) {

                // hide the button
                $( "#submitFinish" ).fadeOut("slow");			
                $( "#contractYes" ).fadeOut("slow");
                $( "#contractNo" ).fadeOut("slow");        
                            
                // show the loading spinner		
                $( "#loadingFinish" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: goal, field4: response1, field5: password, field6: contractFlag} );
                <?php } else { ?>
                var posting = $.post( url, { field1: contractID, field2: token, field3: goal, field4: response1, field6: contractFlag} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                                            
                    ajaxResult = Number(ajaxResult);                    
                    $( "#loadingFinish" ).fadeOut("slow");		
                    $( "#submitFinish" ).fadeIn("slow");			
                    $( "#contractYes" ).fadeIn("slow");
                    $( "#contractNo" ).fadeIn("slow");                        
                    $( "#messageFinish" ).fadeIn("slow");
                    
                    // If no errors, 
                    switch(ajaxResult) {
                        case 1:                                                                            
                            $( "#submitFinish" ).fadeOut("slow");			
                            $( "#contractYes" ).fadeOut("slow");
                            $( "#contractNo" ).fadeOut("slow");                        
                            $( '#messageFinish' ).text("Success. Your goal was submitted!");        
                            setTimeout(function(){ 
                                if (contractFlag == "yes") {
                                    window.location.href = "./selectcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>";
                                } else {
                                    window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                        
                                }
                             }, 3000);                                                                                                                                 
                            break;                                                
                        default:
                            $( '#messageFinish' ).text("There was a problem submitting the goal. Please try later.");                                                                        
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loadingFinish" ).fadeOut("slow");
                    $( "#submitFinish" ).fadeIn("slow");			
                    $( "#messageFinish" ).fadeIn("slow");
                    $( '#messageFinish' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#messageFinish" ).fadeIn("slow");
                $( '#messageFinish' ).text("There was a problem submitting the goal. Please try later.");                                                                    
            }
            
            return false;

        }  	                                     
        
        // handle the start new contract when finished        
        $("#contractYes").on( "click", function() {		        
            submitFinishLast("yes");                        
            return false;
        });
        
        // handle the closed contract when finished        
        $("#contractNo").on( "click", function() {		                    
            submitFinishLast("no");            
            return false;
        });

        // handle the ratings
        $('.starElement').mouseover(function(){
            obj = $(this);
            $('.starElement').removeClass('highlight-stars');
            $('.starElement').each(function(index){
                $(this).addClass('highlight-stars');
                if(index == $('.starElement').index(obj)){
                    return false;
                }
            });
        });

        $('.starElement').mouseleave(function(){
            $('.starElement').removeClass('highlight-stars');
        });

        $('.starElement').click(function(){
            obj = $(this);
            $('.starElement').each(function(index){
                $(this).addClass('highlight-stars');
                $('#duringRating').val((index+1));                
                $('#starMessage').text('Sending. Please wait.');                
                if(index == $('.starElement').index(obj)){
                    sendDuringStars(index + 1);
                    return false;
                }
            });
        });

        $('.starList').mouseleave(function(){
            if($('#duringRating').val()){
                $('.starElement').each(function(index){
                    $(this).addClass('highlight-stars');
                    if((index+1) == $('#duringRating').val()){
                        return false;
                    }
                });
            }
        });

        $('.afterElement').mouseover(function(){            
            obj2 = $(this);
            $('.afterElement').removeClass('after-stars');
            $('.afterElement').each(function(index2){                
                $(this).addClass('after-stars');                
                if(index2 == $('.afterElement').index(obj2)){
                    return false;
                }
            });
        });

        $('.afterElement').mouseleave(function(){            
            $('.afterElement').removeClass('after-stars');
        });

        $('.afterElement').click(function(){
            obj2 = $(this);
            $('.afterElement').each(function(index2){
                $(this).addClass('after-stars');
                $('#afterRating').val((index2+1));                
                $('#afterMessage').text('Sending. Please wait.');                
                if(index2 == $('.afterElement').index(obj2)){
                    sendAfterStars(index2 + 1);
                    return false;
                }
            });
        });

        $('.afterList').mouseleave(function(){
            if($('#afterRating').val()){
                $('.afterElement').each(function(index2){
                    $(this).addClass('after-stars');
                    if((index2+1) == $('#afterRating').val()){
                        return false;
                    }
                });
            }
        });

        // handle during rating star submission
        function sendDuringStars(rating) {
            
            // get the values
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                                           
            var ratingType = "during"; 
            
            // validate the rating
            if (!rating) {
                $('#starMessage').text("There's a problem with the rating. Try later.");
                return false;
            }

            // convert the rating to a number
            rating = Number(rating);

            // validate the rating is between 1 and 5
            if ((rating < 1) || (rating > 5)) {                
                $('#starMessage').text("There's a problem with the rating. Try later.");
                return false;
            }
                                                      
            // set the post url
            var url = "./ajax/submitrating.php";
                                    
            // validate the rating
            if (rating) {
                                                
                var posting = $.post( url, { field1: contractID, field2: token, field3: rating, field4: ratingType} );
                
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                                            
                    ajaxResult = Number(ajaxResult);                    
                    
                    // If no errors, 
                    switch(ajaxResult) {
                        case 1:                                                
                            $('#starMessage').text('Thanks! You have rated ' + rating + ' stars.');                            
                            break;                                                
                        case 2:                                                
                            $('#starMessage').text('You already sent a rating. Please wait 1 day.');                            
                            break;                                                
                        default:
                            $('#starMessage').text("There's a problem with the rating. Try later.");
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			                    
                    $('#starMessage').text("Your internet connection appears to be down. Try later.");                                                                        
                });			
                
            } else {                
                $('#starMessage').text("There's a problem with the rating. Try later.");
            }
            
            return false;

        }
        
        // handle after rating star submission
        function sendAfterStars(rating) {
            
            // get the values
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";                                                           
            var ratingType = "after"; 
            
            // validate the rating
            if (!rating) {
                $('#afterMessage').text("There's a problem with the rating. Try later.");
                return false;
            }

            // convert the rating to a number
            rating = Number(rating);

            // validate the rating is between 1 and 5
            if ((rating < 1) || (rating > 5)) {                
                $('#afterMessage').text("There's a problem with the rating. Try later.");
                return false;
            }
                                                      
            // set the post url
            var url = "./ajax/submitrating.php";
                                    
            // validate the rating
            if (rating) {
                                                
                var posting = $.post( url, { field1: contractID, field2: token, field3: rating, field4: ratingType} );
                
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                                                            
                    ajaxResult = Number(ajaxResult);                    
                    
                    // If no errors, 
                    switch(ajaxResult) {
                        case 1:                                                
                            $('#afterMessage').text('Thanks! You have rated ' + rating + ' stars.');                            
                            break;                                                
                        case 2:                                                
                            $('#afterMessage').text('You already sent a rating. Please wait 1 day.');                            
                            break;                                                
                        default:
                            $('#afterMessage').text("There's a problem with the rating. Try later.");
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			                    
                    $('#afterMessage').text("Your internet connection appears to be down. Try later.");                                                                        
                });			
                
            } else {                
                $('#afterMessage').text("There's a problem with the rating. Try later.");
            }
            
            return false;

        }

    });	

    </script>
	
  </body>
  
</html>
<?php
	
mysqli_close($mysqli);

?>