// require libraries
var Eos = require('eosjs');
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var contractName = process.argv[3];
var tempPrivate = process.argv[4];
var currentPerson = process.argv[5];
var keyType = process.argv[6];
var keyProvider = "";    
var eos = "";    

// set the currency contract parameters
var token = "EOS";
var code = "eosio.token";

// set the output message
var outputMessage = "success";

// set the step release
var stepReleaseP1 = 0;
var stepReleaseP2 = 0;

// validate account
if (!accountName) {
    outputMessage = "error1";
} else if (accountName.length > 12) {
    outputMessage = "error2";
} 

// validate contract
if (!contractName) {
    outputMessage = "error3";
} else if (contractName.length > 12) {
    outputMessage = "error4";
} 

// validate the keys
if (!tempPrivate) {
    outputMessage = "error5";
} else if (tempPrivate.length < 49) {
    outputMessage = "error6";
} 

// validate current person
if (!currentPerson) {
    outputMessage = "error7";
} else if (currentPerson.length > 12) {
    outputMessage = "error8";
} 

// validate keyType
if ((keyType == "active") || (keyType == "owner")) {
    // do nothing
} else {
    outputMessage = "error212";
} 

// if still ok
if (outputMessage == "success") {
    // set the network
    keyProvider = tempPrivate;    
    eos = Eos.Localnet({keyProvider,httpEndpoint: path3.blockurl});    
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
        var matcheosFeePayout = 0;
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
        
        // validate only step 1 of no steps
        if (currentSteps > 1) {
            outputMessage = "error11";
        }
        
        // validate person1 is not the current person rejecting already started contract
        if (person1 == currentPerson) {
            outputMessage = "error12";
        }

        // validate only other person starting balance minus any first release
        if (person2 == currentPerson) {
            if (currentBalanceP1 <= 0) {
                outputMessage = "error13";
            }
            if (currentBalanceP2 > 0) {
                outputMessage = "error14";
            }            
        } else {
            outputMessage = "error15";
        }

        // validate not terminated, arbitration, or rejected
        if ((terminate == "no") && (arb == "no") && (reject == "no")) {
            // do nothing
        } else {
            outputMessage = "error16";
        }
        
        if (outputMessage == "success") {                                
            checkWallet(person1,currentBalanceP1);
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
function checkWallet(person1,currentBalanceP1) {
    var expectedBalance = Number(currentBalanceP1);    
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
                updateContract(person1,expectedBalance);
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
function updateContract(person1,expectedBalance) {
    
    eos.contract(contractName)    
    .then((contract) => {        
        contract.dbinsert({sender:accountName,key:"reject",value:"yes"},{scope: accountName, authorization: contractName + "@" + keyType})                
        .then(trx => {
            this.transaction = trx;            
            transferFunding(person1,expectedBalance);
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
function transferFunding(person1,expectedBalance) {    
    var options = {broadcast: true};    
    eos
    .contract('eosio.token')
    .then((contract) => {
        contract.transfer( {from:accountName,to:person1,quantity:expectedBalance + " " + token,memo:"rejected release"},
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