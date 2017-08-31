#!/bin/bash

# This script is design to run the tester process in a docker container using sudo
# tester process require the $JAIL directory to be shared between the host and the container
# This script check if the sharing folder is the subfolder of ${HOME}
# It's to ensure user cannot abuse the scritp to escalate their privilege and share the whole system to the containter

share_directory=${1}

echo $SUDO_USER
echo $USER 
echo ${HOME}

result=$(find "${HOME}" -type d -name "$share_directory")
if [[ -n $result ]]
then 
	
else
	echo "Share directory '$share_directory' does not belongs in your home directory '${HOME}'"
fi