<?php

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
* PHP class t1asm	(for mPDF)                                                   *
*                                                                              *
* Version:  1.2		                                                       *
* Date:     2010-04-12                                                         *
* Author:   Ian Back <ianb@bpm1.com>                                           *
* License:  LGPL                                                               *
*                                                                              *
* The idea and some of the code has been ported from the t1utils scripts       *
* http://www.lcdf.org/type/#t1utils                                            *
* The original copyright notice is reproduced below                            *
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/* t1utils
 *
 * Copyright (c) 1992 by I. Lee Hetherington, all rights reserved.
 *
 * Permission is hereby granted to use, modify, and distribute this program
 * for any purpose provided this copyright notice and the one below remain
 * intact.
 *
 * I. Lee Hetherington (ilh@lcs.mit.edu)
 *
 * 1.5 and later versions contain changes by, and are maintained by,
 * Eddie Kohler <ekohler@gmail.com>.
 *
 * Ported to Microsoft C/C++ Compiler and MS-DOS operating system by
 * Kai-Uwe Herbing (herbing@netmbx.netmbx.de) on June 12, 1992. Code
 * specific to the MS-DOS version is encapsulated with #ifdef _MSDOS
 * ... #endif, where _MSDOS is an identifier, which is automatically
 * defined, if you compile with the Microsoft C/C++ Compiler.
 *
 */


/*
SAMPLE USE OF tiasm

$asm = new t1asm();
$asm->LoadFontFile('originalfont');	// Loads originalfont.t1a and looks for (optional) originalfont.ufm for glyph name map

// Override fontnames if required
$asm->SetFontName('mPDF__001');
$asm->SetFamilyName('mPDF');
$asm->SetFullName('mPDF__001-Italic');

// Define array of codepoint(decimal) to Unicode(decimal)
$chars = array(
0=>0
1=>0
2=>0
...
83=>83
84=>84
85=>85
86=>86
...
253=>20156
254=>19289
255=>4013
);
$asm->DefineChars($chars);

//Available:
$asm->pdf_diffstr;	// String of /Diffs for inclusion in a PDF file


$s = $asm->OutputPFB('', false);
// OR
$asm->OutputPFB('myfontassembled',false);	// makes a .pfb if (*,true) or gzcompressed as a .z file if not

//Available:
$asm->of_size1;	// size in bytes of 1st (ASCII) block
$asm->of_size2;	// size in bytes of 2nd (binary) block

*/

//======================================================



