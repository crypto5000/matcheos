<?php

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

// set the counts
$contractCount = 0;
$waitingCount = 0;
$openCount = 0;
$closedCount = 0;

// set the arrays
$contractIDArray = array();
$matchIDArray = array();
$spotID1Array = array();
$spotID2Array = array();
$offer1Array = array();
$offer2Array = array();
$contractTypeArray = array();
$contractGoalArray = array();
$contractFormatArray = array();
$contractStepsArray = array();
$contractDoneeArray = array();
$contractArbFeeArray = array();
$contractFeeArray = array();
$status1Array = array();
$status2Array = array();
$rejectFlagArray = array();
$rejectIDArray = array();
$colorArray = array();

$firstNameArray = array();
$location1Array = array();
$location2Array = array();
$taglineArray = array();

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        
$rejectFlag = "yes";

// pull the contracts where the match is involved
$sql = "SELECT contractID, matchID, spotID1, spotID2, offer1, offer2, contractType, contractGoal, contractFormat, contractSteps, contractDonee, contractArbFee, contractFee, status1, status2, rejectFlag, rejectID FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

       // no contracts - check below if create contract was abanonded and needs to be set up

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $contractIDBind, $matchIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $contractTypeBind, $contractGoalBind, $contractFormatBind, $contractStepsBind, $contractDoneeBind, $contractArbFeeBind, $contractFeeBind, $status1Bind, $status2Bind, $rejectFlagBind, $rejectIDBind);
        
        // cycle through and get the values
        while ($stmt->fetch()) {

            $contractIDArray[$contractCount] = $contractIDBind;
            $matchIDArray[$contractCount] = $matchIDBind;
            $spotID1Array[$contractCount] = $spotID1Bind;
            $spotID2Array[$contractCount] = $spotID2Bind;
            $offer1Array[$contractCount] = $offer1Bind;
            $offer2Array[$contractCount] = $offer2Bind;
            $contractTypeArray[$contractCount] = $contractTypeBind;
            $contractGoalArray[$contractCount] = $contractGoalBind;
            $contractFormatArray[$contractCount] = $contractFormatBind;
            $contractStepsArray[$contractCount] = $contractStepsBind;
            $contractDoneeArray[$contractCount] = $contractDoneeBind;
            $contractArbFeeArray[$contractCount] = $contractArbFeeBind;
            $contractFeeArray[$contractCount] = $contractFeeBind;
            $status1Array[$contractCount] = $status1Bind;
            $status2Array[$contractCount] = $status2Bind;
            $rejectFlagArray[$contractCount] = $rejectFlagBind;
            $rejectIDArray[$contractCount] = $rejectIDBind;
            
            // validate the data         
            if (is_numeric($contractIDArray[$contractCount])) {
                $contractIDArray[$contractCount] = (int) $contractIDArray[$contractCount];
            } else {
                $contractIDArray[$contractCount] = -1;
            }

            // validate the data         
            if (is_numeric($matchIDArray[$contractCount])) {
                $matchIDArray[$contractCount] = (int) $matchIDArray[$contractCount];
            } else {
                $matchIDArray[$contractCount] = -1;
            }
            
            // validate the data         
            if (is_numeric($spotID1Array[$contractCount])) {
                $spotID1Array[$contractCount] = (int) $spotID1Array[$contractCount];
            } else {
                $spotID1Array[$contractCount] = -1;
            }

            // validate the data         
            if (is_numeric($spotID2Array[$contractCount])) {
                $spotID2Array[$contractCount] = (int) $spotID2Array[$contractCount];
            } else {
                $spotID2Array[$contractCount] = -1;
            }

            // validate the data         
            if (is_numeric($offer1Array[$contractCount])) {
                // do nothing
            } else {
                $offer1Array[$contractCount] = -1;
            }

            // validate the data         
            if (is_numeric($offer2Array[$contractCount])) {
                // do nothing
            } else {
                $offer2Array[$contractCount] = -1;
            }

            // validate the data         
            if (($contractTypeArray[$contractCount] == "work") || ($contractTypeArray[$contractCount] == "love") || ($contractTypeArray[$contractCount] == "friend")) {
                // do nothing
            } else {
                $contractTypeArray[$contractCount] = "work";
            }

            // validate the data         
            if (is_numeric($contractStepsArray[$contractCount])) {
                $contractStepsArray[$contractCount] = (int) $contractStepsArray[$contractCount];
            } else {
                $contractStepsArray[$contractCount] = -1;
            }

            // validate the data         
            if (is_numeric($contractArbFeeArray[$contractCount])) {
                $contractArbFeeArray[$contractCount] = (int) $contractArbFeeArray[$contractCount];
            } else {
                $contractArbFeeArray[$contractCount] = -1;
            }

            // validate the data         
            if (is_numeric($contractFeeArray[$contractCount])) {
                $contractFeeArray[$contractCount] = (int) $contractFeeArray[$contractCount];
            } else {
                $contractFeeArray[$contractCount] = -1;
            }

            // validate the data         
            if (($status1Array[$contractCount] == "waiting") || ($status1Array[$contractCount] == "open") || ($status1Array[$contractCount] == "terminated") || ($status1Array[$contractCount] == "finished")  || ($status1Array[$contractCount] == "arbitration")) {
                // do nothing
            } else {
                $status1Array[$contractCount] = "finished";
            }
            
            // validate the data         
            if (($status2Array[$contractCount] == "waiting") || ($status2Array[$contractCount] == "open") || ($status2Array[$contractCount] == "terminated") || ($status2Array[$contractCount] == "finished")  || ($status2Array[$contractCount] == "arbitration")) {
                // do nothing
            } else {
                $status2Array[$contractCount] = "finished";
            }

            // validate the flags
            if (($rejectFlagArray[$contractCount] == "yes") || ($rejectFlagArray[$contractCount] == "no")) {
                // do nothing
            } else {
                $rejectFlagArray[$contractCount] = "no";
            }

            // set up the counts
            if ($userID == $spotID1Array[$contractCount]) {

                if ($status1Array[$contractCount] == "waiting") {
                    $waitingCount++;
                }

                // set up the counts
                if ($status1Array[$contractCount] == "open") {
                    $openCount++;
                }

                // set up the counts
                if (($status1Array[$contractCount] == "finished") || ($status1Array[$contractCount] == "terminated") || ($status1Array[$contractCount] == "arbitration"))  {
                    $status1Array[$contractCount] = "closed";
                    $closedCount++;
                }

            } else {

                if ($status2Array[$contractCount] == "waiting") {
                    $waitingCount++;
                }

                // set up the counts
                if ($status2Array[$contractCount] == "open") {
                    $openCount++;
                }

                // set up the counts
                if (($status2Array[$contractCount] == "finished") || ($status2Array[$contractCount] == "terminated") || ($status2Array[$contractCount] == "arbitration"))  {
                    $status2Array[$contractCount] = "closed";
                    $closedCount++;
                }

            }
            
            // set up colors
            if ($contractTypeArray[$contractCount] == "friend") {
                $colorArray[$contractCount] = "blue";
            } elseif ($contractTypeArray[$contractCount] == "love") {
                $colorArray[$contractCount] = "red";
            } else {
                $colorArray[$contractCount] = "black";
            }

            $contractCount++;

        }
    
        // close statement
        mysqli_stmt_close($stmt);
        
    }

}

