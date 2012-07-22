#!/bin/bash
php5 test_suite.php > logs/results.xml
xsltproc simpletest_to_junit.xsl logs/results.xml > logs/junit.xml
