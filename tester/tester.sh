#!/bin/bash

#    In the name of ALLAH
#    Sharif Judge
#    Copyright (C) 2014  Mohammad Javad Naderi <mjnaderi@gmail.com>
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.



##################### Example Usage #####################
# tester.sh /home/mohammad/judge/homeworks/hw6/p1 mjn problem problem c 1 1 50000 1000000 diff -bB 1 1 1 0 1 1
# In this example judge assumes that the file is located at:
# /home/mohammad/judge/homeworks/hw6/p1/mjn/problem.c
# And test cases are located at:
# /home/mohammad/judge/homeworks/hw6/p1/in/  {input1.txt, input2.txt, ...}
# /home/mohammad/judge/homeworks/hw6/p1/out/ {output1.txt, output2.txt, ...}



####################### Output #######################
# Output is just one line. One of these:
#   a number (score form 10000)
#   Compilation Error
#   Syntax Error
#   Invalid Tester Code
#   File Format Not Supported
#   Judge Error



# Get Current Time (in milliseconds)
START=$(($(date +%s%N)/1000000));


################### Getting Arguments ###################
# Tester directory
tester_dir="$(pwd)"
# problem directory
PROBLEMPATH=${1}
# username
USERDIR=${2}

RESULTFILE=${3}
LOGFILE=${4}
# main file name (used only for java)
#MAINFILENAME=${3}
# file name without extension
FILENAME=${5}
# file extension
EXT=${6}
# time limit in seconds
TIMELIMIT=${7}
# integer time limit in seconds (should be an integer greater than TIMELIMIT)
TIMELIMITINT=${8}
# memory limit in kB
MEMLIMIT=${9}
# output size limit in Bytes
OUTLIMIT=${10}
# diff tool (default: diff)
DIFFTOOL=${11}
# diff options (default: -bB)
DIFFOPTION=${12}
# enable/disable judge log
if [ ${13} = "1" ]; then
	LOG_ON=true
else
	LOG_ON=false
fi
# enable/disable easysandbox
# if [ ${13} = "1" ]; then
# 	SANDBOX_ON=true
# else
# 	SANDBOX_ON=false
# fi


# enable/disable java security manager
# if [ ${14} = "1" ]; then
# 	JAVA_POLICY="-Djava.security.manager -Djava.security.policy=java.policy"
# else
# 	JAVA_POLICY=""
# fi

# enable/disable displaying java exception to students


DISPLAY_JAVA_EXCEPTION_ON=true

#$runcode
declare -A languages_to_docker
languages_to_docker["c"]="gcc:6"
languages_to_docker["cpp"]="gcc:6"
languages_to_docker["py2"]="python:2"
languages_to_docker["py3"]="python:3"
languages_to_docker["numpy"]="truongan/wecodejudge:numpy"
languages_to_docker["java"]="openjdk:8"
languages_to_docker["pas"]="nacyot/pascal-fp_compiler:apt"

# DIFFOPTION can also be "ignore" or "exact".
# ignore: In this case, before diff command, all newlines and whitespaces will be removed from both files
# identical: diff will compare files without ignoring anything. files must be identical to be accepted
DIFFARGUMENT=""
if [[ "$DIFFOPTION" != "identical" && "$DIFFOPTION" != "ignore" ]]; then
	DIFFARGUMENT=$DIFFOPTION
fi

echo "" >$LOGFILE
function shj_log
{
	if $LOG_ON; then
		echo -e "$@" >>$LOGFILE
	fi
}


function shj_finish
{
	# Get Current Time (in milliseconds)
	END=$(($(date +%s%N)/1000000));
	shj_log "\nTotal Execution Time: $((END-START)) ms"
	echo $@
	exit 0
}



#################### Initialization #####################

shj_log "Starting tester..."
shj_log $@
# detecting existence of perl

shj_log "diff argu $DIFFOPTION"

PERL_EXISTS=true
hash perl 2>/dev/null || PERL_EXISTS=false
if ! $PERL_EXISTS; then
	shj_log "Warning: perl not found. We continue without perl..."
fi
JAIL=jail-$RANDOM
if ! mkdir $JAIL; then
	shj_log "Error: Folder 'tester' is not writable! Exiting..."
	shj_finish "Judge Error"
fi
cd $JAIL

shj_log "$(date)"
shj_log "Language: $EXT"
shj_log "Time Limit: $TIMELIMIT s"
shj_log "Memory Limit: $MEMLIMIT kB"
shj_log "Output size limit: $OUTLIMIT bytes"
if [[ $EXT = "c" || $EXT = "cpp" ]]; then
	shj_log "C/C++ Shield: $C_SHIELD_ON"
elif [[ $EXT = "py2" || $EXT = "py3" ]]; then
	shj_log "Python Shield: $PY_SHIELD_ON"
elif [[ $EXT = "java" ]]; then
	shj_log "JAVA_POLICY: \"$JAVA_POLICY\""
	shj_log "DISPLAY_JAVA_EXCEPTION_ON: $DISPLAY_JAVA_EXCEPTION_ON"
