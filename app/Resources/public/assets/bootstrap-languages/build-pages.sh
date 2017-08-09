#!/bin/sh

git checkout gh-pages
git checkout master index.html languages.min.css languages.png
git commit -a -m 'Pages built.'
git push
git checkout master
