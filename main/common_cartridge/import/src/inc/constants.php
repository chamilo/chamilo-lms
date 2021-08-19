<?php
/* For licensing terms, see /license.txt */

// GENERAL PARAMETERS ************************************************************************************************* //
define('ROOT_DEEP', 2);

// PACKAGES FORMATS *************************************************************************************************** //
define('FORMAT_UNKNOWN', 'NA');
define('FORMAT_COMMON_CARTRIDGE', 'CC');
define('FORMAT_BLACK_BOARD', 'BB');

// FORMATS NAMESPACES ************************************************************************************************* //
define('NS_COMMON_CARTRIDGE', 'http://www.imsglobal.org/xsd/imsccv1p3/imscp_v1p1');
define('NS_BLACK_BOARD', 'http://www.blackboard.com/content-packaging');

// CC RESOURCES TYPE ************************************************************************************************** //
define('CC_TYPE_FORUM', 'imsdt_xmlv1p3');
define('CC_TYPE_QUIZ', 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment');
define('CC_TYPE_QUESTION_BANK', 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank');
define('CC_TYPE_WEBLINK', 'imswl_xmlv1p3');
define('CC_TYPE_WEBCONTENT', 'webcontent');
define('CC_TYPE_ASSOCIATED_CONTENT', 'associatedcontent/imscc_xmlv1p3/learning-application-resource');
define('CC_TYPE_BASICLTI', 'imsbasiclti_xmlv1p3');
define('CC_TYPE_EMPTY', '');

// COURSE RESOURCES TYPE ********************************************************************************************** //
define('TOOL_TYPE_FORUM', 'forum');
define('TOOL_TYPE_QUIZ', 'quiz');
define('TOOL_TYPE_DOCUMENT', 'document');
define('TOOL_TYPE_WEBLINK', 'link');

// UNKNOWN TYPE ******************************************************************************************************* //
define('TYPE_UNKNOWN', '[UNKNOWN]');

// CC QUESTIONS TYPES ************************************************************************************************* //
define('CC_QUIZ_MULTIPLE_CHOICE', 'cc.multiple_choice.v0p1');
define('CC_QUIZ_TRUE_FALSE', 'cc.true_false.v0p1');
define('CC_QUIZ_FIB', 'cc.fib.v0p1');
define('CC_QUIZ_MULTIPLE_RESPONSE', 'cc.multiple_response.v0p1');
define('CC_QUIZ_PATTERN_MACHT', 'cc.pattern_match.v0p1');
define('CC_QUIZ_ESSAY', 'cc.essay.v0p1');

//COURSE QUESTIONS TYPES ********************************************************************************************** //
define('TOOL_QUIZ_MULTIPLE_CHOICE', 'multichoice');
define('TOOL_QUIZ_MULTIANSWER', 'multianswer');
define('TOOL_QUIZ_MULTIPLE_RESPONSE', 'multichoice');
