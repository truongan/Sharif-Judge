#!/bin/bash

cp ../java.policy java.policy
cp $USERDIR/$FILENAME.java $FILENAME.java
shj_log "Compiling as Java"
shj_log "$tester_dir/run_judge_in_docker.sh "`pwd` " ${languages_to_docker[$EXT]} javac $FILENAME.java >/dev/null 2>cerr"
$tester_dir/run_judge_in_docker.sh `pwd` ${languages_to_docker[$EXT]} javac $FILENAME.java >/dev/null 2>cerr

EXITCODE=$?
COMPILE_END_TIME=$(($(date +%s%N)/1000000));
shj_log "Compiled. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
if [ $EXITCODE -ne 0 ]; then
	shj_log "Compile Error"
	shj_log "$(cat cerr|head -10)"
	echo '<span class="text-primary">Compile Error</span>' >$USERDIR/result.html
	echo '<span class="text-danger">' >> $USERDIR/result.html
	#filepath="$(echo "${JAIL}/${FILENAME}.${EXT}" | sed 's/\//\\\//g')" #replacing / with \/
	(cat cerr | head -10 | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $USERDIR/result.html
	#(cat $JAIL/cerr) >> $USERDIR/result.html
	echo "</span>" >> $USERDIR/result.html
	cd ..
	rm -r $JAIL >/dev/null 2>/dev/null
	shj_finish "Compilation Error"
fi