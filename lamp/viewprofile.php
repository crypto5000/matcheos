<?php

include('./ajax/authorization.php');

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        

// pull the profile data for the user
$sql = "SELECT firstName, location1, location2, tagline, image, whyMeet FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
        $firstName = "";
        $location1 = "";
        $location2 = "";
        $tagline = "";
        $image = "";
        $whyMeet = "";

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $firstNameBind, $location1Bind, $location2Bind, $taglineBind, $imageBind, $whyMeetBind);

        // fetch the results
        $stmt->fetch();
        
        // set variables
        $firstName = $firstNameBind;
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
    
?>

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - View Profile</title>

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
            <!-- Item Row -->            
            <div class="form-group row pGroup" id="nGroup3">                                                    
                <div class="col-lg-4"></div>                                                
                <div class="col-lg-4">                 
                    <input class="form-control" id="title" type="text" value="Your Displayed Profile" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                </div>                        
                <div class="col-lg-4"></div>                                                
            </div>                                                                                           
            <div class="row">                           
                <div class="col-12">
                    <h3><?=htmlspecialchars($firstName)?> from <?=htmlspecialchars($location1)?>, <?=htmlspecialchars($location2)?></h3>                        
                    <img class="img-responsive whyMe" src="<?=htmlspecialchars($image)?>" style="width:50%;box-shadow: 3px 3px 5px #888888;">                      
                    <h4 style="margin-top:5px;" class="tagline"><?=htmlspecialchars($tagline)?></h4>
                    <hr class="intro-divider">          
                    <h4 style="margin-top:5px;" class="whyText">Why Should People Want to Meet You?</h4>
                    <h5 style="margin-top:5px;" class="whyMeet"><?=htmlspecialchars($whyMeet)?></h5>      
                    <button type="submit" class="btn btn-primary" id="editProfile" style="box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Edit Profile</h4></button>                    
                </div>                                                     
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
           
        // go to settings
        $("#editProfile").on( "click", function() {		
            location.href = './settings.php?v=<?=htmlspecialchars($loginCheck)?>';            
        });  	                                     
    });	

    </script>
	
  </body>
  
</html>
<?php
	
mysqli_close($mysqli);

?>  