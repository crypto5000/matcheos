// require libraries
var Eos = require('eosjs');
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var contractName = process.argv[3];
var tempPrivate = process.argv[4];
var userName1 = process.argv[5];
var userName2 = process.argv[6];
var ruling1 = process.argv[7];
var ruling2 = process.argv[8];
var rulingDonee = process.argv[9];
var arbFeePayorName = process.argv[10];
var keyType = process.argv[11];
var arbPaidFee = 0;
var stepReleaseP1 = 0;
var stepReleaseP2 = 0;
var keyProvider = "";    
var eos = "";    

// set the output message
var outputMessage = "success";

// set the currency contract parameters
var token = "EOS";
var code = "eosio.token";

// validate inputs
validateName(accountName,1);
validateName(contractName,2);
validateKey(tempPrivate,3);
validateName(userName1,4);
validateName(userName2,5);
validateNumber(ruling1,6);
validateNumber(ruling2,7);
validateNumber(rulingDonee,8);
validateName(arbFeePayorName,9);
validateString(keyType,10);

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

// check ruling percents add up to 100
if (outputMessage == "success") {
    if (parseInt(ruling1) + parseInt(ruling2) + parseInt(rulingDonee) != 100) {
        outputMessage == "error1000"
    }
}

// if still ok
if (outputMessage == "success") {
    // set the network
    keyProvider = tempPrivate;    
    eos = Eos.Localnet({keyProvider, httpEndpoint: path3.blockurl});    
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
        
        // validate some steps exist
        if (currentSteps < 1) {
            outputMessage = "error11";
        }
        
        // validate person1 is userName1
        if (person1 != userName1) {
            outputMessage = "error12";
        }

        // validate person2 is userName2
        if (person2 != userName2) {           
            outputMessage = "error13";                        
        } 

        // validate the non-arb fee - matcheos fee
        if (currentSteps < 3) {
            // NOTE: matcheos fee (if exists) normally paid on step 1 and step 2.
            // NOTE: So no matcheos fee if arbitration occurs on step 1 or 2 - fee replaced with arbitration fee
        }

        // validate not terminated, arbitration, or rejected
        if ((terminate == "no") && (arb == "no") && (reject == "no")) {
            // do nothing
        } else {
            outputMessage = "error16";
        }
        
        // validate the raw arb fee and who should normally pay the fee
        if (person1 == arbFeePayorName) {
            arbPaidFee = parseFloat(arbFee/100) * person1Start;
            arbPaidFee = arbPaidFee.toFixed(4);            
        } else {
            arbPaidFee = parseFloat(arbFee/100) * person2Start;
            arbPaidFee = arbPaidFee.toFixed(4);
            arbFeePayorName = person2;
        }

        if (outputMessage == "success") {                                
            checkWallet(currentBalanceP1,currentBalanceP2,arbPaidFee,arbFeePayorName,person1,person2,arbAccount,feeAccount);
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
function checkWallet(currentBalanceP1,currentBalanceP2,arbPaidFee,arbFeePayorName,person1,person2,arbAccount,feeAccount) {

    var expectedBalance = Number(currentBalanceP1) + Number(currentBalanceP2);    
    callback = (err, res) => {err ? finalReturn("31") : validateBalance(res)}
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

            // check if balance equals expected
            if (finalBalance.toFixed(4) == expectedBalance.toFixed(4)) {                                    
                // calculate releases
                calculateRelease(currentBalanceP1,currentBalanceP2,arbPaidFee,arbFeePayorName,person1,person2,arbAccount,feeAccount);
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

// calculate the release balances
function calculateRelease(currentBalanceP1,currentBalanceP2,arbPaidFee,arbFeePayorName,person1,person2,arbAccount,feeAccount) {
 
    var person1Release = 0;
    var person2Release = 0;
    var doneeRelease = 0;
    var totalWallet = Number(currentBalanceP1) + Number(currentBalanceP2);

    // validate the totalWallet
    if (totalWallet < 0) {
        outputMessage = "error2000";
        totalWallet = 0;
    }

    // check that the total wallet is bigger than the fee
    if (totalWallet <= Number(arbPaidFee)) {
        
        // if balances are too small, dust goes to fee
        arbPaidFee = totalWallet;        

    } else {

        // calculate the ruling amounts from the percentages - ensure 4 decimals of precision
        if (Number(rulingDonee) > 0) {
            person1Release = (parseFloat(ruling1)/100) * totalWallet;
            person1Release = Number(person1Release.toFixed(4));
            person2Release = (parseFloat(ruling2)/100) * totalWallet;
            person2Release = Number(person2Release.toFixed(4));
            doneeRelease = totalWallet - person1Release - person2Release;        
        } else if (Number(ruling2) > 0) {    
            person1Release = (parseFloat(ruling1)/100) * totalWallet;
            person1Release = Number(person1Release.toFixed(4));
            person2Release = totalWallet - person1Release;
            doneeRelease = 0;        
        } else {
            person2Release = (parseFloat(ruling2)/100) * totalWallet;
            person2Release = Number(person2Release.toFixed(4));
            person1Release = totalWallet - person2Release;
            doneeRelease = 0;        
        }

        // remove the fee from the appropriate person
        if (arbFeePayorName == person1) {
            person1Release = Number(person1Release) - Number(arbPaidFee);
        }

        // remove the fee from the appropriate person
        if (arbFeePayorName == person2) {
            person2Release = Number(person2Release) - Number(arbPaidFee);
        }

        // validate the release amount
        if (person1Release < 0) {            

            // adjust the arbPaidFee to come from donee, if person doesn't have enough balance
            if (Number(doneeRelease) >= Number(person1Release) * (-1)) {
                // person 1 is negative, so adding reduces it
                doneeRelease = Number(doneeRelease) + Number(person1Release);                                
            } else {
                // donee doesn't have enough balance either, just reduce fee rather than taking from person2
                // because person2 should have good ruling if no doneeRelease balance 
                arbPaidFee = Number(arbPaidFee) + Number(person1Release);
            }

            person1Release = 0;
        } 

        if (person2Release < 0) {
            
            // adjust the arbPaidFee to come from donee, if person doesn't have enough balance
            if (Number(doneeRelease) >= Number(person2Release) * (-1)) {
                // person 2 is negative, so adding reduces it
                doneeRelease = Number(doneeRelease) + Number(person2Release);                                
            } else {
                // donee doesn't have enough balance either, just reduce fee rather than taking from person1
                // because person1 should have good ruling if no doneeRelease balance 
                arbPaidFee = Number(arbPaidFee) + Number(person2Release);
            }

            person2Release = 0;
        }


        // convert to 4 decimal precision
        person1Release = person1Release.toFixed(4);
        person2Release = person2Release.toFixed(4);
        doneeRelease = doneeRelease.toFixed(4);

        // validate the total released is the total wallet - 4 digits of precision
        if (Math.abs(Number(person1Release) + Number(person2Release) + Number(doneeRelease) + Number(arbPaidFee) - Number(totalWallet.toFixed(4))) < 0.00001  ) {
                        
            // validate all balances are positive
            if (Number(person1Release) < 0) {
                outputMessage = "error3001";
            }

            // validate all balances are positive
            if (Number(person2Release) < 0) {
                outputMessage = "error3002";
            }

            // validate all balances are positive
            if (Number(doneeRelease) < 0) {
                outputMessage = "error3003";
            }

            // validate all balances are positive
            if (Number(arbPaidFee) < 0) {
                outputMessage = "error3004";
            }

        } else {

            // balance error, fee too big for balances
            outputMessage = "error3000";
        
        }

    }

    if (outputMessage == "success") {
        // update contract
        updateContract(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
    } else {
        finalReturn(outputMessage);
    }
    
}    

// update contract
function updateContract(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount) {    
    
    eos.contract(contractName)    
    .then((contract) => {        
        contract.dbinsert({sender:accountName,key:"arb",value:"yes"},{scope: accountName, authorization: contractName + "@" + keyType})                
        contract.dbinsert({sender:accountName,key:"arbperson1",value:person1Release},{scope: accountName, authorization: contractName + "@" + keyType})                
        contract.dbinsert({sender:accountName,key:"arbperson2",value:person2Release},{scope: accountName, authorization: contractName + "@" + keyType})                
        contract.dbinsert({sender:accountName,key:"arbdonee",value:doneeRelease},{scope: accountName, authorization: contractName + "@" + keyType})                
        contract.dbinsert({sender:accountName,key:"arbmatcheos",value:arbPaidFee},{scope: accountName, authorization: contractName + "@" + keyType})                
        .then(trx => {
            this.transaction = trx;            
            if (Number(arbPaidFee) <= 0) {
                finalReturn("error23");            
            } else {            
                transferFundingFee(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
            }
        }).catch(e => {            
            finalReturn("error301");            
        })        
    })
    .catch(function(exception) {                
        if (exception) {
            console.log("error21")
        }
    })
        
}

// transfer funding - fee should exist
function transferFundingFee(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount) {        
    var options = {broadcast: true};    
    eos
    .contract(code)
    .then((contract) => {
        contract.transfer( {from:accountName,to:feeAccount,quantity:arbPaidFee + " " + token,memo:"arbitration release"},
        { scope: code, authorization: [accountName + "@" + keyType] })
        .then(trx => {
            this.transaction = trx;            
            if (Number(doneeRelease) <= 0) {
                // skip donee - nothing to release
                transferFundingP1(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
            } else {
                transferFundingDonee(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
            }
        })
        .catch(function (err) { console.log('error100'); })        
    })              
}

// transfer funding to donee - may not exist
function transferFundingDonee(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount) {    
    var options = {broadcast: true};    
    eos
    .contract(code)
    .then((contract) => {
        contract.transfer( {from:accountName,to:arbAccount,quantity:doneeRelease + " " + token,memo:"arbitration release"},
        { scope: code, authorization: [accountName + "@" + keyType] })
        .then(trx => {
            this.transaction = trx;            
            if (Number(person1Release) <= 0) {
                // skip person1 - nothing to release
                transferFundingP2(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
            } else {
                transferFundingP1(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
            }
        })
        .catch(function (err) { console.log('error101'); })        
    })              
}

// transfer funding to person1 - may not exist
function transferFundingP1(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount) {    
    if (Number(person1Release > 0)) {
        var options = {broadcast: true};    
        eos
        .contract(code)
        .then((contract) => {
            contract.transfer( {from:accountName,to:person1,quantity:person1Release + " " + token,memo:"arbitration release"},
            { scope: code, authorization: [accountName + "@" + keyType] })
            .then(trx => {
                this.transaction = trx;            
                if (Number(person2Release) <= 0) {
                    // finish - nothing to release
                    finalReturn(outputMessage);
                } else {
                    transferFundingP2(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
                }
            })
            .catch(function (err) { console.log('error102'); })        
        })              
    } else {
        // skip to person2
        transferFundingP2(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount);
    }
}

// transfer funding to person2 - may not exist
function transferFundingP2(person1Release,person2Release,doneeRelease,arbPaidFee,person1,person2,arbAccount,feeAccount) {    
    if (Number(person2Release > 0)) {
        var options = {broadcast: true};    
        eos
        .contract(code)
        .then((contract) => {
            contract.transfer( {from:accountName,to:person2,quantity:person2Release + " " + token,memo:"arbitration release"},
            { scope: code, authorization: [accountName + "@" + keyType] })
            .then(trx => {
                this.transaction = trx;            
                finalReturn(outputMessage);
            })
            .catch(function (err) { console.log('error103'); })        
        })              
    } else {
        // finish
        finalReturn(outputMessage);
    }
}

// output final return state
function finalReturn(outputMessage) {
    console.log(outputMessage);
}