var Eos = require('eosjs');
var {} = Eos.modules;
var path3 = require('./include/path3.js');

// set the values
var code = process.argv[2];
var accountName = process.argv[3];
var token = process.argv[4];
var blockchainurl = process.argv[5];
var finalBalance = 0;

// set the output message
var outputMessage = "success";

// validate code
if (!code) {
    outputMessage = "error1";
} else if (code.length > 12) {
    outputMessage = "error2";
} 

// validate accountname
if (!accountName) {
    outputMessage = "error3";
} else if (accountName.length > 12) {
    outputMessage = "error4";
} 

// validate token
if (!token) {
    outputMessage = "error5";
} else if (token.length > 12) {
    outputMessage = "error6";
} 

// validate the url - if sent
if (blockchainurl) {    
    if (blockchainurl.length < 5) {
        outputMessage = "error8";
    } else if (blockchainurl.indexOf("http://") === -1) {
        outputMessage = "error9";    
    }
}

if (outputMessage == "success") {
    // check balance            
    var eos = Eos.Localnet({httpEndpoint: path3.blockurl});        
    callback = (err, res) => {err ? finalReturn("0") : validateBalance(res)}
    eos.getCurrencyBalance(code,accountName,token,callback)

    function validateBalance(res) {    
        var tokenBalance = [];
        if (res) {
            var resString = res.toString();
            if (resString.length > 3) {
                // parse the number from token symbol
                tokenBalance = resString.split(" ");           
            }

            // get the final balance
            if (tokenBalance.length == 2) {
                finalBalance = tokenBalance[0];            
            }
        } 
        finalReturn(finalBalance);
    }
} else {
    finalReturn(finalBalance);
}

function finalReturn(finalBalance) {
    console.log(finalBalance);
}