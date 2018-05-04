<?php

include('./ajax/authorization.php');

// get the match, matchType
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
      $subject = 'MATCH FAIL: NO DATA FOUND ON SELECT CONTRACT';
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
            // invalid ids
            $invalidFlag = "yes";
        }

        if ($invalidFlag == "yes") {
            // send an email to the admin         
            $to = ADMINEMAIL;  
            $subject = 'MATCH FAIL: INVALID DATA FOUND ON SELECT CONTRACT';
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
        } else {
            $offerID = $spotID1;
            $offer = $offer2;            
        }        

        // close statement
        mysqli_stmt_close($stmt);        
        
    }

}

// check no open contracts (can have closed/terminated/arbitration), cap of 1 open contract per match at a time
$sql = "SELECT contractID, status1, status2 FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND matchID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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

        // do nothing - this is fine

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $contractIDBind, $status1Bind, $status2Bind);
    
        // fetch the results
        while ($stmt->fetch()) {

            // reset the invalid flag
            $invalidFlag = 'no';  
            
            // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
            if (($status1Bind == "waiting") && ($status2Bind == "waiting")) {
                $invalidFlag = "yes";
            }

            // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
            if (($status1Bind == "waiting") && ($status2Bind == "open")) {
                $invalidFlag = "yes";
            }

            // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
            if (($status1Bind == "open") && ($status2Bind == "waiting")) {
                $invalidFlag = "yes";
            }

            // check if status is waiting/waiting, waiting/open, open/waiting, or open/open
            if (($status1Bind == "open") && ($status2Bind == "open")) {
                $invalidFlag = "yes";
            }

            // redirect to contract - because there is already 1 open contract for this match
            if ($invalidFlag == "yes") {
                // redirect
                header('Location: ./contract.php?v='.$loginCheck.'&w='.$matchID);	
                exit();
            } 

        }
        
        // close statement
          mysqli_stmt_close($stmt);                    

    }
    
}

// pull the goals for type
$goalCount = 0;
$goalArray = array();
$goalHeaderArray = array();
$goalImageArray = array();

$sql = "SELECT goalID, goal, goalHeader, goalImage FROM goalTable WHERE goalType = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

