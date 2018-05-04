<?php

include('./ajax/authorization.php');

// set the counts
$matchCount = 0;
$newCount = 0;
$activeCount = 0;
$closedCount = 0;

// set the arrays
$matchIDArray = array();
$spotID1Array = array();
$spotID2Array = array();
$offer1Array = array();
$offer2Array = array();
$matchTypeArray = array();
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

// pull the matches where the user is involved
$sql = "SELECT matchID, spotID1, spotID2, offer1, offer2, matchType, status1, status2, rejectFlag, rejectID FROM matchTable WHERE (spotID1 = ? OR spotID2 = ?) AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
    // bind parameters for markers
    mysqli_stmt_bind_param($stmt, "iisss", $userID, $userID, $aliveFlag, $deleteFlag, $errorFlag);

    // execute query
    mysqli_stmt_execute($stmt);

    // store result to get num rows				
    $stmt->store_result();
            
    // get the number of rows
    $numRows = $stmt->num_rows;
                            
    if ($numRows < 1) {

       // no matches

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $matchIDBind, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $matchTypeBind, $status1Bind, $status2Bind, $rejectFlagBind, $rejectIDBind);
        
        // cycle through and get the values
        while ($stmt->fetch()) {

            $matchIDArray[$matchCount] = $matchIDBind;
            $spotID1Array[$matchCount] = $spotID1Bind;
            $spotID2Array[$matchCount] = $spotID2Bind;
            $offer1Array[$matchCount] = $offer1Bind;
            $offer2Array[$matchCount] = $offer2Bind;
            $matchTypeArray[$matchCount] = $matchTypeBind;
            $status1Array[$matchCount] = $status1Bind;
            $status2Array[$matchCount] = $status2Bind;
            $rejectFlagArray[$matchCount] = $rejectFlagBind;
            $rejectIDArray[$matchCount] = $rejectIDBind;
            
            // validate the data         
            if (is_numeric($matchIDArray[$matchCount])) {
                $matchIDArray[$matchCount] = (int) $matchIDArray[$matchCount];
            } else {
                $matchIDArray[$matchCount] = -1;
            }
            
            // validate the data         
            if (is_numeric($spotID1Array[$matchCount])) {
                $spotID1Array[$matchCount] = (int) $spotID1Array[$matchCount];
            } else {
                $spotID1Array[$matchCount] = -1;
            }

            // validate the data         
            if (is_numeric($spotID2Array[$matchCount])) {
                $spotID2Array[$matchCount] = (int) $spotID2Array[$matchCount];
            } else {
                $spotID2Array[$matchCount] = -1;
            }

            // validate the data         
            if (is_numeric($offer1Array[$matchCount])) {
                // do nothing
            } else {
                $offer1Array[$matchCount] = -1;
            }

            // validate the data         
            if (is_numeric($offer2Array[$matchCount])) {
                // do nothing
            } else {
                $offer2Array[$matchCount] = -1;
            }

            // validate the data         
            if (($matchTypeArray[$matchCount] == "work") || ($matchTypeArray[$matchCount] == "love") || ($matchTypeArray[$matchCount] == "friend")) {
                // do nothing
            } else {
                $matchTypeArray[$matchCount] = "work";
            }

            // validate the data         
            if (($status1Array[$matchCount] == "new") || ($status1Array[$matchCount] == "active") || ($status1Array[$matchCount] == "closed")) {
                // do nothing
            } else {
                $status1Array[$matchCount] = "closed";
            }
            
            // validate the data         
            if (($status2Array[$matchCount] == "new") || ($status2Array[$matchCount] == "active") || ($status2Array[$matchCount] == "closed")) {
                // do nothing
            } else {
                $status2Array[$matchCount] = "closed";
            }

            // validate the flags
            if (($rejectFlagArray[$matchCount] == "yes") || ($rejectFlagArray[$matchCount] == "no")) {
                // do nothing
            } else {
                $rejectFlagArray[$matchCount] = "no";
            }

            // set up the counts
            if ($userID == $spotID1Array[$matchCount]) {

                if ($status1Array[$matchCount] == "new") {
                    $newCount++;
                }

                // set up the counts
                if ($status1Array[$matchCount] == "active") {
                    $activeCount++;
                }

                // set up the counts
                if ($status1Array[$matchCount] == "closed") {
                    $closedCount++;
                }

            } else {

                if ($status2Array[$matchCount] == "new") {
                    $newCount++;
                }

                // set up the counts
                if ($status2Array[$matchCount] == "active") {
                    $activeCount++;
                }

                // set up the counts
                if ($status2Array[$matchCount] == "closed") {
                    $closedCount++;
                }

            }
            
            // set up colors
            if ($matchTypeArray[$matchCount] == "friend") {
                $colorArray[$matchCount] = "blue";
            } elseif ($matchTypeArray[$matchCount] == "love") {
                $colorArray[$matchCount] = "red";
            } else {
                $colorArray[$matchCount] = "black";
            }

            $matchCount++;

        }
    
        // close statement
        mysqli_stmt_close($stmt);
        
    }

}

