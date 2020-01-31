#!/usr/bin/env bash

shopt -s globstar

PHP='/usr/bin/env php'
RETURN=0

# check XML files
for i in `find . -path ./vendor -prune -o -path ./node_modules -prune -o -name '*.xml' -print`
do
    if [ -f "$i" ]; then
        FILE="${i%/*}/${i##*/}"

        $PHP -r "exit(xml_parse(xml_parser_create(), file_get_contents('$FILE'), true));"
        if [ $? -ne 1 ]; then
            echo "Syntax check failed for ${FILE}"
            RETURN=`expr ${RETURN} + 1`
        fi
    fi
done

