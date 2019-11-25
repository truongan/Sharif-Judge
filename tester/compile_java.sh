#!/bin/bash

cp ../java.policy java.policy
cp $USERDIR/$FILENAME.java solution.java
shj_log "Compiling as Java"

shj_log "$tester_dir/run_judge_in_docker.sh "`pwd` " ${languages_to_docker[$EXT]} javac solution.java >/dev/null 2>cerr"
$tester_dir/run_judge_in_docker.sh `pwd` ${languages_to_docker[$EXT]} javac solution.java >/dev/null 2>cerr
# cp solution $FILENAME

EXITCODE=$?
COMPILE_END_TIME=$(($(date +%s%N)/1000000));
shj_log "Compiled. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
if [ $EXITCODE -ne 0 ]; then
	shj_log "Compile Error"
	shj_log "$(cat cerr|head -10)"
	echo '<span class="text-primary">Compile Error</span>' >$RESULTFILE
	echo '<span class="text-danger">' >> $RESULTFILE
	#filepath="$(echo "${JAIL}/${FILENAME}.${EXT}" | sed 's/\//\\\//g')" #replacing / with \/
	(cat cerr | head -10 | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $RESULTFILE
	#(cat $JAIL/cerr) >> $RESULTFILE
	echo "</span>" >> $RESULTFILE
	cd ..
	rm -r $JAIL >/dev/null 2>/dev/null
	shj_finish "Compilation Error"
fi