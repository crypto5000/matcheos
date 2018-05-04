<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - Meet People using EOS</title>

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
      <a class="navbar-brand" href="https://matcheos.com">Matcheos</a>
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
        <div class="intro-message">
          <br><br>          
          <h2 style="padding-bottom:10px;margin-top:-15px">A New Way to Meet People<br>using the EOS platform.</h2>                    
          <hr class="intro-divider">          
          <ul class="list-inline intro-social-buttons">            
            <div class="form-group row">                                
                <div class="col-lg-12">                                     
                    <input type="email" class="form-control" id="email" placeholder="you@example.com" style="padding-left:12px;width:80%;margin: 0 auto;">
                    <input type="password" class="form-control" id="passKey" value="" placeholder="YOUR 64 CHARACTER PASSKEY" style="padding-left:12px;width:80%;margin: 0 auto;">
                    <br>
                    <button type="submit" class="btn btn-primary" id="loginClick" style="padding-top:10px"><h3 style="margin-top:5px">Matcheos Alpha Login</h3></button>                                                                                                        
                    <div id="loadingTop" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Loading</div>                    
                    <div id="loginError" class='alert alert-danger' style="display:none"></div>
                </div>
            </div>                                                
          </ul>          
          <h4 style="margin-top:-15px">Matcheos is in private alpha. You must be invited to join.<br>To receive an invitation, sign up to the <a href="https://matcheos.com" style="color:white;text-decoration:underline">waiting list</a>.</h4>          
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

        // handle login
        $("#loginClick").on( "click", function() {		

            // get the email, referral
            var email = $("#email").val();                
            var passKey = $("#passKey").val();              

            // clear the error text, hide display
            $( '#loginError' ).hide();  
            $( '#loginError' ).text("");  

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
            if (!passKey) {
                alert("Passkey is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the password is less than 200 characters
            if (passKey.length != 64) {
                alert("Passkey should be 64 characters.");                                        
                return false;
            }

            // set the post url
            var url = "./ajax/login.php";
                                    
            // validate the email and password
            if ((email.length > 1) && (passKey.length > 1)) {

                // hide the login
                $( "#loginClick" ).fadeOut("slow");			                
                
                // show the loading spinner		
                $( "#loadingTop" ).fadeIn("slow");
                
                // Send the data using post 
                var posting = $.post( url, { field1: email, field2: passKey} );

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		
                    var authToken = jsonData.authToken;		
                    ajaxResult = Number(ajaxResult);
                    $( "#loadingTop" ).fadeOut("slow");		
                    $( "#loginClick" ).fadeIn("slow");			
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // go to results on profile
                            window.location.href = "./profile.php?v=" + authToken;						
                            break;
                        case 2:
                            $( '#loginError' ).show();
                            $( '#loginError' ).text("Your login credentials were not valid. Please try again.");                            
                            break;
                        case 3:
                            $( '#loginError' ).show();
                            $( '#loginError' ).text("You have made 3 failed login attempts. You'll have to wait 24 hours to login again.");                                                        
                            break;                        
                        case 4:
                            // if user has no profile
                            window.location.href = "./newprofile.php?v=" + authToken;						
                            break;                        
                        default:
                            $( '#loginError' ).show();
                            $( '#loginError' ).text("There was a problem with the login. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loadingTop" ).fadeOut("slow");
                    $( "#loginClick" ).fadeIn("slow");			
                    $( '#loginError' ).show();
                    $( '#loginError' ).text("Your internet connection appears to be down. Try again later.");                                                                        
                });			
                
            } else {
                $( '#loginError' ).show();
                $( '#loginError' ).text("There was a problem with the login. Please try later.");                                                                    
            }
            
            return false;
        });                                                  
        
    });	

    </script>
	
  </body>
  
</html>
