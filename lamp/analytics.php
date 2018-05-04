<?php

include('./ajax/authorization.php');

// set the defaults
$profileViews = 0;
$newMatchesLove = 0;
$newMatchesWork = 0;
$newMatchesFriend = 0;
$sentOffersLove = 0;
$sentOffersWork = 0;
$sentOffersFriend = 0;
$receivedOffersLove = 0;
$receivedOffersWork = 0;
$receivedOffersFriend = 0;
$sentOffersLoveEOS = 0.0000;
$sentOffersWorkEOS = 0.0000;
$sentOffersFriendEOS = 0.0000;

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        

// pull the current analytics
$sql = "SELECT profileViews, newMatchesLove, newMatchesWork, newMatchesFriend, sentOffersLove, sentOffersWork, sentOffersFriend, sentOffersLoveEOS, sentOffersWorkEOS, sentOffersFriendEOS, receivedOffersLove, receivedOffersWork, receivedOffersFriend FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
      $subject = 'ANALYTICS FAIL: NO DATA FOUND ON ANALYTICS PAGE';
      $logging = "No user analytics found at: ";
      $logging .= $createDate;
      $logging .= ' for userID: '.$userID;
      $logging .= ' for ipAddress: '.$ipAddress;			      
      $header = 'From: donotrespond@matcheos.com';
      if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

      // redirect - not valid
	  header('Location: ./index.php?v=yes');	
	  exit();

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $profileViewsBind, $newMatchesLoveBind, $newMatchesWorkBind, $newMatchesFriendBind, $sentOffersLoveBind, $sentOffersWorkBind, $sentOffersFriendBind, $sentOffersLoveEOSBind, $sentOffersWorkEOSBind, $sentOffersFriendEOSBind, $receivedOffersLoveBind, $receivedOffersWorkBind, $receivedOffersFriendBind);
    
        // fetch the results
        $stmt->fetch();

        // set variables
        $profileViews = $profileViewsBind;
        $newMatchesLove = $newMatchesLoveBind;
        $newMatchesWork = $newMatchesWorkBind;
        $newMatchesFriend = $newMatchesFriendBind;
        $sentOffersLove = $sentOffersLoveBind;
        $sentOffersWork = $sentOffersWorkBind;
        $sentOffersFriend = $sentOffersFriendBind;
        $sentOffersLoveEOS = $sentOffersLoveEOSBind;
        $sentOffersWorkEOS = $sentOffersWorkEOSBind;
        $sentOffersFriendEOS = $sentOffersFriendEOSBind;
        $receivedOffersLove = $receivedOffersLoveBind;
        $receivedOffersWork = $receivedOffersWorkBind;
        $receivedOffersFriend = $receivedOffersFriendBind;

        // validate the variables 
        if (is_numeric($profileViews)) {
            $profileViews = (int) $profileViews;
        } else {
            $profileViews = 0;
        }

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

        // validate the variables 
        if (is_numeric($receivedOffersLove)) {
            $receivedOffersLove = (int) $receivedOffersLove;
        } else {
            $receivedOffersLove = 0;
        }

        // validate the variables 
        if (is_numeric($receivedOffersWork)) {
            $receivedOffersWork = (int) $receivedOffersWork;
        } else {
            $receivedOffersWork = 0;
        }

        // validate the variables 
        if (is_numeric($receivedOffersFriend)) {
            $receivedOffersFriend = (int) $receivedOffersFriend;
        } else {
            $receivedOffersFriend = 0;
        }

        // close statement
        mysqli_stmt_close($stmt);
        
    }

}

?>
<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - Analytics</title>

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
            <a class="nav-link" href="./match.php?v=<?=htmlspecialchars($loginCheck)?>">Matches</a>
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
                    <h3 class="page-header">Analytics: Views, Offers, and Matches</h3>
                    <br>
                </div>                                
            </div>
            <div class="form-group row pGroup" id="nGroup3">                                                    
                <div class="col-lg-4">                                     
                </div>                                                
                <div class="col-lg-4">                 
                    <input class="form-control" id="profileViews" type="text" value="Total Views of My Profile: <?=htmlspecialchars($profileViews)?>" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                </div>                        
                <div class="col-lg-4">                                                 
                </div>                                                
            </div>                                                
            <div class="row text-center">                
                <div class="col-lg-12">
                    <h4 class="page-header" style="text-align:left">Received Offers:</h4>
                </div>                                
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-heart fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($receivedOffersLove)?> Love</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-address-card fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($receivedOffersWork)?> Work</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-thumbs-up fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($receivedOffersFriend)?> Friends</h4>                    
                </div>
            </div>
            <hr class="intro-divider">          
            <div class="row text-center" style="margin-top:20px">                
                <div class="col-lg-12">
                    <h4 class="page-header" style="text-align:left">Sent Offers:</h4>
                </div>                                
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-heart fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($sentOffersLove)?> Love</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-address-card fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($sentOffersWork)?> Work</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-thumbs-up fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($sentOffersFriend)?> Friends</h4>                    
                </div>
            </div>

            <div class="row text-center" style="margin-top:20px">                
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-heart fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=number_format(htmlspecialchars($sentOffersLoveEOS),4)?> EOS</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-address-card fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=number_format(htmlspecialchars($sentOffersWorkEOS),4)?> EOS</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-thumbs-up fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=number_format(htmlspecialchars($sentOffersFriendEOS),4)?> EOS</h4>                    
                </div>
            </div>
            <hr class="intro-divider">          
            <div class="row text-center" style="margin-top:20px">                
                <div class="col-lg-12">
                    <h4 class="page-header" style="text-align:left">New Matches:</h4>
                </div>                                
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-heart fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($newMatchesLove)?> Love</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-address-card fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($newMatchesWork)?> Work</h4>                    
                </div>
                <div class="col-md-4">
                    <span class="fa-stack fa-4x">
                        <i class="fa fa-circle fa-stack-2x text-primary"></i>
                        <i class="fa fa-thumbs-up fa-stack-1x fa-inverse"></i>
                    </span>
                    <h4 class="service-heading"><?=htmlspecialchars($newMatchesFriend)?> Friends</h4>                    
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

    });	

    </script>
	
  </body>
  
</html>
<?php
	
mysqli_close($mysqli);

?>