// if there are no contracts, check if user abandoned after accepting match, but before accepting contract
if ($contractCount == 0) {

    // check that the match is active - if currently in limbo with no contract
    $sql = "SELECT spotID1, spotID2, status1, status2 FROM matchTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "iiisss", $userID, $userID, $matchID, $aliveFlag, $deleteFlag, $errorFlag);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
                
        // get the number of rows
        $numRows = $stmt->num_rows;
                                
        if ($numRows == 1) {

            // bind result variables
            mysqli_stmt_bind_result($stmt, $spotID1Bind, $spotID2Bind, $status1Bind, $status2Bind);
        
            // fetch the results
            $stmt->fetch();

            // set variables
            $spotID1 = $spotID1Bind;
            $spotID2 = $spotID2Bind;
            $status1 = $status1Bind;
            $status2 = $status2Bind;            
            
            // check which spot the user is in, and set the status            
            if ($spotID1 == $userID) {                                
                $statusCurrent = $status1;
                $statusOther = $status2;
            } else {
                $statusCurrent = $status2;
                $statusOther = $status1;
            }

            // check if the match is active for user, new for the other (abandoned before creating contract)
            if (($statusCurrent == "active") && ($statusOther == "new")) {

                // redirect to select a contract                
                header('Location: ./selectcontract.php?v='.$loginCheck.'&w='.$matchID);	                
                mysqli_stmt_close($stmt); 
                mysqli_close($mysqli);
                exit();

            } else {
            
                // redirect - invalid contracts for match - could be closed or rejected
                header('Location: ./match.php?v='.$loginCheck);	                
                mysqli_stmt_close($stmt); 
                mysqli_close($mysqli);
                exit();

            }
            
            // close statement
            mysqli_stmt_close($stmt);        
                
        } else {

            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'CONTRACT FAIL: INVALID MATCH WITHOUT A CONTRACT';
            $logging = "Only 1 matchID should be found at: ";
            $logging .= $createDate;
            $logging .= ' for matchID: '.$matchID;
            $logging .= ' for userID: '.$userID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

            // redirect - not valid
            header('Location: ./index.php?v=yes');	
            mysqli_close($mysqli);
            exit();

        }

    }

}

