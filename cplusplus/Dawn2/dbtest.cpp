/**
 *  @Matcheos Smart Contract
 *  @copyright as defined in eos/LICENSE.txt
 *  @include standard EOS libraries
 */
#include "dbtest.hpp"
#include <eosiolib/db.h>
#include <eosiolib/types.hpp>
#include <eosiolib/datastream.hpp>

/**
 *  EOS methods must have C calling convention so that the blockchain can lookup and
 *  call these methods.
 */
extern "C" {

    /**
     *  This method is called once when the contract is published or updated.
     */
    void init()  {
       eosio::print( "Matcheos Smart Contract has been instantiated!\n" );
    }

    /**
    * The apply method implements the dispatch of events to this contract
    */        
    void apply( uint64_t code, uint64_t action ) {
        
        /**
        * Handle the permissions, smart contract only permitted to update itself
        */        
        eosio::print("Performing contract authentication.\n");
        require_auth( N(dbtest) );

        /**
        * Handle the contract action as dispatched
        */        
        eosio::print("Receiving data for Matcheos Smart Contract.\n");
        if( code == N(dbtest) ) {
            
            eosio::print("Matcheos db insert action identified..\n");
            if( action == N(dbinsert) ) {
            
                /**
                * Handle the logic for storing the contract data into the table
                */        
                eosio::print("Inserting key-value store into contract.\n");
                const auto &dbitem = eosio::current_message<key_value>();                
                eosio::dump(dbitem);
                bytes b = eosio::raw::pack(dbitem.value);
                uint32_t err = store_str( N(dbtest), N(keyvalue), (char *)dbitem.key.get_data(), dbitem.key.get_size(), (char*)b.data, b.len);         
                eosio::print("Matcheos success.\n");
            } else {
              
                /**
                * Handle the logic for an unidentified action
                */        
                assert(0, "An insertion error occurred. Action is not valid.");
            }
        }
    }
}