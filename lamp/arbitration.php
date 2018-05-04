<?php
session_start();
$sessionFlag = "no";

// check if session variable exists
if (isset($_SESSION["userKeyEncoded"])) {
    $sessionFlag = "yes";
}

include('./ajax/authorization.php');

// get the contract being arbitrated
$contractID = $_GET["w"];

// validation - check for html special characters
$contractID = htmlspecialchars($contractID);

// validation - strip slashes
$contractID = stripslashes($contractID);

// validation - utf8 encode
$contractID = utf8_encode($contractID);

// replace escape character
$contractID = preg_replace('!\r\n?!', '\n', $contractID);

// validate that the field was submitted
if (!isset($contractID)) {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// validate that the field was submitted
if (strlen($contractID) < 1) {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// validate that the field is not an overflow
if (strlen($contractID) > 500) {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// validate that the field is a number
if (is_numeric($contractID)) {
    // convert to int
    $contractID = (int) $contractID;
} else {
	// redirect - not valid
	header('Location: ./index.php?v=yes');	
	exit();
}

// set the flags
$aliveFlag = "yes";
$deleteFlag = "no";
$errorFlag = "no";        
$invalidFlag = "no";
$arbitrationFee = 0;

// pull data
$sql = "SELECT spotID1, spotID2, offer1, offer2, status1, status2, rejectFlag, contractArbFee, terminationRelease FROM contractTable WHERE (spotID1 = ? OR spotID2 = ?) AND contractID = ? AND aliveFlag = ? AND deleteFlag = ? AND errorFlag = ?";

if ($stmt = mysqli_prepare($mysqli, $sql)) {

    // bind parameters for markers
    mysqli_stmt_bind_param($stmt, "iiisss", $userID, $userID, $contractID, $aliveFlag, $deleteFlag, $errorFlag);

    // execute query
    mysqli_stmt_execute($stmt);

    // store result to get num rows				
    $stmt->store_result();
            
    // get the number of rows
    $numRows = $stmt->num_rows;

    if ($numRows < 1) {

        // send an email to the admin         
        $to = ADMINEMAIL;  
        $subject = 'CONTRACT ARBITRATION FAIL: NO DATA FOUND ON PAGE';
        $logging = "No user contractID found at: ";
        $logging .= $createDate;
        $logging .= ' for contractID: '.$contractID;
        $logging .= ' for userID: '.$userID;			      
        $header = 'From: donotrespond@matcheos.com';
        if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

        // redirect - not valid
        header('Location: ./index.php?v=yes');	
        exit();

    } else {

        // bind result variables
        mysqli_stmt_bind_result($stmt, $spotID1Bind, $spotID2Bind, $offer1Bind, $offer2Bind, $status1Bind, $status2Bind, $rejectFlagBind, $contractArbFeeBind, $terminationReleaseBind);
    
        // fetch the results
        $stmt->fetch();
                    
        $spotID1 = $spotID1Bind;
        $spotID2 = $spotID2Bind;
        $offer1 = $offer1Bind;
        $offer2 = $offer2Bind;       
        $status1 = $status1Bind;
        $status2 = $status2Bind;
        $rejectFlag = $rejectFlagBind;
        $contractArbFee = $contractArbFeeBind;
        $terminationRelease = $terminationReleaseBind;

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

        if ((($status1 == "open") || ($status1 == "terminated")) && (($status2 == "open") || ($status2 == "terminated"))) {
            // do nothing
        } else {
            // invalid status - can only be open or terminated
            $invalidFlag = "yes";
        }        

        if (($status1 == "terminated") || ($status2 == "terminated")) {
            if (isset($terminationRelease)) {
                // tokens already released back - past 24 hour termination date
                $invalidFlag = "yes";
            }            
        }

        if ($rejectFlag == "yes") {
            // invalid if the contract was rejected
            $invalidFlag = "yes";
        }

        if (is_numeric($contractArbFee)) {
            $contractArbFee = (int) $contractArbFee;
            if (($contractArbFee < 0) || ($contractArbFee > 100)) {
                // invalid fee
                $invalidFlag = "yes";
            }
        } else {
            // invalid fee
            $invalidFlag = "yes";
        }

        if ($invalidFlag == "yes") {
            // send email to admin, invalid
            $to = ADMINEMAIL;  
            $subject = 'CONTRACT ARBITRATION FAIL: INVALID DATA FOUND ON PAGE';
            $logging = "Contract could not be arbitrated because of invalid data at: ";
            $logging .= $createDate;
            $logging .= ' for contractID: '.$contractID;
            $logging .= ' for userID: '.$userID;			      
            $header = 'From: donotrespond@matcheos.com';
            if (!strstr($_SERVER['HTTP_HOST'],"localhost")) {mail($to, $subject, $logging, $header);}			

            // redirect - not valid
            header('Location: ./index.php?v=yes');	
            exit();
        }
        
        // close statement
        mysqli_stmt_close($stmt);        

        // calculate arbitration fee with 4 decimals
        if ($spotID1 == $userID) {
            $arbitrationFee = number_format($offer1 * ($contractArbFee / 100),4);    
        } else {
            $arbitrationFee = number_format($offer2 * ($contractArbFee / 100),4);
        }
        
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

    <title>Matcheos - Arbitration</title>

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
                    <a class="nav-link" href="./analytics.php?v=<?=htmlspecialchars($loginCheck)?>">Analytics</a>
                </li>          
            </ul>
        </div>
    </nav>

    <!-- Header -->
    <header class="intro-header">
        <div class="container">
            <div class="form-group row pGroup" id="nGroup3">                                                    
                <div class="col-lg-4"></div>                                                
                <div class="col-lg-4">                 
                    <input class="form-control" id="arbFee" type="text" value="Arbitration Fee: <?=htmlspecialchars($arbitrationFee)?> EOS" disabled style="background-color:#d3d3d3;font-weight:bold;text-align:center">
                </div>                        
                <div class="col-lg-4"></div>                                                
            </div>                                                                                           
            <h2 style="padding-bottom:10px;">Arbitration is a serious action. You must provide evidence of a breach of the Code of Conduct. Per the Contract Terms, all arbitration decisions are final.</h2>                    
            <hr class="intro-divider">                      
            <div id="newSignup">
                <div class="form-group row pGroup" id="nGroup1">                                                    
                    <div class="col-lg-2">                                                             
                    </div>                                                
                    <div class="col-lg-8">                 
                        <h5>Please describe the issue in as much detail as possible:</h5>
                        <textarea id="detail" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                    </div>                                                
                    <div class="col-lg-2">                                         
                    </div>                                                
                </div>                                                
                <div class="form-group row pGroup" id="nGroup2">                                                    
                    <div class="col-lg-2">                                                             
                    </div>                                                
                    <div class="col-lg-8">                 
                        <h5>What provision of the <a href="./code.html" target="_blank" style="color:white;text-decoration:underline">Code of Conduct</a> was violated?</h5>
                        <textarea id="conduct" rows="3" style="-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;width: 100%;"></textarea>                                                                                              
                    </div>                        
                    <div class="col-lg-2">                                                 
                    </div>                                                
                </div>                                                
                
                <div class="form-group row pGroup" id="nGroup3">                        
                    <input type="hidden" id="newMatch4" value="">
                    <div class="col-lg-12">                                           
                        <input type="checkbox" name="terms" value="terms" id="terms" style="display:inline"><h5 style="display:inline;margin-left:10px">I agree to pay arbitration fee.</h5><br>
                        <input type="password" name="password" id="password" placeholder="Enter Password to Confirm" style="display:none"/>
                        <button type="submit" class="btn btn-primary" id="enterArbitration" style="margin-top:10px;margin-right:5px;box-shadow: 1px 1px 2px #888888;"><h4 style="margin-top:5px">Enter Arbitration</h4></button>                         
                        <div id="loading" style="display:none"><img src="./img/loading.gif" alt="loading" height="20" width="20">&nbsp;&nbsp;&nbsp;Sending</div>                    
                        <div id="message" class='alert alert-info' style="display:none"></div>
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
        
        // handle the arbitration submit        
        $("#enterArbitration").on( "click", function() {		

            // get the values
            var detail = $("#detail").val();
            var conduct = $("#conduct").val();            
            var contractID = <?=htmlspecialchars($contractID)?>;
            var token = "<?=htmlspecialchars($loginCheck)?>";
            var password = $("#password").val();

            var isTerms = $('#terms').is(":checked");

            // check terms is clicked
            if (isTerms != true) {                            
                alert("Please agree to the terms of arbitration.");
                return false;
            }
                
            // clear message, hide
            $( "#message" ).fadeOut("slow");
            $( "#message" ).text("");
            
            // validate the field exists
            if (!detail) {
                alert("The detailed description is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the field is more than 10 characters
            if (detail.length < 10) {
                alert("The detailed description is too short. Please enter a longer value.");                                        
                return false;
            }

            // validate the field is less than 5000 characters
            if (detail.length > 5000) {
                alert("The detailed description is too long. Please enter a shorter value (<5000 characters).");                                        
                return false;
            }

            // validate the field exists
            if (!conduct) {
                alert("The Code of Conduct section is invalid. Please enter a value.");                                        
                return false;
            }

            // validate the field is more than 5 characters
            if (conduct.length < 5) {
                alert("The Code of Conduct section is too short. Please enter a longer value.");                                        
                return false;
            }

            // validate the field is less than 5000 characters
            if (conduct.length > 5000) {
                alert("The Code of Conduct is too long. Please enter a shorter value (<5000 characters).");                                        
                return false;
            }

            <?php
            if ($sessionFlag == "no") {
            ?>
            if (!password) {
                alert('For security, please enter your password into the password field. Then hit the button again.');
                $( "#password" ).fadeIn("slow");
                return false;
            }
            <?php } ?>

            // set the post url
            var url = "./ajax/enterarbitration.php";

            // validate the id exists
            if (contractID) {

                // hide the button
                $( "#enterArbitration" ).fadeOut("slow");			
                
                // show the loading spinner		
                $( "#loading" ).fadeIn("slow");
                                
                <?php
                if ($sessionFlag == "no") {
                ?>
                var posting = $.post( url, { field1: detail, field2: conduct, field3: contractID, field4: token, field5: password} );
                <?php } else { ?>
                var posting = $.post( url, { field1: detail, field2: conduct, field3: contractID, field4: token} );
                <?php }  ?>

                // Put the results in a div 
                posting.done(function( data ) {	

                    // Receive status code
                    var jsonData  = JSON.parse(data);					
                    var ajaxResult = jsonData.ajaxResult;		                    
                    ajaxResult = Number(ajaxResult);
                    $( "#loading" ).fadeOut("slow");		
                    $( "#enterArbitration" ).fadeIn("slow");			
                    $( "#message" ).fadeIn("slow");
                    
                    // If no errors, got to results
                    switch(ajaxResult) {
                        case 1:
                            // show success message
                            $( '#message' ).text("Your arbitration is now being reviewed. All decisions are final.");                            
                            $( "#enterArbitration" ).fadeOut("slow");			
                            setTimeout(function(){ 
                                window.location.href = "./match.php?v=<?=htmlspecialchars($loginCheck)?>";                                                                    
                             }, 3000);                            
                            break;                        
                        default:
                            $( '#message' ).text("There was a problem with the submission. Please try later.");                                                    
                    }							
                });
        
                /* Check for error */
                posting.fail(function( data ) {			
                    $( "#loading" ).fadeOut("slow");
                    $( "#enterArbitration" ).fadeIn("slow");			
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