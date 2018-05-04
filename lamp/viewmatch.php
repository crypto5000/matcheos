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
      $subject = 'MATCH FAIL: NO DATA FOUND ON PAGE';
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
            // invalid ids
            $invalidFlag = "yes";
        }

        if (($status1 == "new") || ($status1 == "active") || ($status1 == "closed")) {
            // do nothing
        } else {
            // invalid ids
            $invalidFlag = "yes";
        }

        if (($status2 == "new") || ($status2 == "active") || ($status2 == "closed")) {
            // do nothing
        } else {
            // invalid ids
            $invalidFlag = "yes";
        }

        if ($rejectFlag == "no") {
            // do nothing
        } else {
            // invalid ids
            $invalidFlag = "yes";
        }

        if ($invalidFlag == "yes") {
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'MATCH FAIL: INVALID DATA FOUND ON PAGE';
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

        // set the other person's id, user's offer, user's status
        if ($spotID1 == $userID) {
            $offerID = $spotID2;
            $offer = $offer1;
            $status = $status1;
        } else {
            $offerID = $spotID1;
            $offer = $offer2;
            $status = $status2;
        }        

        // close statement
        mysqli_stmt_close($stmt);        
        
    }

}

// check that status is new (that match has not been accepted or rejected, closed, or active)
if ($status != "new") {

    // pull contractID
    $sql = "SELECT contractID FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
        $subject = 'MATCH FAIL: NO CONTRACT DATA FOUND ON PAGE';
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
            mysqli_stmt_bind_result($stmt, $contractIDBind);
        
            // fetch the results
            $stmt->fetch();

            // set variables
            $contractID = $contractIDBind;

            // close statement
            mysqli_stmt_close($stmt);        

        }
        
    }

    // if contract exists, go to contract page
    if (is_numeric($contractID)) {

        // redirect 
        header('Location: ./contract.php?v='.$loginCheck.'&w='.$matchID);	
        exit();

    } else {
                
        // else go to match page
        header('Location: ./match.php?v='.$loginCheck);	
        exit();

    }

}

// pull profile data for other person
$sql = "SELECT firstName, location1, location2, tagline, image, whyMeet FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

        // no profile found            
        $to = ADMINEMAIL;  
        $subject = 'MATCH FAIL: NO PROFILE DATA FOUND ON PAGE';
        $logging = "No user profile found at: ";
        $logging .= $createDate;
        $logging .= ' for matchID: '.$matchID;
        $logging .= ' for offerID: '.$offerID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect - not valid
        header('Location: ./index.php?v=yes');	
        exit();

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

// pull ratings for match
$duringRating = 0;
$duringRatingCount = 0;
$duringRatingCountDisplay = "No Reviews";
$afterRating = 0;
$afterRatingCount = 0;
$afterRatingCountDisplay = "No Reviews";

// pull the ratings where the offer person is involved - can be any type
$sql = "SELECT rating, ratingType FROM ratingTable WHERE userIDRated = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

       // no ratings

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $ratingBind, $ratingTypeBind);
        
        // cycle through and get the values
        while ($stmt->fetch()) {

            // validate the data         
            if (is_numeric($ratingBind)) {
              
                $ratingBind = (int) $ratingBind;
                if ($ratingTypeBind == "during") {            
                    $duringRating = $duringRating + $ratingBind;
                    $duringRatingCount++;
                } else {
                    $afterRating = $afterRating + $ratingBind;
                    $afterRatingCount++;
                }

            }             

        }
    
        // close statement
        mysqli_stmt_close($stmt);
        
    }

}

// set the final ratings
if ($duringRatingCount > 0) {
    $duringRating = $duringRating / $duringRatingCount;
    $duringRatingCountDisplay = $duringRatingCount." Reviews";
}

if ($duringRatingCount == 1) {
    $duringRatingCountDisplay = "1 Review";
}

// set the final ratings
if ($afterRatingCount > 0) {
    $afterRating = $afterRating / $afterRatingCount;
    $afterRatingCountDisplay = $afterRatingCount." Reviews";
}

if ($afterRatingCount == 1) {
    $afterRatingCountDisplay = "1 Review";
}

// set contract history
$contractHistory = "new";
$rejectHistory = "no";

