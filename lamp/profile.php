<?php

include('./ajax/authorization.php');

// set the defaults
$currentMatches = "0";
$profileViews = "0";

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        

// pull current matches, views from analytics
$sql = "SELECT profileViews, newMatchesLove, newMatchesWork, newMatchesFriend FROM analyticsTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
  
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
      $subject = 'ANALYTICS FAIL: NO DATA FOUND';
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
      mysqli_stmt_bind_result($stmt, $profileViewsBind, $newMatchesLoveBind, $newMatchesWorkBind, $newMatchesFriendBind);							
      
      // fetch the results
      $stmt->fetch();
                      
      // set variables
      $profileViews = $profileViewsBind;
      $newMatchesLove = $newMatchesLoveBind;
      $newMatchesWork = $newMatchesWorkBind;
      $newMatchesFriend = $newMatchesFriendBind;

      // convert to int
      if (is_numeric($profileViews)) {                
        $profileViews = (int) $profileViews;            
      } else {
        $profileViews = 0;
      } 

      // convert to int
      if (is_numeric($newMatchesLove)) {                
        $newMatchesLove = (int) $newMatchesLove;            
      } else {
        $newMatchesLove = 0;
      }

      // convert to int
      if (is_numeric($newMatchesWork)) {                
        $newMatchesWork = (int) $newMatchesWork;            
      } else {
        $newMatchesWork = 0;
      }

      // convert to int
      if (is_numeric($newMatchesFriend)) {                
        $newMatchesFriend = (int) $newMatchesFriend;            
      } else {
        $newMatchesFriend = 0;
      }

      // sum up for total new matches
      $currentMatches = $newMatchesLove + $newMatchesWork + $newMatchesFriend; 

      // close statement
      mysqli_stmt_close($stmt);

  }
  
}

// set the arrays
$pullCount = 0;
$userIDArray = array();
$firstNameArray = array();
$location1Array = array();
$location2Array = array();
$taglineArray = array();
$imageArray = array();
$whyMeetArray = array();

// set up the final shuffled arrays
$userIDArrayFinal = [];
$firstNameArrayFinal = [];
$location1ArrayFinal = [];
$location2ArrayFinal = [];
$taglineArrayFinal = [];
$imageArrayFinal = [];
$whyMeetArrayFinal = [];

// pull the first batch of users to display (chunk of 10 users, with offset of 0, ordered by the latest created, not current user)
$offset = 0;
$sql = "SELECT userID, firstName, location1, location2, tagline, image, whyMeet FROM profileTable WHERE userID <> ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ? ORDER BY createDate DESC LIMIT 0, 10";

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
    $subject = 'PROFILE FAIL: NO DATA FOUND';
    $logging = "No profiles found at: ";
    $logging .= $createDate;
    $logging .= ' for userID: '.$userID;
    $logging .= ' for ipAddress: '.$ipAddress;			      
    $header = 'From: donotrespond@matcheos.com';
    if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

    // redirect - not valid
    header('Location: ./index.php?v=yes');	
    exit();
    
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

?>

