<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

abstract class CcQtiValues
{
    public const string EXAM_PROFILE = 'cc.exam.v0p1';
    public const string YES = 'Yes';
    public const string NO = 'No';
    public const string RESPONSE = 'Response';
    public const string SOLUTION = 'Solution';
    public const string HINT = 'Hint';
    public const string EXAMINATION = 'Examination';
    public const string PERCENTAGE = 'Percentage';
    public const string UNLIMITED = 'unlimited';
    public const string SINGLE = 'Single';
    public const string MULTIPLE = 'Multiple';
    public const string ORDERER = 'Ordered';
    public const string ASTERISK = 'Asterisk';
    public const string BOX = 'Box';
    public const string DASHLINE = 'Dashline';
    public const string UNDERLINE = 'Underline';
    public const string DECIMAL = 'Decimal';
    public const string INTEGER = 'Integer';
    public const string SCIENTIFIC = 'Scientific';
    public const string STRING = 'String';
    public const string SCORE = 'SCORE';
    public const string SET = 'Set';
    public const string COMPLETE = 'Complete';
    public const string TEXTTYPE = 'text/plain';
    public const string HTMLTYPE = 'text/html';
}
