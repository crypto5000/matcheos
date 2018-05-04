// require libraries
var Eos = require('eosjs');
var fs = require('fs');

// set dev mode
var developmentFlag = "no";

// set paths
var path1 = require('./include/path1.js');
var path2 = require('./include/path2.js');
var path3 = require('./include/path3.js');

// set the values
var accountName = process.argv[2];
var wastName = process.argv[3];
var contractName = process.argv[4];
var tempPrivate2 = process.argv[5];
var userName = process.argv[6];
var offer = process.argv[7];
var otherUserName = process.argv[8];
var contractType = process.argv[9];
var contractGoal = process.argv[10];
var contractFormat = process.argv[11];
var contractSteps = process.argv[12];
var contractDonee = process.argv[13];
var contractArbFee = process.argv[14];
var contractFee = process.argv[15];
var feeAccount = process.argv[16];
var keyType = process.argv[17];
var keyProvider = "";    
var eos = "";    

// set the output message
var outputMessage = "success";

// validate inputs
validateName(accountName,1);
validateName(wastName,2);
validateName(contractName,3);
validateKey(tempPrivate2,4);
validateName(userName,5);
validateNumber(offer,6);
validateName(otherUserName,7);
validateString(contractType,8);
validateString(contractGoal,9);
validateString(contractFormat,10);
validateNumber(contractSteps,11);
validateName(contractDonee,12);
validateNumber(contractArbFee,13);
validateNumber(contractFee,14);
validateName(feeAccount,15);
validateString(keyType,16);

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
    const config = {binaryen: require("binaryen"), keyProvider: tempPrivate2, httpEndpoint: path3.blockurl}
    eos = Eos.Localnet(config)    
    checkExists();
} else {
    finalReturn(outputMessage);
}

function checkExists() {
    // verify contract does not exist
    callback = (err, res) => {err ? finalReturn("error100") : validateCheck(res)};
    eos.getCode(contractName, callback);
    function validateCheck(result){        
        // code has should be 0000000000000000000000000000000000000000000000000000000000000000
        if (result["code_hash"] != "0000000000000000000000000000000000000000000000000000000000000000") {
            finalReturn("error101");            
        } else {
            // move on to publishing contract
            publishContract();
        }    
    }
}

function publishContract() {
    
    // set the wast/abi
    var wast = "";
    var abi =  "";

    // create contract using wast/abi files        
    if (developmentFlag == "yes") {
        wast = fs.readFileSync(`${path1.dev}/${contractName}/${contractName}.wast`)
        abi = fs.readFileSync(`${path1.dev}/${contractName}/${contractName}.abi`)
    } else {
        wast = fs.readFileSync(`${path2.prod}/${contractName}.wast`)
        abi = fs.readFileSync(`${path2.prod}/${contractName}.abi`)
    }    

    eos.setcode(contractName, 0, 0, wast, {scope: accountName, authorization: contractName + "@" + keyType})
    eos.setabi(contractName, JSON.parse(abi), {scope: accountName, authorization: contractName + "@" + keyType})

    // start a timer loop - poll every 2 seconds, with 30 second timeout
    var counter = 0;
    var maxCount = 15;
    var deployFound = "no";
    var deployCode = setInterval(function(){ pollChain(counter,maxCount) }, 2000);

    function pollChain(counter,maxCount) {

        // increment counter
        counter++;

        // check if 60 second timeout
        if (counter >= maxCount) {
            stopPolling();
        } else {
            // check if code exists on blockchain            
            callback = (err, res) => {err ? finalReturn("error200") : validatePublish(res)}
            eos.getCode(contractName, callback)
            function validatePublish(result){        

                if (deployFound == "yes") {
                    stopPolling();
                } else if (result["code_hash"] != "0000000000000000000000000000000000000000000000000000000000000000") {
                    stopPolling();
                    deployFound = "yes";
                    // move on to updating contract
                    updateContract();
                } else {
                    // keep polling - code_hash could have been 0000's if contract wasn't set yet b/c of async
                }

            }

        }
    }

    function stopPolling() {
        // stop polling
        if (deployCode) {
            clearInterval(deployCode);
        }
    }
    
}

// update contract
function updateContract() {

    eos.contract(contractName)    
    .then((contract) => {        
        contract.dbinsert({sender:accountName,key:"totalsteps",value:contractSteps},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"person1",value:userName},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"person2",value:otherUserName},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"person1start",value:offer},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"contractfee",value:contractFee},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"arbfee",value:contractArbFee},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"arbaccount",value:contractDonee},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"feeaccount",value:feeAccount},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"contracttype",value:contractType},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"contractgoal",value:contractGoal},{scope: accountName, authorization: contractName + "@" + keyType});                
        contract.dbinsert({sender:accountName,key:"contractformat",value:contractFormat},{scope: accountName, authorization: contractName + "@" + keyType})                
        .then(trx => {
            this.transaction = trx;            
            finalReturn(outputMessage);
        }).catch(e => {            
                console.log(e);                    
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