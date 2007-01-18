#!/bin/sh

for img in `ls *.png`
do
  imgbase=`echo $img | sed "s/\.png//g"`
  convert $img $imgbase.gif
done