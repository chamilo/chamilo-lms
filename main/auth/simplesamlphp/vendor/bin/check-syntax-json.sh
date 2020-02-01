#!/usr/bin/env bash

shopt -s globstar

PHP='/usr/bin/env php'
RETURN=0

for i in `find . -path ./vendor -prune -o -name '*.json' -print`
do
    if [ -f "$i" ]; then
        FILE="${i%/*}/${i##*/}"
        $PHP -r "exit((json_decode(file_get_contents('$FILE')) === null) ? 1 : 0);"
        if ! [[ $? == 0 ]];
        then
            echo "Syntax check failed for ${FILE}"
            RETURN=$((RETURN + 1))
        fi
    fi
done

exit "$RETURN"