// cycle through profile data
$i = 0;
while ($i < $matchCount) {

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

    <title>Matcheos - Matches</title>

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
                    <h3 class="page-header">Matches: New, Active, Closed</h3>
                    <br>
                </div>                                
            </div>
            <div class="row text-center">                
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-plus-circle fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($newCount)?> New</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-play-circle fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($activeCount)?> Active</h4>                    
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
                    <h3 class="page-header">New Matches:</h3>
                    <br>
                </div>                                
                <div class="col-lg-12">
                    <div id="newMatches">
                        <?php
                        if ($newCount == 0) { echo htmlspecialchars("You have no new matches.");}
                        // cycle through all matches only setting the new ones
                        $i = 0;                        
                        while ($i < $matchCount) {
                            // should be new in either spot1 or spot2, depending on where userID exists
                            if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "new")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "new"))) {                            
                        ?>
                        <div class="form-group row pGroup" id="nGroup<?=htmlspecialchars($i)?>">                        
                            <input type="hidden" id="newMatch<?=htmlspecialchars($i)?>" value="<?=htmlspecialchars($matchIDArray[$i])?>">
                            <div class="col-lg-2">                                     
                                <input class="form-control" id="newInputType<?=htmlspecialchars($i)?>" type="text" value="<?=ucfirst(htmlspecialchars($matchTypeArray[$i]))?>" disabled style="background-color:#d3d3d3;color:<?=htmlspecialchars($colorArray[$i])?>">
                            </div>                                                
                            <div class="col-lg-8">                 
                                <input class="form-control" id="newInput<?=htmlspecialchars($i)?>" type="text" value="<?=htmlspecialchars($firstNameArray[$i])?> from <?=htmlspecialchars($location1Array[$i])?>, <?=htmlspecialchars($location2Array[$i])?>, <?=htmlspecialchars($taglineArray[$i])?>" disabled>
                            </div>                                                
                            <div class="col-lg-2">                 
                                <button type="submit" class="btn btn-primary pButton" id="fetchN<?=htmlspecialchars($i)?>">View Match</button>                                   
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
                    <h3 class="page-header">Active Matches:</h3>
                    <br>
                </div>                                
                <div class="col-lg-12">
                    <div id="activeMatches">
                        <?php
                        if ($activeCount == 0) { echo htmlspecialchars("You have no active matches.");}
                        // cycle through active matches
                        $i = 0;                        
                        while ($i < $matchCount) {                            
                            // should be active in either spot1 or spot2, depending on where userID exists
                            if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "active")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "active"))) {                            
                        ?>
                        <div class="form-group row pGroup" id="aGroup<?=htmlspecialchars($i)?>">                        
                            <input type="hidden" id="activeMatch<?=htmlspecialchars($i)?>" value="<?=htmlspecialchars($matchIDArray[$i])?>">
                            <div class="col-lg-2">                                     
                                <input class="form-control" id="activeInputType<?=htmlspecialchars($i)?>" type="text" value="<?=ucfirst(htmlspecialchars($matchTypeArray[$i]))?>" disabled style="background-color:#d3d3d3;color:<?=htmlspecialchars($colorArray[$i])?>">
                            </div>                                                
                            <div class="col-lg-8">                 
                                <input class="form-control" id="activeInput<?=htmlspecialchars($i)?>" type="text" value="<?=htmlspecialchars($firstNameArray[$i])?> from <?=htmlspecialchars($location1Array[$i])?>, <?=htmlspecialchars($location2Array[$i])?>, <?=htmlspecialchars($taglineArray[$i])?>" disabled>
                            </div>                                                
                            <div class="col-lg-2">                 
                                <button type="submit" class="btn btn-primary pButton" id="fetchA<?=htmlspecialchars($i)?>">View Match</button>                                   
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
                    <h3 class="page-header">Closed Matches:</h3>
                    <br>
                </div>                                
                <div class="col-lg-12">
                    <div id="closedMatches">
                        <?php
                        if ($closedCount == 0) { echo htmlspecialchars("You have no closed matches.");}
                        // cycle through new matches
                        $i = 0;
                        while ($i < $matchCount) {
                            // should be closed in either spot1 or spot2, depending on where userID exists
                            if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "closed")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "closed"))) {                            
                        ?>
                        <div class="form-group row pGroup" id="cGroup<?=htmlspecialchars($i)?>">                        
                            <input type="hidden" id="closedMatch<?=htmlspecialchars($i)?>" value="<?=htmlspecialchars($matchIDArray[$i])?>">
                            <div class="col-lg-2">                                     
                                <input class="form-control" id="closedInputType<?=htmlspecialchars($i)?>" type="text" value="<?=ucfirst(htmlspecialchars($matchTypeArray[$i]))?>" disabled style="background-color:#d3d3d3;color:<?=htmlspecialchars($colorArray[$i])?>">
                            </div>                                                
                            <div class="col-lg-8">                 
                                <input class="form-control" id="closedInput<?=htmlspecialchars($i)?>" type="text" value="<?=htmlspecialchars($firstNameArray[$i])?> from <?=htmlspecialchars($location1Array[$i])?>, <?=htmlspecialchars($location2Array[$i])?>, <?=htmlspecialchars($taglineArray[$i])?>" disabled>
                            </div>                                                
                            <div class="col-lg-2">                 
                                <?php
                                if ($rejectFlagArray[$i] == "yes") {
                                ?>
                                <button type="submit" class="btn btn-primary pButton" id="fetchC<?=htmlspecialchars($i)?>">Match Refused</button>
                                <?php
                                } else {
                                ?>
                                <button type="submit" class="btn btn-primary pButton" id="fetchC<?=htmlspecialchars($i)?>">View Match</button>
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
            while ($i < $matchCount) {
            // should be new in either spot1 or spot2, depending on where userID exists
                if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "new")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "new"))) {                            
        ?>            
            $("#fetchN<?=htmlspecialchars($i)?>").on( "click", function() {		
                location.href = './viewmatch.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchIDArray[$i])?>';             
            });  	                                     
        <?php
                }
                $i++;
            }

        ?>

        <?php
            $i = 0;
            while ($i < $matchCount) {
                // should be active in either spot1 or spot2, depending on where userID exists
                if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "active")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "active"))) {                            
        ?>            
            $("#fetchA<?=htmlspecialchars($i)?>").on( "click", function() {		
                location.href = './contract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchIDArray[$i])?>';             
            });  	                                     
        <?php
                }
                $i++;
            }

        ?>

        <?php
            $i = 0;
            while ($i < $matchCount) {
                // should be closed in either spot1 or spot2, depending on where userID exists
                if ((($userID == $spotID1Array[$i]) && ($status1Array[$i] == "closed")) || (($userID == $spotID2Array[$i]) && ($status2Array[$i] == "closed"))) {                                                
                    ?>                    
            $("#fetchC<?=htmlspecialchars($i)?>").on( "click", function() {		
                location.href = './contract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchIDArray[$i])?>';             
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