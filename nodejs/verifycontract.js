// require libraries
var Eos = require('eosjs');
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var contractName = process.argv[3];
var userName = process.argv[4];
var otherUserName = process.argv[5];
var offerUser = process.argv[6];
var offerOther = process.argv[7];
var contractType = process.argv[8];
var contractGoal = process.argv[9];
var contractFormat = process.argv[10];
var contractSteps = process.argv[11];
var contractDonee = process.argv[12];
var contractArbFee = process.argv[13];
var contractFee = process.argv[14];
var contractFeeAccount = process.argv[15];
var statusUser = process.argv[16];
var statusOther = process.argv[17];
var stepCount = process.argv[18];
var keyProvider = "";    
var eos = "";    
var namePerson1 = "";
var namePerson2 = "";
var statusPerson1 = "";
var statusPerson2 = "";
var offerPerson1 = 0;
var offerPerson2 = 0;
var stepReleaseP1 = 0;
var stepReleaseP2 = 0;

// set the output message
var outputMessage = "success";

// set the currency contract parameters
var token = "EOS";
var code = "eosio.token";

// validate inputs
validateName(accountName,1);
validateName(contractName,2);
validateName(userName,3);
validateName(otherUserName,4);
validateNumber(offerUser,5);
validateNumber(offerOther,6);
validateString(contractType,7);
validateString(contractGoal,8);
validateString(contractFormat,9);
validateNumber(contractSteps,10);
validateName(contractDonee,11);
validateNumber(contractArbFee,12);
validateNumber(contractFee,13);
validateName(contractFeeAccount,14);
validateString(statusUser,15);
validateString(statusOther,16);
validateNumber(stepCount,17);

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
    // set the network - no key for just simple query    
    eos = Eos.Localnet({keyProvider,httpEndpoint: path3.blockurl});    
    checkContract();
} else {
    finalReturn(outputMessage);
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
            finalReturn("error201");
        }
    })
    .catch(function(exception) {        
        // api is throwing an error if key not found
        if (exception) {
            // contract does not exist
            finalReturn("error202");
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
        var matcheosFeePayout = 0;
        var currentType = "";
        var currentFormat = "";
        var currentGoal = "";
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

            // check for terminated flag
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
        
            // check for arbitration decision - payout to person1
            if (keyData == "arbperson1") {
                arbPerson1 = parseFloat(valueData);
            }

            // check for arbitration decision - payout to person2
            if (keyData == "arbperson2") {
                arbPerson2 = parseFloat(valueData);
            }

            // check for arbitration decision - payout to donee
            if (keyData == "arbdonee") {
                arbDonee = parseFloat(valueData);
            }

            // check for arbitration decision - payout of arb fee
            if (keyData == "arbmatcheos") {
                arbMatcheos = parseFloat(valueData);
            }

            // check for contract type
            if (keyData == "contracttype") {
                currentType = valueData;
            }

            // check for contract format
            if (keyData == "contractformat") {
                currentFormat = valueData;
            }

            // check for contract goal
            if (keyData == "contractgoal") {
                currentGoal = valueData;
            }
        
            counter++;
        }    
        
        // validate current steps
        if (currentSteps != parseInt(stepCount)) {
            outputMessage = "errorMatch101";
        }

        // validate the total steps
        if (parseInt(contractSteps) != totalSteps) {
            outputMessage = "errorMatch102";
        }

        // validate the currentSteps can't be more than totalSteps
        if (currentSteps > totalSteps) {
            outputMessage = "errorMatch103";
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

        // validate the contract format
        if (contractFormat != currentFormat) {
            outputMessage = "errorMatch104";
        }

        // validate the contract type
        if (contractType != currentType) {
            outputMessage = "errorMatch105";
        }

        // validate the contract goal
        if (contractGoal != currentGoal) {
            outputMessage = "errorMatch106";
        }

        // validate the person1 - must exist
        if (person1 == userName) {
            // set the person1            
            namePerson1 = person1;
            offerPerson1 = Number(offerUser);
            statusPerson1 = statusUser;
            statusPerson2 = statusOther;
        } else if (person1 == otherUserName) {
            // set the person1            
            namePerson1 = person1;   
            offerPerson1 = Number(offerOther); 
            statusPerson1 = statusOther;
            statusPerson2 = statusUser;    
        } else {
            outputMessage = "errorMatch107";
        }

        // validate the person1 starting offer - must exist        
        if (outputMessage == "success") {
            if (person1Start != offerPerson1) {
                outputMessage = "errorMatch108";
            }
        }
        
        // validate the arb account
        if (contractDonee != arbAccount) {
            outputMessage = "errorMatch109";
        }

        // validate the arb fee
        if (parseInt(contractArbFee) != arbFee) {
            outputMessage = "errorMatch110";
        }

        // validate the matcheos account
        if (contractFeeAccount != feeAccount) {
            outputMessage = "errorMatch111";
        }

        // validate the matcheos fee
        if (parseInt(contractFee) != matcheosFee) {
            outputMessage = "errorMatch112";
        }

        // validate the contract status matches database status 
        var openContractFlag = "no";
        if (arb == "yes") {
            if ((statusUser == "arbitration") && (statusOther == "arbitration")) {
                // do nothing
            } else {
                outputMessage = "errorMatch113";
            }
        }

        // validate the contract status matches database status 
        if (reject == "yes") {
            if ((statusUser == "terminated") && (statusOther == "terminated")) {
                // do nothing - termination validation occurs below
            } else {
                outputMessage = "errorMatch114";
            }
        }

        // validate the contract status matches database status 
        if (terminate == "yes") {
            if ((statusUser == "terminated") && (statusOther == "terminated")) {
                // do nothing
            } else {
                outputMessage = "errorMatch115";
            }
        }

        // validate the contract status matches database status 
        if (totalSteps == currentSteps) {
            if ((statusUser == "finished") && (statusOther == "finished")) {
                // do nothing
            } else {
                outputMessage = "errorMatch116";
            }
        }        

        // validate if contract is open, database status is open/waiting - totalSteps must be > (other cases above)
        if ((terminate == "no") && (arb == "no") && (reject == "no") && (totalSteps > currentSteps)) {            
            // check if in arbitration, but not with final ruling
            if ((statusUser == "arbitration") && (statusOther == "arbitration")) {
                // do nothing
            } else if ((statusUser == "terminated") && (statusOther == "terminated")) {
                // do nothing - could be waiting for 24 hour termination release
            // status of person 1 should be open, status of person 2 should be open or waiting
            } else if ((statusPerson1 == "open") && ((statusPerson2 == "open") || (statusPerson = "waiting"))){
                // set contractflag to open
                openContractFlag = "yes";
            } else {                
                outputMessage = "errorMatch117";
            }
        }
            
        // validate each database status state (finished)
        if ((statusUser == "finished") || (statusOther == "finished")) {
            
            // both persons exists            
            if ((namePerson1 == userName) && (person2 != otherUserName)) {
                outputMessage = "errorMatch118";
            } 
            
            // both persons exists            
            if ((namePerson1 == otherUserName) && (person2 != userName)) {
                outputMessage = "errorMatch119";
            }

            // both starting offers exist
            if ((offerPerson1 == Number(offerUser)) && (person2Start != Number(offerOther))) {
                outputMessage = "errorMatch120";
            } 
            
            // both starting offers exist
            if ((offerPerson1 == Number(offerOther)) && (person2Start != Number(offerUser))) {
                outputMessage = "errorMatch121";
            }

            // all steps completed
            if (totalSteps != currentSteps) {
                outputMessage = "errorMatch122";
            }

            // release balance should equal starting balance 
            currentReleaseP1 = person1Start;
            currentReleaseP2 = person2Start;
            
        }
        
        // validate each database status state (terminated)
        if ((statusUser == "terminated") || (statusOther == "terminated")) {
            
            // check steps - just 1 or many
            if (currentSteps == 1) {                

                // check if 2 people exist - could be both if terminated after person2 funding
                if (person2) {
             
                    // both persons exists            
                    if ((namePerson1 == userName) && (person2 != otherUserName)) {
                        outputMessage = "errorMatch123";
                    } 
                    
                    // both persons exists            
                    if ((namePerson1 == otherUserName) && (person2 != userName)) {
                        outputMessage = "errorMatch124";
                    }

                    // both starting offers exist
                    if ((offerPerson1 == Number(offerUser)) && (person2Start != Number(offerOther))) {
                        outputMessage = "errorMatch125";
                    } 
                    
                    // both starting offers exist
                    if ((offerPerson1 == Number(offerOther)) && (person2Start != Number(offerUser))) {
                        outputMessage = "errorMatch126";
                    }

                } else {
                    
                    // only 1 person exists
                    if ((namePerson1 == userName) && (person2 != "")) {
                        outputMessage = "errorMatch127";
                    } 
                    
                    // only 1 person exists           
                    if ((namePerson1 == otherUserName) && (person2 != "")) {
                        outputMessage = "errorMatch128";
                    }

                    // only 1 starting offer exists
                    if ((offerPerson1 == Number(offerUser)) && (person2Start != 0)) {
                        outputMessage = "errorMatch129";
                    } 
                    
                    // only starting offer exists
                    if ((offerPerson1 == Number(offerOther)) && (person2Start != 0)) {
                        outputMessage = "errorMatch130";
                    }                    

                }
                
                // release balance should equal starting balance 
                currentReleaseP1 = person1Start;
                currentReleaseP2 = person2Start;

            } else if (currentSteps > 1) {                

                // both persons exists            
                if ((namePerson1 == userName) && (person2 != otherUserName)) {
                    outputMessage = "errorMatch131";
                } 
                
                // both persons exists            
                if ((namePerson1 == otherUserName) && (person2 != userName)) {
                    outputMessage = "errorMatch132";
                }

                // both starting offers exist
                if ((offerPerson1 == Number(offerUser)) && (person2Start != Number(offerOther))) {
                    outputMessage = "errorMatch133";
                } 
                
                // both starting offers exist
                if ((offerPerson1 == Number(offerOther)) && (person2Start != Number(offerUser))) {
                    outputMessage = "errorMatch134";
                }

                // release balance should equal starting balance 
                currentReleaseP1 = person1Start;
                currentReleaseP2 = person2Start;

            } else {
                outputMessage = "errorMatch135";
            }            

        }
            
        // validate each database status state (arbitration - open, no fee paid)
        if (((statusUser == "arbitration") || (statusOther == "arbitration")) && (arbMatcheos == 0)) {
            
            // both persons exists            
            if ((namePerson1 == userName) && (person2 != otherUserName)) {
                outputMessage = "errorMatch136";
            } 
            
            // both persons exists            
            if ((namePerson1 == otherUserName) && (person2 != userName)) {
                outputMessage = "errorMatch137";
            }

            // both starting offers exist
            if ((offerPerson1 == Number(offerUser)) && (person2Start != Number(offerOther))) {
                outputMessage = "errorMatch138";
            } 
            
            // both starting offers exist
            if ((offerPerson1 == Number(offerOther)) && (person2Start != Number(offerUser))) {
                outputMessage = "errorMatch139";
            }

            // some steps completed
            if (currentSteps < 1) {
                outputMessage = "errorMatch140";
            }

            // release balance should be less than starting balance - calculated above
            currentReleaseP1 = currentReleaseP1;
            currentReleaseP2 = currentReleaseP2;

        }

        // validate each database status state (arbitration - closed, fee paid)
        if (((statusUser == "arbitration") || (statusOther == "arbitration")) && (arbMatcheos != 0)) {
            
            // both persons exists            
            if ((namePerson1 == userName) && (person2 != otherUserName)) {
                outputMessage = "errorMatch141";
            } 
            
            // both persons exists            
            if ((namePerson1 == otherUserName) && (person2 != userName)) {
                outputMessage = "errorMatch142";
            }

            // both starting offers exist
            if ((offerPerson1 == Number(offerUser)) && (person2Start != Number(offerOther))) {
                outputMessage = "errorMatch143";
            } 
            
            // both starting offers exist
            if ((offerPerson1 == Number(offerOther)) && (person2Start != Number(offerUser))) {
                outputMessage = "errorMatch144";
            }

            // some steps completed
            if (currentSteps < 1) {
                outputMessage = "errorMatch145";
            }

            // release balance should equal starting balance 
            currentReleaseP1 = person1Start;
            currentReleaseP2 = person2Start;

        }
        
        // validate each database status state (openContractFlag, both waiting)
        if ((statusUser == "waiting") && (statusOther == "waiting") && (openContractFlag == "yes")) {
            // this should not happen
            outputMessage = "errorMatch146";
        }

        // validate each database status state (openContractFlag, person1 open, person2 waiting)
        if ((statusPerson1 == "open") && (statusPerson2 == "waiting") && (openContractFlag == "yes")) {
            
            // only 1 person should have balance 
            if (person2Start > 0) {
                outputMessage = "errorMatch147";     
            }
            
            // only 1 step max 
            if (currentSteps > 1) {
                outputMessage = "errorMatch148";     
            }

        }

        // validate each database status state (openContractFlag, person1 waiting, person2 open)
        if ((statusPerson1 == "waiting") && (statusPerson2 == "open") && (openContractFlag == "yes")) {
            outputMessage = "errorMatch149";
        }

        // validate each database status state (openContractFlag, person1 open, person2 open)
        if ((statusUser == "open") && (statusOther == "open") && (openContractFlag == "yes")) {
            
            // both persons exists            
            if ((namePerson1 == userName) && (person2 != otherUserName)) {
                outputMessage = "errorMatch150";
            } 
            
            // both persons exists            
            if ((namePerson1 == otherUserName) && (person2 != userName)) {
                outputMessage = "errorMatch151";
            }

            // both starting offers exist
            if ((offerPerson1 == Number(offerUser)) && (person2Start != Number(offerOther))) {
                outputMessage = "errorMatch152";
            } 
            
            // both starting offers exist
            if ((offerPerson1 == Number(offerOther)) && (person2Start != Number(offerUser))) {
                outputMessage = "errorMatch153";
            }

            // some steps completed
            if (currentSteps < 1) {
                outputMessage = "errorMatch154";
            }
            
        }
     
        // check if contract is open, but current user hasn't funded yet - as person2
        if ((openContractFlag == "yes") && (namePerson1 == otherUserName) && (person2 != userName)) {
            outputMessage = "errorNoFunding";
        }
        
        // send final return
        if (outputMessage == "success") {                                
            // contract is valid, check current wallet
            checkWallet(currentReleaseP1,currentReleaseP2,currentSteps,person2Start);        
        } else {            
            // contract does not match database
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
function checkWallet(releaseP1,releaseP2,currentSteps,person2Start) {
    // the second person only funds if there are more than 1 step
    if ((parseInt(currentSteps) <= 1) && (person2Start == 0))  {
        var expectedBalance = Number(offerUser) - Number(releaseP1);    
    } else { 
        var expectedBalance = Number(offerUser) + Number(offerOther) - Number(releaseP1) - Number(releaseP2);    
    }
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

            // check if balance equals expected with 4 digtits of precision
            if ( Math.abs(Number(finalBalance.toFixed(4)) - Number(expectedBalance.toFixed(4))) < 0.00001 ) {                                    
                // contract is valid
                finalReturn("success");
            } else {
                // balance error
                finalReturn("error301");
            }

        } else {
            // balance not found
            finalReturn("error302");
        }        
    }
}

// output final return state
function finalReturn(outputMessage) {
    console.log(outputMessage);
}