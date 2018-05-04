var Eos = require('eosjs');
var {} = Eos.modules;
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var userName = process.argv[3];
var offer = process.argv[4];
var userPrivate = process.argv[5];
var contractName = process.argv[6];
var keyProvider = userPrivate;    
var eos = Eos.Localnet({keyProvider,httpEndpoint: path3.blockurl});        

// set the output message
var outputMessage = "successContractExists";

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
if (!offer) {
    outputMessage = "error5";
} else if (Number(offer) <= 0) {
    outputMessage = "error6";
} 

// validate the keys
if (!userPrivate) {
    outputMessage = "error7";
} else if (userPrivate.length < 49) {
    outputMessage = "error8";
} 

// validate the contractname
if (!contractName) {
    outputMessage = "error9";
} else if (contractName.length > 12) {
    outputMessage = "error10";
} 

// if still ok
if (outputMessage == "successContractExists") {    
    verifyFunds();
} else {
    finalReturn(outputMessage);
}

// verify funds sent from account to wallet
function verifyFunds () {        
    
    eos.getTransactions(accountName).then(result => {validateFunds(result)})        
    function validateFunds(result){        
        // cycle through all transactions
        var totalRows = result["transactions"].length;
        var counter = 0;
        var transData = "";
        var transDataFrom = "";
        var transDataTo = "";
        var transDataAmount = "";
        var currentSent = 0;        
        var transDataAmountArray = [];

        while (counter < totalRows) {            
            transData = result["transactions"][counter];
            transData = transData["transaction"]["transaction"];
            transData = transData["actions"][0]["data"];
            transDataFrom = transData["from"];
            transDataTo = transData["to"];
            transDataAmount = transData["quantity"];            
            // parse the quantity
            if (transDataAmount) {
                if (transDataAmount.length > 0) {
                    transDataAmountArray = transDataAmount.split(" ");                    
                } 
                if (transDataAmountArray.length == 2) {
                    transDataAmount = parseFloat(transDataAmountArray[0]);              
                } else {
                    outputMessage = "error777";
                }
            }

            // check if transaction is from user to wallet
            if ((transDataFrom == userName) && (transDataTo == accountName)) {
                // add amount to currentSent                
                currentSent = currentSent + transDataAmount;
            }
            counter++;
        }
        
        // check if amount is greater than offer
        if ((currentSent >= offer) && (outputMessage == "successContractExists")) {
            // move on to check if contract exists
            checkContract();
        } else {
            finalReturn("error101");
        }    
    }
}

// check if contract has been published already
function checkContract() {        
    eos.getCode(contractName)
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
        "code": contractName,
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
