#!/usr/bin/env bash

shopt -s globstar

PHP='/usr/bin/env php'
RETURN=0

for i in `find . -path ./vendor -prune -o -name '*.php' -print`
do
    if [ -f "$i" ]; then
        FILE="${i%/*}/${i##*/}"
        if ! $PHP -l "$FILE" > /dev/null 2>&1
        then
            echo "Syntax check failed for ${FILE}"
            RETURN=$((RETURN + 1))
        fi
    fi
done

exit "$RETURN"
