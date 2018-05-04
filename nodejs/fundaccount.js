var Eos = require('eosjs');
var {} = Eos.modules;
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var userName = process.argv[3];
var offer = process.argv[4];
var userPrivate = process.argv[5];

// set the output message
var outputMessage = "success";

// validate accountname
if (!accountName) {
    outputMessage = "error1";
} else if (accountName.length > 12) {
    outputMessage = "error2";
} 

// validate username
if (!userName) {
    outputMessage = "error3";
} else if (userName.length > 12) {
    outputMessage = "error4";
} 

// validate the offer
offer = parseFloat(offer);
if (!offer) {
    outputMessage = "error5";
} else if (offer <= 0) {
    outputMessage = "error6";
} 

// validate the keys
if (!userPrivate) {
    outputMessage = "error7";
} else if (userPrivate.length < 49) {
    outputMessage = "error8";
} 

// if still ok
if (outputMessage == "success") {

    // transfer funding
    var keyProvider = userPrivate;    
    var eos = Eos.Localnet({keyProvider,httpEndpoint: path3.blockurl});        
    var options = {broadcast: true};
    eos
    .contract('eosio.token')
    .then((contract) => {
        contract.transfer( {from:userName,to:accountName,quantity:offer.toFixed(4) + " EOS",memo:"fund account"},
        { scope: 'eosio.token', authorization: [userName] })
        .then(trx => {
            this.transaction = trx;            
            checkContract();
        })
        .catch(function (err) { console.log('error100'); })        
    })          
    
} else {

    // output error
    console.log(outputMessage);

}

// check if contract has been published already
function checkContract() {        
    eos.getCode(accountName)
    .then(result => {
        // code has should be 0000000000000000000000000000000000000000000000000000000000000000 if no exists
        if (result["code_hash"] != "0000000000000000000000000000000000000000000000000000000000000000") {
            // contract exists - check if there are any rows
            getTableData();
        } else {
            // contract does not exist
            finalReturn("successNoContractExists");
        }
    })
    .catch(function(exception) {        
        // api is throwing an error if key not found
        if (exception) {
            console.log("successNoContractExists")
        }
    })       
}

// query the contract and pull rows
function getTableData() {    

    eos.getTableRows({
        "json": true,
        "scope": accountName,
        "code": accountName,
        "table": "keyvalues",
        "limit": 500
    }).then(result => {
        
        // check the number of rows
        if (result["rows"]) {
            var totalRows = result["rows"].length;            
            if (totalRows > 0) {
                console.log("successContractExists");
            } else {
                console.log("successNoContractExists");
            }
        } else {
            finalReturn("error110");
        }        
        
    })
    .catch(function(exception) {                
        if (exception) {            
            finalReturn("error200");
        }
    })       
} 

// output final return state
function finalReturn(outputMessage) {
    console.log(outputMessage);
}
