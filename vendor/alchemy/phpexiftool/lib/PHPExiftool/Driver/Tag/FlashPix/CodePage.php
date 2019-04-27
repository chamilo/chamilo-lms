<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FlashPix;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CodePage extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'CodePage';

    protected $FullName = 'FlashPix::SummaryInfo';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Code Page';

    protected $local_g2 = 'Other';

    protected $Values = array(
        31 => array(
            'Id' => 31,
            'Label' => 'IBM EBCDIC US-Canada',
        ),
        437 => array(
            'Id' => 437,
            'Label' => 'DOS United States',
        ),
        500 => array(
            'Id' => 500,
            'Label' => 'IBM EBCDIC International',
        ),
        708 => array(
            'Id' => 708,
            'Label' => 'Arabic (ASMO 708)',
        ),
        709 => array(
            'Id' => 709,
            'Label' => 'Arabic (ASMO-449+, BCON V4)',
        ),
        710 => array(
            'Id' => 710,
            'Label' => 'Arabic - Transparent Arabic',
        ),
        720 => array(
            'Id' => 720,
            'Label' => 'DOS Arabic (Transparent ASMO)',
        ),
        737 => array(
            'Id' => 737,
            'Label' => 'DOS Greek (formerly 437G)',
        ),
        775 => array(
            'Id' => 775,
            'Label' => 'DOS Baltic',
        ),
        850 => array(
            'Id' => 850,
            'Label' => 'DOS Latin 1 (Western European)',
        ),
        852 => array(
            'Id' => 852,
            'Label' => 'DOS Latin 2 (Central European)',
        ),
        855 => array(
            'Id' => 855,
            'Label' => 'DOS Cyrillic (primarily Russian)',
        ),
        857 => array(
            'Id' => 857,
            'Label' => 'DOS Turkish',
        ),
        858 => array(
            'Id' => 858,
            'Label' => 'DOS Multilingual Latin 1 with Euro',
        ),
        860 => array(
            'Id' => 860,
            'Label' => 'DOS Portuguese',
        ),
        861 => array(
            'Id' => 861,
            'Label' => 'DOS Icelandic',
        ),
        862 => array(
            'Id' => 862,
            'Label' => 'DOS Hebrew',
        ),
        863 => array(
            'Id' => 863,
            'Label' => 'DOS French Canadian',
        ),
        864 => array(
            'Id' => 864,
            'Label' => 'DOS Arabic',
        ),
        865 => array(
            'Id' => 865,
            'Label' => 'DOS Nordic',
        ),
        866 => array(
            'Id' => 866,
            'Label' => 'DOS Russian (Cyrillic)',
        ),
        869 => array(
            'Id' => 869,
            'Label' => 'DOS Modern Greek',
        ),
        870 => array(
            'Id' => 870,
            'Label' => 'IBM EBCDIC Multilingual/ROECE (Latin 2)',
        ),
        874 => array(
            'Id' => 874,
            'Label' => 'Windows Thai (same as 28605, ISO 8859-15)',
        ),
        875 => array(
            'Id' => 875,
            'Label' => 'IBM EBCDIC Greek Modern',
        ),
        932 => array(
            'Id' => 932,
            'Label' => 'Windows Japanese (Shift-JIS)',
        ),
        936 => array(
            'Id' => 936,
            'Label' => 'Windows Simplified Chinese (PRC, Singapore)',
        ),
        949 => array(
            'Id' => 949,
            'Label' => 'Windows Korean (Unified Hangul Code)',
        ),
        950 => array(
            'Id' => 950,
            'Label' => 'Windows Traditional Chinese (Taiwan)',
        ),
        1026 => array(
            'Id' => 1026,
            'Label' => 'IBM EBCDIC Turkish (Latin 5)',
        ),
        1047 => array(
            'Id' => 1047,
            'Label' => 'IBM EBCDIC Latin 1/Open System',
        ),
        1140 => array(
            'Id' => 1140,
            'Label' => 'IBM EBCDIC US-Canada with Euro',
        ),
        1141 => array(
            'Id' => 1141,
            'Label' => 'IBM EBCDIC Germany with Euro',
        ),
        1142 => array(
            'Id' => 1142,
            'Label' => 'IBM EBCDIC Denmark-Norway with Euro',
        ),
        1143 => array(
            'Id' => 1143,
            'Label' => 'IBM EBCDIC Finland-Sweden with Euro',
        ),
        1144 => array(
            'Id' => 1144,
            'Label' => 'IBM EBCDIC Italy with Euro',
        ),
        1145 => array(
            'Id' => 1145,
            'Label' => 'IBM EBCDIC Latin America-Spain with Euro',
        ),
        1146 => array(
            'Id' => 1146,
            'Label' => 'IBM EBCDIC United Kingdom with Euro',
        ),
        1147 => array(
            'Id' => 1147,
            'Label' => 'IBM EBCDIC France with Euro',
        ),
        1148 => array(
            'Id' => 1148,
            'Label' => 'IBM EBCDIC International with Euro',
        ),
        1149 => array(
            'Id' => 1149,
            'Label' => 'IBM EBCDIC Icelandic with Euro',
        ),
        1200 => array(
            'Id' => 1200,
            'Label' => 'Unicode UTF-16, little endian',
        ),
        1201 => array(
            'Id' => 1201,
            'Label' => 'Unicode UTF-16, big endian',
        ),
        1250 => array(
            'Id' => 1250,
            'Label' => 'Windows Latin 2 (Central European)',
        ),
        1251 => array(
            'Id' => 1251,
            'Label' => 'Windows Cyrillic',
        ),
        1252 => array(
            'Id' => 1252,
            'Label' => 'Windows Latin 1 (Western European)',
        ),
        1253 => array(
            'Id' => 1253,
            'Label' => 'Windows Greek',
        ),
        1254 => array(
            'Id' => 1254,
            'Label' => 'Windows Turkish',
        ),
        1255 => array(
            'Id' => 1255,
            'Label' => 'Windows Hebrew',
        ),
        1256 => array(
            'Id' => 1256,
            'Label' => 'Windows Arabic',
        ),
        1257 => array(
            'Id' => 1257,
            'Label' => 'Windows Baltic',
        ),
        1258 => array(
            'Id' => 1258,
            'Label' => 'Windows Vietnamese',
        ),
        1361 => array(
            'Id' => 1361,
            'Label' => 'Korean (Johab)',
        ),
        10000 => array(
            'Id' => 10000,
            'Label' => 'Mac Roman (Western European)',
        ),
        10001 => array(
            'Id' => 10001,
            'Label' => 'Mac Japanese',
        ),
        10002 => array(
            'Id' => 10002,
            'Label' => 'Mac Traditional Chinese',
        ),
        10003 => array(
            'Id' => 10003,
            'Label' => 'Mac Korean',
        ),
        10004 => array(
            'Id' => 10004,
            'Label' => 'Mac Arabic',
        ),
        10005 => array(
            'Id' => 10005,
            'Label' => 'Mac Hebrew',
        ),
        10006 => array(
            'Id' => 10006,
            'Label' => 'Mac Greek',
        ),
        10007 => array(
            'Id' => 10007,
            'Label' => 'Mac Cyrillic',
        ),
        10008 => array(
            'Id' => 10008,
            'Label' => 'Mac Simplified Chinese',
        ),
        10010 => array(
            'Id' => 10010,
            'Label' => 'Mac Romanian',
        ),
        10017 => array(
            'Id' => 10017,
            'Label' => 'Mac Ukrainian',
        ),
        10021 => array(
            'Id' => 10021,
            'Label' => 'Mac Thai',
        ),
        10029 => array(
            'Id' => 10029,
            'Label' => 'Mac Latin 2 (Central European)',
        ),
        10079 => array(
            'Id' => 10079,
            'Label' => 'Mac Icelandic',
        ),
        10081 => array(
            'Id' => 10081,
            'Label' => 'Mac Turkish',
        ),
        10082 => array(
            'Id' => 10082,
            'Label' => 'Mac Croatian',
        ),
        12000 => array(
            'Id' => 12000,
            'Label' => 'Unicode UTF-32, little endian',
        ),
        12001 => array(
            'Id' => 12001,
            'Label' => 'Unicode UTF-32, big endian',
        ),
        20000 => array(
            'Id' => 20000,
            'Label' => 'CNS Taiwan',
        ),
        20001 => array(
            'Id' => 20001,
            'Label' => 'TCA Taiwan',
        ),
        20002 => array(
            'Id' => 20002,
            'Label' => 'Eten Taiwan',
        ),
        20003 => array(
            'Id' => 20003,
            'Label' => 'IBM5550 Taiwan',
        ),
        20004 => array(
            'Id' => 20004,
            'Label' => 'TeleText Taiwan',
        ),
        20005 => array(
            'Id' => 20005,
            'Label' => 'Wang Taiwan',
        ),
        20105 => array(
            'Id' => 20105,
            'Label' => 'IA5 (IRV International Alphabet No. 5, 7-bit)',
        ),
        20106 => array(
            'Id' => 20106,
            'Label' => 'IA5 German (7-bit)',
        ),
        20107 => array(
            'Id' => 20107,
            'Label' => 'IA5 Swedish (7-bit)',
        ),
        20108 => array(
            'Id' => 20108,
            'Label' => 'IA5 Norwegian (7-bit)',
        ),
        20127 => array(
            'Id' => 20127,
            'Label' => 'US-ASCII (7-bit)',
        ),
        20261 => array(
            'Id' => 20261,
            'Label' => 'T.61',
        ),
        20269 => array(
            'Id' => 20269,
            'Label' => 'ISO 6937 Non-Spacing Accent',
        ),
        20273 => array(
            'Id' => 20273,
            'Label' => 'IBM EBCDIC Germany',
        ),
        20277 => array(
            'Id' => 20277,
            'Label' => 'IBM EBCDIC Denmark-Norway',
        ),
        20278 => array(
            'Id' => 20278,
            'Label' => 'IBM EBCDIC Finland-Sweden',
        ),
        20280 => array(
            'Id' => 20280,
            'Label' => 'IBM EBCDIC Italy',
        ),
        20284 => array(
            'Id' => 20284,
            'Label' => 'IBM EBCDIC Latin America-Spain',
        ),
        20285 => array(
            'Id' => 20285,
            'Label' => 'IBM EBCDIC United Kingdom',
        ),
        20290 => array(
            'Id' => 20290,
            'Label' => 'IBM EBCDIC Japanese Katakana Extended',
        ),
        20297 => array(
            'Id' => 20297,
            'Label' => 'IBM EBCDIC France',
        ),
        20420 => array(
            'Id' => 20420,
            'Label' => 'IBM EBCDIC Arabic',
        ),
        20423 => array(
            'Id' => 20423,
            'Label' => 'IBM EBCDIC Greek',
        ),
        20424 => array(
            'Id' => 20424,
            'Label' => 'IBM EBCDIC Hebrew',
        ),
        20833 => array(
            'Id' => 20833,
            'Label' => 'IBM EBCDIC Korean Extended',
        ),
        20838 => array(
            'Id' => 20838,
            'Label' => 'IBM EBCDIC Thai',
        ),
        20866 => array(
            'Id' => 20866,
            'Label' => 'Russian/Cyrillic (KOI8-R)',
        ),
        20871 => array(
            'Id' => 20871,
            'Label' => 'IBM EBCDIC Icelandic',
        ),
        20880 => array(
            'Id' => 20880,
            'Label' => 'IBM EBCDIC Cyrillic Russian',
        ),
        20905 => array(
            'Id' => 20905,
            'Label' => 'IBM EBCDIC Turkish',
        ),
        20924 => array(
            'Id' => 20924,
            'Label' => 'IBM EBCDIC Latin 1/Open System with Euro',
        ),
        20932 => array(
            'Id' => 20932,
            'Label' => 'Japanese (JIS 0208-1990 and 0121-1990)',
        ),
        20936 => array(
            'Id' => 20936,
            'Label' => 'Simplified Chinese (GB2312)',
        ),
        20949 => array(
            'Id' => 20949,
            'Label' => 'Korean Wansung',
        ),
        21025 => array(
            'Id' => 21025,
            'Label' => 'IBM EBCDIC Cyrillic Serbian-Bulgarian',
        ),
        21027 => array(
            'Id' => 21027,
            'Label' => 'Extended Alpha Lowercase (deprecated)',
        ),
        21866 => array(
            'Id' => 21866,
            'Label' => 'Ukrainian/Cyrillic (KOI8-U)',
        ),
        28591 => array(
            'Id' => 28591,
            'Label' => 'ISO 8859-1 Latin 1 (Western European)',
        ),
        28592 => array(
            'Id' => 28592,
            'Label' => 'ISO 8859-2 (Central European)',
        ),
        28593 => array(
            'Id' => 28593,
            'Label' => 'ISO 8859-3 Latin 3',
        ),
        28594 => array(
            'Id' => 28594,
            'Label' => 'ISO 8859-4 Baltic',
        ),
        28595 => array(
            'Id' => 28595,
            'Label' => 'ISO 8859-5 Cyrillic',
        ),
        28596 => array(
            'Id' => 28596,
            'Label' => 'ISO 8859-6 Arabic',
        ),
        28597 => array(
            'Id' => 28597,
            'Label' => 'ISO 8859-7 Greek',
        ),
        28598 => array(
            'Id' => 28598,
            'Label' => 'ISO 8859-8 Hebrew (Visual)',
        ),
        28599 => array(
            'Id' => 28599,
            'Label' => 'ISO 8859-9 Turkish',
        ),
        28603 => array(
            'Id' => 28603,
            'Label' => 'ISO 8859-13 Estonian',
        ),
        28605 => array(
            'Id' => 28605,
            'Label' => 'ISO 8859-15 Latin 9',
        ),
        29001 => array(
            'Id' => 29001,
            'Label' => 'Europa 3',
        ),
        38598 => array(
            'Id' => 38598,
            'Label' => 'ISO 8859-8 Hebrew (Logical)',
        ),
        50220 => array(
            'Id' => 50220,
            'Label' => 'ISO 2022 Japanese with no halfwidth Katakana (JIS)',
        ),
        50221 => array(
            'Id' => 50221,
            'Label' => 'ISO 2022 Japanese with halfwidth Katakana (JIS-Allow 1 byte Kana)',
        ),
        50222 => array(
            'Id' => 50222,
            'Label' => 'ISO 2022 Japanese JIS X 0201-1989 (JIS-Allow 1 byte Kana - SO/SI)',
        ),
        50225 => array(
            'Id' => 50225,
            'Label' => 'ISO 2022 Korean',
        ),
        50227 => array(
            'Id' => 50227,
            'Label' => 'ISO 2022 Simplified Chinese',
        ),
        50229 => array(
            'Id' => 50229,
            'Label' => 'ISO 2022 Traditional Chinese',
        ),
        50930 => array(
            'Id' => 50930,
            'Label' => 'EBCDIC Japanese (Katakana) Extended',
        ),
        50931 => array(
            'Id' => 50931,
            'Label' => 'EBCDIC US-Canada and Japanese',
        ),
        50933 => array(
            'Id' => 50933,
            'Label' => 'EBCDIC Korean Extended and Korean',
        ),
        50935 => array(
            'Id' => 50935,
            'Label' => 'EBCDIC Simplified Chinese Extended and Simplified Chinese',
        ),
        50936 => array(
            'Id' => 50936,
            'Label' => 'EBCDIC Simplified Chinese',
        ),
        50937 => array(
            'Id' => 50937,
            'Label' => 'EBCDIC US-Canada and Traditional Chinese',
        ),
        50939 => array(
            'Id' => 50939,
            'Label' => 'EBCDIC Japanese (Latin) Extended and Japanese',
        ),
        51932 => array(
            'Id' => 51932,
            'Label' => 'EUC Japanese',
        ),
        51936 => array(
            'Id' => 51936,
            'Label' => 'EUC Simplified Chinese',
        ),
        51949 => array(
            'Id' => 51949,
            'Label' => 'EUC Korean',
        ),
        51950 => array(
            'Id' => 51950,
            'Label' => 'EUC Traditional Chinese',
        ),
        52936 => array(
            'Id' => 52936,
            'Label' => 'HZ-GB2312 Simplified Chinese',
        ),
        54936 => array(
            'Id' => 54936,
            'Label' => 'Windows XP and later: GB18030 Simplified Chinese (4 byte)',
        ),
        57002 => array(
            'Id' => 57002,
            'Label' => 'ISCII Devanagari',
        ),
        57003 => array(
            'Id' => 57003,
            'Label' => 'ISCII Bengali',
        ),
        57004 => array(
            'Id' => 57004,
            'Label' => 'ISCII Tamil',
        ),
        57005 => array(
            'Id' => 57005,
            'Label' => 'ISCII Telugu',
        ),
        57006 => array(
            'Id' => 57006,
            'Label' => 'ISCII Assamese',
        ),
        57007 => array(
            'Id' => 57007,
            'Label' => 'ISCII Oriya',
        ),
        57008 => array(
            'Id' => 57008,
            'Label' => 'ISCII Kannada',
        ),
        57009 => array(
            'Id' => 57009,
            'Label' => 'ISCII Malayalam',
        ),
        57010 => array(
            'Id' => 57010,
            'Label' => 'ISCII Gujarati',
        ),
        57011 => array(
            'Id' => 57011,
            'Label' => 'ISCII Punjabi',
        ),
        65000 => array(
            'Id' => 65000,
            'Label' => 'Unicode (UTF-7)',
        ),
        65001 => array(
            'Id' => 65001,
            'Label' => 'Unicode (UTF-8)',
        ),
    );

}