// cycle through profile data - should be the same profile for all contracts
$i = 0;
while ($i < $contractCount) {

    // set the current id of the other person - the spotID not equal to userID
    $tempID1 = $spotID1Array[$i];
    $tempID2 = $spotID2Array[$i];
    $currentID = $tempID1;
    if ($tempID1 == $userID) {
        $currentID = $tempID2;
    }

    // pull the profile data for the current ID
    $sql = "SELECT firstName, location1, location2, tagline FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

    if ($stmt = mysqli_prepare($mysqli, $sql)) {
                    
        // bind parameters for markers
        mysqli_stmt_bind_param($stmt, "isss", $currentID, $aliveFlag, $deleteFlag, $errorFlag);

        // execute query
        mysqli_stmt_execute($stmt);

        // store result to get num rows				
        $stmt->store_result();
                
        // get the number of rows
        $numRows = $stmt->num_rows;
                                
        if ($numRows < 1) {

            // no profile found            
            $firstNameArray[$i] = "";
            $location1Array[$i] = "";
            $location2Array[$i] = "";
            $taglineArray[$i] = "";

        } else {
            
            // bind result variables
            mysqli_stmt_bind_result($stmt, $firstNameBind, $location1Bind, $location2Bind, $taglineBind);

            // fetch the results
            $stmt->fetch();
            
            // set variables
            $firstNameArray[$i] = $firstNameBind;
            $location1Array[$i] = $location1Bind;
            $location2Array[$i] = $location2Bind;
            $taglineArray[$i] = $taglineBind;

            // close statement
            mysqli_stmt_close($stmt);
      
        }

    }
    
    $i++;
}

