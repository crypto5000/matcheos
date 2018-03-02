
![alt text](https://github.com/crypto5000/matcheos/blob/master/img/index.png "Website Homepage")

Visit the official website at [https://matcheos.com](https://matcheos.com) for more information.

# A "Sort-of" Analogy
Sometimes it's easiest to explain what something is using an analogy - even when that analogy is a bit incorrect. With this said, you can "sort-of" think of Matcheos as a Tinder with an EOS layer. Instead of swiping on people you like, you make an EOS bid. And once a match is made, you incorporate a smart contract to align incentives.

# Simplified Example
Assume Matcheos contains 6 people (Alice, Bob, Charles, Dave, Eve and Fran). All 6 people have profiles. Each person can browse through other people's profiles and submit a EOS bid. For example, Alice can bid 1 EOS to meet Bob and 5 EOS to meet Charles. Dave can bid 5 EOS to meet Fran. Etc. Etc.

At certain intervals, matches are made according to an algorithm. This is a weakened version of the "stable marriage problem". Essentially, a "quasi-best" global solution is chosen subject to certain constraints. At the end of this process, there should be certain matches. Bob and Alice may be a match. Fran and Charles may be a match. 

Once a match is made, both people have the option to accept. You don't have to accept a match. However, if you accept, you are required to send your bid into a smart contract. 

Let's assume Dave and Fran are a match. Dave has bid 5 EOS to meet Fran. And Fran has bid 3 EOS to meet Dave. If both accept, they send their bids to a smart contract. Dave must send 5 EOS and Fran must send 3 EOS. So the total contract now has 8 EOS.

The rest of the Matcheos process is how the 8 EOS is released from the smart contract. The current proposal is both people ultimately get back the lesser of the 2 bids. The excess value is either used for funding Matcheos (or a portion of it may be given to the relatively "more" desired person). In the current example, Fran would get back 3 EOS. Dave would get back 3 EOS. And the excess value of 5-3=2 would be used to fund the app. Note that if both people submit equal bids, there would be no excess value. Both people get back their entire EOS.

But how are the funds released? The purpose of locking the funds is to prevent bad behavior. For example, if Fran and Dave agree to meet and Fran doesn't respond to any of Dave's requests, Fran could lose her staked EOS. In other words, Fran has an incentive to behave or her staked EOS are at risk. The specific release mechanisms are still being analyzed.

# A Single User Flow
For a single user, the flow is as follows:
1. Create an account within EOS.
2. Acquire some EOS tokens.
3. Create a profile within Matcheos.
4. Select a purpose (dating, work, friends).
5. Browse through other people's profiles.
6. Submit bids on people you want to meet.
7. Receive matches from the Matcheos system.
8. Accept a match.
9. Send your EOS bid into the smart contract.
10. Meet with the match.
11. Receive some (or all) of your EOS back.

# Different Purposes
Matcheos can be used to match any grouping. It can be used for dating. It can be used for companies, where there is a match between the potential employee and the employer. Or it can be used for social groups and affiliations.

# Bidding
Bids are just indications of interest. At the time of the bid, you don't lose control of your EOS. But you need to have an account balance that is at least as great as your bid. Unless you are a whale, you won't be able to make a bid of millions of EOS.

# Matching
Bids will be weighted according to a user's balance. So the higher the percentage of your total balance you bid, the more likely it is you will be matched with that person. The desired outcome of the matching process is to match up 2 people who have expressed a high preference for each other. You may really like someone, but if they don't like you, the match is probably not good. 

# Meeting
The meeting should be problem free. That's the goal. Unfortunately, the goal rarely occurs. Here is where the smart contract comes in. Because tokens are at risk, both people should behave. If they misbehave, they may lose their tokens.

The mechanics of the release are being finalized. There are several options (including but not limited to):
1. The tokens are in a multisig wallet where both people require sign-off to release any funds. So if a stalemate occurs, the coins stay locked.
2. The tokens are in a 2 of 3 multisig with the Matcheos account as a tiebreaker.
3. There are 2 different release events with each person taking a turn.

The release quantity is also being finalized. There are several options (including but not limited to):
1. Just have 1 discrete release event.
2. Split the release into 2 events. 50 percent at the beginning and 50 percent at the end.
3. Have multiple small release events throughout the meeting.

The release format is also being finalized: There are several options (including but not limited to):
1. Release at the occurence of text correspondence inside of Matcheos.
2. Release at the occurence of some outside "in-person" event.

# Upcoming Milestones (Status)
1. Create Upcoming Milestones (Pending)
2. Get Initial Feedback (Pending)
3. Build Mock-ups of User Flow (Pending)