if ($stmt = mysqli_prepare($mysqli, $sql)) {
                
    // bind parameters for markers
    mysqli_stmt_bind_param($stmt, "ssss", $matchType, $aliveFlag, $deleteFlag, $errorFlag);

    // execute query
    mysqli_stmt_execute($stmt);

    // store result to get num rows				
    $stmt->store_result();
            
    // get the number of rows
    $numRows = $stmt->num_rows;
                            
    if ($numRows < 1) {

        // send an email to the admin         
        $to = ADMINEMAIL;  
        $subject = 'GOAL FAIL: NO DATA FOUND ON SELECT CONTRACT';
        $logging = "No goals found at: ";
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
        mysqli_stmt_bind_result($stmt, $goalIDBind, $goalBind, $goalHeaderBind, $goalImageBind);
    
        // fetch the results
        while ($stmt->fetch()) {

            $goalIDArray[$goalCount] = $goalIDBind;
            $goalArray[$goalCount] = $goalBind;
            $goalHeaderArray[$goalCount] = $goalHeaderBind;
            $goalImageArray[$goalCount] = $goalImageBind;
            $goalCount++;
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

    <title>Matcheos - Select Contract</title>
      
    <!-- Bootstrap core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        
    <!-- Custom styles for this template -->
    <link href="css/contractslider.css" rel="stylesheet">        
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
      </div>
    </nav>
    
    <header>      
      <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="false" data-pause="hover">                
        <ol class="carousel-indicators">
          <div class="row text-center" style="margin-bottom:20px;margin-top:-25px">            
              <button type="submit" class="btn btn-primary contractDetails" style="box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Select Contract Details</h4></button>              
          </div>
        </ol>
        <div class="carousel-inner" role="listbox">
        <?php
          // loop through profiles to set slides (first one is active)
          $count = 0;          
          while ($count < $goalCount) {
            
            // set the active flag
            if ($count == 0) {
        ?>
          <!-- Slide One - Set the background image for this slide in the line below -->
          <div class="carousel-item active" style="background-image: url('./img/intro-bg.jpg')">
            <div class="carousel-caption">              
                <div class="container" style="margin-top:7%;margin-bottom:8%">        
                    <!-- Item Row -->
                    <div class="row">                    
                        <div class="col-12">
                            <h3><button type="submit" class="btn btn-primary pButton eosContract">Accept Default</button>&nbsp;&nbsp;Choose a Smart Contract</h3>                                                           
                            <img class="img-responsive whyMe" src="<?=htmlspecialchars($goalImageArray[$count])?>" style="width:50%;box-shadow: 3px 3px 5px #888888;">                      
                            <h4 style="margin-top:15px;" class="tagline"><?=htmlspecialchars($goalArray[$count])?></h4>
                        </div>                                              
                    </div>
                
                  <!-- /.row -->
                </div>
          
            </div>
          </div>

        <?php
            } else {
        ?>
          <!-- Slide Two - Set the background image for this slide in the line below -->
          <div class="carousel-item" style="background-image: url('./img/intro-bg.jpg')">        
            <div class="carousel-caption">              
              <div class="container" style="margin-top:7%;margin-bottom:8%">        
                <!-- Item Row -->
                <div class="row">                    
                    <div class="col-12">
                        <h3><?=htmlspecialchars($goalHeaderArray[$count])?></h3>
                        <img class="img-responsive whyMe" src="<?=htmlspecialchars($goalImageArray[$count])?>" style="width:50%;box-shadow: 3px 3px 5px #888888;">                      
                        <h4 style="margin-top:15px;" class="tagline"><?=htmlspecialchars($goalArray[$count])?></h4>
                    </div>                                              
                </div>
              
                <!-- /.row -->
              </div>
        
            </div>
          </div>
          <?php
            }
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

    <div class="contractBox">      
      <h5 style="text-align:center;margin-top:10px">Set the Contract Specifics:</h5>    
      <div style="text-align:center;color:#6699cc">
        Interaction Format
        <select class="form-control" name="contractFormat" id="contractFormat" style="width:90%;margin-left:20px;color:black">
            <option value="twosided" selected>Two Sided: You Respond then Ask Question</option>
            <option value="chat">Simple Chat: You Respond, They Respond</option>            
            <option value="interview">An Interview: You Question, They Answer</option>            
        </select>        
        Number of Interaction Steps
        <input type="number" name="contractSteps" id="contractSteps" value="10" min="4" max="50" step="1" style="width:90%;padding-left:12px;color:black"><br>
        Donee: If Code of Conduct Breached
        <select class="form-control" name="contractDonee" id="contractDonee" style="width:90%;margin-left:20px;color:black">
            <option value="redcross" selected>Red Cross</option>
            <option value="doctors">Doctors without Borders</option>            
        </select>
        <button type="submit" class="btn btn-primary eosContract" style="box-shadow: 1px 1px 2px #888888;margin-top:10px">Submit</button>
      </div>      
    </div>
    
    <!-- Bootstrap core JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.4/jquery.touchSwipe.min.js"></script>

    <!-- Page-Level Demo Scripts -->
    <script>
      $(document).ready(function() {                        
  
          // redirect to https if not using it
          if (location.protocol != 'https:') {
              location.href = 'https:' + window.location.href.substring(window.location.protocol.length);
          }       
        
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

          // remove the auto carousel                  
          $('.carousel').carousel({
              interval: false
          }); 

          // add swipe for mobile
          $(".carousel").swipe({
            swipe: function(event, direction, distance, duration, fingerCount, fingerData) {
              if (direction == 'left') $(this).carousel('next');
              if (direction == 'right') $(this).carousel('prev');
            },
            allowPageScroll:"vertical"
          });

          // set the initial slot id to 0
          var currentSlot = 0;

          // set the max number of slots (starts at 0)
          var maxSlots = <?=htmlspecialchars($goalCount)?> - 1;

          // set up the slots
          var slotArray = [];
          <?php
            $i = 0;
            while ($i < $goalCount) {
          ?>
              slotArray.push("<?=htmlspecialchars($goalIDArray[$i])?>");
          <?php
              $i++;
            }
          ?>
          
          // hide all dialogs on moving to previous person
          $(".carousel-control-prev").on( "click", function() {		
            $(".contractBox").hide();            
            // subtract 1 to set new position
            if (currentSlot > 0) {
              currentSlot = currentSlot - 1;
            } else {
              currentSlot = maxSlots;
            }            
          });  	                                     

          // hide all dialogs on moving to next person
          $(".carousel-control-next").on( "click", function() {		
            $(".contractBox").hide();            
            // add 1 to set new position
            if (currentSlot < maxSlots) {
              currentSlot = currentSlot + 1;
            } else {
              currentSlot = 0;
            }            
          });  	                                       	                                     
                
          // handle the set contact
          $(".contractDetails").on( "click", function() {		
            $(".contractBox").toggle();            
          });  	                                               

          // go to accept contract
          $(".eosContract").on( "click", function() {		

            // get the contract values
            var contractSteps = $("#contractSteps").val();            
            var contractFormat = $("#contractFormat").val();            
            var contractDonee = $("#contractDonee").val();            
            var goalID = slotArray[currentSlot];            
            
            // validate the data
            if (!contractSteps) {
                alert("Your contract steps is invalid. Please try again.");
                return false;  
            }

            contractSteps = Number(contractSteps);

            if ((contractSteps < 4) || (contractSteps > 50)) {
                alert("Your contract flow is invalid. Contract steps must be between 4 and 50.");
                return false;
            }
            
            // validate the donee
            if ((contractFormat == "twosided") || (contractFormat == "chat") || (contractFormat == "interview")) {
              // do nothing
            } else {
              alert("Your contract flow is invalid. Please adjust your .");
              return false;
            }

            // validate the donee
            if ((contractDonee == "redcross") || (contractDonee == "doctors")) {
              // do nothing
            } else {
              alert("Your contract donee is invalid. Please try again.");
              return false;
            }

            // validate the data
            if (!goalID) {
                alert("Your contract goal is invalid. Please try again.");
                return false;  
            }

            goalID = Number(goalID);
            
            if (goalID < 0) {
                alert("Your contract goal is invalid. Please try again.");
                return false;
            }

            // post the data to accept contract page            
            post('./acceptcontract.php?v=<?=htmlspecialchars($loginCheck)?>&w=<?=htmlspecialchars($matchID)?>', {steps: contractSteps, format: contractFormat, donee: contractDonee, goal: goalID});
            
          });  	                                     

          // function to post the data
          function post(path, params, method) {
            method = method || "post"; 
                    
            var form = document.createElement("form");
            form.setAttribute("method", method);
            form.setAttribute("action", path);
        
            for(var key in params) {
                if(params.hasOwnProperty(key)) {
                    var hiddenField = document.createElement("input");
                    hiddenField.setAttribute("type", "hidden");
                    hiddenField.setAttribute("name", key);
                    hiddenField.setAttribute("value", params[key]);        
                    form.appendChild(hiddenField);
                }
            }
        
            document.body.appendChild(form);
            form.submit();
        }
         
      });	
  
      </script>

  </body>

</html>
<?php
	
mysqli_close($mysqli);

?>