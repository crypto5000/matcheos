<?php
include('./include/domain.php');

// generate a random string for passKey
$randBytes = random_bytes(64);
$randBytesString = bin2hex($randBytes);
$randBytesString = substr($randBytesString,0,64);

?>
<!DOCTYPE html>
<html lang="en">

  <head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matcheos is a new way to meet people using the EOS platform.">
    <meta name="author" content="Matcheos">

    <title>Matcheos - Signup</title>

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

    <link href="css/fullslider.css" rel="stylesheet">    

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
                <li class="nav-item">
                    <a class="nav-link" href="https://matcheos.com/index.php#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?=htmlspecialchars(LOGINURL)?>">Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://github.com/crypto5000/matcheos">Github</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://matcheos.com/index.php##faq">FAQs</a>
                </li>          
            </ul>
        </div>
    </nav>

    <!-- Header -->
    <header class="intro-header">
        <div class="container">
            <div class="intro-message">
                <br><br>          
                <h2 style="padding-bottom:10px;">A New Way to Meet People<br>using the EOS platform.</h2>                    
                <hr class="intro-divider">          
                <div class="col-lg-12">
                    <div id="newSignup">
                        <div class="form-group row pGroup" id="nGroup1">                                                    
                            <div class="col-lg-3">                                     
                                <input class="form-control" id="accountNameTitle" type="text" value="EOS Account Name:" disabled style="background-color:#d3d3d3;font-weight:bold">
                            </div>                                                
                            <div class="col-lg-7">                 
                                <input class="form-control" name="accountName" id="accountName" type="text" placeholder="bytemaster" tabindex="1">
                            </div>                                                
                            <div class="col-lg-2">                 
                                <button type="submit" class="btn btn-primary pButton" id="noAccount">No EOS Account?</button>                                   
                            </div>                                                
                        </div>                                                
                        <div class="form-group row pGroup" id="nGroup2">                                                    
                            <div class="col-lg-3">                                     
                                <input class="form-control" id="emailTitle" type="text" value="Email:" disabled style="background-color:#d3d3d3;font-weight:bold">
                            </div>                                                
                            <div class="col-lg-7">                 
                                <input class="form-control" name="email" id="email" type="email" placeholder="you@example.com" tabindex="2">
                            </div>                        
                            <div class="col-lg-2">                                                 
                            </div>                                                
                        </div>                                                
                        <div class="form-group row pGroup" id="nGroup3">                                                    
                            <div class="col-lg-3">                                     
                                <input class="form-control" id="birthTitle" type="text" value="Year of Birth:" disabled style="background-color:#d3d3d3;font-weight:bold">
                            </div>                                                
                            <div class="col-lg-7">                 
                                <input class="form-control" id="birthYear" type="number" value="1993" min="1918" max="2050" tabindex="3">
                            </div>                        
                            <div class="col-lg-2">                                                 
                            </div>                                                
                        </div>                                                
                        <div class="form-group row pGroup" id="nGroup3">                                                    
                            <div class="col-lg-3">                                     
                                <input class="form-control" id="languageTitle" type="text" value="Language:" disabled style="background-color:#d3d3d3;font-weight:bold" tabindex="4">
                            </div>                                                
                            <div class="col-lg-7">                 
                                <select class="form-control" id="language">
                                    <option value="english" selected>English</option>
                                    <option value="chinese">Chinese</option>
                                    <option value="korean">Korean</option>                                    
                                    <option value="russian">Russian</option>
                                    <option value="spanish">Spanish</option>
                                </select>
                            </div>                        
                            <div class="col-lg-2">                                                 
                            </div>                                                
                        </div>                                                
                        <div class="form-group row pGroup" id="nGroup4">                                                    
                            <div class="col-lg-3">                                                                     
                            </div>                                                
                            <div class="col-lg-5" style="text-align:left">                 
                                <button type="submit" class="btn btn-primary pButton" id="signupButton">Signup to Matcheos</button>                                                                                              
                                <h6 style="display:inline;margin-left:20px"><a href="<?=htmlspecialchars(LOGINURL)?>" style="color:white;text-decoration:underline">Signed up? Login.</a></h6>                                
                            </div>                                                    
                            <div class="col-lg-3">                                                 
                            </div>                                                
                        </div>                                                
                    </div>

                    <div class="col-lg-12" id="passDisplay" style="display:none">
                        <div>
                            <h5 style="text-align:center">Please Safely Store Your Matcheos PassKey:</h5>    
                            <div style="text-align:center;padding-left:20px;padding-right:20px;padding-top:5px;padding-bottom:5px;word-wrap:break-word;"><?=htmlspecialchars($randBytesString)?></div> 
                            <h4 style="text-align:center;padding-left:20px;padding-right:20px">You will need this password to login to Matcheos.</h4>
                            <div style="text-align:center"><button type="submit" class="btn btn-primary bidBtn" id="acceptPasskey" style="box-shadow: 1px 1px 2px #888888;">Ok. I stored it.</button>                
                            <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                            <div id="message" class='alert alert-info' style="display:none"></div>
                        </div>
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
        
        // set defaults
        var accountName = "";
        var email = "";
        var birthYear = 0;
        var language = "";            

        // handle signup
        $("#signupButton").on( "click", function() {		

            // get the values
            accountName = $("#accountName").val();
            email = $("#email").val();
            birthYear = $("#birthYear").val();
            language = $("#language").val();            

            // validate the field
            if (!accountName) {
                alert("Please enter an EOS account name.");
                return false;                
            }

            if (accountName.length < 1) {
                alert("Please enter a valid EOS account name. Name is too short.");
                return false;                
            }

            if (accountName.length > 12) {
                alert("Please enter a valid EOS account name. Name is too long.");
                return false;                
            }

            if (!email) {
                alert("Please enter a email.");
                return false;                
            }

            if (email.length < 3) {
                alert("Please enter a valid email. Email is too short.");
                return false;                
            }

            if (email.length > 500) {
                alert("Please enter a valid email. Email is too long.");
                return false;                
            }

            // validate the email
            if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,15})+$/.test(email)) {
                // do nothing
            } else {
                alert("Email is invalid. Please enter a new value.");
                return false;
            }
       
            if (!language) {
                alert("Please select a language from the dropdown.");
                return false;                
            }

            if ((language == "english") || (language == "chinese") || (language == "korean") || (language == "russian") || (language == "spanish")) {
                // do nothing
            } else {
                alert("Please select a valid language from the dropdown.");
                return false;                
            }

            if (!birthYear) {
                alert("Please enter a birth year.");
                return false;                
            }

            if (birthYear > 2003) {
                alert("You must be at least 15 to use Matcheos.");
                return false;                
            }

            if (birthYear < 1918) {
                alert("Please enter a birth year after 1918.");
                return false;                
            }

            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
                                                                     
            // validate the email exists
            if (email) {

                // show passKey
                $("#newSignup").hide();            
                $("#passDisplay").show();            
                
            } else {                
                alert("There was a problem with the submission. Please try later.");                
            }
            
            return false;
        });                                                  

        // handle accept passkey
        $("#acceptPasskey").on( "click", function() {		
                        
            sendData(accountName,email,birthYear,language);

        });                     
 
        function sendData(accountName,email,birthYear,language) {

            // set the post url
            var url = "./ajax/signupuser.php";
            
            // hide the button
            $( "#acceptPasskey" ).fadeOut("slow");			
                
            // show the loading spinner		
            $( "#loading" ).fadeIn("slow");
            
            // Send the data using post 
            var posting = $.post( url, { field1: accountName, field2: email, field3: birthYear, field4: language, field5: "<?=htmlspecialchars($randBytesString)?>"} );

            // Put the results in a div 
            posting.done(function( data ) {	

                // Receive status code
                var jsonData  = JSON.parse(data);					
                var ajaxResult = jsonData.ajaxResult;		                    
                var authToken = jsonData.authToken;
                ajaxResult = Number(ajaxResult);
                $( "#loading" ).fadeOut("slow");		
                $( "#acceptPasskey" ).fadeIn("slow");			                
                
                // If no errors, got to results
                switch(ajaxResult) {
                    case 1:
                        // go to new profile page
                        window.location.href = "./newprofile.php?v=" + authToken;                                                                    
                        break;                        
                    case 2:
                        // account not found
                        $( "#message" ).fadeIn("slow");
                        $( '#message' ).text("There was a problem with the account name. It was not found on the blockchain.");                                                    
                        break;                        
                    case 3:
                        // invalid email
                        $( "#message" ).fadeIn("slow");
                        $( '#message' ).text("There was a problem with the email. Please try again.");                                                    
                        break;                        
                    case 4:
                        // invalid birthYear
                        $( "#message" ).fadeIn("slow");
                        $( '#message' ).text("There was a problem with the birth year. Please try again.");                                                    
                        break;                        
                    case 5:
                        // invalid language
                        $( "#message" ).fadeIn("slow");
                        $( '#message' ).text("There was a problem with the language. Please try again.");                                                    
                        break;                        
                    default:
                        $( "#message" ).fadeIn("slow");
                        $( '#message' ).text("There was a problem with the submission. Please try later.");                                                    
                }							
            });
    
            /* Check for error */
            posting.fail(function( data ) {			
                $( "#loading" ).fadeOut("slow");
                $( "#acceptPasskey" ).fadeIn("slow");			
                $( "#message" ).fadeIn("slow");
                $( '#message' ).text("Your internet connection appears to be down. Try again later.");                                                                        
            });			                
            
            return false;
        }

        // handle no eos account
        $("#noAccount").on( "click", function() {		
            // go to eos.io 
            window.location.href = "http://eos.io";                                                                                
        });

    });	

    </script>
	
  </body>
  
</html>
<?php
unset($randBytesString);
?>