#!/bin/sh

# Minify our CSS
curl -X POST -s --data-urlencode 'input@languages.css' http://cssminifier.com/raw > languages.min.css