?>
<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - Contracts</title>

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

  <body>

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
                <a class="nav-link" href="./analytics.php?v=<?=htmlspecialchars($loginCheck)?>">Analytics</a>
            </li>          
            <li class="nav-item">
                <a class="nav-link" href="./settings.php?v=<?=htmlspecialchars($loginCheck)?>">Settings</a>
            </li>          
        </ul>
      </div>
    </nav>

    <!-- Header -->
    <header class="intro-header">
      <div class="container">
                        
            <div class="row text-center">
                <div class="col-lg-12">
                    <h3 class="page-header">Contracts <?php if (isset($firstNameArray[0])) {echo htmlspecialchars("with ".$firstNameArray[0]);}?>: New, Active, Closed</h3>
                    <br>
                </div>                                
            </div>
            <div class="row text-center">                
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-plus-circle fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($waitingCount)?> New</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-play-circle fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($openCount)?> Active</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-stop-circle fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($closedCount)?> Closed</h4>                    
                </div>
            </div>
            <hr class="intro-divider">          
            <div class="row text-center" style="margin-top:20px">                
                <div class="col-lg-12">
                    <h3 class="page-header">New Contracts:</h3>
                    <br>
                </div>                                
                <div class="col-lg-12">
                    <div id="newMatches">
                        <?php
                        if ($waitingCount == 0) { echo htmlspecialchars("You have no new contracts with this match.");}
                        // cycle through all contracts only setting the new ones
                        $i = 0;
                        while ($i < $contractCount) {          
                            // should be waiting in either spot1 or spot2, depending on where userID exists
                            if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "waiting")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "waiting"))) {                            
                        ?>
                        <div class="form-group row pGroup" id="nGroup<?=htmlspecialchars($i)?>">                        
                            <input type="hidden" id="newContract<?=htmlspecialchars($i)?>" value="<?=htmlspecialchars($contractIDArray[$i])?>">
                            <div class="col-lg-2">                                     
                                <input class="form-control" id="newInputType<?=htmlspecialchars($i)?>" type="text" value="<?=ucfirst(htmlspecialchars($contractTypeArray[$i]))?>" disabled style="background-color:#d3d3d3;color:<?=htmlspecialchars($colorArray[$i])?>">
                            </div>                                                
                            <div class="col-lg-8">                 
                                <input class="form-control" id="newInput<?=htmlspecialchars($i)?>" type="text" value="<?=htmlspecialchars($firstNameArray[$i])?> from <?=htmlspecialchars($location1Array[$i])?>, <?=htmlspecialchars($location2Array[$i])?>, <?=htmlspecialchars($taglineArray[$i])?>" disabled>
                            </div>                                                
                            <div class="col-lg-2">                 
                                <button type="submit" class="btn btn-primary pButton" id="fetchN<?=htmlspecialchars($i)?>">View Contract</button>                                   
                            </div>                                                
                        </div>                  
                        <?php
                            }
                            $i++;
                        }
                        ?>                  
                    </div>
        
                </div>

                <hr class="intro-divider">          
                <div class="col-lg-12">
                    <h3 class="page-header">Active Contracts:</h3>
                    <br>
                </div>                                
                <div class="col-lg-12">
                    <div id="activeMatches">
                        <?php
                        if ($openCount == 0) { echo htmlspecialchars("You have no active contracts with this match.");}
                        // cycle through active matches
                        $i = 0;                        
                        while ($i < $contractCount) {
                            // should be open in either spot1 or spot2, depending on where userID exists
                            if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "open")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "open"))) {                            
                        ?>
                        <div class="form-group row pGroup" id="aGroup<?=htmlspecialchars($i)?>">                        
                            <input type="hidden" id="activeContract<?=htmlspecialchars($i)?>" value="<?=htmlspecialchars($contractIDArray[$i])?>">
                            <div class="col-lg-2">                                     
                                <input class="form-control" id="activeInputType<?=htmlspecialchars($i)?>" type="text" value="<?=ucfirst(htmlspecialchars($contractTypeArray[$i]))?>" disabled style="background-color:#d3d3d3;color:<?=htmlspecialchars($colorArray[$i])?>">
                            </div>                                                
                            <div class="col-lg-8">                 
                                <input class="form-control" id="activeInput<?=htmlspecialchars($i)?>" type="text" value="<?=htmlspecialchars($firstNameArray[$i])?> from <?=htmlspecialchars($location1Array[$i])?>, <?=htmlspecialchars($location2Array[$i])?>, <?=htmlspecialchars($taglineArray[$i])?>" disabled>
                            </div>                                                
                            <div class="col-lg-2">                 
                                <button type="submit" class="btn btn-primary pButton" id="fetchA<?=htmlspecialchars($i)?>">View Contract</button>                                   
                            </div>                                                
                        </div>                  
                        <?php
                            }
                            $i++;
                        }
                        ?>                  
                    </div>
        
                </div>

                <hr class="intro-divider">          
                <div class="col-lg-12">
                    <h3 class="page-header">Closed Contracts:</h3>
                    <br>
                </div>                                
                <div class="col-lg-12">
                    <div id="closedMatches">
                        <?php
                        if ($closedCount == 0) { echo htmlspecialchars("You have no closed contracts with this match.");}
                        // cycle through new matches
                        $i = 0;
                        while ($i < $contractCount) {
                            // should be closed in either spot1 or spot2, depending on where userID exists
                            if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "closed")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "closed"))) {                            
                        ?>
                        <div class="form-group row pGroup" id="cGroup<?=htmlspecialchars($i)?>">                        
                            <input type="hidden" id="closedContract<?=htmlspecialchars($i)?>" value="<?=htmlspecialchars($contractIDArray[$i])?>">
                            <div class="col-lg-2">                                     
                                <input class="form-control" id="closedInputType<?=htmlspecialchars($i)?>" type="text" value="<?=ucfirst(htmlspecialchars($contractTypeArray[$i]))?>" disabled style="background-color:#d3d3d3;color:<?=htmlspecialchars($colorArray[$i])?>">
                            </div>                                                
                            <div class="col-lg-8">                 
                                <input class="form-control" id="closedInput<?=htmlspecialchars($i)?>" type="text" value="<?=htmlspecialchars($firstNameArray[$i])?> from <?=htmlspecialchars($location1Array[$i])?>, <?=htmlspecialchars($location2Array[$i])?>, <?=htmlspecialchars($taglineArray[$i])?>" disabled>
                            </div>                                                
                            <div class="col-lg-2">                 
                                <?php
                                if ($rejectFlagArray[$i] == "yes") {
                                ?>
                                <button type="submit" class="btn btn-primary pButton" id="fetchC<?=htmlspecialchars($i)?>">Contract Rejected</button>
                                <?php
                                } else {
                                ?>
                                <button type="submit" class="btn btn-primary pButton" id="fetchC<?=htmlspecialchars($i)?>">View Contract</button>
                                <?php
                                }
                                ?>
                            </div>                                                
                        </div>                  
                        <?php
                            }
                            $i++;
                        }
                        ?>                  
                    </div>
        
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
        
        <?php
            $i = 0;
            while ($i < $contractCount) {
            // should be waiting in either spot1 or spot2, depending on where userID exists
                if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "waiting")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "waiting"))) {                            
        ?>            
            $("#fetchN<?=htmlspecialchars($i)?>").on( "click", function() {		
                location.href = './acceptcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>';             
            });  	                                     
        <?php
                }
                $i++;
            }

        ?>

        <?php
            $i = 0;
            while ($i < $contractCount) {
                // should be open in either spot1 or spot2, depending on where userID exists
                if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "open")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "open"))) {                            
        ?>            
            $("#fetchA<?=htmlspecialchars($i)?>").on( "click", function() {		
                location.href = './viewsteps.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($contractIDArray[$i])?>';             
            });  	                                     
        <?php
                }
                $i++;
            }

        ?>

        <?php
            $i = 0;
            while ($i < $contractCount) {
                // should be closed in either spot1 or spot2, depending on where userID exists
                if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "closed")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "closed"))) {                                                                    
        ?>
            $("#fetchC<?=htmlspecialchars($i)?>").on( "click", function() {		
                location.href = './viewsteps.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($contractIDArray[$i])?>';             
            });  	                                     
        <?php
                }
                $i++;
            }

        ?>        
                
    });	

    </script>
	
  </body>
  
</html>
<?php
	
mysqli_close($mysqli);

?>