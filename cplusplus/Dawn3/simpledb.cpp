/**
 *  @Matcheos Smart Contract
 *  @copyright as defined in eos/LICENSE.txt
 *  @include standard EOS libraries
 */
#include <eosiolib/eosio.hpp>
#include <eosiolib/print.hpp>

using namespace eosio;
using namespace std;

class simpledb : public eosio::contract {
        
    public:

        /**
        * Perform the boilerplate setup of constructors and classes
        */                
        using contract::contract;

        simpledb( account_name self ) :
            contract(self),_keyvalues(_self, _self){}        
        
        // @abi action
        void dbinsert( name sender, string key, string value ){
            
            /**
            * Handle the permissions, smart contract only permitted to perform action
            */        
            eosio::print("Performing contract authentication.\n");
            require_auth( N(simpledb) );                

            /**
            * Handle the logic for storing the contract data into the table
            * Only allow inserts
            */        
            eosio::print("Performing database operation. Inserting new value.\n");
            auto new_keyvalue_itr = _keyvalues.emplace(_self, [&](auto& row){
                row.id         = _keyvalues.available_primary_key();
                row.key        = key;
                row.value      = value;               
             });                                                
        }

    private:

        //@abi table
        struct keyvalues {
            uint64_t id;
            string key;
            string value;            
            uint64_t primary_key()const { return id; }
            EOSLIB_SERIALIZE( keyvalues, (id)(key)(value) )
        };

        /**
        * Setup a multi index table with auto incremented primary keys
        */        
        multi_index<N(keyvalues), keyvalues> _keyvalues;


};

EOSIO_ABI( simpledb, (dbinsert) )