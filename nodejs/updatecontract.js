// require libraries
var Eos = require('eosjs');
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var offer = process.argv[3];
var contractName = process.argv[4];
var userName = process.argv[5];
var userPrivate = process.argv[6];
var keyType = process.argv[7];
var keyProvider = "";    
var eos = "";    

// set the output message
var outputMessage = "success";

// validate inputs
validateName(accountName,1);
validateNumber(offer,2);
validateName(contractName,4);
validateName(userName,4);
validateKey(userPrivate,5);
validateString(keyType,6);

// validate
function validateName(txt,num){
    if (!txt) {
        outputMessage = "error" + num;
    } else if (txt.length > 12) {
        outputMessage = "error" + num;
    }     
}

// validate
function validateKey(txt,num){
    if (!txt) {
        outputMessage = "error" + num;
    } else if (txt.length < 49) {
        outputMessage = "error" + num;
    }     
}

// validate
function validateString(txt,num){
    if (!txt) {
        outputMessage = "error" + num;
    } else if (txt.length > 500) {
        outputMessage = "error" + num;
    }     
}

// validate
function validateNumber(txt,num){    
    if (!txt) {
        outputMessage = "error" + num;
    } else if (parseInt(txt) < 0) {
        outputMessage = "error" + num;
    }     
}

// if still ok
if (outputMessage == "success") {
    // set the network
    keyProvider = userPrivate;    
    eos = Eos.Localnet({keyProvider,httpEndpoint:path3.blockurl});    
    checkExists();
} else {
    finalReturn(outputMessage);
}

function checkExists() {    
    // verify contract does not exist
    eos.getCode(contractName)
    .then(result => {
        // code has should be 0000000000000000000000000000000000000000000000000000000000000000 if no exists
        if (result["code_hash"] == "0000000000000000000000000000000000000000000000000000000000000000") {
            // contract does not exist
            finalReturn("error101");            
        } else {            
            // move on to checking user
            getTableData();
        }
    })
    .catch(function(exception) {        
        // api is throwing an error if key not found
        if (exception) {
            // move on to checking user
            getTableData();
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
        var totalRows = result["rows"].length;
        var counter = 0;
        var keyData = "";
        var valueData = "";
        var currentSteps = 0;        
        var currentBalanceP1 = 0;        
        var currentBalanceP2 = 0;        
        var currentReleaseP1 = 0;        
        var currentReleaseP2 = 0;        
        var person1 = "";
        var person2 = "";
        var person1Start = 0;
        var person2Start = 0;
        var totalSteps = 0;
        var matcheosFee = 0;
        var feeAccount = "";
        var arbFee = 0;
        var arbAccount = "";
        var arbPayout = 0;
        var arbPerson1 = 0;
        var arbPerson2 = 0;
        var arbMatcheos = 0;        
        var arb = "no";
        var terminate = "no";        
        var reject = "no";
        // cycle through rows
        while (counter < totalRows) {

            keyData = result["rows"][counter]["key"];
            valueData = result["rows"][counter]["value"];

            // check how many steps
            if (keyData == "totalsteps") {
                totalSteps = parseInt(valueData);
            }

            // check person1
            if (keyData == "person1") {
                person1 = valueData;
            }

            // check person2
            if (keyData == "person2") {
                person2 = valueData;
            }
            
            // check person1 starting balance
            if (keyData == "person1start") {
                person1Start = parseFloat(valueData);
            }

            // check person2 starting balance
            if (keyData == "person2start") {
                person2Start = parseFloat(valueData);
            }                                    

            // check matcheos fee
            if (keyData == "contractfee") {
                matcheosFee = parseInt(valueData);
            }

            // check arbitration fee
            if (keyData == "arbfee") {
                arbFee = parseInt(valueData);
            }

            // check arbitration account
            if (keyData == "arbaccount") {
                arbAccount = valueData;
            }            

            // check matcheos fee account
            if (keyData == "feeaccount") {
                feeAccount = valueData;
            }

            // check for arbitration flag
            if ((keyData == "arb") && (valueData == "yes")) {
                arb = "yes";
            }

            // check for termination flag
            if ((keyData == "terminate") && (valueData == "yes")) {
                terminate = "yes";
            }

            // check for rejection flag
            if ((keyData == "reject") && (valueData == "yes")) {
                reject = "yes";
            }

            // calculate the current steps
            if (keyData.indexOf("stepno") !== -1) {
                currentSteps = currentSteps + 1; 
            }
        
           counter++;
        }    
        
        // validate only step 1 of no steps
        if (currentSteps > 1) {
            outputMessage = "error11";
        }
        
        // validate userName is person2
        if (person2 != userName) {
            outputMessage = "error12";
        }

        // validate personStart2 is not set - still should zero
        if (person2Start != 0) {
            outputMessage = "error12";
        }
        
        // validate not terminated, arbitration, or rejected
        if ((terminate == "no") && (arb == "no") && (reject == "no")) {
            // do nothing
        } else {
            outputMessage = "error16";
        }
        
        if (outputMessage == "success") {                                
            updateContract();            
        } else {            
            finalReturn(outputMessage);
        }

    })
    .catch(function(exception) {                
        if (exception) {                        
            finalReturn("error10")
        }
    })       

} 

// update contract
function updateContract() {
    
    eos.contract(contractName)    
    .then((contract) => {        
        contract.dbinsert({sender:accountName,key:"person2start",value:offer},{scope: accountName, authorization: contractName + "@" + keyType})                
        .then(trx => {
            this.transaction = trx;            
            finalReturn(outputMessage);
        }).catch(e => {            
            finalReturn("error302");            
        })        
    })
    .catch(function(exception) {                
        if (exception) {
            console.log("error500")
        }
    })
        
}

// output final return state
function finalReturn(outputMessage) {
    console.log(outputMessage);
}