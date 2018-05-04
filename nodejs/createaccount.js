var Eos = require('eosjs');
var {} = Eos.modules;
var path3 = require('./include/path3.js');

var accountName = process.argv[2];
var publicKey1 = process.argv[3];
var publicKey2 = process.argv[4];
var matcheosPrivate = process.argv[5];
var matcheosAccount = process.argv[6];

// set the output message
var outputMessage = "success";

// validate accountname
if (!accountName) {
    outputMessage = "error1";
} else if (accountName.length > 12) {
    outputMessage = "error2";
} 

// validate the keys
if (!publicKey1) {
    outputMessage = "error3";
} else if (publicKey1.length < 49) {
    outputMessage = "error4";
} else if (publicKey1.indexOf("EOS") == -1) {
    outputMessage = "error5";
}

// validate the keys
if (!publicKey2) {
    outputMessage = "error6";
} else if (publicKey2.length < 49) {
    outputMessage = "error7";
} else if (publicKey2.indexOf("EOS") == -1) {
    outputMessage = "error8";
}

// validate the keys
if (!matcheosPrivate) {
    outputMessage = "error9";
} else if (matcheosPrivate.length < 49) {
    outputMessage = "error10";
} 

// validate accountname
if (!matcheosAccount) {
    outputMessage = "error11";
} else if (matcheosAccount.length > 12) {
    outputMessage = "error12";
} 

// if still ok
if (outputMessage == "success") {

    var keyProvider = matcheosPrivate;    
    var eos = Eos.Localnet({keyProvider,httpEndpoint: path3.blockurl});            
    eos.newaccount({
      creator: matcheosAccount,
      name: accountName,
      owner: publicKey1,
      active: publicKey2,
      recovery: "matcheos",          
    });

    // replace callback with a console message
    console.log(outputMessage);

} else {

    // output error
    console.log(outputMessage);

}
