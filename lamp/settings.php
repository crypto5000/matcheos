<?php

include('./ajax/authorization.php');

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        

// pull the profile data for the user
$sql = "SELECT firstName, location1, location2, tagline, whyMeet FROM profileTable WHERE userID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

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
        $whyMeet = "";

    } else {
        
        // bind result variables
        mysqli_stmt_bind_result($stmt, $firstNameBind, $location1Bind, $location2Bind, $taglineBind, $whyMeetBind);

        // fetch the results
        $stmt->fetch();
        
        // set variables
        $firstName = $firstNameBind;
        $location1 = $location1Bind;
        $location2 = $location2Bind;
        $tagline = $taglineBind;
        $whyMeet = $whyMeetBind;

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

    <title>Matcheos - Settings</title>

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
            <div class="row">                    
                <div class="col-12">
                    <h3 style="display:inline">Edit Your Profile Settings</h3><button type="submit" class="btn btn-primary pButton" id="viewProfile" style="margin-left:10px;margin-bottom:10px">View Profile</button>                                   
                    <div class="form-group row pGroup" id="nGroup1">                                                    
                        <div class="col-lg-3"></div>                                                
                        <div class="col-lg-3">                                     
                            <input class="form-control" id="firstName" type="text" value="<?=htmlspecialchars($firstName)?>" placeholder="First Name" style="display:inline">
                        </div>                                            
                        <div class="col-lg-3">                 
                            <input class="form-control" id="location1" type="text" value="<?=htmlspecialchars($location1)?>" placeholder="City">
                            <input class="form-control" id="location2" type="text" value="<?=htmlspecialchars($location2)?>" placeholder="State">
                        </div>                                                
                        <div class="col-lg-3"></div>                                                
                    </div>                                                
                    <img class="img-responsive whyMe" src="img/profile.jpg" style="width:50%;box-shadow: 3px 3px 5px #888888;">                                          
                    <br><br>
                    <div class="form-group row pGroup" id="nGroup2">                                                    
                        <div class="col-lg-3"></div>                                                
                        <div class="col-lg-6">                                     
                                <input class="form-control" id="tagline" type="text" value="<?=htmlspecialchars($tagline)?>" placeholder="Title/Job" style="display:inline">
                        </div>                                                                    
                        <div class="col-lg-3"></div>                                                
                    </div>                                                                   
                    <h4 style="margin-top:5px;" class="whyShouldMeet">Why Should People Want to Meet You?</h4>
                    <div class="form-group row pGroup" id="nGroup3">                                                    
                        <div class="col-lg-3"></div>                                                
                        <div class="col-lg-6">                                     
                            <input class="form-control" id="whyMeet" type="text" value="<?=htmlspecialchars($whyMeet)?>" placeholder="I can spell the alphabet backwards while blindfolded." style="display:inline">
                        </div>                                            
                        <div class="col-lg-3"></div>                                                
                    </div>                                                                                       
                    <div class="form-group row pGroup" id="nGroup4">                                                    
                        <div class="col-lg-3"></div>                                                
                        <div class="col-lg-3">                                     
                            <input class="form-control" id="email" type="email" value="<?=htmlspecialchars($email)?>" placeholder="you@example.com" style="display:inline">
                        </div>                                            
                        <div class="col-lg-3">                 
                            <select class="form-control" id="language">
                                    <option value="english" <?php if ($langID == 1) {echo htmlspecialchars("selected");}?>>English</option>
                                    <option value="chinese" <?php if ($langID == 2) {echo htmlspecialchars("selected");}?>>Chinese</option>
                                    <option value="korean" <?php if ($langID == 3) {echo htmlspecialchars("selected");}?>>Korean</option>
                                    <option value="russian" <?php if ($langID == 4) {echo htmlspecialchars("selected");}?>>Russian</option>
                                    <option value="spanish" <?php if ($langID == 5) {echo htmlspecialchars("selected");}?>>Spanish</option>
                            </select>
                        </div>                                                
                        <div class="col-lg-3"></div>                                                
                    </div>                                                                   
                    <div class="form-group row pGroup" id="nGroup5">                                                    
                        <div class="col-lg-4"></div>                                                
                        <div class="col-lg-4">                                     
                            <button type="submit" class="btn btn-primary" id="updateProfile" style="box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Update Profile</h4></button>
                            <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Updating</div>                    
                            <div id="message" class='alert alert-info' style="display:none"></div>
                        </div>                                                                    
                        <div class="col-lg-4"></div>                                                
                    </div>                                                                                           
                </div>                                              
            </div>
            
            <!-- /.row -->
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
           
        // handle the change photo        
        $(".whyMe").on( "click", function() {		
            location.href = './changephoto.php?v=<?=htmlspecialchars($loginCheck)?>';
        });  	                                             

        // handle the view profile button
        $("#viewProfile").on( "click", function() {		
            location.href = './viewprofile.php?v=<?=htmlspecialchars($loginCheck)?>';
        });  	                                     

        // handle the update profile button
        $("#updateProfile").on( "click", function() {		

            // get the values
            var firstName = $("#firstName").val();
            var location1 = $("#location1").val();
            var location2 = $("#location2").val();
            var tagline = $("#tagline").val();
            var whyMeet = $("#whyMeet").val();
            var email = $("#email").val();                
            var language = $("#language").val();                

            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");

            // validate the first name exists
            if (!firstName) {
                alert("First name is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the field is more than 1 character
            if (firstName.length < 1) {
                alert("First name is too short. Please enter a longer value.");                                        
                return false;
            }

            // validate the field is less than 100 characters
            if (firstName.length > 100) {
                alert("First name is too long. Please enter a shorter value.");                                        
                return false;
            }

            // validate the field exists
            if (!location1) {
                alert("City is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the field is more than 1 character
            if (location1.length < 1) {
                alert("City is too short. Please enter a longer value.");                                        
                return false;
            }

            // validate the field is less than 200 characters
            if (location1.length > 200) {
                alert("City is too long. Please enter a shorter value.");                                        
                return false;
            }

            // validate the field exists
            if (!location2) {
                alert("State is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the field is more than 1 character
            if (location2.length < 1) {
                alert("State is too short. Please enter a longer value.");                                        
                return false;
            }

            // validate the field is less than 200 characters
            if (location2.length > 200) {
                alert("State is too long. Please enter a shorter value.");                                        
                return false;
            }

            // validate the field exists
            if (!tagline) {
                alert("Tagline is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the field is more than 1 character
            if (tagline.length < 3) {
                alert("Tagline is too short. Please enter a longer value.");                                        
                return false;
            }

            // validate the field is less than 200 characters
            if (tagline.length > 300) {
                alert("Tagline is too long. Please enter a shorter value.");                                        
                return false;
            }

            // validate the field exists
            if (!whyMeet) {
                alert("Why Meet? field is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the field is more than 1 character
            if (whyMeet.length < 1) {
                alert("Why Meet? field is too short. Please enter a longer value.");                                        
                return false;
            }

            // validate the field is less than 200 characters
            if (whyMeet.length > 400) {
                alert("Why Meet? field is too long. Please enter a shorter value.");                                        
                return false;
            }

            // validate the email exists
            if (!email) {
                alert("Email is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the email
            if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,15})+$/.test(email)) {
                // do nothing
            } else {
                alert("Email is invalid. Please enter a new value.");
                return false;
            }
       
            // validate the email is less than 100 characters
            if (email.length > 100) {
                alert("Email is too long. Please enter a shorter value.");                                        
                return false;
            }

            // validate the password exists
            if ((language == "english") || (language == "chinese") || (language == "korean") || (language == "russian") || (language == "spanish")) {
                // do nothing
            } else {
                alert("Language is invalid. Please select a value.");                                        
                return false;
            }
         
            // set the post url
            var url = "./ajax/updateprofile.php";
                                    
            // validate the email and password
            if (email.length > 1) {

                // hide the button
                $( "#updateProfile" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                
                // Send the data using post 
                var posting = $.post( url, { field1: firstName, field2: location1, field3: location2, field4: tagline, field5: whyMeet, field6: email, field7: language, field8: "<?=htmlspecialchars($loginCheck)?>"} );

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		
                    $( "#updateProfile" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // show success message
                            $( "#updateProfile" ).fadeOut("slow");			
                            $( '#message' ).text("Success. Your profile has been updated. Continue browsing.");                                                    
                            setTimeout(function(){ 
                              window.location.href = "./profile.php?v=<?=htmlspecialchars($loginCheck)?>";		
                             }, 3000);
                            break;
                        case 2:
                            $( '#message' ).text("There was a problem with your first name. Please change and retry.");                            
                            break;
                        case 3:
                            $( '#message' ).text("There was a problem with your city name. Please change and retry.");                            
                            break;                        
                        case 4:
                            $( '#message' ).text("There was a problem with your state name. Please change and retry.");                            
                            break;                        
                        case 5:
                            $( '#message' ).text("There was a problem with your tagline. Please change and retry.");                            
                            break;                        
                        case 6:
                            $( '#message' ).text("There was a problem with your Why Meet? response. Please change and retry.");                            
                            break;                        
                        case 7:
                            $( '#message' ).text("There was a problem with your email. Please change and retry.");                            
                            break;                        
                        case 8:
                            $( '#message' ).text("There was a problem with your language. Please change and retry.");                            
                            break;                        
                        default:
                            $( '#message' ).text("There was a problem with the update. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#updateProfile" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("There was a problem with the update. Please try later.");                                                                    
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