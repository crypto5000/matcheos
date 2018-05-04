<?php

include('./ajax/authorization.php');

// set the arrays
$imageCount = 0;
$imageArray = array();

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        

// pull the possible photo avatars
$sql = "SELECT image FROM imageTable WHERE aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";
  
if ($stmt = mysqli_prepare($mysqli, $sql)) {
            
  // bind parameters for markers
  mysqli_stmt_bind_param($stmt, "sss", $aliveFlag, $deleteFlag, $errorFlag);

  // execute query
  mysqli_stmt_execute($stmt);

  // store result to get num rows				
  $stmt->store_result();
  
  // get the number of rows
  $numRows = $stmt->num_rows;
  
  if ($numRows < 1) {
  
      // send an email to the admin         
      $to = ADMINEMAIL;  
      $subject = 'IMAGE FAIL: NO DATA FOUND';
      $logging = "No images found at: ";
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
      mysqli_stmt_bind_result($stmt, $imageBind);							
      
      // cycle through and get the options
      while ($stmt->fetch()) {
      
          // validate the image url
          $startPath = "https://matcheos.com/img/profiles/";
          if (substr($imageBind, 0, strlen($startPath)) === $startPath) {          
            $imageArray[$imageCount] = htmlspecialchars($imageBind);    
          } else {
            // not valid format - set generic image
            $imageArray[$imageCount] = "https://matcheos.com/img/alice4.jpg";
          }

          // validate and bind the text displayed
          $imageArray[$imageCount] = htmlspecialchars($imageBind);        
          $imageCount++;      
          
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

    <title>Matcheos - Change Profile Photo</title>
      
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
          <div class="row text-center" style="margin-bottom:5px;">            
              <button type="submit" class="btn btn-primary" id="choosePhoto" style="box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Choose Photo</h4></button>              
              <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Updating</div>                                  
              <div id="success" class='alert alert-info' style="display:none"></div>
          </div>
        </ol>
        <div class="carousel-inner" role="listbox">

        <?php
          // loop through images to set slides (first one is active)
          $count = 0;          
          while ($count < $imageCount) {
                        
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
                <div class="row">                    
                    <div class="col-12 photoBox">                        
                      <h3 id="headerProfile">Select Your Public Avatar:</h3>                          
                      <img class="img-responsive whyMe" src="<?=htmlspecialchars($imageArray[$count])?>" style="width:50%;box-shadow: 3px 3px 5px #888888;">                                              
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
            $('.photoBox').css('padding-bottom','30%');            
            $('#carouselExampleIndicators').css('margin-top','-130px');                        
          } else if ($(window).width() < 768) {                  
            $('.tagline').css('padding-bottom','10%');                                                        
            $('#carouselExampleIndicators').css('margin-top','-25px');          
            $('img').css('width','60%');                        
            $('.photoBox').css('padding-bottom','17%');            
          } else if ($(window).width() < 820) {                  
            $('.tagline').css('padding-bottom','10%');                                    
            $('.photoBox').css('padding-bottom','20%');            
            $('#carouselExampleIndicators').css('margin-top','-25px');          
          } else if ($(window).width() < 992) {                  
            $('.tagline').css('padding-bottom','10%');             
            $('.photoBox').css('padding-bottom','10%');                       
          } else if ($(window).width() < 1200) {                  
            $('.tagline').css('padding-bottom','5%');                        
          } 

          // resizing responsive stying based on screen width
          $(window).on('resize', function() {            
            if ($(window).width() < 576) {                  
              $('.tagline').css('padding-bottom','15%');            
              $('#carouselExampleIndicators').css('margin-top','-100px');                 
              $('.photoBox').css('padding-bottom','20%');               
              $('img').css('width','70%');                   
            } else if ($(window).width() < 768) {                  
              $('.tagline').css('padding-bottom','10%');                                                        
              $('#carouselExampleIndicators').css('margin-top','-50px');          
              $('.photoBox').css('padding-bottom','20%');               
              $('img').css('width','60%');                        
            } else if ($(window).width() < 820) {                  
              $('.tagline').css('padding-bottom','10%');                                                        
              $('#carouselExampleIndicators').css('margin-top','-5px');          
              $('.photoBox').css('padding-bottom','20%');               
              $('img').css('width','50%');            
            } else if ($(window).width() < 992) {                  
              $('.tagline').css('padding-bottom','10%');                        
              $('#carouselExampleIndicators').css('margin-top','0px');                        
              $('.photoBox').css('padding-bottom','10%');               
              $('img').css('width','50%');            
            } else if ($(window).width() < 1200) {                  
              $('.tagline').css('padding-bottom','5%');                        
              $('#carouselExampleIndicators').css('margin-top','10px');                   
              $('.photoBox').css('padding-bottom','5%');               
              $('img').css('width','50%');                        
            } else {
              $('.tagline').css('padding-bottom','0%');            
              $('#carouselExampleIndicators').css('margin-top','20px');                        
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
          
          // set the initial slot id to 0
          var currentSlot = 0;

          // set the max number of slots (starts at 0)
          var maxSlots = <?=htmlspecialchars($imageCount)?> - 1;

          // set up the slots
          var slotArray = [];
          var j = 0;
          <?php
            $i = 0;
            while ($i < $imageCount) {
          ?>
              slotArray.push("<?=htmlspecialchars($imageArray[$i])?>");              
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
          });  	                                     

          // hide all dialogs on moving to next person
          $(".carousel-control-next").on( "click", function() {		
            $(".bidBox").hide();   
            // add 1 to set new position
            if (currentSlot < maxSlots) {
              currentSlot = currentSlot + 1;
            } else {
              currentSlot = 0;
            }            
          });  	                                     
          
          // handle the choose photo selection
          $("#choosePhoto").on( "click", function() {		              

            // get the image from the slotarray
            var image = slotArray[currentSlot];

            // set the post url
            var url = "./ajax/choosephoto.php";

            // validate the image
            if (!image) {
              alert("Your image selection is invalid. Please try again later.");
              return false;
            }

            // validate the image
            if (image.indexOf("https://") === -1) {
              alert("Your image selection is invalid. Please try again later.");
              return false;
            }
            
            // validate the email and password
            if (image.length >= 1) {

                // hide the submit button
                $( "#choosePhoto" ).fadeOut("slow");			
                
                // show the `ing spinner		
                $( "#loading" ).fadeIn("slow");

                // clear any success text
                $( "#success" ).text("");
                
                // Send the data using post 
                var posting = $.post( url, { field1: image, field2: '<?=htmlspecialchars($loginCheck)?>'} );

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		                    
                    $( "#success" ).fadeIn("slow");			
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // success
                            $( '#success' ).text("Success. Your photo has been updated. Continue browsing.");                                                    
                            setTimeout(function(){ 
                              window.location.href = "./profile.php?v=<?=htmlspecialchars($loginCheck)?>";		
                             }, 3000);
                            break;
                        default:
                            $( '#success' ).text("There was a problem with the submission. Please try again later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#choosePhoto" ).fadeIn("slow");			
                    $( "#success" ).fadeIn("slow");			
                    $( '#success' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#success" ).fadeIn("slow");			
                $( '#success' ).text("There was a problem with the offer. Please try again later.");                                                                    
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