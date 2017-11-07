#!/bin/bash

if [ "$EXT" = "py3" ]; then
	python="python3"
	shield="../shield/shield_py3.py"
else
	python="python2"
	shield="../shield/shield_py2.py"
fi

cp $PROBLEMPATH/$UN/$FILENAME.py $FILENAME.py
shj_log "Checking Python Syntax"
$python -O -m py_compile $FILENAME.py >/dev/null 2>cerr
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
if $PY_SHIELD_ON; then
	shj_log "Enabling Shield For Python 3"
	# adding shield to beginning of code:
	cat $shield | cat - $FILENAME.py > thetemp && mv thetemp $FILENAME.py
fi

