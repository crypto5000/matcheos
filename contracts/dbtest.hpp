/**
 *  @Matcheos Smart Contract
 *  @copyright as defined in eos/LICENSE.txt
 *  @include standard EOS libraries
 */
#include <eoslib/eos.hpp>
#include <eoslib/string.hpp>

/* @abi action dbinsert
 * @abi table
*/
struct key_value {
   eosio::string key;
   eosio::string value;
};

