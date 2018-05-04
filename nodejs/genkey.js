var Eos = require('eosjs');
var {ecc} = Eos.modules;

// create keypair using random string as seed
var currencyPrivate = ecc.seedPrivate(process.argv[2]);
var currencyPublic = ecc.privateToPublic(currencyPrivate);;

// output private/public with space separator
console.log(currencyPrivate + " " + currencyPublic);