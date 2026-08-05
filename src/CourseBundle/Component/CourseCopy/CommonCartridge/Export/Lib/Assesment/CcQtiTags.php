<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

abstract class CcQtiTags
{
    public const string QUESTESTINTEROP = 'questestinterop';
    public const string ASSESSMENT = 'assessment';
    public const string QTIMETADATA = 'qtimetadata';
    public const string QTIMETADATAFIELD = 'qtimetadatafield';
    public const string FIELDLABEL = 'fieldlabel';
    public const string FIELDENTRY = 'fieldentry';
    public const string SECTION = 'section';
    public const string IDENT = 'ident';
    public const string ITEM = 'item';
    public const string TITLE = 'title';
    public const string ITEMMETADATA = 'itemmetadata';
    public const string PRESENTATION = 'presentation';
    public const string MATERIAL = 'material';
    public const string MATTEXT = 'mattext';
    public const string MATREF = 'matref';
    public const string MATBREAK = 'matbreak';
    public const string TEXTTYPE = 'texttype';
    public const string RESPONSE_LID = 'response_lid';
    public const string RENDER_CHOICE = 'render_choice';
    public const string RESPONSE_LABEL = 'response_label';
    public const string RESPROCESSING = 'resprocessing';
    public const string OUTCOMES = 'outcomes';
    public const string DECVAR = 'decvar';
    public const string RESPCONDITION = 'respcondition';
    public const string CONDITIONVAR = 'conditionvar';
    public const string OTHER = 'other';
    public const string DISPLAYFEEDBACK = 'displayfeedback';
    public const string MAXVALUE = 'maxvalue';
    public const string MINVALUE = 'minvalue';
    public const string VARNAME = 'varname';
    public const string VARTYPE = 'vartype';
    public const string CONTINUE_ = 'continue';
    public const string FEEDBACKTYPE = 'feedbacktype';
    public const string LINKREFID = 'linkrefid';
    public const string VAREQUAL = 'varequal';
    public const string RESPIDENT = 'respident';
    public const string ITEMFEEDBACK = 'itemfeedback';
    public const string FLOW_MAT = 'flow_mat';
    public const string RCARDINALITY = 'rcardinality';
    public const string CHARSET = 'charset';
    public const string LABEL = 'label';
    public const string URI = 'uri';
    public const string WIDTH = 'width';
    public const string HEIGHT = 'height';
    public const string X0 = 'x0';
    public const string Y0 = 'y0';
    public const string XML_LANG = 'lang';
    public const string XML_SPACE = 'space';
    public const string RUBRIC = 'rubric';
    public const string ALTMATERIAL = 'altmaterial';
    public const string PRESENTATION_MATERIAL = 'presentation_material';
    public const string T_CLASS = 'class';
    public const string MATERIAL_REF = 'material_ref';
    public const string RTIMING = 'rtiming';
    public const string RENDER_FIB = 'render_fib';
    public const string SHUFFLE = 'shuffle';
    public const string MINNUMBER = 'minnumber';
    public const string MAXNUMBER = 'maxnumber';
    public const string ENCODING = 'encoding';
    public const string MAXCHARS = 'maxchars';
    public const string PROMPT = 'prompt';
    public const string FIBTYPE = 'fibtype';
    public const string ROWS = 'rows';
    public const string COLUMNS = 'columns';
    public const string LABELREFID = 'labelrefid';
    public const string RSHUFFLE = 'rshuffle';
    public const string MATCH_GROUP = 'match_group';
    public const string MATCH_MAX = 'match_max';
    public const string FLOW = 'flow';
    public const string RESPONSE_STR = 'response_str';
    public const string FLOW_LABEL = 'flow_label';
    public const string SETVAR = 'setvar';
    public const string ACTION = 'action';
    public const string AND_ = 'and';
    public const string NOT_ = 'not';
    public const string CASE_ = 'case';
    public const string VARSUBSTRING = 'varsubstring';
    public const string HINT = 'hint';
    public const string SOLUTION = 'solution';
    public const string FEEDBACKSTYLE = 'feedbackstyle';
    public const string SOLUTIONMATERIAL = 'solutionmaterial';
    public const string HINTMATERIAL = 'hintmaterial';
}