fi

########################################################################################################
################################################ COMPILING #############################################
########################################################################################################

COMPILE_BEGIN_TIME=$(($(date +%s%N)/1000000));

if [ "$EXT" = "java" ]; then
	source $tester_dir/compile_java.sh
elif [ "$EXT" = "py3"  ] || [ "$EXT" = "py2" ] || [ "$EXT" = "numpy" ]; then
	source $tester_dir/compile_python.sh
elif [ "$EXT" = "c" ] || [ "$EXT" = "cpp" ]; then
	source $tester_dir/compile_c.sh
elif [ "$EXT" = "pas" ]; then
	source $tester_dir/compile_pascal.sh
fi

########################################################################################################
################################################ TESTING ###############################################
########################################################################################################

TST="$(ls $PROBLEMPATH/in/input*.txt | wc -l)"  # Number of Test Cases


shj_log "\nTesting..."
shj_log "$TST test cases found"

echo "" >$RESULTFILE

if [ -f "$PROBLEMPATH/tester.cpp" ] && [ ! -f "$PROBLEMPATH/tester.executable" ]; then
	shj_log "Tester file found. Compiling tester..."
	TST_COMPILE_BEGIN_TIME=$(($(date +%s%N)/1000000));
	# An: 20160321 change
	# no optimization when compile tester code
	g++ -std=c++11 $PROBLEMPATH/tester.cpp -o $PROBLEMPATH/tester.executable 2>cerr
	EC=$?
	TST_COMPILE_END_TIME=$(($(date +%s%N)/1000000));
	if [ $EC -ne 0 ]; then
		shj_log "Compiling tester failed."
		shj_log `cat cerr`
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "Invalid Tester Code"
	else
		shj_log "Tester compiled. Execution Time: $((TST_COMPILE_END_TIME-TST_COMPILE_BEGIN_TIME)) ms"
	fi
fi

if [ -f "$PROBLEMPATH/tester.executable" ]; then
	shj_log "Copying tester executable to current directory"
	cp $PROBLEMPATH/tester.executable shj_tester
	chmod +x shj_tester
fi


PASSEDTESTS=0
###################################################################
######################## CODE RUNNING #############################
###################################################################

cp $PROBLEMPATH/in/input*.txt ./

declare -A languages_to_comm
languages_to_comm["c"]="./$EXEFILE"
languages_to_comm["cpp"]="./$EXEFILE"
languages_to_comm["pas"]="./$EXEFILE"
languages_to_comm["py2"]="python2 -O $FILENAME.py2"
languages_to_comm["py3"]="python3 -O $FILENAME.py3"
languages_to_comm["numpy"]="python3 -O $FILENAME.numpy"
languages_to_comm["java"]="java -mx${MEMLIMIT}k solution"
declare -A errors
errors["SHJ_TIME"]="Time Limit Exceeded"
errors["SHJ_MEM"]="Memory Limit Exceeded"
errors["SHJ_HANGUP"]="Process hanged up"
errors["SHJ_SIGNAL"]="Killed by a signal"
errors["SHJ_OUTSIZE"]="Output Size Limit Exceeded"

for((i=1;i<=TST;i++)); do
	shj_log "\n=== TEST $i ==="
	echo "<span class=\"text-primary\">Test $i</span>" >>$RESULTFILE

	touch err

	# Copy file from original path to the jail.
	# Since we share jail with docker container, user may overwrite those file before hand
	cp $tester_dir/timeout ./timeout
	chmod +x timeout
	cp $tester_dir/runcode.sh ./runcode.sh
	chmod +x runcode.sh

	if [ ! ${languages_to_comm[$EXT]+_} ]; then
		shj_log "File Format Not Supported"
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "File Format Not Supported"
	fi
	command=${languages_to_comm[$EXT]}

	runcode=""
	if $PERL_EXISTS; then
		runcode="./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT ./input$i.txt ./timeout --just-kill -nosandbox -l $OUTLIMIT -t $TIMELIMIT -m $MEMLIMIT $command"
	else
		runcode="./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT ./input$i.txt $command"
	fi

	shj_log "$tester_dir/run_judge_in_docker.sh "`pwd` "${languages_to_docker[$EXT]} $runcode"
	
	$tester_dir/run_judge_in_docker.sh `pwd` ${languages_to_docker[$EXT]} > run_judge_error $runcode 2>&1
	EXITCODE=$?

	shj_log `cat run_judge_error`
	rm run_judge_error


	shj_log "exit code $EXITCODE"
