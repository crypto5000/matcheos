#!/bin/bash
# Script to pre-compile a number of smart contracts to deploy

# Set the directory paths
TEMPARCHIVEDIR=/home/ubuntu/eos/build/contracts/genwasttemp
ARCHIVEDIR=/home/ubuntu/eos/build/contracts/genwast
BASECONTRACTSDIR=/home/ubuntu/eos/build/contracts/dbtest/
HOMECONTRACTSDIR=/home/ubuntu/eos/build/contracts/
LISTFILE=/home/ubuntu/eos/build/contracts/genwastlistfile.txt

# Start message
echo "Script is running..."

# stop the script if an error occurs
set -e

# set the number of contracts
declare -i y=3

# check that archive directory exists
if [ ! -d "$ARCHIVEDIR" ]; then
  # make directory 
  echo "Making archive directory..."
  mkdir $ARCHIVEDIR
  chmod 777 $ARCHIVEDIR
fi

# check that temp archive directory exists
if [ ! -d "$TEMPARCHIVEDIR" ]; then
  # make directory 
  echo "Making temp archive directory..."
  mkdir $TEMPARCHIVEDIR
fi

# check that base contract directory exists
if [ ! -d "$BASECONTRACTSDIR" ]; then
  # exit out of loop
  echo "Dbtest directory does not exist in contracts directory."
  y=-1
fi

# check if list file exists
if [ -f "$LISTFILE" ]
then
	echo "$LISTFILE found."
  rm $LISTFILE
else
	echo "$LISTFILE not found."
  touch $LISTFILE
fi

# set the starting loop
declare -i x=0
while [ $x -lt $y ]
do

  # create random file name and directory path
  FILENAME=$(cat /dev/urandom | tr -cd 'a-z' | head -c 12)
  echo $FILENAME
  NEWCONTRACTSDIR=$TEMPARCHIVEDIR/$FILENAME
  echo $NEWCONTRACTSDIR

  # add filename to list
  echo $FILENAME >> $LISTFILE

  # copy base contract folder
  cp -R $BASECONTRACTSDIR $NEWCONTRACTSDIR

  # rename all files in base contract to random file name
  mv $NEWCONTRACTSDIR/dbtest.cpp $NEWCONTRACTSDIR/$FILENAME.cpp

  # replace all occurrences of base contract inside files
  sed -i "s/dbtest/$FILENAME/g" "$NEWCONTRACTSDIR/$FILENAME.cpp"

  # compile the new contract
  cd $NEWCONTRACTSDIR
  eosiocpp -o $FILENAME.wast $FILENAME.cpp
  eosiocpp -g $FILENAME.abi $FILENAME.cpp

  # copy the wast and abi to final archive
  cp $FILENAME.wast $ARCHIVEDIR/$FILENAME.wast
  cp $FILENAME.abi $ARCHIVEDIR/$FILENAME.abi

  x=$(( $x + 1 ))
done

# zip the files into an archive
zip -r -j $ARCHIVEDIR.zip $ARCHIVEDIR

# cleanup the directories
cd $HOMECONTRACTSDIR
rm -rf genwasttemp
rm -rf genwast