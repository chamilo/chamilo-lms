<?php

/* For licensing terms, see /license.txt */
declare(strict_types=1);

// GENERAL PARAMETERS ************************************************************************************************* //
if (!defined('ROOT_DEEP')) {
    define('ROOT_DEEP', 2);
}

// PACKAGES FORMATS *************************************************************************************************** //
if (!defined('FORMAT_UNKNOWN')) {
    define('FORMAT_UNKNOWN', 'NA');
}
if (!defined('FORMAT_COMMON_CARTRIDGE')) {
    define('FORMAT_COMMON_CARTRIDGE', 'CC');
}
if (!defined('FORMAT_BLACK_BOARD')) {
    define('FORMAT_BLACK_BOARD', 'BB');
}

// FORMATS NAMESPACES ************************************************************************************************* //
if (!defined('NS_COMMON_CARTRIDGE')) {
    define('NS_COMMON_CARTRIDGE', 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1');
}
if (!defined('NS_BLACK_BOARD')) {
    define('NS_BLACK_BOARD', 'http://www.blackboard.com/content-packaging');
}

// CC RESOURCES TYPE ************************************************************************************************** //
if (!defined('CC_TYPE_FORUM')) {
    define('CC_TYPE_FORUM', 'imsdt_xmlv1p3');
}
if (!defined('CC_TYPE_QUIZ')) {
    define('CC_TYPE_QUIZ', 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment');
}
if (!defined('CC_TYPE_QUESTION_BANK')) {
    define('CC_TYPE_QUESTION_BANK', 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank');
}
if (!defined('CC_TYPE_WEBLINK')) {
    define('CC_TYPE_WEBLINK', 'imswl_xmlv1p3');
}
if (!defined('CC_TYPE_WEBCONTENT')) {
    define('CC_TYPE_WEBCONTENT', 'webcontent');
}
if (!defined('CC_TYPE_ASSOCIATED_CONTENT')) {
    define('CC_TYPE_ASSOCIATED_CONTENT', 'associatedcontent/imscc_xmlv1p3/learning-application-resource');
}
if (!defined('CC_TYPE_BASICLTI')) {
    define('CC_TYPE_BASICLTI', 'imsbasiclti_xmlv1p3');
}
if (!defined('CC_TYPE_EMPTY')) {
    define('CC_TYPE_EMPTY', '');
}

// COURSE RESOURCES TYPE ********************************************************************************************** //
if (!defined('TOOL_TYPE_FORUM')) {
    define('TOOL_TYPE_FORUM', 'forum');
}
if (!defined('TOOL_TYPE_QUIZ')) {
    define('TOOL_TYPE_QUIZ', 'quiz');
}
if (!defined('TOOL_TYPE_DOCUMENT')) {
    define('TOOL_TYPE_DOCUMENT', 'document');
}
if (!defined('TOOL_TYPE_WEBLINK')) {
    define('TOOL_TYPE_WEBLINK', 'link');
}

// UNKNOWN TYPE ******************************************************************************************************* //
if (!defined('TYPE_UNKNOWN')) {
    define('TYPE_UNKNOWN', '[UNKNOWN]');
}

// CC QUESTIONS TYPES ************************************************************************************************* //
if (!defined('CC_QUIZ_MULTIPLE_CHOICE')) {
    define('CC_QUIZ_MULTIPLE_CHOICE', 'cc.multiple_choice.v0p1');
}
if (!defined('CC_QUIZ_TRUE_FALSE')) {
    define('CC_QUIZ_TRUE_FALSE', 'cc.true_false.v0p1');
}
if (!defined('CC_QUIZ_FIB')) {
    define('CC_QUIZ_FIB', 'cc.fib.v0p1');
}
if (!defined('CC_QUIZ_MULTIPLE_RESPONSE')) {
    define('CC_QUIZ_MULTIPLE_RESPONSE', 'cc.multiple_response.v0p1');
}
if (!defined('CC_QUIZ_PATTERN_MACHT')) {
    define('CC_QUIZ_PATTERN_MACHT', 'cc.pattern_match.v0p1');
}
if (!defined('CC_QUIZ_ESSAY')) {
    define('CC_QUIZ_ESSAY', 'cc.essay.v0p1');
}

// COURSE QUESTIONS TYPES (legacy mapping – not used directly here) *************************************************** //
if (!defined('TOOL_QUIZ_MULTIPLE_CHOICE')) {
    define('TOOL_QUIZ_MULTIPLE_CHOICE', 'multichoice');
}
if (!defined('TOOL_QUIZ_MULTIANSWER')) {
    define('TOOL_QUIZ_MULTIANSWER', 'multianswer');
}
if (!defined('TOOL_QUIZ_MULTIPLE_RESPONSE')) {
    define('TOOL_QUIZ_MULTIPLE_RESPONSE', 'multichoice');
}
