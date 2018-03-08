#!/bin/bash


####################### Options #######################
#
# Compile options for C/C++
C_OPTIONS="-fno-asm -Dasm=error -lm -O2"
#
# Warning Options for C/C++
# -w: Inhibit all warning messages
# -Werror: Make all warnings into errors
# -Wall ...
# Read more: http://gcc.gnu.org/onlinedocs/gcc/Warning-Options.html
C_WARNING_OPTION="-w"

COMPILER="gcc -std=c11"
if [ "$EXT" = "cpp" ]; then
    COMPILER="g++ -std=c++11"
fi
EXEFILE="s_$(echo $FILENAME | sed 's/[^a-zA-Z0-9]//g')" # Name of executable file

NEEDCOMPILE=1
if [ -f "$PROBLEMPATH/template.cpp" ]; then
    t="$PROBLEMPATH/template.cpp"
    f=$PROBLEMPATH/$UN/$FILENAME.$EXT
    banned=`sed -n -e '/\/\*###Begin banned keyword/,/###End banned keyword/p' $t | sed -e '1d' -e '$d'`
    code=`sed -e '1,/###End banned keyword/d' $t`
    while read -r line
    do
        if [[ "$line" == "" ]]; then
            continue
        fi
        line=`echo $line | tr -d '\r'`
        #echo grep -q "$line" $f
        if grep -q "$line" $f ;then
            echo "code.$EXT: forbidden phrase: \"$line\" is banned" >> cerr
            NEEDCOMPILE=0
        fi
    done <<< "$banned"
    echo "$code" | sed -e "/\/\/###INSERT CODE HERE/r $f" -e '/\/\/###INSERT CODE HERE/d' > code.c
else
    cp $PROBLEMPATH/$UN/$FILENAME.$EXT code.c
fi

shj_log "Compiling as $EXT"

if [ $NEEDCOMPILE -eq 0 ]; then
    EXITCODE=110
else
    if $C_SHIELD_ON; then
        shj_log "Enabling Shield For C/C++"
        # if code contains any 'undef', raise compile error:
        if tr -d ' \t\n\r\f' < code.c | grep -q '#undef'; then
            echo 'code.c:#undef is not allowed' >cerr
            EXITCODE=110
        else
            cp ../shield/shield.$EXT shield.$EXT
            cp ../shield/def$EXT.h def.h
            # adding define to beginning of code:
            echo '#define main themainmainfunction' | cat - code.c > thetemp && mv thetemp code.c
            $COMPILER shield.$EXT $C_OPTIONS $C_WARNING_OPTION -o $EXEFILE >/dev/null 2>cerr
            EXITCODE=$?
        fi
    else
        mv code.c code.$EXT
        $COMPILER code.$EXT $C_OPTIONS $C_WARNING_OPTION -o $EXEFILE >/dev/null 2>cerr
        EXITCODE=$?
    fi
fi

COMPILE_END_TIME=$(($(date +%s%N)/1000000));
shj_log "Compiled. Exit Code=$EXITCODE  Execution Time: $((COMPILE_END_TIME-COMPILE_BEGIN_TIME)) ms"
if [ $EXITCODE -ne 0 ]; then
    shj_log "Compile Error"
    #shj_log "$(cat cerr | head -10)"
    shj_log "$(cat cerr )"
    echo '<span class="text-primary">Compile Error<br>Error Messages: (line numbers are not correct)</span>' >$PROBLEMPATH/$UN/result.html
    echo '<span class="text-danger">' >> $PROBLEMPATH/$UN/result.html
    SHIELD_ACT=false
    if $C_SHIELD_ON; then
        while read line; do
            if [ "`echo $line|cut -d" " -f1`" = "#define" ]; then
                if grep -wq $(echo $line|cut -d" " -f3) cerr; then
                    echo `echo $line|cut -d"/" -f3` >> $PROBLEMPATH/$UN/result.html
                    SHIELD_ACT=true
                    break
                fi
            fi
        done <def.h
    fi
    if ! $SHIELD_ACT; then
        echo -e "\n" >> cerr
        echo "" > cerr2
        while read line; do
            # An's note: 2017-30-12
            # All this shit just to remove the file name from error messgae.
            if [ "`echo $line|cut -d: -f1`" = "code.$EXT" ]; then
                echo ${line#code.$EXT:} >>cerr2
            fi
            if [ "`echo $line|cut -d: -f1`" = "shield.$EXT" ]; then
                echo ${line#shield.$EXT:} >>cerr2
            fi
        done <cerr

        (cat cerr2 | head -10 | sed 's/themainmainfunction/main/g' ) > cerr;
    fi
    (cat cerr | sed 's/&/\&amp;/g' | sed 's/</\&lt;/g' | sed 's/>/\&gt;/g' | sed 's/"/\&quot;/g') >> $PROBLEMPATH/$UN/result.html
    echo "</span>" >> $PROBLEMPATH/$UN/result.html
    cd ..
    rm -r $JAIL >/dev/null 2>/dev/null
    shj_finish "Compilation Error"
fi
