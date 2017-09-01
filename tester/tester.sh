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
UN=${2}
# main file name (used only for java)
MAINFILENAME=${3}
# file name without extension
FILENAME=${4}
# file extension
EXT=${5}
# time limit in seconds
TIMELIMIT=${6}
# integer time limit in seconds (should be an integer greater than TIMELIMIT)
TIMELIMITINT=${7}
# memory limit in kB
MEMLIMIT=${8}
# output size limit in Bytes
OUTLIMIT=${9}
# diff tool (default: diff)
DIFFTOOL=${10}
# diff options (default: -bB)
DIFFOPTION=${11}
# enable/disable judge log
if [ ${12} = "1" ]; then
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
# enable/disable C/C++ shield
if [ ${13} = "1" ]; then
	C_SHIELD_ON=true
else
	C_SHIELD_ON=false
fi
# enable/disable Python shield
if [ ${14} = "1" ]; then
	PY_SHIELD_ON=true
else
	PY_SHIELD_ON=false
fi
# enable/disable java security manager
if [ ${15} = "1" ]; then
	JAVA_POLICY="-Djava.security.manager -Djava.security.policy=java.policy"
else
	JAVA_POLICY=""
fi
# enable/disable displaying java exception to students
if [ ${16} = "1" ]; then
	DISPLAY_JAVA_EXCEPTION_ON=true
else
	DISPLAY_JAVA_EXCEPTION_ON=false
fi

# DIFFOPTION can also be "ignore" or "exact".
# ignore: In this case, before diff command, all newlines and whitespaces will be removed from both files
# identical: diff will compare files without ignoring anything. files must be identical to be accepted
DIFFARGUMENT=""
if [[ "$DIFFOPTION" != "identical" && "$DIFFOPTION" != "ignore" ]]; then
	DIFFARGUMENT=$DIFFOPTION
fi


LOG="$PROBLEMPATH/$UN/log"; echo "" >$LOG
function shj_log
{
	if $LOG_ON; then
		echo -e "$@" >>$LOG
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

# detecting existence of perl
PERL_EXISTS=true
hash perl 2>/dev/null || PERL_EXISTS=false
if ! $PERL_EXISTS; then
	shj_log "Warning: perl not found. We continue without perl..."
fi

TST="$(ls $PROBLEMPATH/in | wc -l)"  # Number of Test Cases

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
elif [ "$EXT" = "py3"  ] || [ "$EXT" = "py2" ]; then
	source $tester_dir/compile_python.sh
elif [ "$EXT" = "c" ] || [ "$EXT" = "cpp" ]; then
	source $tester_dir/compile_c.sh
fi

########################################################################################################
################################################ TESTING ###############################################
########################################################################################################

shj_log "\nTesting..."
shj_log "$TST test cases found"

echo "" >$PROBLEMPATH/$UN/result.html

if [ -f "$PROBLEMPATH/tester.cpp" ] && [ ! -f "$PROBLEMPATH/tester.executable" ]; then
	shj_log "Tester file found. Compiling tester..."
	TST_COMPILE_BEGIN_TIME=$(($(date +%s%N)/1000000));
	# An: 20160321 change
	# no optimization when compile tester code
	g++ $PROBLEMPATH/tester.cpp -o $PROBLEMPATH/tester.executable
	EC=$?
	TST_COMPILE_END_TIME=$(($(date +%s%N)/1000000));
	if [ $EC -ne 0 ]; then
		shj_log "Compiling tester failed."
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

for((i=1;i<=TST;i++)); do
	shj_log "\n=== TEST $i ==="
	echo "<span class=\"shj_b\">Test $i</span>" >>$PROBLEMPATH/$UN/result.html

	touch err

	# Copy file from original path to the jail.
	# Since we share jail with docker container, user may overwrite those file before hand
	cp $tester_dir/timeout ./timeout
	chmod +x timeout
	cp $tester_dir/runcode.sh ./runcode.sh
	chmod +x runcode.sh
	cp $PROBLEMPATH/in/input$i.txt ./input.txt

	command=""
	case $EXT in
		"c") command="./$EXEFILE"
		;;
		"cpp") command="./$EXEFILE"
		;;
		"py2") command="python2 -O $FILENAME.py"
		;;
		"py3") command="python3 -O $FILENAME.py"
		;;
		"java") command="java -mx${MEMLIMIT}k $JAVA_POLICY $MAINFILENAME"
	esac

	if [ "$command" = "" ]; then
		shj_log "File Format Not Supported"
		cd ..
		rm -r $JAIL >/dev/null 2>/dev/null
		shj_finish "File Format Not Supported"
	fi

	runcode=""
	if $PERL_EXISTS; then
		runcode="./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt ./timeout --just-kill -nosandbox -l $OUTLIMIT -t $TIMELIMIT -m $MEMLIMIT $command"
	else
		runcode="./runcode.sh $EXT $MEMLIMIT $TIMELIMIT $TIMELIMITINT $PROBLEMPATH/in/input$i.txt $command"
	fi

	$runcode

	EXITCODE=$?