##################################################################
############## Process error code and error log ##################
##################################################################

	if [ "$EXT" = "java" ]; then
		if grep -iq -m 1 "Too small initial heap" out || grep -q -m 1 "java.lang.OutOfMemoryError" err; then
			shj_log "Memory Limit Exceeded java"
			shj_log `cat out`
			echo "<span class=\"text-warning\">Memory Limit Exceeded</span>" >>$RESULTFILE
			continue
		fi
		if grep -q -m 1 "Exception in" err; then # show Exception
			javaexceptionname=`grep -m 1 "Exception in" err | grep -m 1 -oE 'java\.[a-zA-Z\.]*' | head -1 | head -c 80`
			javaexceptionplace=`grep -m 1 "$MAINFILENAME.java" err | head -1 | head -c 80`
			shj_log "Exception: $javaexceptionname\nMaybe at:$javaexceptionplace"
			# if DISPLAY_JAVA_EXCEPTION_ON is true and the exception is in the trusted list, we show the exception name
			if $DISPLAY_JAVA_EXCEPTION_ON && grep -q -m 1 "^$javaexceptionname\$" ../java_exceptions_list; then
				echo "<span class=\"text-warning\">Runtime Error ($javaexceptionname)</span>" >>$RESULTFILE
			else
				echo "<span class=\"text-warning\">Runtime Error</span>" >>$RESULTFILE
			fi
			continue
		fi
	fi

	shj_log "Exit Code = $EXITCODE"
	shj_log "err file:`cat err`"

	t=`grep "SHJ_" err|cut -d" " -f3`
	m=`grep "SHJ_" err|cut -d" " -f5`
	m2=`grep "SHJ_" err|cut -d" " -f7`
	m=$((m>m2?m:m2))
	echo "<span class=\"text-muted\"><small>$t s and $m KiB</small></span>" >>$RESULTFILE
	# echo "<span class=\"text-secondary\">Used $m KiB</span>" >>$RESULTFILE
	found_error=0

	if ! grep -q "FINISHED" err; then

		for K in "${!errors[@]}"
		do
			if grep -q "$K" err; then
				shj_log ${errors[$K]}
				echo "<span class=\"text-warning\">${errors[$K]}</span>" >>$RESULTFILE
				found_error=1
				break
			fi
		done
			
	fi

	shj_log "Time: $t s"
	shj_log "Mem: $m kib"
	if [ $found_error = "1" ]; then
		continue
		shj_log "found error"
	fi

	if [ $EXITCODE -eq 137 ]; then
		shj_log "Killed"
		echo "<span class=\"text-warning\">Killed</span>" >>$RESULTFILE
		continue
	fi

	if [ $EXITCODE -ne 0 ]; then
		shj_log "Runtime Error"
		echo "<span class=\"text-warning\">Runtime Error</span>" >>$RESULTFILE
		continue
	fi
############################################################################
#################	# checking correctness of output #######################
############################################################################

	ACCEPTED=false
	if [ -f shj_tester ]; then
		#Limit the amount of time tester run.
		#Perhaps 5 times longer than the solution timelimit is enough
		ulimit -t $(($TIMELIMITINT*5))
		./shj_tester $PROBLEMPATH/in/input$i.txt $PROBLEMPATH/out/output$i.txt out 2>cerr
		EC=$?
		shj_log "$EC"
		shj_log `cat cerr`
		if [ $EC -eq 0 ]; then
			ACCEPTED=true
		fi
	else
		cp $PROBLEMPATH/out/output$i.txt correctout
		if [ "$DIFFOPTION" = "ignore" ]; then
			# Removing all newlines and whitespaces before diff
			tr -d ' \t\n\r\f' <out >tmp1 && mv tmp1 out;
			tr -d ' \t\n\r\f' <correctout >tmp1 && mv tmp1 correctout;
		fi
		# Add a newline at the end of both files

		echo "" >> out
		echo "" >> correctout

		shj_log `diff out correctout | grep -e "^[0-9]" | head -n 5 `

		if [ "$DIFFTOOL" = "diff" ]; then
			# Add -q to diff options (for faster diff)
			DIFFTOOL="diff -q "
		fi
		# Compare output files
		if $DIFFTOOL $DIFFARGUMENT out correctout >/dev/null 2>/dev/null; then
			ACCEPTED=true
		fi
	fi

	if $ACCEPTED; then
		shj_log "ACCEPTED"
		echo "<span class=\"text-success\">ACCEPT</span>" >>$RESULTFILE
		((PASSEDTESTS=PASSEDTESTS+1))
	else
		shj_log "WRONG"
		echo "<span class=\"text-danger\">WRONG</span>" >>$RESULTFILE
	fi
done


# After I added the feature for showing java exception name and exception place,
# I found that the way I am doing it is a security risk. So I added the file "tester/java_exceptions_list"
# and now it is safe to show the exception name (if it is in file java_exceptions_list), but we should not
# show place of exception. So I commented following lines:
	## Print last java exception (if enabled)
	#if $DISPLAY_JAVA_EXCEPTION_ON && [ "$javaexceptionname" != "" ]; then
	#	echo -e "\n<span class=\"text-primary\">Last Java Exception:</span>" >>$RESULTFILE
	#	echo -e "$javaexceptionname\n$javaexceptionplace" >>$RESULTFILE
	#fi



cd ..
# cp -r $JAIL "debug-jail-backup"
rm -r $JAIL >/dev/null 2>/dev/null # removing files


((SCORE=PASSEDTESTS*10000/TST)) # give score from 10,000
shj_log "\nScore from 10000: $SCORE"

shj_finish $SCORE
