#!/usr/bin/env bash

shopt -s globstar

PHP='/usr/bin/env php'
RETURN=0

for i in `find . -path ./vendor -prune -o -path ./node_modules -prune -o -name '*.yml' -o -name '*.yaml' -print`
do
    if [ -f "$i" ]; then
        FILE="${i%/*}/${i##*/}"
        $PHP -r "require(dirname(dirname(__FILE__)).'/vendor/autoload.php'); use Symfony\Component\Yaml\Yaml; use Symfony\Component\Yaml\Exception\ParseException; try { Yaml::parseFile('$FILE'); } catch(ParseException \$e) { exit(1); }"
        if ! [[ $? == 0 ]];
        then
            echo "Syntax check failed for ${FILE}"
            RETURN=$((RETURN + 1))
        fi
    fi
done
exit "$RETURN"
