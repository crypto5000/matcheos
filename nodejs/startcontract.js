// require libraries
var Eos = require('eosjs');
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
    } else if (Number(txt) < 0) {
        outputMessage = "error" + num;
    }     
}

// if still ok
if (outputMessage == "success") {
    // set the network
    keyProvider = tempPrivate2;    
    eos = Eos.Localnet({keyProvider,httpEndpoint: path3.blockurl});    
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
        
        if (result["rows"]) {
            var totalRows = result["rows"].length;
            // validate - should be empty
            if (totalRows > 0) {
                outputMessage = "error11";                                
            }
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