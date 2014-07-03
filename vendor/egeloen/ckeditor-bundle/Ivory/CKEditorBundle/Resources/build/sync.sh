#!/bin/bash

# CKEditor path
ckeditorPath=$(readlink -f $(dirname $(readlink -f $0))/../public/)

# Remove CKEditor
rm -rf $ckeditorPath/*

# Clone the latest CKEditor full relase
git clone -b full/stable git://github.com/ckeditor/ckeditor-releases.git $ckeditorPath

# Remove Git versionning
rm -rf $ckeditorPath/.git

# Remove CKEditor samples
rm -rf $ckeditorPath/samples

# Convert windows EOL to unix one
find $ckeditorPath -type f -exec dos2unix {} \;
