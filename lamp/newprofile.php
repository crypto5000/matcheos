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

    <title>Matcheos - New User Profile</title>

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
      <a class="navbar-brand" href="#">Matcheos</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">          
        </ul>
      </div>
    </nav>

    <!-- Header -->
    <header class="intro-header">
        <div class="container">        
            <!-- Item Row -->
            <div class="row">                    
                <div class="col-12">
                    <h3 style="display:inline" id="createProfile">Create Your Profile:</h3>                                   
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
                    <div class="form-group row pGroup" id="nGroup5">                                                    
                        <div class="col-lg-5"></div>                                                
                        <div class="col-lg-2">                                     
                            <button type="submit" class="btn btn-primary" id="updateProfile" style="box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Create Profile</h4></button>
                            <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                            <div id="message" class='alert alert-info' style="display:none"></div>
                        </div>                                                                    
                        <div class="col-lg-5"></div>                                                
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
            alert('You can change your photo after creating the profile.')
        });  	                                             
        
        // handle the update profile button
        $("#updateProfile").on( "click", function() {		

            // get the values
            var firstName = $("#firstName").val();
            var location1 = $("#location1").val();
            var location2 = $("#location2").val();
            var tagline = $("#tagline").val();
            var whyMeet = $("#whyMeet").val();

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

            // set the post url
            var url = "./ajax/newprofile.php";
                                    
            // validate the firstName
            if (firstName.length > 1) {

                // hide the button
                $( "#updateProfile" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                
                // Send the data using post 
                var posting = $.post( url, { field1: firstName, field2: location1, field3: location2, field4: tagline, field5: whyMeet, field6: "<?=htmlspecialchars($loginCheck)?>"} );

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
                            // go to change photo page
                            window.location.href = "./changephoto.php?v=<?=htmlspecialchars($loginCheck)?>";		
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