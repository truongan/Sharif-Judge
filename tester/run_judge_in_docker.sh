#!/bin/bash

# This script is design to run the tester process in a docker container using sudo
# tester process require the $JAIL directory to be shared between the host and the container
# This script check if the sharing folder is the subfolder of ${HOME}
# It's to ensure user cannot abuse the scritp to escalate their privilege and share the whole system to the containter

# Usage: run_judge_in_docker share_directory docker_image command

share_directory=${1}
docker_image=${2}
shift 2
command=$@
owner=`stat -c '%U' $share_directory`
USER=`whoami`
dockername="$USER-$(basename $share_directory)"

if  [ "$owner" = "$USER" ] || [ "$owner" = "$SUDO_USER" ]
then
	echo "docker run --rm \
		-v $share_directory:/work:rw \
		--name=$dockername \
		--network none \
		-u$UID \
		-w /work \
		$docker_image \
		$command "
	docker run --rm \
		-v $share_directory:/work:rw \
		--name=$dockername \
		--network none \
		-u$UID \
		-w /work \
		$docker_image \
		$command
	EC=$?


	exit $EC
else
	echo "Share directory '$share_directory' does not belongs in your home directory '${HOME}'"
fi
