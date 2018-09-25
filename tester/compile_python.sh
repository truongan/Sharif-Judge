#!/bin/bash

if [ "$EXT" = "py3" ]; then
	python="python3"
else
	python="python2"
fi

cp $PROBLEMPATH/$UN/$FILENAME.$EXT $FILENAME.$EXT
shj_log "Checking Python Syntax"
shj_log "$python -O -m py_compile $FILENAME.$EXT >/dev/null 2>cerr"
$python -O -m py_compile $FILENAME.$EXT >/dev/null 2>cerr
EXITCODE=$?
COMPILE_END_TIME=$(($(date +%s%N)/1000000));
shj_log "Syntax checked. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
if [ $EXITCODE -ne 0 ]; then
	shj_log "Syntax Error"
	shj_log "$(cat cerr | head -10)"
	echo '<span class="text-primary">Syntax Error</span>' >$PROBLEMPATH/$UN/result.html
	echo '<span class="text-danger">' >> $PROBLEMPATH/$UN/result.html
	(cat cerr | head -10 | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $PROBLEMPATH/$UN/result.html
	echo "</span>" >> $PROBLEMPATH/$UN/result.html
	cd ..
	rm -r $JAIL >/dev/null 2>/dev/null
	shj_finish "Syntax Error"
fi

