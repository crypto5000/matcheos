/**
 *  @Matcheos Smart Contract
 *  @copyright as defined in eos/LICENSE.txt
 *  @include standard EOS libraries
 */
#include <eosiolib/eosio.hpp>
#include <string>

namespace eosio {
    
    using std::string;

    /* @abi action dbinsert
    * @abi table
    */
    struct key_value {
    eosio::string key;
    eosio::string value;
    };

}