##################################################################
############## Process error code and error log ##################
##################################################################

	if [ "$EXT" = "java" ]; then
		if grep -iq -m 1 "Too small initial heap" out || grep -q -m 1 "java.lang.OutOfMemoryError" err; then
			shj_log "Memory Limit Exceeded"
			echo "<span class=\"shj_o\">Memory Limit Exceeded</span>" >>$PROBLEMPATH/$UN/result.html
			continue
		fi
		if grep -q -m 1 "Exception in" err; then # show Exception
			javaexceptionname=`grep -m 1 "Exception in" err | grep -m 1 -oE 'java\.[a-zA-Z\.]*' | head -1 | head -c 80`
			javaexceptionplace=`grep -m 1 "$MAINFILENAME.java" err | head -1 | head -c 80`
			shj_log "Exception: $javaexceptionname\nMaybe at:$javaexceptionplace"
			# if DISPLAY_JAVA_EXCEPTION_ON is true and the exception is in the trusted list, we show the exception name
			if $DISPLAY_JAVA_EXCEPTION_ON && grep -q -m 1 "^$javaexceptionname\$" ../java_exceptions_list; then
				echo "<span class=\"shj_o\">Runtime Error ($javaexceptionname)</span>" >>$PROBLEMPATH/$UN/result.html
			else
				echo "<span class=\"shj_o\">Runtime Error</span>" >>$PROBLEMPATH/$UN/result.html
			fi
			continue
		fi
	fi

	declare -A errors
	errors=( ["SHJ_TIME"]="Time Limit Exceeded" ["SHJ_MEM"]="Memory Limit Exceeded" ["SHJ_HANGUP"]="Process hanged up" ["SHJ_SIGNAL"]="Killed by a signal" ["SHJ_OUTSIZE"]="Output Size Limit Exceeded")

	shj_log "Exit Code = $EXITCODE"
	shj_log "err file:`cat err`"
	if ! grep -q "FINISHED" err; then
		found_error=0
		for K in "${errors[@]}"
		do
			if grep -q "$K" err; then
				# t=`grep "SHJ_TIME" err|cut -d" " -f3`
				# shj_log "Time Limit Exceeded ($t s)"
				shj_log $errors[$K]
				echo "<span class=\"shj_o\">${$errors[$K]}</span>" >>$PROBLEMPATH/$UN/result.html
				found_error=1
				break
			fi
		done
		if [ $found_error = "1"]; then
			continue
		fi
	else
		t=`grep "FINISHED" err|cut -d" " -f3`
		shj_log "Time: $t s"
	fi

	if [ $EXITCODE -eq 137 ]; then
		shj_log "Killed"
		echo "<span class=\"shj_o\">Killed</span>" >>$PROBLEMPATH/$UN/result.html
		continue
	fi


	if [ $EXITCODE -ne 0 ]; then
		shj_log "Runtime Error"
		echo "<span class=\"shj_o\">Runtime Error</span>" >>$PROBLEMPATH/$UN/result.html
		continue
	fi
############################################################################
#################	# checking correctness of output #######################
############################################################################


	ACCEPTED=false
	if [ -f shj_tester ]; then
		ulimit -t $TIMELIMITINT
		./shj_tester $PROBLEMPATH/in/input$i.txt $PROBLEMPATH/out/output$i.txt out
		EC=$?
		shj_log "$EC"
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
		if [ "$DIFFTOOL" = "diff" ]; then
			# Add -q to diff options (for faster diff)
			DIFFARGUMENT="-q $DIFFARGUMENT"
		fi
		# Compare output files
		if $DIFFTOOL $DIFFARGUMENT out correctout >/dev/null 2>/dev/null; then
			ACCEPTED=true
		fi
	fi

	if $ACCEPTED; then
		shj_log "ACCEPTED"
		echo "<span class=\"shj_g\">ACCEPT</span>" >>$PROBLEMPATH/$UN/result.html
		((PASSEDTESTS=PASSEDTESTS+1))
	else
		shj_log "WRONG"
		echo "<span class=\"shj_r\">WRONG</span>" >>$PROBLEMPATH/$UN/result.html
	fi
done


# After I added the feature for showing java exception name and exception place,
# I found that the way I am doing it is a security risk. So I added the file "tester/java_exceptions_list"
# and now it is safe to show the exception name (if it is in file java_exceptions_list), but we should not
# show place of exception. So I commented following lines:
	## Print last java exception (if enabled)
	#if $DISPLAY_JAVA_EXCEPTION_ON && [ "$javaexceptionname" != "" ]; then
	#	echo -e "\n<span class=\"shj_b\">Last Java Exception:</span>" >>$PROBLEMPATH/$UN/result.html
	#	echo -e "$javaexceptionname\n$javaexceptionplace" >>$PROBLEMPATH/$UN/result.html
	#fi



cd ..
#cp -r $JAIL "debug-jail-backup"
rm -r $JAIL >/dev/null 2>/dev/null # removing files


((SCORE=PASSEDTESTS*10000/TST)) # give score from 10,000
shj_log "\nScore from 10000: $SCORE"

shj_finish $SCORE