class t1asm {


var $PFB_ASCII = 1;
var $PFB_BINARY = 2;
var $PFB_DONE = 3;
var $PFB_MARKER = 0x80;


var $file_op = '';
var $ofp;
var $file_ip = '';
var $tbl_prefix ='';

var $agn;
var $uni2gn;

/* flags */
var $pfb = 1;
var $ever_active = 0;
var $in_eexec = 0;

/* lenIV and charstring start command */
var $lenIV = 4;
var $cs_start = 'RD';

/* decryption stuff */
var $c1 = 52845;
var $c2 = 22719;
var $cr = 4330;
var $er = 55665;

var $cs_commands = array();
var $op_buffer = '';
var $wob = '';
var $blocktyp;
var $dest = '';

var $if_header;
var $if_FullName;
var $if_FontName;
var $if_FamilyName;
var $if_encs;
var $if_eexec_start;
var $if_Subrs;
var $if_CharStrings;
var $if_source;
var $fid;
var $SubroutineStr;

var $pdf_diffstr;
var $pdfa_charset;	// mPDF 4.2.018

var $offs;

var $of_encodingstr;
var $of_header;
var $of_size1;
var $of_size2;

var $of_FullName;
var $of_FontName;
var $of_FamilyName;

//+++++++++++++++++++++++++++++++++++++++++++++

function t1asm() {
	$this->_initialise();
/* Adobe Glyph Names */
$this->agn = array ( 65 => 'A', 198 => 'AE', 508 => 'AEacute', 193 => 'Aacute', 258 => 'Abreve', 194 => 'Acircumflex', 196 => 'Adieresis',
192 => 'Agrave', 913 => 'Alpha', 902 => 'Alphatonos', 256 => 'Amacron', 260 => 'Aogonek', 197 => 'Aring', 506 => 'Aringacute', 195 => 'Atilde',
66 => 'B', 914 => 'Beta', 67 => 'C', 262 => 'Cacute', 268 => 'Ccaron', 199 => 'Ccedilla', 264 => 'Ccircumflex', 266 => 'Cdotaccent', 935 => 'Chi',
68 => 'D', 270 => 'Dcaron', 272 => 'Dcroat', 916 => 'Delta', 69 => 'E', 201 => 'Eacute', 276 => 'Ebreve', 282 => 'Ecaron', 202 => 'Ecircumflex',
203 => 'Edieresis', 278 => 'Edotaccent', 200 => 'Egrave', 274 => 'Emacron', 330 => 'Eng', 280 => 'Eogonek', 917 => 'Epsilon', 904 => 'Epsilontonos',
919 => 'Eta', 905 => 'Etatonos', 208 => 'Eth', 8364 => 'Euro', 70 => 'F', 71 => 'G', 915 => 'Gamma', 286 => 'Gbreve', 486 => 'Gcaron',
284 => 'Gcircumflex', 290 => 'Gcommaaccent', 288 => 'Gdotaccent', 72 => 'H', 9679 => 'H18533', 9642 => 'H18543', 9643 => 'H18551', 9633 => 'H22073',
294 => 'Hbar', 292 => 'Hcircumflex', 73 => 'I', 306 => 'IJ', 205 => 'Iacute', 300 => 'Ibreve', 206 => 'Icircumflex', 207 => 'Idieresis',
304 => 'Idotaccent', 8465 => 'Ifraktur', 204 => 'Igrave', 298 => 'Imacron', 302 => 'Iogonek', 921 => 'Iota', 938 => 'Iotadieresis', 906 => 'Iotatonos',
296 => 'Itilde', 74 => 'J', 308 => 'Jcircumflex', 75 => 'K', 922 => 'Kappa', 310 => 'Kcommaaccent', 76 => 'L', 313 => 'Lacute', 923 => 'Lambda',
317 => 'Lcaron', 315 => 'Lcommaaccent', 319 => 'Ldot', 321 => 'Lslash', 77 => 'M', 924 => 'Mu', 78 => 'N', 323 => 'Nacute', 327 => 'Ncaron',
325 => 'Ncommaaccent', 209 => 'Ntilde', 925 => 'Nu', 79 => 'O', 338 => 'OE', 211 => 'Oacute', 334 => 'Obreve', 212 => 'Ocircumflex', 214 => 'Odieresis',
210 => 'Ograve', 416 => 'Ohorn', 336 => 'Ohungarumlaut', 332 => 'Omacron', 937 => 'Omega', 911 => 'Omegatonos', 927 => 'Omicron', 908 => 'Omicrontonos',
216 => 'Oslash', 510 => 'Oslashacute', 213 => 'Otilde', 80 => 'P', 934 => 'Phi', 928 => 'Pi', 936 => 'Psi', 81 => 'Q', 82 => 'R', 340 => 'Racute',
344 => 'Rcaron', 342 => 'Rcommaaccent', 8476 => 'Rfraktur', 929 => 'Rho', 83 => 'S', 9484 => 'SF010000', 9492 => 'SF020000', 9488 => 'SF030000',
9496 => 'SF040000', 9532 => 'SF050000', 9516 => 'SF060000', 9524 => 'SF070000', 9500 => 'SF080000', 9508 => 'SF090000', 9472 => 'SF100000',
9474 => 'SF110000', 9569 => 'SF190000', 9570 => 'SF200000', 9558 => 'SF210000', 9557 => 'SF220000', 9571 => 'SF230000', 9553 => 'SF240000',
9559 => 'SF250000', 9565 => 'SF260000', 9564 => 'SF270000', 9563 => 'SF280000', 9566 => 'SF360000', 9567 => 'SF370000', 9562 => 'SF380000',
9556 => 'SF390000', 9577 => 'SF400000', 9574 => 'SF410000', 9568 => 'SF420000', 9552 => 'SF430000', 9580 => 'SF440000', 9575 => 'SF450000',
9576 => 'SF460000', 9572 => 'SF470000', 9573 => 'SF480000', 9561 => 'SF490000', 9560 => 'SF500000', 9554 => 'SF510000', 9555 => 'SF520000',
9579 => 'SF530000', 9578 => 'SF540000', 346 => 'Sacute', 352 => 'Scaron', 350 => 'Scedilla', 348 => 'Scircumflex', 536 => 'Scommaaccent',
931 => 'Sigma', 84 => 'T', 932 => 'Tau', 358 => 'Tbar', 356 => 'Tcaron', 354 => 'Tcommaaccent', 920 => 'Theta', 222 => 'Thorn', 85 => 'U',
218 => 'Uacute', 364 => 'Ubreve', 219 => 'Ucircumflex', 220 => 'Udieresis', 217 => 'Ugrave', 431 => 'Uhorn', 368 => 'Uhungarumlaut', 362 => 'Umacron',
370 => 'Uogonek', 933 => 'Upsilon', 978 => 'Upsilon1', 939 => 'Upsilondieresis', 910 => 'Upsilontonos', 366 => 'Uring', 360 => 'Utilde', 86 => 'V',
87 => 'W', 7810 => 'Wacute', 372 => 'Wcircumflex', 7812 => 'Wdieresis', 7808 => 'Wgrave', 88 => 'X', 926 => 'Xi', 89 => 'Y', 221 => 'Yacute',
374 => 'Ycircumflex', 376 => 'Ydieresis', 7922 => 'Ygrave', 90 => 'Z', 377 => 'Zacute', 381 => 'Zcaron', 379 => 'Zdotaccent', 918 => 'Zeta',
97 => 'a', 225 => 'aacute', 259 => 'abreve', 226 => 'acircumflex', 180 => 'acute', 769 => 'acutecomb', 228 => 'adieresis', 230 => 'ae',
509 => 'aeacute', 8213 => 'afii00208', 1040 => 'afii10017', 1041 => 'afii10018', 1042 => 'afii10019', 1043 => 'afii10020', 1044 => 'afii10021',
1045 => 'afii10022', 1025 => 'afii10023', 1046 => 'afii10024', 1047 => 'afii10025', 1048 => 'afii10026', 1049 => 'afii10027', 1050 => 'afii10028',
1051 => 'afii10029', 1052 => 'afii10030', 1053 => 'afii10031', 1054 => 'afii10032', 1055 => 'afii10033', 1056 => 'afii10034', 1057 => 'afii10035',
1058 => 'afii10036', 1059 => 'afii10037', 1060 => 'afii10038', 1061 => 'afii10039', 1062 => 'afii10040', 1063 => 'afii10041', 1064 => 'afii10042',
1065 => 'afii10043', 1066 => 'afii10044', 1067 => 'afii10045', 1068 => 'afii10046', 1069 => 'afii10047', 1070 => 'afii10048', 1071 => 'afii10049',
1168 => 'afii10050', 1026 => 'afii10051', 1027 => 'afii10052', 1028 => 'afii10053', 1029 => 'afii10054', 1030 => 'afii10055', 1031 => 'afii10056',
1032 => 'afii10057', 1033 => 'afii10058', 1034 => 'afii10059', 1035 => 'afii10060', 1036 => 'afii10061', 1038 => 'afii10062', 1072 => 'afii10065',
1073 => 'afii10066', 1074 => 'afii10067', 1075 => 'afii10068', 1076 => 'afii10069', 1077 => 'afii10070', 1105 => 'afii10071', 1078 => 'afii10072',
1079 => 'afii10073', 1080 => 'afii10074', 1081 => 'afii10075', 1082 => 'afii10076', 1083 => 'afii10077', 1084 => 'afii10078', 1085 => 'afii10079',
1086 => 'afii10080', 1087 => 'afii10081', 1088 => 'afii10082', 1089 => 'afii10083', 1090 => 'afii10084', 1091 => 'afii10085', 1092 => 'afii10086',
1093 => 'afii10087', 1094 => 'afii10088', 1095 => 'afii10089', 1096 => 'afii10090', 1097 => 'afii10091', 1098 => 'afii10092', 1099 => 'afii10093',
1100 => 'afii10094', 1101 => 'afii10095', 1102 => 'afii10096', 1103 => 'afii10097', 1169 => 'afii10098', 1106 => 'afii10099', 1107 => 'afii10100',
1108 => 'afii10101', 1109 => 'afii10102', 1110 => 'afii10103', 1111 => 'afii10104', 1112 => 'afii10105', 1113 => 'afii10106', 1114 => 'afii10107',
1115 => 'afii10108', 1116 => 'afii10109', 1118 => 'afii10110', 1039 => 'afii10145', 1122 => 'afii10146', 1138 => 'afii10147', 1140 => 'afii10148',
1119 => 'afii10193', 1123 => 'afii10194', 1139 => 'afii10195', 1141 => 'afii10196', 1241 => 'afii10846', 8206 => 'afii299', 8207 => 'afii300',
8205 => 'afii301', 1642 => 'afii57381', 1548 => 'afii57388', 1632 => 'afii57392', 1633 => 'afii57393', 1634 => 'afii57394', 1635 => 'afii57395',
1636 => 'afii57396', 1637 => 'afii57397', 1638 => 'afii57398', 1639 => 'afii57399', 1640 => 'afii57400', 1641 => 'afii57401', 1563 => 'afii57403',
1567 => 'afii57407', 1569 => 'afii57409', 1570 => 'afii57410', 1571 => 'afii57411', 1572 => 'afii57412', 1573 => 'afii57413', 1574 => 'afii57414',
1575 => 'afii57415', 1576 => 'afii57416', 1577 => 'afii57417', 1578 => 'afii57418', 1579 => 'afii57419', 1580 => 'afii57420', 1581 => 'afii57421',
1582 => 'afii57422', 1583 => 'afii57423', 1584 => 'afii57424', 1585 => 'afii57425', 1586 => 'afii57426', 1587 => 'afii57427', 1588 => 'afii57428',
1589 => 'afii57429', 1590 => 'afii57430', 1591 => 'afii57431', 1592 => 'afii57432', 1593 => 'afii57433', 1594 => 'afii57434', 1600 => 'afii57440',
1601 => 'afii57441', 1602 => 'afii57442', 1603 => 'afii57443', 1604 => 'afii57444', 1605 => 'afii57445', 1606 => 'afii57446', 1608 => 'afii57448',
1609 => 'afii57449', 1610 => 'afii57450', 1611 => 'afii57451', 1612 => 'afii57452', 1613 => 'afii57453', 1614 => 'afii57454', 1615 => 'afii57455',
1616 => 'afii57456', 1617 => 'afii57457', 1618 => 'afii57458', 1607 => 'afii57470', 1700 => 'afii57505', 1662 => 'afii57506', 1670 => 'afii57507',
1688 => 'afii57508', 1711 => 'afii57509', 1657 => 'afii57511', 1672 => 'afii57512', 1681 => 'afii57513', 1722 => 'afii57514', 1746 => 'afii57519',
1749 => 'afii57534', 8362 => 'afii57636', 1470 => 'afii57645', 1475 => 'afii57658', 1488 => 'afii57664', 1489 => 'afii57665', 1490 => 'afii57666',
1491 => 'afii57667', 1492 => 'afii57668', 1493 => 'afii57669', 1494 => 'afii57670', 1495 => 'afii57671', 1496 => 'afii57672', 1497 => 'afii57673',
1498 => 'afii57674', 1499 => 'afii57675', 1500 => 'afii57676', 1501 => 'afii57677', 1502 => 'afii57678', 1503 => 'afii57679', 1504 => 'afii57680',
1505 => 'afii57681', 1506 => 'afii57682', 1507 => 'afii57683', 1508 => 'afii57684', 1509 => 'afii57685', 1510 => 'afii57686', 1511 => 'afii57687',
1512 => 'afii57688', 1513 => 'afii57689', 1514 => 'afii57690', 1520 => 'afii57716', 1521 => 'afii57717', 1522 => 'afii57718', 1460 => 'afii57793',
1461 => 'afii57794', 1462 => 'afii57795', 1467 => 'afii57796', 1464 => 'afii57797', 1463 => 'afii57798', 1456 => 'afii57799', 1458 => 'afii57800',
1457 => 'afii57801', 1459 => 'afii57802', 1474 => 'afii57803', 1473 => 'afii57804', 1465 => 'afii57806', 1468 => 'afii57807', 1469 => 'afii57839',
1471 => 'afii57841', 1472 => 'afii57842', 700 => 'afii57929', 8453 => 'afii61248', 8467 => 'afii61289', 8470 => 'afii61352', 8236 => 'afii61573',
8237 => 'afii61574', 8238 => 'afii61575', 8204 => 'afii61664', 1645 => 'afii63167', 701 => 'afii64937', 224 => 'agrave', 8501 => 'aleph',
945 => 'alpha', 940 => 'alphatonos', 257 => 'amacron', 38 => 'ampersand', 8736 => 'angle', 9001 => 'angleleft', 9002 => 'angleright',
903 => 'anoteleia', 261 => 'aogonek', 8776 => 'approxequal', 229 => 'aring', 507 => 'aringacute', 8596 => 'arrowboth', 8660 => 'arrowdblboth',
8659 => 'arrowdbldown', 8656 => 'arrowdblleft', 8658 => 'arrowdblright', 8657 => 'arrowdblup', 8595 => 'arrowdown', 8592 => 'arrowleft',
8594 => 'arrowright', 8593 => 'arrowup', 8597 => 'arrowupdn', 8616 => 'arrowupdnbse', 94 => 'asciicircum', 126 => 'asciitilde', 42 => 'asterisk',
8727 => 'asteriskmath', 64 => 'at', 227 => 'atilde', 98 => 'b', 92 => 'backslash', 124 => 'bar', 946 => 'beta', 9608 => 'block', 123 => 'braceleft',
125 => 'braceright', 91 => 'bracketleft', 93 => 'bracketright', 728 => 'breve', 166 => 'brokenbar', 8226 => 'bullet', 99 => 'c', 263 => 'cacute',
711 => 'caron', 8629 => 'carriagereturn', 269 => 'ccaron', 231 => 'ccedilla', 265 => 'ccircumflex', 267 => 'cdotaccent', 184 => 'cedilla',
162 => 'cent', 967 => 'chi', 9675 => 'circle', 8855 => 'circlemultiply', 8853 => 'circleplus', 710 => 'circumflex', 9827 => 'club', 58 => 'colon',
8353 => 'colonmonetary', 44 => 'comma', 8773 => 'congruent', 169 => 'copyright', 164 => 'currency', 100 => 'd', 8224 => 'dagger', 8225 => 'daggerdbl',
271 => 'dcaron', 273 => 'dmacron', 176 => 'degree', 948 => 'delta', 9830 => 'diamond', 168 => 'dieresis', 901 => 'dieresistonos', 247 => 'divide',
9619 => 'dkshade', 9604 => 'dnblock', 36 => 'dollar', 8363 => 'dong', 729 => 'dotaccent', 803 => 'dotbelowcomb', 305 => 'dotlessi', 8901 => 'dotmath',
101 => 'e', 233 => 'eacute', 277 => 'ebreve', 283 => 'ecaron', 234 => 'ecircumflex', 235 => 'edieresis', 279 => 'edotaccent', 232 => 'egrave',
56 => 'eight', 8712 => 'element', 8230 => 'ellipsis', 275 => 'emacron', 8212 => 'emdash', 8709 => 'emptyset', 8211 => 'endash', 331 => 'eng',
281 => 'eogonek', 949 => 'epsilon', 941 => 'epsilontonos', 61 => 'equal', 8801 => 'equivalence', 8494 => 'estimated', 951 => 'eta', 942 => 'etatonos',
240 => 'eth', 33 => 'exclam', 8252 => 'exclamdbl', 161 => 'exclamdown', 8707 => 'existential', 102 => 'f', 9792 => 'female', 8210 => 'figuredash',
9632 => 'filledbox', 9644 => 'filledrect', 53 => 'five', 8541 => 'fiveeighths', 402 => 'florin', 52 => 'four', 8260 => 'fraction', 8355 => 'franc',
103 => 'g', 947 => 'gamma', 287 => 'gbreve', 487 => 'gcaron', 285 => 'gcircumflex', 291 => 'gcommaaccent', 289 => 'gdotaccent', 223 => 'germandbls',
8711 => 'gradient', 96 => 'grave', 768 => 'gravecomb', 62 => 'greater', 8805 => 'greaterequal', 171 => 'guillemotleft', 187 => 'guillemotright',
8249 => 'guilsinglleft', 8250 => 'guilsinglright', 104 => 'h', 295 => 'hbar', 293 => 'hcircumflex', 9829 => 'heart', 777 => 'hookabovecomb',
8962 => 'house', 733 => 'hungarumlaut', 45 => 'hyphen', 105 => 'i', 237 => 'iacute', 301 => 'ibreve', 238 => 'icircumflex', 239 => 'idieresis',
236 => 'igrave', 307 => 'ij', 299 => 'imacron', 8734 => 'infinity', 8747 => 'integral', 8993 => 'integralbt', 8992 => 'integraltp',
8745 => 'intersection', 9688 => 'invbullet', 9689 => 'invcircle', 9787 => 'invsmileface', 303 => 'iogonek', 953 => 'iota', 970 => 'iotadieresis',
912 => 'iotadieresistonos', 943 => 'iotatonos', 297 => 'itilde', 106 => 'j', 309 => 'jcircumflex', 107 => 'k', 954 => 'kappa', 311 => 'kcommaaccent',
312 => 'kgreenlandic', 108 => 'l', 314 => 'lacute', 955 => 'lambda', 318 => 'lcaron', 316 => 'lcommaaccent', 320 => 'ldot', 60 => 'less',
8804 => 'lessequal', 9612 => 'lfblock', 8356 => 'lira', 8743 => 'logicaland', 172 => 'logicalnot', 8744 => 'logicalor', 383 => 'longs',
9674 => 'lozenge', 322 => 'lslash', 9617 => 'ltshade', 109 => 'm', 175 => 'macron', 9794 => 'male', 8722 => 'minus', 8242 => 'minute', 956 => 'mu',
215 => 'multiply', 9834 => 'musicalnote', 9835 => 'musicalnotedbl', 110 => 'n', 324 => 'nacute', 329 => 'napostrophe', 328 => 'ncaron',
326 => 'ncommaaccent', 57 => 'nine', 8713 => 'notelement', 8800 => 'notequal', 8836 => 'notsubset', 241 => 'ntilde', 957 => 'nu', 35 => 'numbersign',
111 => 'o', 243 => 'oacute', 335 => 'obreve', 244 => 'ocircumflex', 246 => 'odieresis', 339 => 'oe', 731 => 'ogonek', 242 => 'ograve',
417 => 'ohorn', 337 => 'ohungarumlaut', 333 => 'omacron', 969 => 'omega', 982 => 'omega1', 974 => 'omegatonos', 959 => 'omicron',
972 => 'omicrontonos', 49 => 'one', 8228 => 'onedotenleader', 8539 => 'oneeighth', 189 => 'onehalf', 188 => 'onequarter', 8531 => 'onethird',
9702 => 'openbullet', 170 => 'ordfeminine', 186 => 'ordmasculine', 8735 => 'orthogonal', 248 => 'oslash', 511 => 'oslashacute', 245 => 'otilde',
112 => 'p', 182 => 'paragraph', 40 => 'parenleft', 41 => 'parenright', 8706 => 'partialdiff', 37 => 'percent', 46 => 'period', 183 => 'periodcentered',
8869 => 'perpendicular', 8240 => 'perthousand', 8359 => 'peseta', 966 => 'phi', 981 => 'phi1', 960 => 'pi', 43 => 'plus', 177 => 'plusminus',
8478 => 'prescription', 8719 => 'product', 8834 => 'propersubset', 8835 => 'propersuperset', 8733 => 'proportional', 968 => 'psi', 113 => 'q',
63 => 'question', 191 => 'questiondown', 34 => 'quotedbl', 8222 => 'quotedblbase', 8220 => 'quotedblleft', 8221 => 'quotedblright',
8216 => 'quoteleft', 8219 => 'quotereversed', 8217 => 'quoteright', 8218 => 'quotesinglbase', 39 => 'quotesingle', 114 => 'r', 341 => 'racute',
8730 => 'radical', 345 => 'rcaron', 343 => 'rcommaaccent', 8838 => 'reflexsubset', 8839 => 'reflexsuperset', 174 => 'registered',
8976 => 'revlogicalnot', 961 => 'rho', 730 => 'ring', 9616 => 'rtblock', 115 => 's', 347 => 'sacute', 353 => 'scaron', 351 => 'scedilla',
349 => 'scircumflex', 537 => 'scommaaccent', 8243 => 'second', 167 => 'section', 59 => 'semicolon', 55 => 'seven', 8542 => 'seveneighths',
9618 => 'shade', 963 => 'sigma', 962 => 'sigma1', 8764 => 'similar', 54 => 'six', 47 => 'slash', 9786 => 'smileface', 32 => 'space',
9824 => 'spade', 163 => 'sterling', 8715 => 'suchthat', 8721 => 'summation', 9788 => 'sun', 116 => 't', 964 => 'tau', 359 => 'tbar', 357 => 'tcaron',
355 => 'tcommaaccent', 8756 => 'therefore', 952 => 'theta', 977 => 'theta1', 254 => 'thorn', 51 => 'three', 8540 => 'threeeighths',
190 => 'threequarters', 732 => 'tilde', 771 => 'tildecomb', 900 => 'tonos', 8482 => 'trademark', 9660 => 'triagdn', 9668 => 'triaglf',
9658 => 'triagrt', 9650 => 'triagup', 50 => 'two', 8229 => 'twodotenleader', 8532 => 'twothirds', 117 => 'u', 250 => 'uacute', 365 => 'ubreve',
251 => 'ucircumflex', 252 => 'udieresis', 249 => 'ugrave', 432 => 'uhorn', 369 => 'uhungarumlaut', 363 => 'umacron', 95 => 'underscore',
8215 => 'underscoredbl', 8746 => 'union', 8704 => 'universal', 371 => 'uogonek', 9600 => 'upblock', 965 => 'upsilon', 971 => 'upsilondieresis',
944 => 'upsilondieresistonos', 973 => 'upsilontonos', 367 => 'uring', 361 => 'utilde', 118 => 'v', 119 => 'w', 7811 => 'wacute', 373 => 'wcircumflex',
7813 => 'wdieresis', 8472 => 'weierstrass', 7809 => 'wgrave', 120 => 'x', 958 => 'xi', 121 => 'y', 253 => 'yacute', 375 => 'ycircumflex',
255 => 'ydieresis', 165 => 'yen', 7923 => 'ygrave', 122 => 'z', 378 => 'zacute', 382 => 'zcaron', 380 => 'zdotaccent', 48 => 'zero', 950 => 'zeta',
);


/* initialise table of charstring commands */
$this->cs_commands = array(
  'abs' => array(12, 9 ),
  'add' => array(12, 10 ),
  'and' => array(12, 3 ),
  'blend' => array(16, -1 ),
  'callgsubr' => array(29, -1 ),
  'callothersubr' => array(12, 16 ),
  'callsubr' => array(10, -1 ),
  'closepath' => array(9, -1 ),
  'cntrmask' => array(20, -1 ),
  'div' => array(12, 12 ),
  'dotsection' => array(12, 0 ),
  'drop' => array(12, 18 ),
  'dup' => array(12, 27 ),
  'endchar' => array(14, -1 ),
  'eq' => array(12, 15 ),
  'error' => array(0, -1 ),
  'escape' => array(12, -1 ),
  'exch' => array(12, 28 ),
  'flex' => array(12, 35 ),
  'flex1' => array(12, 37 ),
  'get' => array(12, 21 ),
  'hflex' => array(12, 34 ),
  'hflex1' => array(12, 36 ),
  'hhcurveto' => array(27, -1 ),
  'hintmask' => array(19, -1 ),
  'hlineto' => array(6, -1 ),
  'hmoveto' => array(22, -1 ),
  'hsbw' => array(13, -1 ),
  'hstem' => array(1, -1 ),
  'hstem3' => array(12, 2 ),
  'hstemhm' => array(18, -1 ),
  'hvcurveto' => array(31, -1 ),
  'ifelse' => array(12, 22 ),
  'index' => array(12, 29 ),
  'load' => array(12, 13 ),
  'mul' => array(12, 24 ),
  'neg' => array(12, 14 ),
  'not' => array(12, 5 ),
  'or' => array(12, 4 ),
  'pop' => array(12, 17 ),
  'put' => array(12, 20 ),
  'random' => array(12, 23 ),
  'rcurveline' => array(24, -1 ),
  'return' => array(11, -1 ),
  'rlinecurve' => array(25, -1 ),
  'rlineto' => array(5, -1 ),
  'rmoveto' => array(21, -1 ),
  'roll' => array(12, 30 ),
  'rrcurveto' => array(8, -1 ),
  'sbw' => array(12, 7 ),
  'seac' => array(12, 6 ),
  'setcurrentpoint' => array(12, 33 ),
  'sqrt' => array(12, 26 ),
  'store' => array(12, 8 ),
  'sub' => array(12, 11 ),
  'vhcurveto' => array(30, -1 ),
  'vlineto' => array(7, -1 ),
  'vmoveto' => array(4, -1 ),
  'vstem' => array(3, -1 ),
  'vstem3' => array(12, 1 ),
  'vstemhm' => array(23, -1 ),
  'vvcurveto' => array(26, -1 )
);

	$this->SubroutineStr = "dup 0 15 RD \x0a\xc2\xbf1p|\x0a\x0e\x0a=-\xe2\x80\x9cD\\xc3\xa2R NP\ndup 1 9 RD \x0a\xc2\xbf1py\xc2\xbc\xc3\xb6Uz NP\ndup 2 9 RD \x0a\xc2\xbf1py\xc2\xbd\xc3\x84\xc5\xbei NP\ndup 3 5 RD \x0a\xc2\xbf1p\xc3\xb9 NP\ndup 4 12 RD \x0a\xc2\xbf1p~\xc2\xb6+6\xc3\xa46z NP\n";

}

//+++++++++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++++++++
// Public functions

function LoadFontFile($file) {
/* returns:
var $if_header;
var $if_FullName;
var $if_FontName;
var $if_FamilyName;
var $if_encs;
var $if_eexec_start;
var $if_Subrs;
var $if_CharStrings;
var $if_source;
var $fid;
var $SubroutineStr;
*/
	$this->_initialise();
	$mqr=ini_get("magic_quotes_runtime");
	if ($mqr) { set_magic_quotes_runtime(0); }
	$this->lenIV = 4;
	$this->cs_start = 'RD';
	$fontname = preg_replace('/.*\//', '', $file);

	$this->file_ip = _MPDF_PATH.'unifont/'.$fontname;
	$this->lenIV = 4;
	$this->cs_start = 'RD';

	$this->SubroutineStr = "dup 0 15 RD \x0a\xc2\xbf1p|\x0a\x0e\x0a=-\xe2\x80\x9cD\\xc3\xa2R NP\ndup 1 9 RD \x0a\xc2\xbf1py\xc2\xbc\xc3\xb6Uz NP\ndup 2 9 RD \x0a\xc2\xbf1py\xc2\xbd\xc3\x84\xc5\xbei NP\ndup 3 5 RD \x0a\xc2\xbf1p\xc3\xb9 NP\ndup 4 12 RD \x0a\xc2\xbf1p~\xc2\xb6+6\xc3\xa46z NP\n";
	$offs = array();
	include($this->file_ip .'.dat.php');
	if (!isset($offs)) { die("ERROR - Data file not found to embed font subsets - ".$this->file_ip .'.dat.php'); }
	$this->offs = $offs;
	$fh = fopen($this->file_ip .'.dat', "rb") or die("ERROR - Data file not found to embed font subsets - ".$this->file_ip .'.dat');  // mPDF 4.5.008
	$l = $this->_freadint($fh);	// Read 4-byte integer
	$this->if_header = fread($fh, $l);
	$l = $this->_freadint($fh);	// Read 4-byte integer
	$this->if_eexec_start = fread($fh, $l);
	fclose($fh);

	// Read uni2gn [code-point to glyph-name] derived from .ufm file if exists
	$this->uni2gn = array();
	include($file.'.uni2gn.php');
	if (!$this->uni2gn) { die("ERROR - Data file not found to embed font subsets - ".$this->file_ip .'.uni2gn.php'); }
	if ($mqr) { set_magic_quotes_runtime($mqr); }
}



function SetFontName($fn) {
	$this->of_FontName = $fn;
}

function SetFamilyName($fn) {
	$this->of_FamilyName = $fn;
}

function SetFullName($fn) {
	$this->of_FullName = $fn;
}

function DefineChars($chars,$pdfa=false) {		// mPDF 4.2.018 PDFA
	// chars = code point (decimal) => Unicode(decimal)
	// convertto  [0]=>'exclamationmark', [1]=>'afii080012'
	// e.g of code point (decimal) => Adobe glyph name
	$last_c1 = -99;
	$this->pdf_diffstr = '';	// String of /Diffs for PDF file
	$this->pdfa_charset = '';	// mPDF 4.2.018 String of /glyphnames for PDFA file CharSet
	$this->of_encodingstr = '';
	$this->useChars = array();
	foreach ($chars AS $c1=>$c2) {	// c1 decimal codepoint => c2 decimal(Unicode)
			if (isset($this->uni2gn[$c2]) && isset($this->offs[$this->uni2gn[$c2]])) {
				$op = $this->uni2gn[$c2];
			}
			else {
				// debug** print out missing characters - not found in the t1a file
				//if ($c2 >31) { echo '&#x'.dechex($c2).'; '.$c2.'; Ux'.dechex($c2).' <br />'; }
				$op = '.notdef';
			}
			if ($c1 != ($last_c1+1)) { $this->pdf_diffstr .= $c1 . " "; }
			$this->pdf_diffstr .= "/" . $op . " ";
			$this->of_encodingstr .= "dup ". $c1 . " /" . $op . " put\n";
			if ($pdfa && $op != '.notdef') { $this->pdfa_charset .= "/" . $op; }	// mPDF 4.2.018 (don't include .notdef)
			$this->useChars[] = $op;
			$last_c1 = $c1;
	}
}

function OutputPFB($file='', $compress=true, $subr=true) {
	// $subr - also get list of subroutines
	// takes longer but will make more compact output file
	// mPDF 4.5.002
	$mqr=ini_get("magic_quotes_runtime");
	if ($mqr) { set_magic_quotes_runtime(0); }
	if (empty($this->useChars) || empty($this->of_encodingstr)) {
		$this->pfb_error('Error [OutputPDF]: No characters have been defined.');
	}
	if ($file) {
		$this->dest = 'F';
	}
	else {
		$this->dest = 'R';
	}
	$this->of_size1 = 0;
	$this->of_size2 = 0;
	$this->op_buffer = '';
	$this->wob = '';
	$this->in_eexec = 0;
	$this->blocktyp = $this->PFB_ASCII;
	$this->pfb = 1;
	$this->ever_active = 0;
	$this->lenIV = 4;
	$this->cs_start = 'RD';
	$this->c1 = 52845;
	$this->c2 = 22719;
	$this->cr = 4330;
	$this->er = 55665;

	// Replace Header items
	$this->of_header = $this->if_header;

	if ($this->of_FullName)
		$this->of_header = preg_replace('/(\/FullName\s+\().*?(\) readonly)/', '\\1'.$this->of_FullName.'\\2' , $this->of_header);
	if ($this->of_FamilyName)
		$this->of_header = preg_replace('/(\/FamilyName\s+\().*?(\) readonly)/', '\\1'.$this->of_FamilyName.'\\2' , $this->of_header);
	if ($this->of_FontName)
		$this->of_header = preg_replace('/(\/FontName\s+\/)\S*( def)/', '\\1'.$this->of_FontName.'\\2' , $this->of_header);

	// Add Header to write output buffer
	$this->wob .= $this->of_header;
	// Add Encodings
	$this->wob .= "/Encoding ".count($this->useChars)." array\n";
	$this->wob .= $this->of_encodingstr;
	$this->wob .= "readonly def\n";
	$this->wob .= "currentdict end\n";
	$this->wob .= "currentfile eexec\n";

	// Write ASCII block to main output buffer
	$this->pfb_writer_output_block();

	/************  BINARY  *****************/
	$this->in_eexec = 1;
	$this->blocktyp = $this->PFB_BINARY;

	$buffer = '';
	for ($i = 0; $i < $this->lenIV; $i++) {
		$buffer .= chr(0);
	}
	$this->eexec_string($buffer);
	// Add eexec section start
	$this->if_eexec_start = trim($this->if_eexec_start) . "\n";

	$this->eexec_string($this->if_eexec_start);
	//+++++++++++++++++++++++++++++++++++++++++++++
	// Subroutines
	$this->eexec_string("/Subrs 5 array\n");
	//Subrs 0 - 4 are obligatory and fixed
	$this->eexec_string($this->SubroutineStr);
	$this->eexec_string("ND\n2 index ");

	//+++++++++++++++++++++++++++++++++++++++++++++
	// CharStrings
	$of_CharStrings = array();
		foreach($this->useChars AS $uc) {	// glyphname e.g. lambda
			if (isset($this->offs[$uc])) {
				$of_CharStrings[$uc] = $this->offs[$uc];
			}
		}
		//NB .notdef is obligatory
		$of_CharStrings['.notdef'] = $this->offs['.notdef'];

		$this->eexec_string("/CharStrings ".count($of_CharStrings)." dict dup begin\n");
		$fh = fopen($this->file_ip .'.dat', "rb") or die("ERROR - Data file not found to embed font subsets - ".$this->file_ip .'.dat');
		foreach($of_CharStrings AS $gn => $offset) {
			fseek($fh, $offset);
			$l = $this->_freadint($fh);	// Read 4-byte integer
			$cdat = fread($fh, $l) or die("HELP!");
			$this->eexec_string("/".$gn." ");
			$this->eexec_string($cdat);
			$this->eexec_string("\n");
		}
		fclose($fh);
	$this->eexec_string("end\nend\nreadonly put\nnoaccess put\ndup/FontName get exch definefont pop\nmark currentfile closefile\n");
	$this->pfb_writer_output_block();


	// ASCII trailer
	$this->blocktyp = $this->PFB_ASCII;
	$this->in_eexec = 0;
	for ($i = 0; $i < 8; $i++) {
		$this->wob .= "0000000000000000000000000000000000000000000000000000000000000000\n";
	}
	$this->wob .= "cleartomark\n";
	$this->pfb_writer_output_block();
	$this->_out(chr($this->PFB_MARKER));
	$this->_out(chr($this->PFB_DONE));


	// Compress
	if ($compress) {
		$pos=strpos($this->op_buffer,'eexec');
		$this->of_size1=$pos+6;
		$pos=strpos($this->op_buffer,'00000000');
		$this->of_size2=$pos-$this->of_size1;
		$this->op_buffer=substr($this->op_buffer,0,$this->of_size1+$this->of_size2);
		$this->op_buffer = gzcompress($this->op_buffer);
		$this->file_op = $file.'.z';
	}
	else {
		$this->file_op = $file.'.pfb';
	}
	// Output to file or return
	if ($this->dest == 'F') {
		$this->ofp = fopen($this->file_op, "wb");
		fwrite($this->ofp,$this->op_buffer,strlen($this->op_buffer));
		fclose($this->ofp);
		if ($mqr) { set_magic_quotes_runtime($mqr); }	// mPDF 4.5.002
	}
	else {
		if ($mqr) { set_magic_quotes_runtime($mqr); }	// mPDF 4.5.002
		return $this->op_buffer;
	}
}




//+++++++++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++++++++
// Private / internal functions
function _freadint($f)
{
	//Read a 4-byte integer from file
	$i=ord(fread($f,1))<<24;
	$i+=ord(fread($f,1))<<16;
	$i+=ord(fread($f,1))<<8;
	$i+=ord(fread($f,1));
	return $i;
}

function _initialise() {
	global $mpdf_tbl_prefix;
	$this->tbl_prefix = $mpdf_tbl_prefix;
	$this->if_source = 'F';
	$this->fid = false;
	$this->file_ip = '';
	$this->file_op = '';
	$this->op_buffer = '';
	$this->if_header = '';
	$this->if_FullName = '';
	$this->if_FontName = '';
	$this->if_FamilyName = '';
	$this->if_encs = array();
	$this->if_eexec_start = '';
	$this->if_Subrs = array();
	$this->if_CharStrings = array();
	$this->SubroutineStr = '';
	$this->pdf_diffstr = '';	// String of /Diffs for PDF file
	$this->of_encodingstr = '';
	$this->useChars = array();
	$this->uni2gn = array();
}


function pfb_error($msg) {
	echo $msg;
	//die($msg);
}

function eencrypt($plain) {
//return $plain;
	$plain = ord($plain);
	$cipher = ($plain ^ ($this->er >> 8));
	$this->er = (($cipher + $this->er) * $this->c1 + $this->c2) & 0xffff;
	return chr($cipher);
}

function cencrypt($plain) {
//return $plain;
  if ($this->lenIV < 0) return $plain;
  $cipher = ($plain ^ ($this->cr >> 8));
  $this->cr = (($cipher + $this->cr) * $this->c1 + $this->c2) & 0xffff;
  return $cipher;
}

/* This function outputs a byte through possible eexec encryption. */
function eexec_byte($b) {
  if ($this->in_eexec)
    $this->wob .= ($this->eencrypt($b));
  else
    $this->wob .= ($b);
}

/* This function outputs a null-terminated string through possible eexec encryption. */
function eexec_string($string) {
  if ($this->in_eexec) {
	for($i=0; $i<strlen($string); $i++) {
		$this->wob .= ($this->eencrypt(substr($string,$i,1)));
	}
  }
  else { $this->wob .= $string; }
}


/* This function encrypts and buffers a single byte of charstring data. */
function charstring_byte($v) {
	$b = ($v & 0xff);
	$c = $this->cencrypt($b);
	return chr($c);
}

/* This function encodes an integer according to charstring number encoding. */
function charstring_int($num) {
  $c = '';
  if ($num >= -107 && $num <= 107) {
    $c .= $this->charstring_byte($num + 139);
  } else if ($num >= 108 && $num <= 1131) {
    $x = $num - 108;
    $c .= $this->charstring_byte($x / 256 + 247);
    $c .= $this->charstring_byte($x % 256);
  } else if ($num >= -1131 && $num <= -108) {
    $x = abs($num) - 108;
    $c .= $this->charstring_byte($x / 256 + 251);
    $c .= $this->charstring_byte($x % 256);
  } else if ($num >= (-2147483647-1) && $num <= 2147483647) {
    $c .= $this->charstring_byte(255);
    $c .= $this->charstring_byte($num >> 24);
    $c .= $this->charstring_byte($num >> 16);
    $c .= $this->charstring_byte($num >> 8);
    $c .= $this->charstring_byte($num);
  } else {
    $this->pfb_error("can't format huge number `%d'", $num);
    /* output 0 instead */
    $c .= $this->charstring_byte(139);
  }
  return $c;
}

/* This function parses an entire charstring into integers and commands,
   outputting bytes through the charstring buffer. */
function parse_charstring($cs) {
	/* initializes charstring encryption. */
	$buffer = '';
	$this->cr = 4330;
	for ($i = 0; $i < $this->lenIV; $i++) {
		$buffer .= $this->charstring_byte(chr(0));
	}
	$cc = preg_split('/\s+/',$cs,-1,PREG_SPLIT_NO_EMPTY);
	foreach($cc AS $c) {
		// Encode the integers according to charstring number encoding
		if (preg_match('/^[\-]{0,1}\d+$/',$c)) {
			$buffer .= $this->charstring_int($c);
		}

		// Encode the commands according to charstring command encoding
		else if (isset($this->cs_commands[$c])) {
			$one = $this->cs_commands[$c][0];
			$two = $this->cs_commands[$c][1];
			if ($one < 0 || $one > 255)
				$this->pfb_error("bad charstring command number $d in %s in %s", $one, $c, $cs);
			else if ($two > 255)
				$this->pfb_error("bad charstring command number $d in %s in %s", $two, $c, $cs);
			else if ($two < 0) {
				$buffer .= $this->charstring_byte($one);
			}
			else {
				$buffer .= $this->charstring_byte($one);
				$buffer .= $this->charstring_byte($two);
			}
		}
		else {
			$this->pfb_error("unknown charstring entry %s in %s", $c, $cs);
		}
	}
	$s = sprintf("%d ", strlen($buffer));
	$this->eexec_string($s);
	$s = sprintf("%s ", $this->cs_start);
	$this->eexec_string($s);
	$this->eexec_string($buffer);
	$buffer = '';
}

function pfb_writer_output_block() {
	$l = strlen($this->wob);
	if ($l == 0)
		return;
	/* output four-byte block length */
	$this->_out(chr($this->PFB_MARKER));
	$this->_out(chr($this->blocktyp));
	$this->_out(chr($l & 0xff));
	$this->_out(chr(($l >> 8) & 0xff));
	$this->_out(chr(($l >> 16) & 0xff));
	$this->_out(chr(($l >> 24) & 0xff));
	/* output block data */
	$this->_out($this->wob);
	$this->wob = '';
}

function _out($s) {
	$this->op_buffer .= $s;
}


//+++++++++++++++++++++++++++++++++++++++++++++

}	// end of class

?>