<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - Browse and Make Offers</title>
      
    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/fullslider.css" rel="stylesheet">    
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

  <body class="blueBack">

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
      <div class="container">
        <a class="navbar-brand" href="./index.php">Matcheos</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item active">
              <?php if ($currentMatches == 0) { ?>
              <a class="nav-link" id="matchCount" href="./match.php?v=<?=htmlspecialchars($loginCheck)?>">Matches</a>
              <?php } else { ?>
              <a class="nav-link" id="matchCount" href="./match.php?v=<?=htmlspecialchars($loginCheck)?>"><?=htmlspecialchars($currentMatches)?> New</a>
              <?php } ?>
            </li>            
            <li class="nav-item active">
              <a class="nav-link" id="profileCount" href="./analytics.php?v=<?=htmlspecialchars($loginCheck)?>"><?=htmlspecialchars($profileViews)?> Views</a>
            </li>            
            <li class="nav-item active">
              <a class="nav-link" href="./analytics.php?v=<?=htmlspecialchars($loginCheck)?>">Analytics</a>
            </li>            
            <li class="nav-item active">
              <a class="nav-link" href="./settings.php?v=<?=htmlspecialchars($loginCheck)?>">Settings</a>
            </li>            
          </ul>
        </div>
      </div>
    </nav>
    
    <header>      
      <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="false" data-pause="hover">                
        <ol class="carousel-indicators">
          <div class="row text-center" style="margin-bottom:5px;">            
              <button type="submit" class="btn btn-primary" id="loveBid" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Love</h4></button>
              <button type="submit" class="btn btn-primary" id="friendBid" style="margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Friend</h4></button>
              <button type="submit" class="btn btn-primary" id="workBid" style="box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Work</h4></button>
          </div>
        </ol>
        <div class="carousel-inner" role="listbox">

        <?php
          // loop through profiles to set slides (first one is active)
          $count = 0;          
          while ($count < $pullCount) {
            
            // set the active flag
            if ($count == 0) {
        ?>
          <!-- Slide One - Set the background image for this slide in the line below -->
          <div class="carousel-item active" style="background-image: url('./img/intro-bg.jpg')">
        <?php
            } else {
        ?>
          <!-- Slide Two - Set the background image for this slide in the line below -->
          <div class="carousel-item" style="background-image: url('./img/intro-bg.jpg')">
        <?php
            }
        ?>
            <div class="carousel-caption">              
              <div class="container" style="margin-top:7%;margin-bottom:8%">        
                <!-- Item Row -->
                <div class="row boxSlot">                    
                    <div class="col-12">
                        <h3><button type="submit" class="btn btn-primary pButton whyMe">Why Me?</button>&nbsp;&nbsp;<div id="headerProfile<?=htmlspecialchars($count)?>" style="display:inline"><?=htmlspecialchars($firstNameArrayFinal[$count])?> from <?=htmlspecialchars($location1ArrayFinal[$count])?>, <?=htmlspecialchars($location2ArrayFinal[$count])?></h3>                        
                        <img class="img-responsive" src="<?=htmlspecialchars($imageArrayFinal[$count])?>" style="width:50%;box-shadow: 3px 3px 5px #888888;" id="imageProfile<?=htmlspecialchars($count)?>">                      
                        <h4 style="margin-top:5px;" class="tagline" id="taglineProfile<?=htmlspecialchars($count)?>"><?=htmlspecialchars($taglineArrayFinal[$count])?></h4>
                    </div>                                              
                </div>
                <div class="row boxFetch" style="display:none">                    
                    <div class="col-12">
                        <h3>We're Fetching More Profiles.</h3>                        
                        <img class="img-responsive whyMe" src="./img/fetchload.jpg" style="width:50%;box-shadow: 3px 3px 5px #888888;">                      
                        <h4 style="margin-top:5px;" class="tagline">Please Wait.</h4>
                    </div>                                              
                </div>
                <!-- /.row -->
              </div>
        
            </div>
          </div>
        <?php
            $count++;
          }
        ?>
          
        <a class="carousel-control-prev moveNext" href="#carouselExampleIndicators" role="button" data-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next moveNext" href="#carouselExampleIndicators" role="button" data-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="sr-only">Next</span>
        </a>      
      </div>
      
    </header>

    <div class="bidBox" id="bidLoveDialog">  
      <br><br>
      <h5 style="text-align:center">Enter your love offer:</h5>    
      <div style="text-align:center">EOS:<input type="number" name="bid" id="bidLove" value="10.0000" min="0" max="10000" step="0.0001">
      <button type="submit" class="btn btn-primary bidBtn" id="eosBidLove" style="box-shadow: 1px 1px 2px #888888;">Submit</button></div>
      <div class="loading" style="display:none;text-align:center">&nbsp;&nbsp;&nbsp;Sending...</div>                    
      <div class="success alert alert-info" style="display:none;padding:5px;text-align:center"></div>      
      <div class="successMatch alert alert-info" style="display:none;padding:5px;text-align:center">You have a new <a href="./match.php?v=<?=htmlspecialchars($loginCheck)?>">MATCH!</a></div>      
      <h4 class="offerText" style="text-align:center">Offers are Non-Binding.</h4><h6 class="offerText" style="text-align:center">Person doesn't see offer.</h6>
    </div>

    <div class="bidBox" id="bidFriendDialog">  
      <br><br>
      <h5 style="text-align:center">Enter your friend offer:</h5>    
      <div style="text-align:center">EOS:<input type="number" name="bid" id="bidFriend" value="10.0000" min="0" max="10000" step="0.0001">
      <button type="submit" class="btn btn-primary bidBtn" id="eosBidFriend" style="box-shadow: 1px 1px 2px #888888;">Submit</button></div>
      <div class="loading" style="display:none;text-align:center">&nbsp;&nbsp;&nbsp;Sending...</div>                    
      <div class="success alert alert-info" style="display:none;padding:5px;text-align:center"></div>
      <div class="successMatch alert alert-info" style="display:none;padding:5px;text-align:center">You have a new <a href="./match.php?v=<?=htmlspecialchars($loginCheck)?>">MATCH!</a></div>      
      <h4 class="offerText" style="text-align:center">Offers are Non-Binding.</h4><h6 class="offerText" style="text-align:center">Person doesn't see offer.</h6>
    </div>

    <div class="bidBox" id="bidWorkDialog">  
      <br><br>
      <h5 style="text-align:center">Enter your work offer:</h5>    
      <div style="text-align:center">EOS:<input type="number" name="bid" id="bidWork" value="10.0000" min="0" max="10000" step="0.0001">
      <button type="submit" class="btn btn-primary bidBtn" id="eosBidWork" style="box-shadow: 1px 1px 2px #888888;">Submit</button></div>
      <div class="loading" style="display:none;text-align:center">&nbsp;&nbsp;&nbsp;Sending...</div>                    
      <div class="success alert alert-info" style="display:none;padding:5px;text-align:center"></div>
      <div class="successMatch alert alert-info" style="display:none;padding:5px;text-align:center">You have a new <a href="./match.php?v=<?=htmlspecialchars($loginCheck)?>">MATCH!</a></div>      
      <h4 class="offerText" style="text-align:center">Offers are Non-Binding.</h4><h6 class="offerText" style="text-align:center">Person doesn't see offer.</h6>
    </div>

    <div class="bidBox" id="whyMeDialog">  
      <br>
      <h5 style="text-align:center"><button type="submit" class="btn btn-primary pButton whyMe">Close</button>&nbsp;Why Should You Meet Me?</h5>
      <?php
      $count = 0;
      while ($count < $pullCount) {        
        // set the first box to show, others hidden
        if ($count == 0) {
      ?>
      <h4 style="text-align:center" class="whyBox" id="whyMe<?=htmlspecialchars($count)?>"><?=htmlspecialchars($whyMeetArrayFinal[$count])?></h4>
      <?php
        } else {
      ?>
      <h4 style="text-align:center;display:none" class="whyBox" id="whyMe<?=htmlspecialchars($count)?>"><?=htmlspecialchars($whyMeetArrayFinal[$count])?></h4>
      <?php
        }
        $count++;
      }
      ?>
      <h6 style="text-align:center">* Person doesn't see your offer.</h6>      
    </div>
  
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="js/xss.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.4/jquery.touchSwipe.min.js"></script>

    <!-- Page-Level Demo Scripts -->
    <script>
      $(document).ready(function() {                        
  
          // redirect to https if not using it
          if (location.protocol != 'https:') {
              location.href = 'https:' + window.location.href.substring(window.location.protocol.length);
          }       
            
          // set the offset profileTable offset          
          var offset = <?=htmlspecialchars($offset)?>;

          // start responsive stying based on screen width
          if ($(window).width() < 576) {                  
            $('.tagline').css('padding-bottom','25%');            
            $('img').css('width','70%');            
            $('#carouselExampleIndicators').css('margin-top','-130px');                        
          } else if ($(window).width() < 768) {                  
            $('.tagline').css('padding-bottom','10%');                                                        
            $('#carouselExampleIndicators').css('margin-top','-25px');          
            $('img').css('width','60%');                        
          } else if ($(window).width() < 820) {                  
            $('.tagline').css('padding-bottom','10%');                                    
            $('#carouselExampleIndicators').css('margin-top','-25px');          
          } else if ($(window).width() < 992) {                  
            $('.tagline').css('padding-bottom','10%');                        
          } else if ($(window).width() < 1200) {                  
            $('.tagline').css('padding-bottom','5%');                        
          } 

          // resizing responsive stying based on screen width
          $(window).on('resize', function() {            
            if ($(window).width() < 576) {                  
              $('.tagline').css('padding-bottom','15%');            
              $('#carouselExampleIndicators').css('margin-top','-100px');                 
              $('img').css('width','70%');                   
            } else if ($(window).width() < 768) {                  
              $('.tagline').css('padding-bottom','10%');                                                        
              $('#carouselExampleIndicators').css('margin-top','-25px');          
              $('img').css('width','60%');                        
            } else if ($(window).width() < 820) {                  
              $('.tagline').css('padding-bottom','10%');                                                        
              $('#carouselExampleIndicators').css('margin-top','-25px');          
              $('img').css('width','50%');            
            } else if ($(window).width() < 992) {                  
              $('.tagline').css('padding-bottom','10%');                        
              $('#carouselExampleIndicators').css('margin-top','0px');                        
              $('img').css('width','50%');            
            } else if ($(window).width() < 1200) {                  
              $('.tagline').css('padding-bottom','5%');                        
              $('#carouselExampleIndicators').css('margin-top','0px');                        
              $('img').css('width','50%');                        
            } else {
              $('.tagline').css('padding-bottom','0%');            
              $('#carouselExampleIndicators').css('margin-top','0px');                        
              $('img').css('width','50%');            
            }
          })
          
          // add swipe for mobile
          $(".carousel").swipe({
            swipe: function(event, direction, distance, duration, fingerCount, fingerData) {
              if (direction == 'left') $(this).carousel('next');
              if (direction == 'right') $(this).carousel('prev');
            },
            allowPageScroll:"vertical"
          });

          // set the match count
          var matchCount = <?=htmlspecialchars($currentMatches)?>;

          // set the profile count
          var profileCount = <?=htmlspecialchars($profileViews)?>;
          
          // set the initial slot id to 0
          var currentSlot = 0;

          // set the max number of slots (starts at 0)
          var maxSlots = <?=htmlspecialchars($pullCount)?> - 1;

          // set up the slots
          var slotArray = [];          
          <?php
            $i = 0;
            while ($i < $pullCount) {
          ?>
              slotArray.push("<?=htmlspecialchars($userIDArrayFinal[$i])?>");                            
          <?php
              $i++;
            }
          ?>

          // hide all dialogs on moving to previous person
          $(".carousel-control-prev").on( "click", function() {		
            $(".bidBox").hide();   
            // subtract 1 to set new position
            if (currentSlot > 0) {
              currentSlot = currentSlot - 1;
            } else {
              currentSlot = maxSlots;
            }
            // change the whyMe when moving to a new person           
            $(".whyBox").hide();   
            $("#whyMe" + currentSlot ).show();   
            $( ".success" ).fadeOut("slow");			               
            $( ".successMatch" ).fadeOut("slow");			               
            $( ".success" ).text("");                
          });  	                                     

          // hide all dialogs on moving to next person
          $(".carousel-control-next").on( "click", function() {		
            $(".bidBox").hide();               
            // change the whyMe when moving to a new person           
            $(".whyBox").hide();        
            var nextSlot = currentSlot + 1;
            $("#whyMe" + nextSlot).show();   
            $( ".success" ).fadeOut("slow");			               
            $( ".successMatch" ).fadeOut("slow");			               
            $( ".success" ).text("");                
            // add 1 to set new position
            if (currentSlot < maxSlots) {                
              currentSlot = currentSlot + 1;
            } else {
              // load more profiles              
              currentSlot = 0;
              loadMoreProfiles(offset);
            }

          });  	                                     

          // handle the loading of a new set of profles
          function loadMoreProfiles(offset) {
            
            var countLoop = 0;

            // hide buttons, current box, show waiting
            $(".moveNext").fadeOut("slow");			               
            $("#loveBid").fadeOut("slow");			               
            $("#friendBid").fadeOut("slow");			               
            $("#workBid").fadeOut("slow");			               
            $(".boxSlot").fadeOut("slow");			               
            $(".boxFetch").fadeIn("slow");			               

            // set the post url
            var url = "./ajax/loadprofiles.php";
            
            // validate the offset
            if (offset >= 0) {
                
                // Send the data using post 
                var posting = $.post( url, { field1: '<?=htmlspecialchars($loginCheck)?>', field2: offset} );

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    var profileCount = jsonData.profileCount;		                    
                    ajaxResult = Number(ajaxResult);

                    // validate profileCount 
                    if (!profileCount) {
                      alert("There was problem loading more profiles. Try reloading the page.")
                      return false;             
                    }

                    if (isNaN(Number(profileCount))) {
                      alert("There was problem loading more profiles. Try reloading the page.")
                      return false;             
                    }

                    if (Number(profileCount) < 1) {
                      alert("There was problem loading more profiles. Try reloading the page.")
                      return false;             
                    }                                        
                 
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // success - clear any excess profiles
                            slotArray.length = profileCount;

                            // reset the current slot
                            currentSlot = 0;

                            // update the maxSlots - slot count starts at 1
                            maxSlots = profileCount - 1;
                            
                            // load new profiles
                            countLoop = 0;
                            while (countLoop < profileCount) {
                              $("#headerProfile" + countLoop).text(filterXSS(jsonData.firstName[countLoop]) + " from " + filterXSS(jsonData.location1[countLoop]) + ", " + filterXSS(jsonData.location2[countLoop]));			    
                              $("#imageProfile" + countLoop).attr("src", filterXSS(jsonData.image[countLoop]));
                              $("#taglineProfile" + countLoop).text(filterXSS(jsonData.tagline[countLoop]));			    
                              $("#whyMe" + countLoop).text(filterXSS(jsonData.whyMeet[countLoop]));			                                                                            
                              slotArray[countLoop] = filterXSS(jsonData.userID[countLoop]);                        
                              countLoop++;
                            }

                            // update the offet
                            offset = offset + 10;

                            // hide the loading, show the boxes
                            $(".boxFetch").fadeOut("slow");			        
                            $(".moveNext").fadeIn("slow");			               
                            $("#loveBid").fadeIn("slow");			               
                            $("#friendBid").fadeIn("slow");			               
                            $("#workBid").fadeIn("slow");			               
                            $(".boxSlot").fadeIn("slow");			                                           
                            $(".whyBox").hide();   
                            $("#whyMe0").show();                                                           
                            $( ".success" ).fadeOut("slow");			               
                            $( ".successMatch" ).fadeOut("slow");			               
                            $( ".success" ).text("");                
                            break;
                        default:
                          alert("There was problem loading more profiles. Try reloading the page.");             
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                  alert("Your internet may be down. Try reloading the page.");
                });			
                
            } else {
               alert("There was problem loading more profiles. Try reloading the page.");
            }

            return false;

          }

          // handle the work dialog
          $("#loveBid").on( "click", function() {		
            $("#bidLoveDialog").toggle();         
            $( ".offerText" ).fadeIn("slow");			
            $( ".success" ).fadeOut("slow");
            $( ".successMatch" ).fadeOut("slow");			               			               
            $( ".success" ).text("");                
          });  	                                     

          // handle the friend dialog
          $("#friendBid").on( "click", function() {		
            $("#bidFriendDialog").toggle();            
            $( ".offerText" ).fadeIn("slow");			
            $( ".success" ).fadeOut("slow");			
            $( ".successMatch" ).fadeOut("slow");			               
            $( ".success" ).text("");                
          });  	                                     

          // handle the work dialog
          $("#workBid").on( "click", function() {		
            $("#bidWorkDialog").toggle();            
            $( ".offerText" ).fadeIn("slow");			
            $( ".success" ).fadeOut("slow");			
            $( ".successMatch" ).fadeOut("slow");			               
            $( ".success" ).text("");                
          });  	                                     

          // handle the whyMe dialog
          $(".whyMe").on( "click", function() {		
            $("#whyMeDialog").toggle();            
          });  	                                     

          // handle the work bid
          $("#eosBidWork").on( "click", function() {		
            // get the bid work val
            var bidVal = $("#bidWork").val();            
            
            // validate bid
            if (bidVal < <?=htmlspecialchars(MINIMUM_BID)?>) {
              alert("The minimum offer is <?=htmlspecialchars(MINIMUM_BID)?> EOS. Please raise your offer.");
            } else {
              // call bid function
              bidFunction("work",bidVal);  
            }
          });  	                                     

          // handle the friend bid
          $("#eosBidFriend").on( "click", function() {		
            // get the bid friend val
            var bidVal = $("#bidFriend").val();            

            // validate bid
            if (bidVal < <?=htmlspecialchars(MINIMUM_BID)?>) {
              alert("The minimum offer is <?=htmlspecialchars(MINIMUM_BID)?> EOS. Please raise your offer.");
            } else {
              // call bid function
              bidFunction("friend",bidVal);  
            }
          });  	                                     

          // handle the love bid
          $("#eosBidLove").on( "click", function() {		
            // get the bid love val
            var bidVal = $("#bidLove").val();            

            // validate bid
            if (bidVal < <?=htmlspecialchars(MINIMUM_BID)?>) {
              alert("The minimum offer is <?=htmlspecialchars(MINIMUM_BID)?> EOS. Please raise your offer.");
            } else {
              // call bid function
              bidFunction("love",bidVal);  
            }
          });  	                                     

          // function to submit bid
          function bidFunction(bidType,bidVal) {

            // set the post url
            var url = "./ajax/bid.php";

            // convert the bid to a number with 4 decimals
            bidVal = Number(bidVal);
            bidVal = bidVal.toFixed(4);
            bidVal = Number(bidVal);
            
            // validate the bidType
            if ((bidType == "work") || (bidType == "friend") || (bidType == "love")) {
              // do nothing
            } else {
              alert("Your offer type is invalid. Please try again later.");
              return false;
            }

            // validate the bid is above minimum
            if (bidVal < <?=htmlspecialchars(MINIMUM_BID)?>) {
              alert("Your bid is too low. Please try again.");
              return false;
            }

            // validate the bid is below maximum
            if (bidVal > <?=htmlspecialchars(MAXIMUM_BID)?>) {
              alert("Your bid is too high. Maximum bid is " + filterXSS(<?=htmlspecialchars(MAXIMUM_BID)?>) + " EOS.");
              return false;
            }

            // validate the email and password
            if (bidVal >= 1) {

                // hide the submit button, text
                $( ".bidBtn" ).fadeOut("slow");			
                $( ".offerText" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( ".loading" ).fadeIn("slow");

                // clear any success text
                $( ".success" ).text("");

                // hide the view match link
                $( ".successMatch" ).hide();
                
                // Send the data using post 
                var posting = $.post( url, { field1: bidVal, field2: bidType, field3: slotArray[currentSlot], field4: '<?=htmlspecialchars($loginCheck)?>'} );

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( ".loading" ).fadeOut("slow");		
                    $( ".bidBtn" ).fadeIn("slow");	                                        
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // success                            
                            $( ".success" ).fadeIn("slow");			
                            $( '.success' ).text("Success. Your offer was submitted.");                                                    
                            break;
                        case 2:                            
                            // increment the match count
                            matchCount = matchCount + 1;
                            $( "#matchCount" ).text(matchCount + " Matches");
                            // show the view match link
                            $( ".successMatch" ).show();
                            break;                        
                        case 3:
                            $( ".success" ).fadeIn("slow");			    
                            $( '.success' ).text("Your EOS account doesn't have enough balance to cover this offer. Lower your offer.");                            
                            break;                        
                        case 4:
                            $( ".success" ).fadeIn("slow");			
                            $( '.success' ).text("You've already been matched with this person. Check your matches.");                            
                            break;                                                
                        case 5:
                            $( ".success" ).fadeIn("slow");			
                            $( '.success' ).text("You've already made a recent offer for this person.");                            
                            break;                                                
                        default:
                            $( ".success" ).fadeIn("slow");			
                            $( '.success' ).text("There was a problem. Please try again later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( ".loading" ).fadeOut("slow");
                    $( ".bidBtn" ).fadeIn("slow");			
                    $( ".success" ).fadeIn("slow");			
                    $( '.success' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( ".success" ).fadeIn("slow");			
                $( '.success' ).text("There was a problem with the offer. Please try again later.");                                                                    
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