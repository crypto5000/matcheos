// require libraries
var Eos = require('eosjs');
var {} = Eos.modules;
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var contractName = process.argv[3];
var tempPrivate = process.argv[4];
var userName1 = process.argv[5];
var userName2 = process.argv[6];
var userNameTerminated = process.argv[7];
var keyType = process.argv[8];
var keyProvider = "";    
var eos = "";    

// set the currency contract parameters
var token = "EOS";
var code = "eosio.token";

// set the step release
var stepReleaseP1 = 0;
var stepReleaseP2 = 0;

// set the output message
var outputMessage = "success";

// validate inputs
validateName(accountName,1);
validateName(contractName,2);
validateKey(tempPrivate,3);
validateName(userName1,4);
validateName(userName2,5);
validateName(userNameTerminated,6);
validateString(keyType,7);

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
    keyProvider = tempPrivate;    
    eos = Eos.Localnet({keyProvider,httpEndpoint:path3.blockurl});    
    getTableData();
} else {
    finalReturn(outputMessage);
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
        
        // calculate the amount to release to person1 for prior steps - person1 has 1 more step than person2 - fee on step1
        if (totalSteps > 0) {
            stepReleaseP1 = parseFloat(person1Start/totalSteps);
            stepReleaseP1 = stepReleaseP1.toFixed(4);            
        } else {
            stepReleaseP1 = 0;
        }

        // calculate the amount to release to person2 for prior steps - fee subtracted if step 2
        if ((totalSteps > 1) && (currentSteps > 0)) {
            stepReleaseP2 = parseFloat(person2Start/(totalSteps - 1));
            stepReleaseP2 = stepReleaseP2.toFixed(4);            
        } else {
            stepReleaseP2 = 0;
        }

        // calculate the current remaining balance for person1 (has 1 more step than person2)
        var loop = 0;
        if (totalSteps > 0) {
            // sum up the released amounts in discrete units (no float rounding)
            while (loop < currentSteps) {            
                currentReleaseP1 = currentReleaseP1 + Number(stepReleaseP1);                                    
                loop++;
            }        
            currentReleaseP1 = Number(currentReleaseP1.toFixed(4));
            currentBalanceP1 = person1Start - currentReleaseP1;
            currentBalanceP1 = currentBalanceP1.toFixed(4);
        } else {
            currentBalanceP1 = 0;
        }

        // calculate the current remaining balance for person2 (has 1 less step than person1)        
        if ((totalSteps > 1) && (currentSteps > 0)) {            
            loop = 0;
            // sum up the released amounts in discrete units (no float rounding)
            while (loop < currentSteps - 1) {            
                currentReleaseP2 = currentReleaseP2 + Number(stepReleaseP2);                                    
                loop++;
            }        
            currentReleaseP2 = Number(currentReleaseP2.toFixed(4));            
            currentBalanceP2 = person2Start - currentReleaseP2;
            currentBalanceP2 = currentBalanceP2.toFixed(4);
        } else {
            currentBalanceP2 = 0;
        }
        
        // validate the final release for person1
        if (totalSteps > 0) {
            var finalStepReleaseP1 = currentBalanceP1;
            // validate the decimals - 4 units of precision
            var difference1 = person1Start.toFixed(4) - currentReleaseP1.toFixed(4) - finalStepReleaseP1;
            if (Math.abs(difference1) >= 0.000001) {                
                outputMessage = "error1000";
            }

        } 

        // validate the final release for person2
        if ((totalSteps > 1) && (currentSteps > 0)) {
            var finalStepReleaseP2 = currentBalanceP2;
            // validate the decimals - 4 units of precision
            var difference2 = person2Start.toFixed(4) - currentReleaseP2.toFixed(4) - finalStepReleaseP2;
            if (Math.abs(difference2) >= 0.000001) {                
                outputMessage = "error1001";
            }
        } 
        
        // validate person1 
        if (person1 != userName1) {
            outputMessage = "error12";         
        }

        if (currentBalanceP1 < 0) {
            outputMessage = "error13";
        }

        // validate person2
        if (person2 != userName2) {            
            outputMessage = "error14";                    
        } 

        if (currentBalanceP2 < 0) {
            outputMessage = "error15";
        }            

        // validate not terminated, arbitration, or rejected
        if ((terminate == "no") && (arb == "no") && (reject == "no")) {
            // do nothing
        } else {
            outputMessage = "error16";
        }
        
        if (outputMessage == "success") {                                
            checkWallet(person1,person2,currentBalanceP1,currentBalanceP2);
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

// check current wallet equals expected balance
function checkWallet(person1,person2,currentBalanceP1,currentBalanceP2) {
    var expectedBalance = Number(currentBalanceP1) + Number(currentBalanceP2);    
    callback = (err, res) => {err ? finalReturn("error31") : validateBalance(res)}
    eos.getCurrencyBalance(code,accountName,token,callback)

    function validateBalance(res) {    
        var tokenBalance = [];
        if (res) {
            var resString = res.toString();
            var finalBalance = -1;

            if (resString.length > 3) {
                // parse the number from token symbol
                tokenBalance = resString.split(" ");           
            }

            // get the final balance
            if (tokenBalance.length == 2) {
                finalBalance = tokenBalance[0];            
                finalBalance = parseFloat(finalBalance);
            }

            // check if balance equals expected - 4 digits of precision
            if ( Math.abs(Number(finalBalance.toFixed(4)) - Number(expectedBalance.toFixed(4))) < 0.00001 ) {                                    
                updateContract(person1,person2,currentBalanceP1,currentBalanceP2);                       
            } else {
                // balance error
                finalReturn("error32");
            }

        } else {
            // balance not found
            finalReturn("error33");
        }        
    }
}

// update contract
function updateContract(person1,person2,currentBalanceP1,currentBalanceP2) {
    
    eos.contract(contractName)    
    .then((contract) => {        
        contract.dbinsert({sender:accountName,key:"terminate",value:"yes"},{scope: accountName, authorization: contractName + "@" + keyType})                
        .then(trx => {
            this.transaction = trx;            
            transferFunding1(person1,person2,currentBalanceP1,currentBalanceP2);
        }).catch(e => {            
            finalReturn("error20");            
        })        
    })
    .catch(function(exception) {                
        if (exception) {
            console.log("error21")
        }
    })
        
}

// transfer funding to person1
function transferFunding1(person1,person2,currentBalanceP1,currentBalanceP2) {    
    var options = {broadcast: true};    
    eos
    .contract('eosio.token')
    .then((contract) => {
        contract.transfer( {from:accountName,to:person1,quantity:currentBalanceP1 + " " + token,memo:"terminated release"},
        { scope: code, authorization: [accountName + "@" + keyType] })
        .then(trx => {
            this.transaction = trx;            
            // transfer to person2 only if they have positive balance - person1 could have terminated at first step
            if (currentBalanceP2 > 0) {
                transferFunding2(person1,person2,currentBalanceP1,currentBalanceP2);
            } else {
                finalReturn(outputMessage);    
            }
        })
        .catch(function (err) { console.log('error100'); })        
    })          
}

// transfer funding to person2
function transferFunding2(person1,person2,currentBalanceP1,currentBalanceP2) {    
    var options = {broadcast: true};    
    eos
    .contract('eosio.token')
    .then((contract) => {
        contract.transfer( {from:accountName,to:person2,quantity:currentBalanceP2 + " " + token,memo:"terminated release"},
        { scope: code, authorization: [accountName + "@" + keyType] })
        .then(trx => {
            this.transaction = trx;            
            finalReturn(outputMessage);
        })
        .catch(function (err) { console.log('error100'); })        
    })          
}

// output final return state
function finalReturn(outputMessage) {
    console.log(outputMessage);
}