// check contract history for this match
$sql = "SELECT contractID FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND rejectFlag = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
    // bind parameters for markers
    mysqli_stmt_bind_param($stmt, "iiissss", $userID, $userID, $matchID, $rejectHistory, $aliveFlag, $deleteFlag, $errorFlag);

    // execute query
    mysqli_stmt_execute($stmt);

    // store result to get num rows				
    $stmt->store_result();
            
    // get the number of rows
    $numRows = $stmt->num_rows;

    if ($numRows < 1) {

        // do nothing - new

    } elseif ($numRows == 1) {

        // 1 contract
        $contractHistory = "one";

    } else {
        
        // more than 1 contract
        $contractHistory = "many";

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

    <title>Matcheos - View Match</title>

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
    
    <style>
      .starList {
        margin: 0;
        padding: 0;
      }      
      .starElement {
          font-size: 30px;
          color: #F0F0F0;
          display: inline-block;
          text-shadow: 0 0 1px #666666;
      }      
      .starMessage {
          color: #A6A6A6;
          font-style: italic;
      }
      .highlight-stars {
          color: #F4B30A;
          text-shadow: 0 0 1px #F4B30A;
      }
    </style>
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
                    <input class="form-control" id="offer" type="text" value="<?=ucfirst(htmlspecialchars($matchType))?> Match: <?=number_format(htmlspecialchars($offer),4)?> EOS" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                </div>                        
                <div class="col-lg-4"></div>                                                
            </div>                                                                                           
            <div class="row">                           
                <div class="col-12">
                    <h3><?=htmlspecialchars($firstName)?> from <?=htmlspecialchars($location1)?>, <?=htmlspecialchars($location2)?></h3>                        
                    <img class="img-responsive whyMe" src="<?=htmlspecialchars($image)?>" style="width:50%;box-shadow: 3px 3px 5px #888888;">                      
                    <h4 style="margin-top:5px;" class="tagline"><?=htmlspecialchars($tagline)?></h4>
                    <hr class="intro-divider">          
                    <h4 style="margin-top:5px;" class="whyMeetText">Why Should People Want to Meet You?</h4>
                    <h5 style="margin-top:5px;" class="whyMeet"><?=htmlspecialchars($whyMeet)?></h5>      
                    <input type="password" name="password" id="password" placeholder="Enter Password to Confirm" style="display:none"/>
                    <button type="submit" class="btn btn-primary" id="acceptMatch" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Accept</h4></button>
                    <button type="submit" class="btn btn-primary" id="rejectMatch" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Reject</h4></button>           
                    <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                    <div id="message" class='alert alert-info' style="display:none"></div>
                </div>                                                     
            </div>              
            <div class="form-group row pGroup" id="nGroup4" style="margin-top:20px">                                                                
                <div class="col-lg-3"></div>
                <div class="col-lg-3">
                    <ul class="starList">
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($duringRating >= 1) {echo htmlspecialchars('highlight-stars');}?>"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($duringRating >= 2) {echo htmlspecialchars('highlight-stars');}?>"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($duringRating >= 3) {echo htmlspecialchars('highlight-stars');}?>"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($duringRating >= 4) {echo htmlspecialchars('highlight-stars');}?>"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($duringRating >= 5) {echo htmlspecialchars('highlight-stars');}?>"></i></li>
                    </ul>
                    <div class="starMessage" name="starMessage">During Contract: <?=htmlspecialchars($duringRatingCountDisplay)?></div>                                    
                </div>                                                
                <div class="col-lg-3">
                    <ul class="starList">
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($afterRating >= 1) {echo htmlspecialchars('highlight-stars');}?>"></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($afterRating >= 2) {echo htmlspecialchars('highlight-stars');}?>" ></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($afterRating >= 3) {echo htmlspecialchars('highlight-stars');}?>" ></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($afterRating >= 4) {echo htmlspecialchars('highlight-stars');}?>" ></i></li>
                        <li class="starElement"><i class="fa fa-star fa-fw <?php if ($afterRating >= 5) {echo htmlspecialchars('highlight-stars');}?>" ></i></li>
                    </ul>
                    <div class="starMessage" name="starMessage">After Contract: <?=htmlspecialchars($afterRatingCountDisplay)?></div>                                    
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
           
        // set the match type
        var matchType = "<?=htmlspecialchars($matchType)?>";

        // go to select by type
        $("#acceptMatch").on( "click", function() {		                        
            
            // get the values
            var matchID = <?=htmlspecialchars($matchID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";
            
            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                 
            // set the post url
            var url = "./ajax/acceptmatch.php";
                                    
            // validate the id exists
            if (matchID) {

                // hide the button
                $( "#acceptMatch" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                
                // Send the data using post 
                var posting = $.post( url, { field1: matchID, field2: token} );

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		
                    $( "#acceptMatch" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            <?php
                            // if new, go to select a contract
                            if ($contractHistory == "new") {
                            ?>                            
                            window.location.href = './selectcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>';                                                                    
                            <?php
                            // if only one, go to accept the contract
                            } elseif ($contractHistory == "one") {
                            ?>
                            window.location.href = './acceptcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>';                                                                    
                            <?php
                            } elseif ($contractHistory == "many") {
                            ?>
                            window.location.href = './contract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>';                                                                    
                            <?php
                            }
                            ?>                            
                            break;                        
                        default:
                            $( '#message' ).text("There was a problem with the submission. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#acceptMatch" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem with the submission. Please try later.");                                                                    
            }
            
            return false;
        });  	                                     
        
        // handle the reject match
        $("#rejectMatch").on( "click", function() {		

            // get the values
            var matchID = <?=htmlspecialchars($matchID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";
            var password = $("#password").val();

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
            var url = "./ajax/rejectmatch.php";
                                    
            // validate the id exists
            if (matchID) {

                // hide the button
                $( "#rejectMatch" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: matchID, field2: token, field3: password} );
                <?php } else { ?>
                var posting = $.post( url, { field1: matchID, field2: token} );
                <?php }  ?>
                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		
                    $( "#rejectMatch" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");

                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // go to match page
                            window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                    
                            break;                        
                        default:
                            $( '#message' ).text("There was a problem with the submission. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#rejectMatch" ).fadeIn("slow");			
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