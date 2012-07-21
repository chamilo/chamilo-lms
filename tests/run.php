#!/bin/bash
php5 test_suite.php > log/results.xml
xsltproc simpletest_to_junit.xsl log/results.xml > log/results-1.xml
