<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GenreID extends AbstractTag
{

    protected $Id = 'geID';

    protected $Name = 'GenreID';

    protected $FullName = 'QuickTime::ItemList';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Genre ID';

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => 'Music|Blues',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Music|Comedy',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Music|Children\'s Music',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Music|Classical',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Music|Country',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Music|Electronic',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Music|Holiday',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Music|Classical|Opera',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Music|Singer/Songwriter',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Music|Jazz',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Music|Latino',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Music|New Age',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Music|Pop',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Music|R&B/Soul',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Music|Soundtrack',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Music|Dance',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Music|Hip-Hop/Rap',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Music|World',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Music|Alternative',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Music|Rock',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Music|Christian & Gospel',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Music|Vocal',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Music|Reggae',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Music|Easy Listening',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Podcasts',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Music|J-Pop',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Music|Enka',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Music|Anime',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Music|Kayokyoku',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Music Videos',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'TV Shows',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Movies',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Music',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'iPod Games',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'App Store',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Tones',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Books',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Mac App Store',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Textbooks',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Music|Fitness & Workout',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Music|Pop|K-Pop',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Music|Karaoke',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'Music|Instrumental',
        ),
        74 => array(
            'Id' => 74,
            'Label' => 'Audiobooks|News',
        ),
        75 => array(
            'Id' => 75,
            'Label' => 'Audiobooks|Programs & Performances',
        ),
        1001 => array(
            'Id' => 1001,
            'Label' => 'Music|Alternative|College Rock',
        ),
        1002 => array(
            'Id' => 1002,
            'Label' => 'Music|Alternative|Goth Rock',
        ),
        1003 => array(
            'Id' => 1003,
            'Label' => 'Music|Alternative|Grunge',
        ),
        1004 => array(
            'Id' => 1004,
            'Label' => 'Music|Alternative|Indie Rock',
        ),
        1005 => array(
            'Id' => 1005,
            'Label' => 'Music|Alternative|New Wave',
        ),
        1006 => array(
            'Id' => 1006,
            'Label' => 'Music|Alternative|Punk',
        ),
        1007 => array(
            'Id' => 1007,
            'Label' => 'Music|Blues|Chicago Blues',
        ),
        1009 => array(
            'Id' => 1009,
            'Label' => 'Music|Blues|Classic Blues',
        ),
        1010 => array(
            'Id' => 1010,
            'Label' => 'Music|Blues|Contemporary Blues',
        ),
        1011 => array(
            'Id' => 1011,
            'Label' => 'Music|Blues|Country Blues',
        ),
        1012 => array(
            'Id' => 1012,
            'Label' => 'Music|Blues|Delta Blues',
        ),
        1013 => array(
            'Id' => 1013,
            'Label' => 'Music|Blues|Electric Blues',
        ),
        1014 => array(
            'Id' => 1014,
            'Label' => 'Music|Children\'s Music|Lullabies',
        ),
        1015 => array(
            'Id' => 1015,
            'Label' => 'Music|Children\'s Music|Sing-Along',
        ),
        1016 => array(
            'Id' => 1016,
            'Label' => 'Music|Children\'s Music|Stories',
        ),
        1017 => array(
            'Id' => 1017,
            'Label' => 'Music|Classical|Avant-Garde',
        ),
        1018 => array(
            'Id' => 1018,
            'Label' => 'Music|Classical|Baroque Era',
        ),
        1019 => array(
            'Id' => 1019,
            'Label' => 'Music|Classical|Chamber Music',
        ),
        1020 => array(
            'Id' => 1020,
            'Label' => 'Music|Classical|Chant',
        ),
        1021 => array(
            'Id' => 1021,
            'Label' => 'Music|Classical|Choral',
        ),
        1022 => array(
            'Id' => 1022,
            'Label' => 'Music|Classical|Classical Crossover',
        ),
        1023 => array(
            'Id' => 1023,
            'Label' => 'Music|Classical|Early Music',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Music|Classical|Impressionist',
        ),
        1025 => array(
            'Id' => 1025,
            'Label' => 'Music|Classical|Medieval Era',
        ),
        1026 => array(
            'Id' => 1026,
            'Label' => 'Music|Classical|Minimalism',
        ),
        1027 => array(
            'Id' => 1027,
            'Label' => 'Music|Classical|Modern Era',
        ),
        1028 => array(
            'Id' => 1028,
            'Label' => 'Music|Classical|Opera',
        ),
        1029 => array(
            'Id' => 1029,
            'Label' => 'Music|Classical|Orchestral',
        ),
        1030 => array(
            'Id' => 1030,
            'Label' => 'Music|Classical|Renaissance',
        ),
        1031 => array(
            'Id' => 1031,
            'Label' => 'Music|Classical|Romantic Era',
        ),
        1032 => array(
            'Id' => 1032,
            'Label' => 'Music|Classical|Wedding Music',
        ),
        1033 => array(
            'Id' => 1033,
            'Label' => 'Music|Country|Alternative Country',
        ),
        1034 => array(
            'Id' => 1034,
            'Label' => 'Music|Country|Americana',
        ),
        1035 => array(
            'Id' => 1035,
            'Label' => 'Music|Country|Bluegrass',
        ),
        1036 => array(
            'Id' => 1036,
            'Label' => 'Music|Country|Contemporary Bluegrass',
        ),
        1037 => array(
            'Id' => 1037,
            'Label' => 'Music|Country|Contemporary Country',
        ),
        1038 => array(
            'Id' => 1038,
            'Label' => 'Music|Country|Country Gospel',
        ),
        1039 => array(
            'Id' => 1039,
            'Label' => 'Music|Country|Honky Tonk',
        ),
        1040 => array(
            'Id' => 1040,
            'Label' => 'Music|Country|Outlaw Country',
        ),
        1041 => array(
            'Id' => 1041,
            'Label' => 'Music|Country|Traditional Bluegrass',
        ),
        1042 => array(
            'Id' => 1042,
            'Label' => 'Music|Country|Traditional Country',
        ),
        1043 => array(
            'Id' => 1043,
            'Label' => 'Music|Country|Urban Cowboy',
        ),
        1044 => array(
            'Id' => 1044,
            'Label' => 'Music|Dance|Breakbeat',
        ),
        1045 => array(
            'Id' => 1045,
            'Label' => 'Music|Dance|Exercise',
        ),
        1046 => array(
            'Id' => 1046,
            'Label' => 'Music|Dance|Garage',
        ),
        1047 => array(
            'Id' => 1047,
            'Label' => 'Music|Dance|Hardcore',
        ),
        1048 => array(
            'Id' => 1048,
            'Label' => 'Music|Dance|House',
        ),
        1049 => array(
            'Id' => 1049,
            'Label' => 'Music|Dance|Jungle/Drum\'n\'bass',
        ),
        1050 => array(
            'Id' => 1050,
            'Label' => 'Music|Dance|Techno',
        ),
        1051 => array(
            'Id' => 1051,
            'Label' => 'Music|Dance|Trance',
        ),
        1052 => array(
            'Id' => 1052,
            'Label' => 'Music|Jazz|Big Band',
        ),
        1053 => array(
            'Id' => 1053,
            'Label' => 'Music|Jazz|Bop',
        ),
        1054 => array(
            'Id' => 1054,
            'Label' => 'Music|Easy Listening|Lounge',
        ),
        1055 => array(
            'Id' => 1055,
            'Label' => 'Music|Easy Listening|Swing',
        ),
        1056 => array(
            'Id' => 1056,
            'Label' => 'Music|Electronic|Ambient',
        ),
        1057 => array(
            'Id' => 1057,
            'Label' => 'Music|Electronic|Downtempo',
        ),
        1058 => array(
            'Id' => 1058,
            'Label' => 'Music|Electronic|Electronica',
        ),
        1060 => array(
            'Id' => 1060,
            'Label' => 'Music|Electronic|IDM/Experimental',
        ),
        1061 => array(
            'Id' => 1061,
            'Label' => 'Music|Electronic|Industrial',
        ),
        1062 => array(
            'Id' => 1062,
            'Label' => 'Music|Singer/Songwriter|Alternative Folk',
        ),
        1063 => array(
            'Id' => 1063,
            'Label' => 'Music|Singer/Songwriter|Contemporary Folk',
        ),
        1064 => array(
            'Id' => 1064,
            'Label' => 'Music|Singer/Songwriter|Contemporary Singer/Songwriter',
        ),
        1065 => array(
            'Id' => 1065,
            'Label' => 'Music|Singer/Songwriter|Folk-Rock',
        ),
        1066 => array(
            'Id' => 1066,
            'Label' => 'Music|Singer/Songwriter|New Acoustic',
        ),
        1067 => array(
            'Id' => 1067,
            'Label' => 'Music|Singer/Songwriter|Traditional Folk',
        ),
        1068 => array(
            'Id' => 1068,
            'Label' => 'Music|Hip-Hop/Rap|Alternative Rap',
        ),
        1069 => array(
            'Id' => 1069,
            'Label' => 'Music|Hip-Hop/Rap|Dirty South',
        ),
        1070 => array(
            'Id' => 1070,
            'Label' => 'Music|Hip-Hop/Rap|East Coast Rap',
        ),
        1071 => array(
            'Id' => 1071,
            'Label' => 'Music|Hip-Hop/Rap|Gangsta Rap',
        ),
        1072 => array(
            'Id' => 1072,
            'Label' => 'Music|Hip-Hop/Rap|Hardcore Rap',
        ),
        1073 => array(
            'Id' => 1073,
            'Label' => 'Music|Hip-Hop/Rap|Hip-Hop',
        ),
        1074 => array(
            'Id' => 1074,
            'Label' => 'Music|Hip-Hop/Rap|Latin Rap',
        ),
        1075 => array(
            'Id' => 1075,
            'Label' => 'Music|Hip-Hop/Rap|Old School Rap',
        ),
        1076 => array(
            'Id' => 1076,
            'Label' => 'Music|Hip-Hop/Rap|Rap',
        ),
        1077 => array(
            'Id' => 1077,
            'Label' => 'Music|Hip-Hop/Rap|Underground Rap',
        ),
        1078 => array(
            'Id' => 1078,
            'Label' => 'Music|Hip-Hop/Rap|West Coast Rap',
        ),
        1079 => array(
            'Id' => 1079,
            'Label' => 'Music|Holiday|Chanukah',
        ),
        1080 => array(
            'Id' => 1080,
            'Label' => 'Music|Holiday|Christmas',
        ),
        1081 => array(
            'Id' => 1081,
            'Label' => 'Music|Holiday|Christmas: Children\'s',
        ),
        1082 => array(
            'Id' => 1082,
            'Label' => 'Music|Holiday|Christmas: Classic',
        ),
        1083 => array(
            'Id' => 1083,
            'Label' => 'Music|Holiday|Christmas: Classical',
        ),
        1084 => array(
            'Id' => 1084,
            'Label' => 'Music|Holiday|Christmas: Jazz',
        ),
        1085 => array(
            'Id' => 1085,
            'Label' => 'Music|Holiday|Christmas: Modern',
        ),
        1086 => array(
            'Id' => 1086,
            'Label' => 'Music|Holiday|Christmas: Pop',
        ),
        1087 => array(
            'Id' => 1087,
            'Label' => 'Music|Holiday|Christmas: R&B',
        ),
        1088 => array(
            'Id' => 1088,
            'Label' => 'Music|Holiday|Christmas: Religious',
        ),
        1089 => array(
            'Id' => 1089,
            'Label' => 'Music|Holiday|Christmas: Rock',
        ),
        1090 => array(
            'Id' => 1090,
            'Label' => 'Music|Holiday|Easter',
        ),
        1091 => array(
            'Id' => 1091,
            'Label' => 'Music|Holiday|Halloween',
        ),
        1092 => array(
            'Id' => 1092,
            'Label' => 'Music|Holiday|Holiday: Other',
        ),
        1093 => array(
            'Id' => 1093,
            'Label' => 'Music|Holiday|Thanksgiving',
        ),
        1094 => array(
            'Id' => 1094,
            'Label' => 'Music|Christian & Gospel|CCM',
        ),
        1095 => array(
            'Id' => 1095,
            'Label' => 'Music|Christian & Gospel|Christian Metal',
        ),
        1096 => array(
            'Id' => 1096,
            'Label' => 'Music|Christian & Gospel|Christian Pop',
        ),
        1097 => array(
            'Id' => 1097,
            'Label' => 'Music|Christian & Gospel|Christian Rap',
        ),
        1098 => array(
            'Id' => 1098,
            'Label' => 'Music|Christian & Gospel|Christian Rock',
        ),
        1099 => array(
            'Id' => 1099,
            'Label' => 'Music|Christian & Gospel|Classic Christian',
        ),
        1100 => array(
            'Id' => 1100,
            'Label' => 'Music|Christian & Gospel|Contemporary Gospel',
        ),
        1101 => array(
            'Id' => 1101,
            'Label' => 'Music|Christian & Gospel|Gospel',
        ),
        1103 => array(
            'Id' => 1103,
            'Label' => 'Music|Christian & Gospel|Praise & Worship',
        ),
        1104 => array(
            'Id' => 1104,
            'Label' => 'Music|Christian & Gospel|Southern Gospel',
        ),
        1105 => array(
            'Id' => 1105,
            'Label' => 'Music|Christian & Gospel|Traditional Gospel',
        ),
        1106 => array(
            'Id' => 1106,
            'Label' => 'Music|Jazz|Avant-Garde Jazz',
        ),
        1107 => array(
            'Id' => 1107,
            'Label' => 'Music|Jazz|Contemporary Jazz',
        ),
        1108 => array(
            'Id' => 1108,
            'Label' => 'Music|Jazz|Crossover Jazz',
        ),
        1109 => array(
            'Id' => 1109,
            'Label' => 'Music|Jazz|Dixieland',
        ),
        1110 => array(
            'Id' => 1110,
            'Label' => 'Music|Jazz|Fusion',
        ),
        1111 => array(
            'Id' => 1111,
            'Label' => 'Music|Jazz|Latin Jazz',
        ),
        1112 => array(
            'Id' => 1112,
            'Label' => 'Music|Jazz|Mainstream Jazz',
        ),
        1113 => array(
            'Id' => 1113,
            'Label' => 'Music|Jazz|Ragtime',
        ),
        1114 => array(
            'Id' => 1114,
            'Label' => 'Music|Jazz|Smooth Jazz',
        ),
        1115 => array(
            'Id' => 1115,
            'Label' => 'Music|Latino|Latin Jazz',
        ),
        1116 => array(
            'Id' => 1116,
            'Label' => 'Music|Latino|Contemporary Latin',
        ),
        1117 => array(
            'Id' => 1117,
            'Label' => 'Music|Latino|Latin Pop',
        ),
        1118 => array(
            'Id' => 1118,
            'Label' => 'Music|Latino|Raices',
        ),
        1119 => array(
            'Id' => 1119,
            'Label' => 'Music|Latino|Latin Urban',
        ),
        1120 => array(
            'Id' => 1120,
            'Label' => 'Music|Latino|Baladas y Boleros',
        ),
        1121 => array(
            'Id' => 1121,
            'Label' => 'Music|Latino|Latin Alternative & Rock',
        ),
        1122 => array(
            'Id' => 1122,
            'Label' => 'Music|Brazilian',
        ),
        1123 => array(
            'Id' => 1123,
            'Label' => 'Music|Latino|Regional Mexicano',
        ),
        1124 => array(
            'Id' => 1124,
            'Label' => 'Music|Latino|Salsa y Tropical',
        ),
        1125 => array(
            'Id' => 1125,
            'Label' => 'Music|New Age|Environmental',
        ),
        1126 => array(
            'Id' => 1126,
            'Label' => 'Music|New Age|Healing',
        ),
        1127 => array(
            'Id' => 1127,
            'Label' => 'Music|New Age|Meditation',
        ),
        1128 => array(
            'Id' => 1128,
            'Label' => 'Music|New Age|Nature',
        ),
        1129 => array(
            'Id' => 1129,
            'Label' => 'Music|New Age|Relaxation',
        ),
        1130 => array(
            'Id' => 1130,
            'Label' => 'Music|New Age|Travel',
        ),
        1131 => array(
            'Id' => 1131,
            'Label' => 'Music|Pop|Adult Contemporary',
        ),
        1132 => array(
            'Id' => 1132,
            'Label' => 'Music|Pop|Britpop',
        ),
        1133 => array(
            'Id' => 1133,
            'Label' => 'Music|Pop|Pop/Rock',
        ),
        1134 => array(
            'Id' => 1134,
            'Label' => 'Music|Pop|Soft Rock',
        ),
        1135 => array(
            'Id' => 1135,
            'Label' => 'Music|Pop|Teen Pop',
        ),
        1136 => array(
            'Id' => 1136,
            'Label' => 'Music|R&B/Soul|Contemporary R&B',
        ),
        1137 => array(
            'Id' => 1137,
            'Label' => 'Music|R&B/Soul|Disco',
        ),
        1138 => array(
            'Id' => 1138,
            'Label' => 'Music|R&B/Soul|Doo Wop',
        ),
        1139 => array(
            'Id' => 1139,
            'Label' => 'Music|R&B/Soul|Funk',
        ),
        1140 => array(
            'Id' => 1140,
            'Label' => 'Music|R&B/Soul|Motown',
        ),
        1141 => array(
            'Id' => 1141,
            'Label' => 'Music|R&B/Soul|Neo-Soul',
        ),
        1142 => array(
            'Id' => 1142,
            'Label' => 'Music|R&B/Soul|Quiet Storm',
        ),
        1143 => array(
            'Id' => 1143,
            'Label' => 'Music|R&B/Soul|Soul',
        ),
        1144 => array(
            'Id' => 1144,
            'Label' => 'Music|Rock|Adult Alternative',
        ),
        1145 => array(
            'Id' => 1145,
            'Label' => 'Music|Rock|American Trad Rock',
        ),
        1146 => array(
            'Id' => 1146,
            'Label' => 'Music|Rock|Arena Rock',
        ),
        1147 => array(
            'Id' => 1147,
            'Label' => 'Music|Rock|Blues-Rock',
        ),
        1148 => array(
            'Id' => 1148,
            'Label' => 'Music|Rock|British Invasion',
        ),
        1149 => array(
            'Id' => 1149,
            'Label' => 'Music|Rock|Death Metal/Black Metal',
        ),
        1150 => array(
            'Id' => 1150,
            'Label' => 'Music|Rock|Glam Rock',
        ),
        1151 => array(
            'Id' => 1151,
            'Label' => 'Music|Rock|Hair Metal',
        ),
        1152 => array(
            'Id' => 1152,
            'Label' => 'Music|Rock|Hard Rock',
        ),
        1153 => array(
            'Id' => 1153,
            'Label' => 'Music|Rock|Metal',
        ),
        1154 => array(
            'Id' => 1154,
            'Label' => 'Music|Rock|Jam Bands',
        ),
        1155 => array(
            'Id' => 1155,
            'Label' => 'Music|Rock|Prog-Rock/Art Rock',
        ),
        1156 => array(
            'Id' => 1156,
            'Label' => 'Music|Rock|Psychedelic',
        ),
        1157 => array(
            'Id' => 1157,
            'Label' => 'Music|Rock|Rock & Roll',
        ),
        1158 => array(
            'Id' => 1158,
            'Label' => 'Music|Rock|Rockabilly',
        ),
        1159 => array(
            'Id' => 1159,
            'Label' => 'Music|Rock|Roots Rock',
        ),
        1160 => array(
            'Id' => 1160,
            'Label' => 'Music|Rock|Singer/Songwriter',
        ),
        1161 => array(
            'Id' => 1161,
            'Label' => 'Music|Rock|Southern Rock',
        ),
        1162 => array(
            'Id' => 1162,
            'Label' => 'Music|Rock|Surf',
        ),
        1163 => array(
            'Id' => 1163,
            'Label' => 'Music|Rock|Tex-Mex',
        ),
        1165 => array(
            'Id' => 1165,
            'Label' => 'Music|Soundtrack|Foreign Cinema',
        ),
        1166 => array(
            'Id' => 1166,
            'Label' => 'Music|Soundtrack|Musicals',
        ),
        1167 => array(
            'Id' => 1167,
            'Label' => 'Music|Comedy|Novelty',
        ),
        1168 => array(
            'Id' => 1168,
            'Label' => 'Music|Soundtrack|Original Score',
        ),
        1169 => array(
            'Id' => 1169,
            'Label' => 'Music|Soundtrack|Soundtrack',
        ),
        1171 => array(
            'Id' => 1171,
            'Label' => 'Music|Comedy|Standup Comedy',
        ),
        1172 => array(
            'Id' => 1172,
            'Label' => 'Music|Soundtrack|TV Soundtrack',
        ),
        1173 => array(
            'Id' => 1173,
            'Label' => 'Music|Vocal|Standards',
        ),
        1174 => array(
            'Id' => 1174,
            'Label' => 'Music|Vocal|Traditional Pop',
        ),
        1175 => array(
            'Id' => 1175,
            'Label' => 'Music|Jazz|Vocal Jazz',
        ),
        1176 => array(
            'Id' => 1176,
            'Label' => 'Music|Vocal|Vocal Pop',
        ),
        1177 => array(
            'Id' => 1177,
            'Label' => 'Music|World|Afro-Beat',
        ),
        1178 => array(
            'Id' => 1178,
            'Label' => 'Music|World|Afro-Pop',
        ),
        1179 => array(
            'Id' => 1179,
            'Label' => 'Music|World|Cajun',
        ),
        1180 => array(
            'Id' => 1180,
            'Label' => 'Music|World|Celtic',
        ),
        1181 => array(
            'Id' => 1181,
            'Label' => 'Music|World|Celtic Folk',
        ),
        1182 => array(
            'Id' => 1182,
            'Label' => 'Music|World|Contemporary Celtic',
        ),
        1183 => array(
            'Id' => 1183,
            'Label' => 'Music|Reggae|Modern Dancehall',
        ),
        1184 => array(
            'Id' => 1184,
            'Label' => 'Music|World|Drinking Songs',
        ),
        1185 => array(
            'Id' => 1185,
            'Label' => 'Music|Indian|Indian Pop',
        ),
        1186 => array(
            'Id' => 1186,
            'Label' => 'Music|World|Japanese Pop',
        ),
        1187 => array(
            'Id' => 1187,
            'Label' => 'Music|World|Klezmer',
        ),
        1188 => array(
            'Id' => 1188,
            'Label' => 'Music|World|Polka',
        ),
        1189 => array(
            'Id' => 1189,
            'Label' => 'Music|World|Traditional Celtic',
        ),
        1190 => array(
            'Id' => 1190,
            'Label' => 'Music|World|Worldbeat',
        ),
        1191 => array(
            'Id' => 1191,
            'Label' => 'Music|World|Zydeco',
        ),
        1192 => array(
            'Id' => 1192,
            'Label' => 'Music|Reggae|Roots Reggae',
        ),
        1193 => array(
            'Id' => 1193,
            'Label' => 'Music|Reggae|Dub',
        ),
        1194 => array(
            'Id' => 1194,
            'Label' => 'Music|Reggae|Ska',
        ),
        1195 => array(
            'Id' => 1195,
            'Label' => 'Music|World|Caribbean',
        ),
        1196 => array(
            'Id' => 1196,
            'Label' => 'Music|World|South America',
        ),
        1197 => array(
            'Id' => 1197,
            'Label' => 'Music|Arabic',
        ),
        1198 => array(
            'Id' => 1198,
            'Label' => 'Music|World|North America',
        ),
        1199 => array(
            'Id' => 1199,
            'Label' => 'Music|World|Hawaii',
        ),
        1200 => array(
            'Id' => 1200,
            'Label' => 'Music|World|Australia',
        ),
        1201 => array(
            'Id' => 1201,
            'Label' => 'Music|World|Japan',
        ),
        1202 => array(
            'Id' => 1202,
            'Label' => 'Music|World|France',
        ),
        1203 => array(
            'Id' => 1203,
            'Label' => 'Music|World|Africa',
        ),
        1204 => array(
            'Id' => 1204,
            'Label' => 'Music|World|Asia',
        ),
        1205 => array(
            'Id' => 1205,
            'Label' => 'Music|World|Europe',
        ),
        1206 => array(
            'Id' => 1206,
            'Label' => 'Music|World|South Africa',
        ),
        1207 => array(
            'Id' => 1207,
            'Label' => 'Music|Jazz|Hard Bop',
        ),
        1208 => array(
            'Id' => 1208,
            'Label' => 'Music|Jazz|Trad Jazz',
        ),
        1209 => array(
            'Id' => 1209,
            'Label' => 'Music|Jazz|Cool',
        ),
        1210 => array(
            'Id' => 1210,
            'Label' => 'Music|Blues|Acoustic Blues',
        ),
        1211 => array(
            'Id' => 1211,
            'Label' => 'Music|Classical|High Classical',
        ),
        1220 => array(
            'Id' => 1220,
            'Label' => 'Music|Brazilian|Axe',
        ),
        1221 => array(
            'Id' => 1221,
            'Label' => 'Music|Brazilian|Bossa Nova',
        ),
        1222 => array(
            'Id' => 1222,
            'Label' => 'Music|Brazilian|Choro',
        ),
        1223 => array(
            'Id' => 1223,
            'Label' => 'Music|Brazilian|Forro',
        ),
        1224 => array(
            'Id' => 1224,
            'Label' => 'Music|Brazilian|Frevo',
        ),
        1225 => array(
            'Id' => 1225,
            'Label' => 'Music|Brazilian|MPB',
        ),
        1226 => array(
            'Id' => 1226,
            'Label' => 'Music|Brazilian|Pagode',
        ),
        1227 => array(
            'Id' => 1227,
            'Label' => 'Music|Brazilian|Samba',
        ),
        1228 => array(
            'Id' => 1228,
            'Label' => 'Music|Brazilian|Sertanejo',
        ),
        1229 => array(
            'Id' => 1229,
            'Label' => 'Music|Brazilian|Baile Funk',
        ),
        1230 => array(
            'Id' => 1230,
            'Label' => 'Music|Alternative|Chinese Alt',
        ),
        1231 => array(
            'Id' => 1231,
            'Label' => 'Music|Alternative|Korean Indie',
        ),
        1232 => array(
            'Id' => 1232,
            'Label' => 'Music|Chinese',
        ),
        1233 => array(
            'Id' => 1233,
            'Label' => 'Music|Chinese|Chinese Classical',
        ),
        1234 => array(
            'Id' => 1234,
            'Label' => 'Music|Chinese|Chinese Flute',
        ),
        1235 => array(
            'Id' => 1235,
            'Label' => 'Music|Chinese|Chinese Opera',
        ),
        1236 => array(
            'Id' => 1236,
            'Label' => 'Music|Chinese|Chinese Orchestral',
        ),
        1237 => array(
            'Id' => 1237,
            'Label' => 'Music|Chinese|Chinese Regional Folk',
        ),
        1238 => array(
            'Id' => 1238,
            'Label' => 'Music|Chinese|Chinese Strings',
        ),
        1239 => array(
            'Id' => 1239,
            'Label' => 'Music|Chinese|Taiwanese Folk',
        ),
        1240 => array(
            'Id' => 1240,
            'Label' => 'Music|Chinese|Tibetan Native Music',
        ),
        1241 => array(
            'Id' => 1241,
            'Label' => 'Music|Hip-Hop/Rap|Chinese Hip-Hop',
        ),
        1242 => array(
            'Id' => 1242,
            'Label' => 'Music|Hip-Hop/Rap|Korean Hip-Hop',
        ),
        1243 => array(
            'Id' => 1243,
            'Label' => 'Music|Korean',
        ),
        1244 => array(
            'Id' => 1244,
            'Label' => 'Music|Korean|Korean Classical',
        ),
        1245 => array(
            'Id' => 1245,
            'Label' => 'Music|Korean|Korean Trad Song',
        ),
        1246 => array(
            'Id' => 1246,
            'Label' => 'Music|Korean|Korean Trad Instrumental',
        ),
        1247 => array(
            'Id' => 1247,
            'Label' => 'Music|Korean|Korean Trad Theater',
        ),
        1248 => array(
            'Id' => 1248,
            'Label' => 'Music|Rock|Chinese Rock',
        ),
        1249 => array(
            'Id' => 1249,
            'Label' => 'Music|Rock|Korean Rock',
        ),
        1250 => array(
            'Id' => 1250,
            'Label' => 'Music|Pop|C-Pop',
        ),
        1251 => array(
            'Id' => 1251,
            'Label' => 'Music|Pop|Cantopop/HK-Pop',
        ),
        1252 => array(
            'Id' => 1252,
            'Label' => 'Music|Pop|Korean Folk-Pop',
        ),
        1253 => array(
            'Id' => 1253,
            'Label' => 'Music|Pop|Mandopop',
        ),
        1254 => array(
            'Id' => 1254,
            'Label' => 'Music|Pop|Tai-Pop',
        ),
        1255 => array(
            'Id' => 1255,
            'Label' => 'Music|Pop|Malaysian Pop',
        ),
        1256 => array(
            'Id' => 1256,
            'Label' => 'Music|Pop|Pinoy Pop',
        ),
        1257 => array(
            'Id' => 1257,
            'Label' => 'Music|Pop|Original Pilipino Music',
        ),
        1258 => array(
            'Id' => 1258,
            'Label' => 'Music|Pop|Manilla Sound',
        ),
        1259 => array(
            'Id' => 1259,
            'Label' => 'Music|Pop|Indo Pop',
        ),
        1260 => array(
            'Id' => 1260,
            'Label' => 'Music|Pop|Thai Pop',
        ),
        1261 => array(
            'Id' => 1261,
            'Label' => 'Music|Vocal|Trot',
        ),
        1262 => array(
            'Id' => 1262,
            'Label' => 'Music|Indian',
        ),
        1263 => array(
            'Id' => 1263,
            'Label' => 'Music|Indian|Bollywood',
        ),
        1264 => array(
            'Id' => 1264,
            'Label' => 'Music|Indian|Tamil',
        ),
        1265 => array(
            'Id' => 1265,
            'Label' => 'Music|Indian|Telugu',
        ),
        1266 => array(
            'Id' => 1266,
            'Label' => 'Music|Indian|Regional Indian',
        ),
        1267 => array(
            'Id' => 1267,
            'Label' => 'Music|Indian|Devotional & Spiritual',
        ),
        1268 => array(
            'Id' => 1268,
            'Label' => 'Music|Indian|Sufi',
        ),
        1269 => array(
            'Id' => 1269,
            'Label' => 'Music|Indian|Indian Classical',
        ),
        1270 => array(
            'Id' => 1270,
            'Label' => 'Music|World|Russian Chanson',
        ),
        1271 => array(
            'Id' => 1271,
            'Label' => 'Music|World|Dini',
        ),
        1272 => array(
            'Id' => 1272,
            'Label' => 'Music|World|Halk',
        ),
        1273 => array(
            'Id' => 1273,
            'Label' => 'Music|World|Sanat',
        ),
        1274 => array(
            'Id' => 1274,
            'Label' => 'Music|World|Dangdut',
        ),
        1275 => array(
            'Id' => 1275,
            'Label' => 'Music|World|Indonesian Religious',
        ),
        1276 => array(
            'Id' => 1276,
            'Label' => 'Music|World|Calypso',
        ),
        1277 => array(
            'Id' => 1277,
            'Label' => 'Music|World|Soca',
        ),
        1278 => array(
            'Id' => 1278,
            'Label' => 'Music|Indian|Ghazals',
        ),
        1279 => array(
            'Id' => 1279,
            'Label' => 'Music|Indian|Indian Folk',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'Music|World|Arabesque',
        ),
        1281 => array(
            'Id' => 1281,
            'Label' => 'Music|World|Afrikaans',
        ),
        1282 => array(
            'Id' => 1282,
            'Label' => 'Music|World|Farsi',
        ),
        1283 => array(
            'Id' => 1283,
            'Label' => 'Music|World|Israeli',
        ),
        1284 => array(
            'Id' => 1284,
            'Label' => 'Music|Arabic|Khaleeji',
        ),
        1285 => array(
            'Id' => 1285,
            'Label' => 'Music|Arabic|North African',
        ),
        1286 => array(
            'Id' => 1286,
            'Label' => 'Music|Arabic|Arabic Pop',
        ),
        1287 => array(
            'Id' => 1287,
            'Label' => 'Music|Arabic|Islamic',
        ),
        1288 => array(
            'Id' => 1288,
            'Label' => 'Music|Soundtrack|Sound Effects',
        ),
        1289 => array(
            'Id' => 1289,
            'Label' => 'Music|Folk',
        ),
        1290 => array(
            'Id' => 1290,
            'Label' => 'Music|Orchestral',
        ),
        1291 => array(
            'Id' => 1291,
            'Label' => 'Music|Marching',
        ),
        1293 => array(
            'Id' => 1293,
            'Label' => 'Music|Pop|Oldies',
        ),
        1294 => array(
            'Id' => 1294,
            'Label' => 'Music|Country|Thai Country',
        ),
        1295 => array(
            'Id' => 1295,
            'Label' => 'Music|World|Flamenco',
        ),
        1296 => array(
            'Id' => 1296,
            'Label' => 'Music|World|Tango',
        ),
        1297 => array(
            'Id' => 1297,
            'Label' => 'Music|World|Fado',
        ),
        1298 => array(
            'Id' => 1298,
            'Label' => 'Music|World|Iberia',
        ),
        1299 => array(
            'Id' => 1299,
            'Label' => 'Music|World|Russian',
        ),
        1300 => array(
            'Id' => 1300,
            'Label' => 'Music|World|Turkish',
        ),
        1301 => array(
            'Id' => 1301,
            'Label' => 'Podcasts|Arts',
        ),
        1302 => array(
            'Id' => 1302,
            'Label' => 'Podcasts|Society & Culture|Personal Journals',
        ),
        1303 => array(
            'Id' => 1303,
            'Label' => 'Podcasts|Comedy',
        ),
        1304 => array(
            'Id' => 1304,
            'Label' => 'Podcasts|Education',
        ),
        1305 => array(
            'Id' => 1305,
            'Label' => 'Podcasts|Kids & Family',
        ),
        1306 => array(
            'Id' => 1306,
            'Label' => 'Podcasts|Arts|Food',
        ),
        1307 => array(
            'Id' => 1307,
            'Label' => 'Podcasts|Health',
        ),
        1309 => array(
            'Id' => 1309,
            'Label' => 'Podcasts|TV & Film',
        ),
        1310 => array(
            'Id' => 1310,
            'Label' => 'Podcasts|Music',
        ),
        1311 => array(
            'Id' => 1311,
            'Label' => 'Podcasts|News & Politics',
        ),
        1314 => array(
            'Id' => 1314,
            'Label' => 'Podcasts|Religion & Spirituality',
        ),
        1315 => array(
            'Id' => 1315,
            'Label' => 'Podcasts|Science & Medicine',
        ),
        1316 => array(
            'Id' => 1316,
            'Label' => 'Podcasts|Sports & Recreation',
        ),
        1318 => array(
            'Id' => 1318,
            'Label' => 'Podcasts|Technology',
        ),
        1320 => array(
            'Id' => 1320,
            'Label' => 'Podcasts|Society & Culture|Places & Travel',
        ),
        1321 => array(
            'Id' => 1321,
            'Label' => 'Podcasts|Business',
        ),
        1323 => array(
            'Id' => 1323,
            'Label' => 'Podcasts|Games & Hobbies',
        ),
        1324 => array(
            'Id' => 1324,
            'Label' => 'Podcasts|Society & Culture',
        ),
        1325 => array(
            'Id' => 1325,
            'Label' => 'Podcasts|Government & Organizations',
        ),
        1337 => array(
            'Id' => 1337,
            'Label' => 'Music Videos|Classical|Piano',
        ),
        1401 => array(
            'Id' => 1401,
            'Label' => 'Podcasts|Arts|Literature',
        ),
        1402 => array(
            'Id' => 1402,
            'Label' => 'Podcasts|Arts|Design',
        ),
        1404 => array(
            'Id' => 1404,
            'Label' => 'Podcasts|Games & Hobbies|Video Games',
        ),
        1405 => array(
            'Id' => 1405,
            'Label' => 'Podcasts|Arts|Performing Arts',
        ),
        1406 => array(
            'Id' => 1406,
            'Label' => 'Podcasts|Arts|Visual Arts',
        ),
        1410 => array(
            'Id' => 1410,
            'Label' => 'Podcasts|Business|Careers',
        ),
        1412 => array(
            'Id' => 1412,
            'Label' => 'Podcasts|Business|Investing',
        ),
        1413 => array(
            'Id' => 1413,
            'Label' => 'Podcasts|Business|Management & Marketing',
        ),
        1415 => array(
            'Id' => 1415,
            'Label' => 'Podcasts|Education|K-12',
        ),
        1416 => array(
            'Id' => 1416,
            'Label' => 'Podcasts|Education|Higher Education',
        ),
        1417 => array(
            'Id' => 1417,
            'Label' => 'Podcasts|Health|Fitness & Nutrition',
        ),
        1420 => array(
            'Id' => 1420,
            'Label' => 'Podcasts|Health|Self-Help',
        ),
        1421 => array(
            'Id' => 1421,
            'Label' => 'Podcasts|Health|Sexuality',
        ),
        1438 => array(
            'Id' => 1438,
            'Label' => 'Podcasts|Religion & Spirituality|Buddhism',
        ),
        1439 => array(
            'Id' => 1439,
            'Label' => 'Podcasts|Religion & Spirituality|Christianity',
        ),
        1440 => array(
            'Id' => 1440,
            'Label' => 'Podcasts|Religion & Spirituality|Islam',
        ),
        1441 => array(
            'Id' => 1441,
            'Label' => 'Podcasts|Religion & Spirituality|Judaism',
        ),
        1443 => array(
            'Id' => 1443,
            'Label' => 'Podcasts|Society & Culture|Philosophy',
        ),
        1444 => array(
            'Id' => 1444,
            'Label' => 'Podcasts|Religion & Spirituality|Spirituality',
        ),
        1446 => array(
            'Id' => 1446,
            'Label' => 'Podcasts|Technology|Gadgets',
        ),
        1448 => array(
            'Id' => 1448,
            'Label' => 'Podcasts|Technology|Tech News',
        ),
        1450 => array(
            'Id' => 1450,
            'Label' => 'Podcasts|Technology|Podcasting',
        ),
        1454 => array(
            'Id' => 1454,
            'Label' => 'Podcasts|Games & Hobbies|Automotive',
        ),
        1455 => array(
            'Id' => 1455,
            'Label' => 'Podcasts|Games & Hobbies|Aviation',
        ),
        1456 => array(
            'Id' => 1456,
            'Label' => 'Podcasts|Sports & Recreation|Outdoor',
        ),
        1459 => array(
            'Id' => 1459,
            'Label' => 'Podcasts|Arts|Fashion & Beauty',
        ),
        1460 => array(
            'Id' => 1460,
            'Label' => 'Podcasts|Games & Hobbies|Hobbies',
        ),
        1461 => array(
            'Id' => 1461,
            'Label' => 'Podcasts|Games & Hobbies|Other Games',
        ),
        1462 => array(
            'Id' => 1462,
            'Label' => 'Podcasts|Society & Culture|History',
        ),
        1463 => array(
            'Id' => 1463,
            'Label' => 'Podcasts|Religion & Spirituality|Hinduism',
        ),
        1464 => array(
            'Id' => 1464,
            'Label' => 'Podcasts|Religion & Spirituality|Other',
        ),
        1465 => array(
            'Id' => 1465,
            'Label' => 'Podcasts|Sports & Recreation|Professional',
        ),
        1466 => array(
            'Id' => 1466,
            'Label' => 'Podcasts|Sports & Recreation|College & High School',
        ),
        1467 => array(
            'Id' => 1467,
            'Label' => 'Podcasts|Sports & Recreation|Amateur',
        ),
        1468 => array(
            'Id' => 1468,
            'Label' => 'Podcasts|Education|Educational Technology',
        ),
        1469 => array(
            'Id' => 1469,
            'Label' => 'Podcasts|Education|Language Courses',
        ),
        1470 => array(
            'Id' => 1470,
            'Label' => 'Podcasts|Education|Training',
        ),
        1471 => array(
            'Id' => 1471,
            'Label' => 'Podcasts|Business|Business News',
        ),
        1472 => array(
            'Id' => 1472,
            'Label' => 'Podcasts|Business|Shopping',
        ),
        1473 => array(
            'Id' => 1473,
            'Label' => 'Podcasts|Government & Organizations|National',
        ),
        1474 => array(
            'Id' => 1474,
            'Label' => 'Podcasts|Government & Organizations|Regional',
        ),
        1475 => array(
            'Id' => 1475,
            'Label' => 'Podcasts|Government & Organizations|Local',
        ),
        1476 => array(
            'Id' => 1476,
            'Label' => 'Podcasts|Government & Organizations|Non-Profit',
        ),
        1477 => array(
            'Id' => 1477,
            'Label' => 'Podcasts|Science & Medicine|Natural Sciences',
        ),
        1478 => array(
            'Id' => 1478,
            'Label' => 'Podcasts|Science & Medicine|Medicine',
        ),
        1479 => array(
            'Id' => 1479,
            'Label' => 'Podcasts|Science & Medicine|Social Sciences',
        ),
        1480 => array(
            'Id' => 1480,
            'Label' => 'Podcasts|Technology|Software How-To',
        ),
        1481 => array(
            'Id' => 1481,
            'Label' => 'Podcasts|Health|Alternative Health',
        ),
        1602 => array(
            'Id' => 1602,
            'Label' => 'Music Videos|Blues',
        ),
        1603 => array(
            'Id' => 1603,
            'Label' => 'Music Videos|Comedy',
        ),
        1604 => array(
            'Id' => 1604,
            'Label' => 'Music Videos|Children\'s Music',
        ),
        1605 => array(
            'Id' => 1605,
            'Label' => 'Music Videos|Classical',
        ),
        1606 => array(
            'Id' => 1606,
            'Label' => 'Music Videos|Country',
        ),
        1607 => array(
            'Id' => 1607,
            'Label' => 'Music Videos|Electronic',
        ),
        1608 => array(
            'Id' => 1608,
            'Label' => 'Music Videos|Holiday',
        ),
        1609 => array(
            'Id' => 1609,
            'Label' => 'Music Videos|Classical|Opera',
        ),
        1610 => array(
            'Id' => 1610,
            'Label' => 'Music Videos|Singer/Songwriter',
        ),
        1611 => array(
            'Id' => 1611,
            'Label' => 'Music Videos|Jazz',
        ),
        1612 => array(
            'Id' => 1612,
            'Label' => 'Music Videos|Latin',
        ),
        1613 => array(
            'Id' => 1613,
            'Label' => 'Music Videos|New Age',
        ),
        1614 => array(
            'Id' => 1614,
            'Label' => 'Music Videos|Pop',
        ),
        1615 => array(
            'Id' => 1615,
            'Label' => 'Music Videos|R&B/Soul',
        ),
        1616 => array(
            'Id' => 1616,
            'Label' => 'Music Videos|Soundtrack',
        ),
        1617 => array(
            'Id' => 1617,
            'Label' => 'Music Videos|Dance',
        ),
        1618 => array(
            'Id' => 1618,
            'Label' => 'Music Videos|Hip-Hop/Rap',
        ),
        1619 => array(
            'Id' => 1619,
            'Label' => 'Music Videos|World',
        ),
        1620 => array(
            'Id' => 1620,
            'Label' => 'Music Videos|Alternative',
        ),
        1621 => array(
            'Id' => 1621,
            'Label' => 'Music Videos|Rock',
        ),
        1622 => array(
            'Id' => 1622,
            'Label' => 'Music Videos|Christian & Gospel',
        ),
        1623 => array(
            'Id' => 1623,
            'Label' => 'Music Videos|Vocal',
        ),
        1624 => array(
            'Id' => 1624,
            'Label' => 'Music Videos|Reggae',
        ),
        1625 => array(
            'Id' => 1625,
            'Label' => 'Music Videos|Easy Listening',
        ),
        1626 => array(
            'Id' => 1626,
            'Label' => 'Music Videos|Podcasts',
        ),
        1627 => array(
            'Id' => 1627,
            'Label' => 'Music Videos|J-Pop',
        ),
        1628 => array(
            'Id' => 1628,
            'Label' => 'Music Videos|Enka',
        ),
        1629 => array(
            'Id' => 1629,
            'Label' => 'Music Videos|Anime',
        ),
        1630 => array(
            'Id' => 1630,
            'Label' => 'Music Videos|Kayokyoku',
        ),
        1631 => array(
            'Id' => 1631,
            'Label' => 'Music Videos|Disney',
        ),
        1632 => array(
            'Id' => 1632,
            'Label' => 'Music Videos|French Pop',
        ),
        1633 => array(
            'Id' => 1633,
            'Label' => 'Music Videos|German Pop',
        ),
        1634 => array(
            'Id' => 1634,
            'Label' => 'Music Videos|German Folk',
        ),
        1635 => array(
            'Id' => 1635,
            'Label' => 'Music Videos|Alternative|Chinese Alt',
        ),
        1636 => array(
            'Id' => 1636,
            'Label' => 'Music Videos|Alternative|Korean Indie',
        ),
        1637 => array(
            'Id' => 1637,
            'Label' => 'Music Videos|Chinese',
        ),
        1638 => array(
            'Id' => 1638,
            'Label' => 'Music Videos|Chinese|Chinese Classical',
        ),
        1639 => array(
            'Id' => 1639,
            'Label' => 'Music Videos|Chinese|Chinese Flute',
        ),
        1640 => array(
            'Id' => 1640,
            'Label' => 'Music Videos|Chinese|Chinese Opera',
        ),
        1641 => array(
            'Id' => 1641,
            'Label' => 'Music Videos|Chinese|Chinese Orchestral',
        ),
        1642 => array(
            'Id' => 1642,
            'Label' => 'Music Videos|Chinese|Chinese Regional Folk',
        ),
        1643 => array(
            'Id' => 1643,
            'Label' => 'Music Videos|Chinese|Chinese Strings',
        ),
        1644 => array(
            'Id' => 1644,
            'Label' => 'Music Videos|Chinese|Taiwanese Folk',
        ),
        1645 => array(
            'Id' => 1645,
            'Label' => 'Music Videos|Chinese|Tibetan Native Music',
        ),
        1646 => array(
            'Id' => 1646,
            'Label' => 'Music Videos|Hip-Hop/Rap|Chinese Hip-Hop',
        ),
        1647 => array(
            'Id' => 1647,
            'Label' => 'Music Videos|Hip-Hop/Rap|Korean Hip-Hop',
        ),
        1648 => array(
            'Id' => 1648,
            'Label' => 'Music Videos|Korean',
        ),
        1649 => array(
            'Id' => 1649,
            'Label' => 'Music Videos|Korean|Korean Classical',
        ),
        1650 => array(
            'Id' => 1650,
            'Label' => 'Music Videos|Korean|Korean Trad Song',
        ),
        1651 => array(
            'Id' => 1651,
            'Label' => 'Music Videos|Korean|Korean Trad Instrumental',
        ),
        1652 => array(
            'Id' => 1652,
            'Label' => 'Music Videos|Korean|Korean Trad Theater',
        ),
        1653 => array(
            'Id' => 1653,
            'Label' => 'Music Videos|Rock|Chinese Rock',
        ),
        1654 => array(
            'Id' => 1654,
            'Label' => 'Music Videos|Rock|Korean Rock',
        ),
        1655 => array(
            'Id' => 1655,
            'Label' => 'Music Videos|Pop|C-Pop',
        ),
        1656 => array(
            'Id' => 1656,
            'Label' => 'Music Videos|Pop|Cantopop/HK-Pop',
        ),
        1657 => array(
            'Id' => 1657,
            'Label' => 'Music Videos|Pop|Korean Folk-Pop',
        ),
        1658 => array(
            'Id' => 1658,
            'Label' => 'Music Videos|Pop|Mandopop',
        ),
        1659 => array(
            'Id' => 1659,
            'Label' => 'Music Videos|Pop|Tai-Pop',
        ),
        1660 => array(
            'Id' => 1660,
            'Label' => 'Music Videos|Pop|Malaysian Pop',
        ),
        1661 => array(
            'Id' => 1661,
            'Label' => 'Music Videos|Pop|Pinoy Pop',
        ),
        1662 => array(
            'Id' => 1662,
            'Label' => 'Music Videos|Pop|Original Pilipino Music',
        ),
        1663 => array(
            'Id' => 1663,
            'Label' => 'Music Videos|Pop|Manilla Sound',
        ),
        1664 => array(
            'Id' => 1664,
            'Label' => 'Music Videos|Pop|Indo Pop',
        ),
        1665 => array(
            'Id' => 1665,
            'Label' => 'Music Videos|Pop|Thai Pop',
        ),
        1666 => array(
            'Id' => 1666,
            'Label' => 'Music Videos|Vocal|Trot',
        ),
        1671 => array(
            'Id' => 1671,
            'Label' => 'Music Videos|Brazilian',
        ),
        1672 => array(
            'Id' => 1672,
            'Label' => 'Music Videos|Brazilian|Axe',
        ),
        1673 => array(
            'Id' => 1673,
            'Label' => 'Music Videos|Brazilian|Baile Funk',
        ),
        1674 => array(
            'Id' => 1674,
            'Label' => 'Music Videos|Brazilian|Bossa Nova',
        ),
        1675 => array(
            'Id' => 1675,
            'Label' => 'Music Videos|Brazilian|Choro',
        ),
        1676 => array(
            'Id' => 1676,
            'Label' => 'Music Videos|Brazilian|Forro',
        ),
        1677 => array(
            'Id' => 1677,
            'Label' => 'Music Videos|Brazilian|Frevo',
        ),
        1678 => array(
            'Id' => 1678,
            'Label' => 'Music Videos|Brazilian|MPB',
        ),
        1679 => array(
            'Id' => 1679,
            'Label' => 'Music Videos|Brazilian|Pagode',
        ),
        1680 => array(
            'Id' => 1680,
            'Label' => 'Music Videos|Brazilian|Samba',
        ),
        1681 => array(
            'Id' => 1681,
            'Label' => 'Music Videos|Brazilian|Sertanejo',
        ),
        1682 => array(
            'Id' => 1682,
            'Label' => 'Music Videos|Classical|High Classical',
        ),
        1683 => array(
            'Id' => 1683,
            'Label' => 'Music Videos|Fitness & Workout',
        ),
        1684 => array(
            'Id' => 1684,
            'Label' => 'Music Videos|Instrumental',
        ),
        1685 => array(
            'Id' => 1685,
            'Label' => 'Music Videos|Jazz|Big Band',
        ),
        1686 => array(
            'Id' => 1686,
            'Label' => 'Music Videos|Pop|K-Pop',
        ),
        1687 => array(
            'Id' => 1687,
            'Label' => 'Music Videos|Karaoke',
        ),
        1688 => array(
            'Id' => 1688,
            'Label' => 'Music Videos|Rock|Heavy Metal',
        ),
        1689 => array(
            'Id' => 1689,
            'Label' => 'Music Videos|Spoken Word',
        ),
        1690 => array(
            'Id' => 1690,
            'Label' => 'Music Videos|Indian',
        ),
        1691 => array(
            'Id' => 1691,
            'Label' => 'Music Videos|Indian|Bollywood',
        ),
        1692 => array(
            'Id' => 1692,
            'Label' => 'Music Videos|Indian|Tamil',
        ),
        1693 => array(
            'Id' => 1693,
            'Label' => 'Music Videos|Indian|Telugu',
        ),
        1694 => array(
            'Id' => 1694,
            'Label' => 'Music Videos|Indian|Regional Indian',
        ),
        1695 => array(
            'Id' => 1695,
            'Label' => 'Music Videos|Indian|Devotional & Spiritual',
        ),
        1696 => array(
            'Id' => 1696,
            'Label' => 'Music Videos|Indian|Sufi',
        ),
        1697 => array(
            'Id' => 1697,
            'Label' => 'Music Videos|Indian|Indian Classical',
        ),
        1698 => array(
            'Id' => 1698,
            'Label' => 'Music Videos|World|Russian Chanson',
        ),
        1699 => array(
            'Id' => 1699,
            'Label' => 'Music Videos|World|Dini',
        ),
        1700 => array(
            'Id' => 1700,
            'Label' => 'Music Videos|World|Halk',
        ),
        1701 => array(
            'Id' => 1701,
            'Label' => 'Music Videos|World|Sanat',
        ),
        1702 => array(
            'Id' => 1702,
            'Label' => 'Music Videos|World|Dangdut',
        ),
        1703 => array(
            'Id' => 1703,
            'Label' => 'Music Videos|World|Indonesian Religious',
        ),
        1704 => array(
            'Id' => 1704,
            'Label' => 'Music Videos|Indian|Indian Pop',
        ),
        1705 => array(
            'Id' => 1705,
            'Label' => 'Music Videos|World|Calypso',
        ),
        1706 => array(
            'Id' => 1706,
            'Label' => 'Music Videos|World|Soca',
        ),
        1707 => array(
            'Id' => 1707,
            'Label' => 'Music Videos|Indian|Ghazals',
        ),
        1708 => array(
            'Id' => 1708,
            'Label' => 'Music Videos|Indian|Indian Folk',
        ),
        1709 => array(
            'Id' => 1709,
            'Label' => 'Music Videos|World|Arabesque',
        ),
        1710 => array(
            'Id' => 1710,
            'Label' => 'Music Videos|World|Afrikaans',
        ),
        1711 => array(
            'Id' => 1711,
            'Label' => 'Music Videos|World|Farsi',
        ),
        1712 => array(
            'Id' => 1712,
            'Label' => 'Music Videos|World|Israeli',
        ),
        1713 => array(
            'Id' => 1713,
            'Label' => 'Music Videos|Arabic',
        ),
        1714 => array(
            'Id' => 1714,
            'Label' => 'Music Videos|Arabic|Khaleeji',
        ),
        1715 => array(
            'Id' => 1715,
            'Label' => 'Music Videos|Arabic|North African',
        ),
        1716 => array(
            'Id' => 1716,
            'Label' => 'Music Videos|Arabic|Arabic Pop',
        ),
        1717 => array(
            'Id' => 1717,
            'Label' => 'Music Videos|Arabic|Islamic',
        ),
        1718 => array(
            'Id' => 1718,
            'Label' => 'Music Videos|Soundtrack|Sound Effects',
        ),
        1719 => array(
            'Id' => 1719,
            'Label' => 'Music Videos|Folk',
        ),
        1720 => array(
            'Id' => 1720,
            'Label' => 'Music Videos|Orchestral',
        ),
        1721 => array(
            'Id' => 1721,
            'Label' => 'Music Videos|Marching',
        ),
        1723 => array(
            'Id' => 1723,
            'Label' => 'Music Videos|Pop|Oldies',
        ),
        1724 => array(
            'Id' => 1724,
            'Label' => 'Music Videos|Country|Thai Country',
        ),
        1725 => array(
            'Id' => 1725,
            'Label' => 'Music Videos|World|Flamenco',
        ),
        1726 => array(
            'Id' => 1726,
            'Label' => 'Music Videos|World|Tango',
        ),
        1727 => array(
            'Id' => 1727,
            'Label' => 'Music Videos|World|Fado',
        ),
        1728 => array(
            'Id' => 1728,
            'Label' => 'Music Videos|World|Iberia',
        ),
        1729 => array(
            'Id' => 1729,
            'Label' => 'Music Videos|World|Russian',
        ),
        1730 => array(
            'Id' => 1730,
            'Label' => 'Music Videos|World|Turkish',
        ),
        1731 => array(
            'Id' => 1731,
            'Label' => 'Music Videos|Alternative|College Rock',
        ),
        1732 => array(
            'Id' => 1732,
            'Label' => 'Music Videos|Alternative|Goth Rock',
        ),
        1733 => array(
            'Id' => 1733,
            'Label' => 'Music Videos|Alternative|Grunge',
        ),
        1734 => array(
            'Id' => 1734,
            'Label' => 'Music Videos|Alternative|Indie Rock',
        ),
        1735 => array(
            'Id' => 1735,
            'Label' => 'Music Videos|Alternative|New Wave',
        ),
        1736 => array(
            'Id' => 1736,
            'Label' => 'Music Videos|Alternative|Punk',
        ),
        1737 => array(
            'Id' => 1737,
            'Label' => 'Music Videos|Blues|Acoustic Blues',
        ),
        1738 => array(
            'Id' => 1738,
            'Label' => 'Music Videos|Blues|Chicago Blues',
        ),
        1739 => array(
            'Id' => 1739,
            'Label' => 'Music Videos|Blues|Classic Blues',
        ),
        1740 => array(
            'Id' => 1740,
            'Label' => 'Music Videos|Blues|Contemporary Blues',
        ),
        1741 => array(
            'Id' => 1741,
            'Label' => 'Music Videos|Blues|Country Blues',
        ),
        1742 => array(
            'Id' => 1742,
            'Label' => 'Music Videos|Blues|Delta Blues',
        ),
        1743 => array(
            'Id' => 1743,
            'Label' => 'Music Videos|Blues|Electric Blues',
        ),
        1744 => array(
            'Id' => 1744,
            'Label' => 'Music Videos|Children\'s Music|Lullabies',
        ),
        1745 => array(
            'Id' => 1745,
            'Label' => 'Music Videos|Children\'s Music|Sing-Along',
        ),
        1746 => array(
            'Id' => 1746,
            'Label' => 'Music Videos|Children\'s Music|Stories',
        ),
        1747 => array(
            'Id' => 1747,
            'Label' => 'Music Videos|Christian & Gospel|CCM',
        ),
        1748 => array(
            'Id' => 1748,
            'Label' => 'Music Videos|Christian & Gospel|Christian Metal',
        ),
        1749 => array(
            'Id' => 1749,
            'Label' => 'Music Videos|Christian & Gospel|Christian Pop',
        ),
        1750 => array(
            'Id' => 1750,
            'Label' => 'Music Videos|Christian & Gospel|Christian Rap',
        ),
        1751 => array(
            'Id' => 1751,
            'Label' => 'Music Videos|Christian & Gospel|Christian Rock',
        ),
        1752 => array(
            'Id' => 1752,
            'Label' => 'Music Videos|Christian & Gospel|Classic Christian',
        ),
        1753 => array(
            'Id' => 1753,
            'Label' => 'Music Videos|Christian & Gospel|Contemporary Gospel',
        ),
        1754 => array(
            'Id' => 1754,
            'Label' => 'Music Videos|Christian & Gospel|Gospel',
        ),
        1755 => array(
            'Id' => 1755,
            'Label' => 'Music Videos|Christian & Gospel|Praise & Worship',
        ),
        1756 => array(
            'Id' => 1756,
            'Label' => 'Music Videos|Christian & Gospel|Southern Gospel',
        ),
        1757 => array(
            'Id' => 1757,
            'Label' => 'Music Videos|Christian & Gospel|Traditional Gospel',
        ),
        1758 => array(
            'Id' => 1758,
            'Label' => 'Music Videos|Classical|Avant-Garde',
        ),
        1759 => array(
            'Id' => 1759,
            'Label' => 'Music Videos|Classical|Baroque Era',
        ),
        1760 => array(
            'Id' => 1760,
            'Label' => 'Music Videos|Classical|Chamber Music',
        ),
        1761 => array(
            'Id' => 1761,
            'Label' => 'Music Videos|Classical|Chant',
        ),
        1762 => array(
            'Id' => 1762,
            'Label' => 'Music Videos|Classical|Choral',
        ),
        1763 => array(
            'Id' => 1763,
            'Label' => 'Music Videos|Classical|Classical Crossover',
        ),
        1764 => array(
            'Id' => 1764,
            'Label' => 'Music Videos|Classical|Early Music',
        ),
        1765 => array(
            'Id' => 1765,
            'Label' => 'Music Videos|Classical|Impressionist',
        ),
        1766 => array(
            'Id' => 1766,
            'Label' => 'Music Videos|Classical|Medieval Era',
        ),
        1767 => array(
            'Id' => 1767,
            'Label' => 'Music Videos|Classical|Minimalism',
        ),
        1768 => array(
            'Id' => 1768,
            'Label' => 'Music Videos|Classical|Modern Era',
        ),
        1769 => array(
            'Id' => 1769,
            'Label' => 'Music Videos|Classical|Orchestral',
        ),
        1770 => array(
            'Id' => 1770,
            'Label' => 'Music Videos|Classical|Renaissance',
        ),
        1771 => array(
            'Id' => 1771,
            'Label' => 'Music Videos|Classical|Romantic Era',
        ),
        1772 => array(
            'Id' => 1772,
            'Label' => 'Music Videos|Classical|Wedding Music',
        ),
        1773 => array(
            'Id' => 1773,
            'Label' => 'Music Videos|Comedy|Novelty',
        ),
        1774 => array(
            'Id' => 1774,
            'Label' => 'Music Videos|Comedy|Standup Comedy',
        ),
        1775 => array(
            'Id' => 1775,
            'Label' => 'Music Videos|Country|Alternative Country',
        ),
        1776 => array(
            'Id' => 1776,
            'Label' => 'Music Videos|Country|Americana',
        ),
        1777 => array(
            'Id' => 1777,
            'Label' => 'Music Videos|Country|Bluegrass',
        ),
        1778 => array(
            'Id' => 1778,
            'Label' => 'Music Videos|Country|Contemporary Bluegrass',
        ),
        1779 => array(
            'Id' => 1779,
            'Label' => 'Music Videos|Country|Contemporary Country',
        ),
        1780 => array(
            'Id' => 1780,
            'Label' => 'Music Videos|Country|Country Gospel',
        ),
        1781 => array(
            'Id' => 1781,
            'Label' => 'Music Videos|Country|Honky Tonk',
        ),
        1782 => array(
            'Id' => 1782,
            'Label' => 'Music Videos|Country|Outlaw Country',
        ),
        1783 => array(
            'Id' => 1783,
            'Label' => 'Music Videos|Country|Traditional Bluegrass',
        ),
        1784 => array(
            'Id' => 1784,
            'Label' => 'Music Videos|Country|Traditional Country',
        ),
        1785 => array(
            'Id' => 1785,
            'Label' => 'Music Videos|Country|Urban Cowboy',
        ),
        1786 => array(
            'Id' => 1786,
            'Label' => 'Music Videos|Dance|Breakbeat',
        ),
        1787 => array(
            'Id' => 1787,
            'Label' => 'Music Videos|Dance|Exercise',
        ),
        1788 => array(
            'Id' => 1788,
            'Label' => 'Music Videos|Dance|Garage',
        ),
        1789 => array(
            'Id' => 1789,
            'Label' => 'Music Videos|Dance|Hardcore',
        ),
        1790 => array(
            'Id' => 1790,
            'Label' => 'Music Videos|Dance|House',
        ),
        1791 => array(
            'Id' => 1791,
            'Label' => 'Music Videos|Dance|Jungle/Drum\'n\'bass',
        ),
        1792 => array(
            'Id' => 1792,
            'Label' => 'Music Videos|Dance|Techno',
        ),
        1793 => array(
            'Id' => 1793,
            'Label' => 'Music Videos|Dance|Trance',
        ),
        1794 => array(
            'Id' => 1794,
            'Label' => 'Music Videos|Easy Listening|Lounge',
        ),
        1795 => array(
            'Id' => 1795,
            'Label' => 'Music Videos|Easy Listening|Swing',
        ),
        1796 => array(
            'Id' => 1796,
            'Label' => 'Music Videos|Electronic|Ambient',
        ),
        1797 => array(
            'Id' => 1797,
            'Label' => 'Music Videos|Electronic|Downtempo',
        ),
        1798 => array(
            'Id' => 1798,
            'Label' => 'Music Videos|Electronic|Electronica',
        ),
        1799 => array(
            'Id' => 1799,
            'Label' => 'Music Videos|Electronic|IDM/Experimental',
        ),
        1800 => array(
            'Id' => 1800,
            'Label' => 'Music Videos|Electronic|Industrial',
        ),
        1801 => array(
            'Id' => 1801,
            'Label' => 'Music Videos|Hip-Hop/Rap|Alternative Rap',
        ),
        1802 => array(
            'Id' => 1802,
            'Label' => 'Music Videos|Hip-Hop/Rap|Dirty South',
        ),
        1803 => array(
            'Id' => 1803,
            'Label' => 'Music Videos|Hip-Hop/Rap|East Coast Rap',
        ),
        1804 => array(
            'Id' => 1804,
            'Label' => 'Music Videos|Hip-Hop/Rap|Gangsta Rap',
        ),
        1805 => array(
            'Id' => 1805,
            'Label' => 'Music Videos|Hip-Hop/Rap|Hardcore Rap',
        ),
        1806 => array(
            'Id' => 1806,
            'Label' => 'Music Videos|Hip-Hop/Rap|Hip-Hop',
        ),
        1807 => array(
            'Id' => 1807,
            'Label' => 'Music Videos|Hip-Hop/Rap|Latin Rap',
        ),
        1808 => array(
            'Id' => 1808,
            'Label' => 'Music Videos|Hip-Hop/Rap|Old School Rap',
        ),
        1809 => array(
            'Id' => 1809,
            'Label' => 'Music Videos|Hip-Hop/Rap|Rap',
        ),
        1810 => array(
            'Id' => 1810,
            'Label' => 'Music Videos|Hip-Hop/Rap|Underground Rap',
        ),
        1811 => array(
            'Id' => 1811,
            'Label' => 'Music Videos|Hip-Hop/Rap|West Coast Rap',
        ),
        1812 => array(
            'Id' => 1812,
            'Label' => 'Music Videos|Holiday|Chanukah',
        ),
        1813 => array(
            'Id' => 1813,
            'Label' => 'Music Videos|Holiday|Christmas',
        ),
        1814 => array(
            'Id' => 1814,
            'Label' => 'Music Videos|Holiday|Christmas: Children\'s',
        ),
        1815 => array(
            'Id' => 1815,
            'Label' => 'Music Videos|Holiday|Christmas: Classic',
        ),
        1816 => array(
            'Id' => 1816,
            'Label' => 'Music Videos|Holiday|Christmas: Classical',
        ),
        1817 => array(
            'Id' => 1817,
            'Label' => 'Music Videos|Holiday|Christmas: Jazz',
        ),
        1818 => array(
            'Id' => 1818,
            'Label' => 'Music Videos|Holiday|Christmas: Modern',
        ),
        1819 => array(
            'Id' => 1819,
            'Label' => 'Music Videos|Holiday|Christmas: Pop',
        ),
        1820 => array(
            'Id' => 1820,
            'Label' => 'Music Videos|Holiday|Christmas: R&B',
        ),
        1821 => array(
            'Id' => 1821,
            'Label' => 'Music Videos|Holiday|Christmas: Religious',
        ),
        1822 => array(
            'Id' => 1822,
            'Label' => 'Music Videos|Holiday|Christmas: Rock',
        ),
        1823 => array(
            'Id' => 1823,
            'Label' => 'Music Videos|Holiday|Easter',
        ),
        1824 => array(
            'Id' => 1824,
            'Label' => 'Music Videos|Holiday|Halloween',
        ),
        1825 => array(
            'Id' => 1825,
            'Label' => 'Music Videos|Holiday|Thanksgiving',
        ),
        1826 => array(
            'Id' => 1826,
            'Label' => 'Music Videos|Jazz|Avant-Garde Jazz',
        ),
        1828 => array(
            'Id' => 1828,
            'Label' => 'Music Videos|Jazz|Bop',
        ),
        1829 => array(
            'Id' => 1829,
            'Label' => 'Music Videos|Jazz|Contemporary Jazz',
        ),
        1830 => array(
            'Id' => 1830,
            'Label' => 'Music Videos|Jazz|Cool',
        ),
        1831 => array(
            'Id' => 1831,
            'Label' => 'Music Videos|Jazz|Crossover Jazz',
        ),
        1832 => array(
            'Id' => 1832,
            'Label' => 'Music Videos|Jazz|Dixieland',
        ),
        1833 => array(
            'Id' => 1833,
            'Label' => 'Music Videos|Jazz|Fusion',
        ),
        1834 => array(
            'Id' => 1834,
            'Label' => 'Music Videos|Jazz|Hard Bop',
        ),
        1835 => array(
            'Id' => 1835,
            'Label' => 'Music Videos|Jazz|Latin Jazz',
        ),
        1836 => array(
            'Id' => 1836,
            'Label' => 'Music Videos|Jazz|Mainstream Jazz',
        ),
        1837 => array(
            'Id' => 1837,
            'Label' => 'Music Videos|Jazz|Ragtime',
        ),
        1838 => array(
            'Id' => 1838,
            'Label' => 'Music Videos|Jazz|Smooth Jazz',
        ),
        1839 => array(
            'Id' => 1839,
            'Label' => 'Music Videos|Jazz|Trad Jazz',
        ),
        1840 => array(
            'Id' => 1840,
            'Label' => 'Music Videos|Latin|Alternative & Rock in Spanish',
        ),
        1841 => array(
            'Id' => 1841,
            'Label' => 'Music Videos|Latin|Baladas y Boleros',
        ),
        1842 => array(
            'Id' => 1842,
            'Label' => 'Music Videos|Latin|Contemporary Latin',
        ),
        1843 => array(
            'Id' => 1843,
            'Label' => 'Music Videos|Latin|Latin Jazz',
        ),
        1844 => array(
            'Id' => 1844,
            'Label' => 'Music Videos|Latin|Latin Urban',
        ),
        1845 => array(
            'Id' => 1845,
            'Label' => 'Music Videos|Latin|Pop in Spanish',
        ),
        1846 => array(
            'Id' => 1846,
            'Label' => 'Music Videos|Latin|Raices',
        ),
        1847 => array(
            'Id' => 1847,
            'Label' => 'Music Videos|Latin|Regional Mexicano',
        ),
        1848 => array(
            'Id' => 1848,
            'Label' => 'Music Videos|Latin|Salsa y Tropical',
        ),
        1849 => array(
            'Id' => 1849,
            'Label' => 'Music Videos|New Age|Healing',
        ),
        1850 => array(
            'Id' => 1850,
            'Label' => 'Music Videos|New Age|Meditation',
        ),
        1851 => array(
            'Id' => 1851,
            'Label' => 'Music Videos|New Age|Nature',
        ),
        1852 => array(
            'Id' => 1852,
            'Label' => 'Music Videos|New Age|Relaxation',
        ),
        1853 => array(
            'Id' => 1853,
            'Label' => 'Music Videos|New Age|Travel',
        ),
        1854 => array(
            'Id' => 1854,
            'Label' => 'Music Videos|Pop|Adult Contemporary',
        ),
        1855 => array(
            'Id' => 1855,
            'Label' => 'Music Videos|Pop|Britpop',
        ),
        1856 => array(
            'Id' => 1856,
            'Label' => 'Music Videos|Pop|Pop/Rock',
        ),
        1857 => array(
            'Id' => 1857,
            'Label' => 'Music Videos|Pop|Soft Rock',
        ),
        1858 => array(
            'Id' => 1858,
            'Label' => 'Music Videos|Pop|Teen Pop',
        ),
        1859 => array(
            'Id' => 1859,
            'Label' => 'Music Videos|R&B/Soul|Contemporary R&B',
        ),
        1860 => array(
            'Id' => 1860,
            'Label' => 'Music Videos|R&B/Soul|Disco',
        ),
        1861 => array(
            'Id' => 1861,
            'Label' => 'Music Videos|R&B/Soul|Doo Wop',
        ),
        1862 => array(
            'Id' => 1862,
            'Label' => 'Music Videos|R&B/Soul|Funk',
        ),
        1863 => array(
            'Id' => 1863,
            'Label' => 'Music Videos|R&B/Soul|Motown',
        ),
        1864 => array(
            'Id' => 1864,
            'Label' => 'Music Videos|R&B/Soul|Neo-Soul',
        ),
        1865 => array(
            'Id' => 1865,
            'Label' => 'Music Videos|R&B/Soul|Soul',
        ),
        1866 => array(
            'Id' => 1866,
            'Label' => 'Music Videos|Reggae|Modern Dancehall',
        ),
        1867 => array(
            'Id' => 1867,
            'Label' => 'Music Videos|Reggae|Dub',
        ),
        1868 => array(
            'Id' => 1868,
            'Label' => 'Music Videos|Reggae|Roots Reggae',
        ),
        1869 => array(
            'Id' => 1869,
            'Label' => 'Music Videos|Reggae|Ska',
        ),
        1870 => array(
            'Id' => 1870,
            'Label' => 'Music Videos|Rock|Adult Alternative',
        ),
        1871 => array(
            'Id' => 1871,
            'Label' => 'Music Videos|Rock|American Trad Rock',
        ),
        1872 => array(
            'Id' => 1872,
            'Label' => 'Music Videos|Rock|Arena Rock',
        ),
        1873 => array(
            'Id' => 1873,
            'Label' => 'Music Videos|Rock|Blues-Rock',
        ),
        1874 => array(
            'Id' => 1874,
            'Label' => 'Music Videos|Rock|British Invasion',
        ),
        1875 => array(
            'Id' => 1875,
            'Label' => 'Music Videos|Rock|Death Metal/Black Metal',
        ),
        1876 => array(
            'Id' => 1876,
            'Label' => 'Music Videos|Rock|Glam Rock',
        ),
        1877 => array(
            'Id' => 1877,
            'Label' => 'Music Videos|Rock|Hair Metal',
        ),
        1878 => array(
            'Id' => 1878,
            'Label' => 'Music Videos|Rock|Hard Rock',
        ),
        1879 => array(
            'Id' => 1879,
            'Label' => 'Music Videos|Rock|Jam Bands',
        ),
        1880 => array(
            'Id' => 1880,
            'Label' => 'Music Videos|Rock|Prog-Rock/Art Rock',
        ),
        1881 => array(
            'Id' => 1881,
            'Label' => 'Music Videos|Rock|Psychedelic',
        ),
        1882 => array(
            'Id' => 1882,
            'Label' => 'Music Videos|Rock|Rock & Roll',
        ),
        1883 => array(
            'Id' => 1883,
            'Label' => 'Music Videos|Rock|Rockabilly',
        ),
        1884 => array(
            'Id' => 1884,
            'Label' => 'Music Videos|Rock|Roots Rock',
        ),
        1885 => array(
            'Id' => 1885,
            'Label' => 'Music Videos|Rock|Singer/Songwriter',
        ),
        1886 => array(
            'Id' => 1886,
            'Label' => 'Music Videos|Rock|Southern Rock',
        ),
        1887 => array(
            'Id' => 1887,
            'Label' => 'Music Videos|Rock|Surf',
        ),
        1888 => array(
            'Id' => 1888,
            'Label' => 'Music Videos|Rock|Tex-Mex',
        ),
        1889 => array(
            'Id' => 1889,
            'Label' => 'Music Videos|Singer/Songwriter|Alternative Folk',
        ),
        1890 => array(
            'Id' => 1890,
            'Label' => 'Music Videos|Singer/Songwriter|Contemporary Folk',
        ),
        1891 => array(
            'Id' => 1891,
            'Label' => 'Music Videos|Singer/Songwriter|Contemporary Singer/Songwriter',
        ),
        1892 => array(
            'Id' => 1892,
            'Label' => 'Music Videos|Singer/Songwriter|Folk-Rock',
        ),
        1893 => array(
            'Id' => 1893,
            'Label' => 'Music Videos|Singer/Songwriter|New Acoustic',
        ),
        1894 => array(
            'Id' => 1894,
            'Label' => 'Music Videos|Singer/Songwriter|Traditional Folk',
        ),
        1895 => array(
            'Id' => 1895,
            'Label' => 'Music Videos|Soundtrack|Foreign Cinema',
        ),
        1896 => array(
            'Id' => 1896,
            'Label' => 'Music Videos|Soundtrack|Musicals',
        ),
        1897 => array(
            'Id' => 1897,
            'Label' => 'Music Videos|Soundtrack|Original Score',
        ),
        1898 => array(
            'Id' => 1898,
            'Label' => 'Music Videos|Soundtrack|Soundtrack',
        ),
        1899 => array(
            'Id' => 1899,
            'Label' => 'Music Videos|Soundtrack|TV Soundtrack',
        ),
        1900 => array(
            'Id' => 1900,
            'Label' => 'Music Videos|Vocal|Standards',
        ),
        1901 => array(
            'Id' => 1901,
            'Label' => 'Music Videos|Vocal|Traditional Pop',
        ),
        1902 => array(
            'Id' => 1902,
            'Label' => 'Music Videos|Jazz|Vocal Jazz',
        ),
        1903 => array(
            'Id' => 1903,
            'Label' => 'Music Videos|Vocal|Vocal Pop',
        ),
        1904 => array(
            'Id' => 1904,
            'Label' => 'Music Videos|World|Africa',
        ),
        1905 => array(
            'Id' => 1905,
            'Label' => 'Music Videos|World|Afro-Beat',
        ),
        1906 => array(
            'Id' => 1906,
            'Label' => 'Music Videos|World|Afro-Pop',
        ),
        1907 => array(
            'Id' => 1907,
            'Label' => 'Music Videos|World|Asia',
        ),
        1908 => array(
            'Id' => 1908,
            'Label' => 'Music Videos|World|Australia',
        ),
        1909 => array(
            'Id' => 1909,
            'Label' => 'Music Videos|World|Cajun',
        ),
        1910 => array(
            'Id' => 1910,
            'Label' => 'Music Videos|World|Caribbean',
        ),
        1911 => array(
            'Id' => 1911,
            'Label' => 'Music Videos|World|Celtic',
        ),
        1912 => array(
            'Id' => 1912,
            'Label' => 'Music Videos|World|Celtic Folk',
        ),
        1913 => array(
            'Id' => 1913,
            'Label' => 'Music Videos|World|Contemporary Celtic',
        ),
        1914 => array(
            'Id' => 1914,
            'Label' => 'Music Videos|World|Europe',
        ),
        1915 => array(
            'Id' => 1915,
            'Label' => 'Music Videos|World|France',
        ),
        1916 => array(
            'Id' => 1916,
            'Label' => 'Music Videos|World|Hawaii',
        ),
        1917 => array(
            'Id' => 1917,
            'Label' => 'Music Videos|World|Japan',
        ),
        1918 => array(
            'Id' => 1918,
            'Label' => 'Music Videos|World|Klezmer',
        ),
        1919 => array(
            'Id' => 1919,
            'Label' => 'Music Videos|World|North America',
        ),
        1920 => array(
            'Id' => 1920,
            'Label' => 'Music Videos|World|Polka',
        ),
        1921 => array(
            'Id' => 1921,
            'Label' => 'Music Videos|World|South Africa',
        ),
        1922 => array(
            'Id' => 1922,
            'Label' => 'Music Videos|World|South America',
        ),
        1923 => array(
            'Id' => 1923,
            'Label' => 'Music Videos|World|Traditional Celtic',
        ),
        1924 => array(
            'Id' => 1924,
            'Label' => 'Music Videos|World|Worldbeat',
        ),
        1925 => array(
            'Id' => 1925,
            'Label' => 'Music Videos|World|Zydeco',
        ),
        1926 => array(
            'Id' => 1926,
            'Label' => 'Music Videos|Christian & Gospel',
        ),
        1928 => array(
            'Id' => 1928,
            'Label' => 'Music Videos|Classical|Art Song',
        ),
        1929 => array(
            'Id' => 1929,
            'Label' => 'Music Videos|Classical|Brass & Woodwinds',
        ),
        1930 => array(
            'Id' => 1930,
            'Label' => 'Music Videos|Classical|Solo Instrumental',
        ),
        1931 => array(
            'Id' => 1931,
            'Label' => 'Music Videos|Classical|Contemporary Era',
        ),
        1932 => array(
            'Id' => 1932,
            'Label' => 'Music Videos|Classical|Oratorio',
        ),
        1933 => array(
            'Id' => 1933,
            'Label' => 'Music Videos|Classical|Cantata',
        ),
        1934 => array(
            'Id' => 1934,
            'Label' => 'Music Videos|Classical|Electronic',
        ),
        1935 => array(
            'Id' => 1935,
            'Label' => 'Music Videos|Classical|Sacred',
        ),
        1936 => array(
            'Id' => 1936,
            'Label' => 'Music Videos|Classical|Guitar',
        ),
        1938 => array(
            'Id' => 1938,
            'Label' => 'Music Videos|Classical|Violin',
        ),
        1939 => array(
            'Id' => 1939,
            'Label' => 'Music Videos|Classical|Cello',
        ),
        1940 => array(
            'Id' => 1940,
            'Label' => 'Music Videos|Classical|Percussion',
        ),
        1941 => array(
            'Id' => 1941,
            'Label' => 'Music Videos|Electronic|Dubstep',
        ),
        1942 => array(
            'Id' => 1942,
            'Label' => 'Music Videos|Electronic|Bass',
        ),
        1943 => array(
            'Id' => 1943,
            'Label' => 'Music Videos|Hip-Hop/Rap|UK Hip-Hop',
        ),
        1944 => array(
            'Id' => 1944,
            'Label' => 'Music Videos|Reggae|Lovers Rock',
        ),
        1945 => array(
            'Id' => 1945,
            'Label' => 'Music Videos|Alternative|EMO',
        ),
        1946 => array(
            'Id' => 1946,
            'Label' => 'Music Videos|Alternative|Pop Punk',
        ),
        1947 => array(
            'Id' => 1947,
            'Label' => 'Music Videos|Alternative|Indie Pop',
        ),
        1948 => array(
            'Id' => 1948,
            'Label' => 'Music Videos|New Age|Yoga',
        ),
        1949 => array(
            'Id' => 1949,
            'Label' => 'Music Videos|Pop|Tribute',
        ),
        4000 => array(
            'Id' => 4000,
            'Label' => 'TV Shows|Comedy',
        ),
        4001 => array(
            'Id' => 4001,
            'Label' => 'TV Shows|Drama',
        ),
        4002 => array(
            'Id' => 4002,
            'Label' => 'TV Shows|Animation',
        ),
        4003 => array(
            'Id' => 4003,
            'Label' => 'TV Shows|Action & Adventure',
        ),
        4004 => array(
            'Id' => 4004,
            'Label' => 'TV Shows|Classic',
        ),
        4005 => array(
            'Id' => 4005,
            'Label' => 'TV Shows|Kids',
        ),
        4006 => array(
            'Id' => 4006,
            'Label' => 'TV Shows|Nonfiction',
        ),
        4007 => array(
            'Id' => 4007,
            'Label' => 'TV Shows|Reality TV',
        ),
        4008 => array(
            'Id' => 4008,
            'Label' => 'TV Shows|Sci-Fi & Fantasy',
        ),
        4009 => array(
            'Id' => 4009,
            'Label' => 'TV Shows|Sports',
        ),
        4010 => array(
            'Id' => 4010,
            'Label' => 'TV Shows|Teens',
        ),
        4011 => array(
            'Id' => 4011,
            'Label' => 'TV Shows|Latino TV',
        ),
        4401 => array(
            'Id' => 4401,
            'Label' => 'Movies|Action & Adventure',
        ),
        4402 => array(
            'Id' => 4402,
            'Label' => 'Movies|Anime',
        ),
        4403 => array(
            'Id' => 4403,
            'Label' => 'Movies|Classics',
        ),
        4404 => array(
            'Id' => 4404,
            'Label' => 'Movies|Comedy',
        ),
        4405 => array(
            'Id' => 4405,
            'Label' => 'Movies|Documentary',
        ),
        4406 => array(
            'Id' => 4406,
            'Label' => 'Movies|Drama',
        ),
        4407 => array(
            'Id' => 4407,
            'Label' => 'Movies|Foreign',
        ),
        4408 => array(
            'Id' => 4408,
            'Label' => 'Movies|Horror',
        ),
        4409 => array(
            'Id' => 4409,
            'Label' => 'Movies|Independent',
        ),
        4410 => array(
            'Id' => 4410,
            'Label' => 'Movies|Kids & Family',
        ),
        4411 => array(
            'Id' => 4411,
            'Label' => 'Movies|Musicals',
        ),
        4412 => array(
            'Id' => 4412,
            'Label' => 'Movies|Romance',
        ),
        4413 => array(
            'Id' => 4413,
            'Label' => 'Movies|Sci-Fi & Fantasy',
        ),
        4414 => array(
            'Id' => 4414,
            'Label' => 'Movies|Short Films',
        ),
        4415 => array(
            'Id' => 4415,
            'Label' => 'Movies|Special Interest',
        ),
        4416 => array(
            'Id' => 4416,
            'Label' => 'Movies|Thriller',
        ),
        4417 => array(
            'Id' => 4417,
            'Label' => 'Movies|Sports',
        ),
        4418 => array(
            'Id' => 4418,
            'Label' => 'Movies|Western',
        ),
        4419 => array(
            'Id' => 4419,
            'Label' => 'Movies|Urban',
        ),
        4420 => array(
            'Id' => 4420,
            'Label' => 'Movies|Holiday',
        ),
        4421 => array(
            'Id' => 4421,
            'Label' => 'Movies|Made for TV',
        ),
        4422 => array(
            'Id' => 4422,
            'Label' => 'Movies|Concert Films',
        ),
        4423 => array(
            'Id' => 4423,
            'Label' => 'Movies|Music Documentaries',
        ),
        4424 => array(
            'Id' => 4424,
            'Label' => 'Movies|Music Feature Films',
        ),
        4425 => array(
            'Id' => 4425,
            'Label' => 'Movies|Japanese Cinema',
        ),
        4426 => array(
            'Id' => 4426,
            'Label' => 'Movies|Jidaigeki',
        ),
        4427 => array(
            'Id' => 4427,
            'Label' => 'Movies|Tokusatsu',
        ),
        4428 => array(
            'Id' => 4428,
            'Label' => 'Movies|Korean Cinema',
        ),
        4429 => array(
            'Id' => 4429,
            'Label' => 'Movies|Russian',
        ),
        4430 => array(
            'Id' => 4430,
            'Label' => 'Movies|Turkish',
        ),
        4431 => array(
            'Id' => 4431,
            'Label' => 'Movies|Bollywood',
        ),
        4432 => array(
            'Id' => 4432,
            'Label' => 'Movies|Regional Indian',
        ),
        4433 => array(
            'Id' => 4433,
            'Label' => 'Movies|Middle Eastern',
        ),
        4434 => array(
            'Id' => 4434,
            'Label' => 'Movies|African',
        ),
        6000 => array(
            'Id' => 6000,
            'Label' => 'App Store|Business',
        ),
        6001 => array(
            'Id' => 6001,
            'Label' => 'App Store|Weather',
        ),
        6002 => array(
            'Id' => 6002,
            'Label' => 'App Store|Utilities',
        ),
        6003 => array(
            'Id' => 6003,
            'Label' => 'App Store|Travel',
        ),
        6004 => array(
            'Id' => 6004,
            'Label' => 'App Store|Sports',
        ),
        6005 => array(
            'Id' => 6005,
            'Label' => 'App Store|Social Networking',
        ),
        6006 => array(
            'Id' => 6006,
            'Label' => 'App Store|Reference',
        ),
        6007 => array(
            'Id' => 6007,
            'Label' => 'App Store|Productivity',
        ),
        6008 => array(
            'Id' => 6008,
            'Label' => 'App Store|Photo & Video',
        ),
        6009 => array(
            'Id' => 6009,
            'Label' => 'App Store|News',
        ),
        6010 => array(
            'Id' => 6010,
            'Label' => 'App Store|Navigation',
        ),
        6011 => array(
            'Id' => 6011,
            'Label' => 'App Store|Music',
        ),
        6012 => array(
            'Id' => 6012,
            'Label' => 'App Store|Lifestyle',
        ),
        6013 => array(
            'Id' => 6013,
            'Label' => 'App Store|Health & Fitness',
        ),
        6014 => array(
            'Id' => 6014,
            'Label' => 'App Store|Games',
        ),
        6015 => array(
            'Id' => 6015,
            'Label' => 'App Store|Finance',
        ),
        6016 => array(
            'Id' => 6016,
            'Label' => 'App Store|Entertainment',
        ),
        6017 => array(
            'Id' => 6017,
            'Label' => 'App Store|Education',
        ),
        6018 => array(
            'Id' => 6018,
            'Label' => 'App Store|Books',
        ),
        6020 => array(
            'Id' => 6020,
            'Label' => 'App Store|Medical',
        ),
        6021 => array(
            'Id' => 6021,
            'Label' => 'App Store|Newsstand',
        ),
        6022 => array(
            'Id' => 6022,
            'Label' => 'App Store|Catalogs',
        ),
        6023 => array(
            'Id' => 6023,
            'Label' => 'App Store|Food & Drink',
        ),
        7001 => array(
            'Id' => 7001,
            'Label' => 'App Store|Games|Action',
        ),
        7002 => array(
            'Id' => 7002,
            'Label' => 'App Store|Games|Adventure',
        ),
        7003 => array(
            'Id' => 7003,
            'Label' => 'App Store|Games|Arcade',
        ),
        7004 => array(
            'Id' => 7004,
            'Label' => 'App Store|Games|Board',
        ),
        7005 => array(
            'Id' => 7005,
            'Label' => 'App Store|Games|Card',
        ),
        7006 => array(
            'Id' => 7006,
            'Label' => 'App Store|Games|Casino',
        ),
        7007 => array(
            'Id' => 7007,
            'Label' => 'App Store|Games|Dice',
        ),
        7008 => array(
            'Id' => 7008,
            'Label' => 'App Store|Games|Educational',
        ),
        7009 => array(
            'Id' => 7009,
            'Label' => 'App Store|Games|Family',
        ),
        7011 => array(
            'Id' => 7011,
            'Label' => 'App Store|Games|Music',
        ),
        7012 => array(
            'Id' => 7012,
            'Label' => 'App Store|Games|Puzzle',
        ),
        7013 => array(
            'Id' => 7013,
            'Label' => 'App Store|Games|Racing',
        ),
        7014 => array(
            'Id' => 7014,
            'Label' => 'App Store|Games|Role Playing',
        ),
        7015 => array(
            'Id' => 7015,
            'Label' => 'App Store|Games|Simulation',
        ),
        7016 => array(
            'Id' => 7016,
            'Label' => 'App Store|Games|Sports',
        ),
        7017 => array(
            'Id' => 7017,
            'Label' => 'App Store|Games|Strategy',
        ),
        7018 => array(
            'Id' => 7018,
            'Label' => 'App Store|Games|Trivia',
        ),
        7019 => array(
            'Id' => 7019,
            'Label' => 'App Store|Games|Word',
        ),
        8001 => array(
            'Id' => 8001,
            'Label' => 'Tones|Ringtones|Alternative',
        ),
        8002 => array(
            'Id' => 8002,
            'Label' => 'Tones|Ringtones|Blues',
        ),
        8003 => array(
            'Id' => 8003,
            'Label' => 'Tones|Ringtones|Children\'s Music',
        ),
        8004 => array(
            'Id' => 8004,
            'Label' => 'Tones|Ringtones|Classical',
        ),
        8005 => array(
            'Id' => 8005,
            'Label' => 'Tones|Ringtones|Comedy',
        ),
        8006 => array(
            'Id' => 8006,
            'Label' => 'Tones|Ringtones|Country',
        ),
        8007 => array(
            'Id' => 8007,
            'Label' => 'Tones|Ringtones|Dance',
        ),
        8008 => array(
            'Id' => 8008,
            'Label' => 'Tones|Ringtones|Electronic',
        ),
        8009 => array(
            'Id' => 8009,
            'Label' => 'Tones|Ringtones|Enka',
        ),
        8010 => array(
            'Id' => 8010,
            'Label' => 'Tones|Ringtones|French Pop',
        ),
        8011 => array(
            'Id' => 8011,
            'Label' => 'Tones|Ringtones|German Folk',
        ),
        8012 => array(
            'Id' => 8012,
            'Label' => 'Tones|Ringtones|German Pop',
        ),
        8013 => array(
            'Id' => 8013,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap',
        ),
        8014 => array(
            'Id' => 8014,
            'Label' => 'Tones|Ringtones|Holiday',
        ),
        8015 => array(
            'Id' => 8015,
            'Label' => 'Tones|Ringtones|Inspirational',
        ),
        8016 => array(
            'Id' => 8016,
            'Label' => 'Tones|Ringtones|J-Pop',
        ),
        8017 => array(
            'Id' => 8017,
            'Label' => 'Tones|Ringtones|Jazz',
        ),
        8018 => array(
            'Id' => 8018,
            'Label' => 'Tones|Ringtones|Kayokyoku',
        ),
        8019 => array(
            'Id' => 8019,
            'Label' => 'Tones|Ringtones|Latin',
        ),
        8020 => array(
            'Id' => 8020,
            'Label' => 'Tones|Ringtones|New Age',
        ),
        8021 => array(
            'Id' => 8021,
            'Label' => 'Tones|Ringtones|Classical|Opera',
        ),
        8022 => array(
            'Id' => 8022,
            'Label' => 'Tones|Ringtones|Pop',
        ),
        8023 => array(
            'Id' => 8023,
            'Label' => 'Tones|Ringtones|R&B/Soul',
        ),
        8024 => array(
            'Id' => 8024,
            'Label' => 'Tones|Ringtones|Reggae',
        ),
        8025 => array(
            'Id' => 8025,
            'Label' => 'Tones|Ringtones|Rock',
        ),
        8026 => array(
            'Id' => 8026,
            'Label' => 'Tones|Ringtones|Singer/Songwriter',
        ),
        8027 => array(
            'Id' => 8027,
            'Label' => 'Tones|Ringtones|Soundtrack',
        ),
        8028 => array(
            'Id' => 8028,
            'Label' => 'Tones|Ringtones|Spoken Word',
        ),
        8029 => array(
            'Id' => 8029,
            'Label' => 'Tones|Ringtones|Vocal',
        ),
        8030 => array(
            'Id' => 8030,
            'Label' => 'Tones|Ringtones|World',
        ),
        8050 => array(
            'Id' => 8050,
            'Label' => 'Tones|Alert Tones|Sound Effects',
        ),
        8051 => array(
            'Id' => 8051,
            'Label' => 'Tones|Alert Tones|Dialogue',
        ),
        8052 => array(
            'Id' => 8052,
            'Label' => 'Tones|Alert Tones|Music',
        ),
        8053 => array(
            'Id' => 8053,
            'Label' => 'Tones|Ringtones',
        ),
        8054 => array(
            'Id' => 8054,
            'Label' => 'Tones|Alert Tones',
        ),
        8055 => array(
            'Id' => 8055,
            'Label' => 'Tones|Ringtones|Alternative|Chinese Alt',
        ),
        8056 => array(
            'Id' => 8056,
            'Label' => 'Tones|Ringtones|Alternative|College Rock',
        ),
        8057 => array(
            'Id' => 8057,
            'Label' => 'Tones|Ringtones|Alternative|Goth Rock',
        ),
        8058 => array(
            'Id' => 8058,
            'Label' => 'Tones|Ringtones|Alternative|Grunge',
        ),
        8059 => array(
            'Id' => 8059,
            'Label' => 'Tones|Ringtones|Alternative|Indie Rock',
        ),
        8060 => array(
            'Id' => 8060,
            'Label' => 'Tones|Ringtones|Alternative|Korean Indie',
        ),
        8061 => array(
            'Id' => 8061,
            'Label' => 'Tones|Ringtones|Alternative|New Wave',
        ),
        8062 => array(
            'Id' => 8062,
            'Label' => 'Tones|Ringtones|Alternative|Punk',
        ),
        8063 => array(
            'Id' => 8063,
            'Label' => 'Tones|Ringtones|Anime',
        ),
        8064 => array(
            'Id' => 8064,
            'Label' => 'Tones|Ringtones|Arabic',
        ),
        8065 => array(
            'Id' => 8065,
            'Label' => 'Tones|Ringtones|Arabic|Arabic Pop',
        ),
        8066 => array(
            'Id' => 8066,
            'Label' => 'Tones|Ringtones|Arabic|Islamic',
        ),
        8067 => array(
            'Id' => 8067,
            'Label' => 'Tones|Ringtones|Arabic|Khaleeji',
        ),
        8068 => array(
            'Id' => 8068,
            'Label' => 'Tones|Ringtones|Arabic|North African',
        ),
        8069 => array(
            'Id' => 8069,
            'Label' => 'Tones|Ringtones|Blues|Acoustic Blues',
        ),
        8070 => array(
            'Id' => 8070,
            'Label' => 'Tones|Ringtones|Blues|Chicago Blues',
        ),
        8071 => array(
            'Id' => 8071,
            'Label' => 'Tones|Ringtones|Blues|Classic Blues',
        ),
        8072 => array(
            'Id' => 8072,
            'Label' => 'Tones|Ringtones|Blues|Contemporary Blues',
        ),
        8073 => array(
            'Id' => 8073,
            'Label' => 'Tones|Ringtones|Blues|Country Blues',
        ),
        8074 => array(
            'Id' => 8074,
            'Label' => 'Tones|Ringtones|Blues|Delta Blues',
        ),
        8075 => array(
            'Id' => 8075,
            'Label' => 'Tones|Ringtones|Blues|Electric Blues',
        ),
        8076 => array(
            'Id' => 8076,
            'Label' => 'Tones|Ringtones|Brazilian',
        ),
        8077 => array(
            'Id' => 8077,
            'Label' => 'Tones|Ringtones|Brazilian|Axe',
        ),
        8078 => array(
            'Id' => 8078,
            'Label' => 'Tones|Ringtones|Brazilian|Baile Funk',
        ),
        8079 => array(
            'Id' => 8079,
            'Label' => 'Tones|Ringtones|Brazilian|Bossa Nova',
        ),
        8080 => array(
            'Id' => 8080,
            'Label' => 'Tones|Ringtones|Brazilian|Choro',
        ),
        8081 => array(
            'Id' => 8081,
            'Label' => 'Tones|Ringtones|Brazilian|Forro',
        ),
        8082 => array(
            'Id' => 8082,
            'Label' => 'Tones|Ringtones|Brazilian|Frevo',
        ),
        8083 => array(
            'Id' => 8083,
            'Label' => 'Tones|Ringtones|Brazilian|MPB',
        ),
        8084 => array(
            'Id' => 8084,
            'Label' => 'Tones|Ringtones|Brazilian|Pagode',
        ),
        8085 => array(
            'Id' => 8085,
            'Label' => 'Tones|Ringtones|Brazilian|Samba',
        ),
        8086 => array(
            'Id' => 8086,
            'Label' => 'Tones|Ringtones|Brazilian|Sertanejo',
        ),
        8087 => array(
            'Id' => 8087,
            'Label' => 'Tones|Ringtones|Children\'s Music|Lullabies',
        ),
        8088 => array(
            'Id' => 8088,
            'Label' => 'Tones|Ringtones|Children\'s Music|Sing-Along',
        ),
        8089 => array(
            'Id' => 8089,
            'Label' => 'Tones|Ringtones|Children\'s Music|Stories',
        ),
        8090 => array(
            'Id' => 8090,
            'Label' => 'Tones|Ringtones|Chinese',
        ),
        8091 => array(
            'Id' => 8091,
            'Label' => 'Tones|Ringtones|Chinese|Chinese Classical',
        ),
        8092 => array(
            'Id' => 8092,
            'Label' => 'Tones|Ringtones|Chinese|Chinese Flute',
        ),
        8093 => array(
            'Id' => 8093,
            'Label' => 'Tones|Ringtones|Chinese|Chinese Opera',
        ),
        8094 => array(
            'Id' => 8094,
            'Label' => 'Tones|Ringtones|Chinese|Chinese Orchestral',
        ),
        8095 => array(
            'Id' => 8095,
            'Label' => 'Tones|Ringtones|Chinese|Chinese Regional Folk',
        ),
        8096 => array(
            'Id' => 8096,
            'Label' => 'Tones|Ringtones|Chinese|Chinese Strings',
        ),
        8097 => array(
            'Id' => 8097,
            'Label' => 'Tones|Ringtones|Chinese|Taiwanese Folk',
        ),
        8098 => array(
            'Id' => 8098,
            'Label' => 'Tones|Ringtones|Chinese|Tibetan Native Music',
        ),
        8099 => array(
            'Id' => 8099,
            'Label' => 'Tones|Ringtones|Christian & Gospel',
        ),
        8100 => array(
            'Id' => 8100,
            'Label' => 'Tones|Ringtones|Christian & Gospel|CCM',
        ),
        8101 => array(
            'Id' => 8101,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Christian Metal',
        ),
        8102 => array(
            'Id' => 8102,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Christian Pop',
        ),
        8103 => array(
            'Id' => 8103,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Christian Rap',
        ),
        8104 => array(
            'Id' => 8104,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Christian Rock',
        ),
        8105 => array(
            'Id' => 8105,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Classic Christian',
        ),
        8106 => array(
            'Id' => 8106,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Contemporary Gospel',
        ),
        8107 => array(
            'Id' => 8107,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Gospel',
        ),
        8108 => array(
            'Id' => 8108,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Praise & Worship',
        ),
        8109 => array(
            'Id' => 8109,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Southern Gospel',
        ),
        8110 => array(
            'Id' => 8110,
            'Label' => 'Tones|Ringtones|Christian & Gospel|Traditional Gospel',
        ),
        8111 => array(
            'Id' => 8111,
            'Label' => 'Tones|Ringtones|Classical|Avant-Garde',
        ),
        8112 => array(
            'Id' => 8112,
            'Label' => 'Tones|Ringtones|Classical|Baroque Era',
        ),
        8113 => array(
            'Id' => 8113,
            'Label' => 'Tones|Ringtones|Classical|Chamber Music',
        ),
        8114 => array(
            'Id' => 8114,
            'Label' => 'Tones|Ringtones|Classical|Chant',
        ),
        8115 => array(
            'Id' => 8115,
            'Label' => 'Tones|Ringtones|Classical|Choral',
        ),
        8116 => array(
            'Id' => 8116,
            'Label' => 'Tones|Ringtones|Classical|Classical Crossover',
        ),
        8117 => array(
            'Id' => 8117,
            'Label' => 'Tones|Ringtones|Classical|Early Music',
        ),
        8118 => array(
            'Id' => 8118,
            'Label' => 'Tones|Ringtones|Classical|High Classical',
        ),
        8119 => array(
            'Id' => 8119,
            'Label' => 'Tones|Ringtones|Classical|Impressionist',
        ),
        8120 => array(
            'Id' => 8120,
            'Label' => 'Tones|Ringtones|Classical|Medieval Era',
        ),
        8121 => array(
            'Id' => 8121,
            'Label' => 'Tones|Ringtones|Classical|Minimalism',
        ),
        8122 => array(
            'Id' => 8122,
            'Label' => 'Tones|Ringtones|Classical|Modern Era',
        ),
        8123 => array(
            'Id' => 8123,
            'Label' => 'Tones|Ringtones|Classical|Orchestral',
        ),
        8124 => array(
            'Id' => 8124,
            'Label' => 'Tones|Ringtones|Classical|Renaissance',
        ),
        8125 => array(
            'Id' => 8125,
            'Label' => 'Tones|Ringtones|Classical|Romantic Era',
        ),
        8126 => array(
            'Id' => 8126,
            'Label' => 'Tones|Ringtones|Classical|Wedding Music',
        ),
        8127 => array(
            'Id' => 8127,
            'Label' => 'Tones|Ringtones|Comedy|Novelty',
        ),
        8128 => array(
            'Id' => 8128,
            'Label' => 'Tones|Ringtones|Comedy|Standup Comedy',
        ),
        8129 => array(
            'Id' => 8129,
            'Label' => 'Tones|Ringtones|Country|Alternative Country',
        ),
        8130 => array(
            'Id' => 8130,
            'Label' => 'Tones|Ringtones|Country|Americana',
        ),
        8131 => array(
            'Id' => 8131,
            'Label' => 'Tones|Ringtones|Country|Bluegrass',
        ),
        8132 => array(
            'Id' => 8132,
            'Label' => 'Tones|Ringtones|Country|Contemporary Bluegrass',
        ),
        8133 => array(
            'Id' => 8133,
            'Label' => 'Tones|Ringtones|Country|Contemporary Country',
        ),
        8134 => array(
            'Id' => 8134,
            'Label' => 'Tones|Ringtones|Country|Country Gospel',
        ),
        8135 => array(
            'Id' => 8135,
            'Label' => 'Tones|Ringtones|Country|Honky Tonk',
        ),
        8136 => array(
            'Id' => 8136,
            'Label' => 'Tones|Ringtones|Country|Outlaw Country',
        ),
        8137 => array(
            'Id' => 8137,
            'Label' => 'Tones|Ringtones|Country|Thai Country',
        ),
        8138 => array(
            'Id' => 8138,
            'Label' => 'Tones|Ringtones|Country|Traditional Bluegrass',
        ),
        8139 => array(
            'Id' => 8139,
            'Label' => 'Tones|Ringtones|Country|Traditional Country',
        ),
        8140 => array(
            'Id' => 8140,
            'Label' => 'Tones|Ringtones|Country|Urban Cowboy',
        ),
        8141 => array(
            'Id' => 8141,
            'Label' => 'Tones|Ringtones|Dance|Breakbeat',
        ),
        8142 => array(
            'Id' => 8142,
            'Label' => 'Tones|Ringtones|Dance|Exercise',
        ),
        8143 => array(
            'Id' => 8143,
            'Label' => 'Tones|Ringtones|Dance|Garage',
        ),
        8144 => array(
            'Id' => 8144,
            'Label' => 'Tones|Ringtones|Dance|Hardcore',
        ),
        8145 => array(
            'Id' => 8145,
            'Label' => 'Tones|Ringtones|Dance|House',
        ),
        8146 => array(
            'Id' => 8146,
            'Label' => 'Tones|Ringtones|Dance|Jungle/Drum\'n\'bass',
        ),
        8147 => array(
            'Id' => 8147,
            'Label' => 'Tones|Ringtones|Dance|Techno',
        ),
        8148 => array(
            'Id' => 8148,
            'Label' => 'Tones|Ringtones|Dance|Trance',
        ),
        8149 => array(
            'Id' => 8149,
            'Label' => 'Tones|Ringtones|Disney',
        ),
        8150 => array(
            'Id' => 8150,
            'Label' => 'Tones|Ringtones|Easy Listening',
        ),
        8151 => array(
            'Id' => 8151,
            'Label' => 'Tones|Ringtones|Easy Listening|Lounge',
        ),
        8152 => array(
            'Id' => 8152,
            'Label' => 'Tones|Ringtones|Easy Listening|Swing',
        ),
        8153 => array(
            'Id' => 8153,
            'Label' => 'Tones|Ringtones|Electronic|Ambient',
        ),
        8154 => array(
            'Id' => 8154,
            'Label' => 'Tones|Ringtones|Electronic|Downtempo',
        ),
        8155 => array(
            'Id' => 8155,
            'Label' => 'Tones|Ringtones|Electronic|Electronica',
        ),
        8156 => array(
            'Id' => 8156,
            'Label' => 'Tones|Ringtones|Electronic|IDM/Experimental',
        ),
        8157 => array(
            'Id' => 8157,
            'Label' => 'Tones|Ringtones|Electronic|Industrial',
        ),
        8158 => array(
            'Id' => 8158,
            'Label' => 'Tones|Ringtones|Fitness & Workout',
        ),
        8159 => array(
            'Id' => 8159,
            'Label' => 'Tones|Ringtones|Folk',
        ),
        8160 => array(
            'Id' => 8160,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Alternative Rap',
        ),
        8161 => array(
            'Id' => 8161,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Chinese Hip-Hop',
        ),
        8162 => array(
            'Id' => 8162,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Dirty South',
        ),
        8163 => array(
            'Id' => 8163,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|East Coast Rap',
        ),
        8164 => array(
            'Id' => 8164,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Gangsta Rap',
        ),
        8165 => array(
            'Id' => 8165,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Hardcore Rap',
        ),
        8166 => array(
            'Id' => 8166,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Hip-Hop',
        ),
        8167 => array(
            'Id' => 8167,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Korean Hip-Hop',
        ),
        8168 => array(
            'Id' => 8168,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Latin Rap',
        ),
        8169 => array(
            'Id' => 8169,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Old School Rap',
        ),
        8170 => array(
            'Id' => 8170,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Rap',
        ),
        8171 => array(
            'Id' => 8171,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|Underground Rap',
        ),
        8172 => array(
            'Id' => 8172,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|West Coast Rap',
        ),
        8173 => array(
            'Id' => 8173,
            'Label' => 'Tones|Ringtones|Holiday|Chanukah',
        ),
        8174 => array(
            'Id' => 8174,
            'Label' => 'Tones|Ringtones|Holiday|Christmas',
        ),
        8175 => array(
            'Id' => 8175,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Children\'s',
        ),
        8176 => array(
            'Id' => 8176,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Classic',
        ),
        8177 => array(
            'Id' => 8177,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Classical',
        ),
        8178 => array(
            'Id' => 8178,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Jazz',
        ),
        8179 => array(
            'Id' => 8179,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Modern',
        ),
        8180 => array(
            'Id' => 8180,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Pop',
        ),
        8181 => array(
            'Id' => 8181,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: R&B',
        ),
        8182 => array(
            'Id' => 8182,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Religious',
        ),
        8183 => array(
            'Id' => 8183,
            'Label' => 'Tones|Ringtones|Holiday|Christmas: Rock',
        ),
        8184 => array(
            'Id' => 8184,
            'Label' => 'Tones|Ringtones|Holiday|Easter',
        ),
        8185 => array(
            'Id' => 8185,
            'Label' => 'Tones|Ringtones|Holiday|Halloween',
        ),
        8186 => array(
            'Id' => 8186,
            'Label' => 'Tones|Ringtones|Holiday|Thanksgiving',
        ),
        8187 => array(
            'Id' => 8187,
            'Label' => 'Tones|Ringtones|Indian',
        ),
        8188 => array(
            'Id' => 8188,
            'Label' => 'Tones|Ringtones|Indian|Bollywood',
        ),
        8189 => array(
            'Id' => 8189,
            'Label' => 'Tones|Ringtones|Indian|Devotional & Spiritual',
        ),
        8190 => array(
            'Id' => 8190,
            'Label' => 'Tones|Ringtones|Indian|Ghazals',
        ),
        8191 => array(
            'Id' => 8191,
            'Label' => 'Tones|Ringtones|Indian|Indian Classical',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'Tones|Ringtones|Indian|Indian Folk',
        ),
        8193 => array(
            'Id' => 8193,
            'Label' => 'Tones|Ringtones|Indian|Indian Pop',
        ),
        8194 => array(
            'Id' => 8194,
            'Label' => 'Tones|Ringtones|Indian|Regional Indian',
        ),
        8195 => array(
            'Id' => 8195,
            'Label' => 'Tones|Ringtones|Indian|Sufi',
        ),
        8196 => array(
            'Id' => 8196,
            'Label' => 'Tones|Ringtones|Indian|Tamil',
        ),
        8197 => array(
            'Id' => 8197,
            'Label' => 'Tones|Ringtones|Indian|Telugu',
        ),
        8198 => array(
            'Id' => 8198,
            'Label' => 'Tones|Ringtones|Instrumental',
        ),
        8199 => array(
            'Id' => 8199,
            'Label' => 'Tones|Ringtones|Jazz|Avant-Garde Jazz',
        ),
        8201 => array(
            'Id' => 8201,
            'Label' => 'Tones|Ringtones|Jazz|Big Band',
        ),
        8202 => array(
            'Id' => 8202,
            'Label' => 'Tones|Ringtones|Jazz|Bop',
        ),
        8203 => array(
            'Id' => 8203,
            'Label' => 'Tones|Ringtones|Jazz|Contemporary Jazz',
        ),
        8204 => array(
            'Id' => 8204,
            'Label' => 'Tones|Ringtones|Jazz|Cool',
        ),
        8205 => array(
            'Id' => 8205,
            'Label' => 'Tones|Ringtones|Jazz|Crossover Jazz',
        ),
        8206 => array(
            'Id' => 8206,
            'Label' => 'Tones|Ringtones|Jazz|Dixieland',
        ),
        8207 => array(
            'Id' => 8207,
            'Label' => 'Tones|Ringtones|Jazz|Fusion',
        ),
        8208 => array(
            'Id' => 8208,
            'Label' => 'Tones|Ringtones|Jazz|Hard Bop',
        ),
        8209 => array(
            'Id' => 8209,
            'Label' => 'Tones|Ringtones|Jazz|Latin Jazz',
        ),
        8210 => array(
            'Id' => 8210,
            'Label' => 'Tones|Ringtones|Jazz|Mainstream Jazz',
        ),
        8211 => array(
            'Id' => 8211,
            'Label' => 'Tones|Ringtones|Jazz|Ragtime',
        ),
        8212 => array(
            'Id' => 8212,
            'Label' => 'Tones|Ringtones|Jazz|Smooth Jazz',
        ),
        8213 => array(
            'Id' => 8213,
            'Label' => 'Tones|Ringtones|Jazz|Trad Jazz',
        ),
        8214 => array(
            'Id' => 8214,
            'Label' => 'Tones|Ringtones|Pop|K-Pop',
        ),
        8215 => array(
            'Id' => 8215,
            'Label' => 'Tones|Ringtones|Karaoke',
        ),
        8216 => array(
            'Id' => 8216,
            'Label' => 'Tones|Ringtones|Korean',
        ),
        8217 => array(
            'Id' => 8217,
            'Label' => 'Tones|Ringtones|Korean|Korean Classical',
        ),
        8218 => array(
            'Id' => 8218,
            'Label' => 'Tones|Ringtones|Korean|Korean Trad Instrumental',
        ),
        8219 => array(
            'Id' => 8219,
            'Label' => 'Tones|Ringtones|Korean|Korean Trad Song',
        ),
        8220 => array(
            'Id' => 8220,
            'Label' => 'Tones|Ringtones|Korean|Korean Trad Theater',
        ),
        8221 => array(
            'Id' => 8221,
            'Label' => 'Tones|Ringtones|Latin|Alternative & Rock in Spanish',
        ),
        8222 => array(
            'Id' => 8222,
            'Label' => 'Tones|Ringtones|Latin|Baladas y Boleros',
        ),
        8223 => array(
            'Id' => 8223,
            'Label' => 'Tones|Ringtones|Latin|Contemporary Latin',
        ),
        8224 => array(
            'Id' => 8224,
            'Label' => 'Tones|Ringtones|Latin|Latin Jazz',
        ),
        8225 => array(
            'Id' => 8225,
            'Label' => 'Tones|Ringtones|Latin|Latin Urban',
        ),
        8226 => array(
            'Id' => 8226,
            'Label' => 'Tones|Ringtones|Latin|Pop in Spanish',
        ),
        8227 => array(
            'Id' => 8227,
            'Label' => 'Tones|Ringtones|Latin|Raices',
        ),
        8228 => array(
            'Id' => 8228,
            'Label' => 'Tones|Ringtones|Latin|Regional Mexicano',
        ),
        8229 => array(
            'Id' => 8229,
            'Label' => 'Tones|Ringtones|Latin|Salsa y Tropical',
        ),
        8230 => array(
            'Id' => 8230,
            'Label' => 'Tones|Ringtones|Marching Bands',
        ),
        8231 => array(
            'Id' => 8231,
            'Label' => 'Tones|Ringtones|New Age|Healing',
        ),
        8232 => array(
            'Id' => 8232,
            'Label' => 'Tones|Ringtones|New Age|Meditation',
        ),
        8233 => array(
            'Id' => 8233,
            'Label' => 'Tones|Ringtones|New Age|Nature',
        ),
        8234 => array(
            'Id' => 8234,
            'Label' => 'Tones|Ringtones|New Age|Relaxation',
        ),
        8235 => array(
            'Id' => 8235,
            'Label' => 'Tones|Ringtones|New Age|Travel',
        ),
        8236 => array(
            'Id' => 8236,
            'Label' => 'Tones|Ringtones|Orchestral',
        ),
        8237 => array(
            'Id' => 8237,
            'Label' => 'Tones|Ringtones|Pop|Adult Contemporary',
        ),
        8238 => array(
            'Id' => 8238,
            'Label' => 'Tones|Ringtones|Pop|Britpop',
        ),
        8239 => array(
            'Id' => 8239,
            'Label' => 'Tones|Ringtones|Pop|C-Pop',
        ),
        8240 => array(
            'Id' => 8240,
            'Label' => 'Tones|Ringtones|Pop|Cantopop/HK-Pop',
        ),
        8241 => array(
            'Id' => 8241,
            'Label' => 'Tones|Ringtones|Pop|Indo Pop',
        ),
        8242 => array(
            'Id' => 8242,
            'Label' => 'Tones|Ringtones|Pop|Korean Folk-Pop',
        ),
        8243 => array(
            'Id' => 8243,
            'Label' => 'Tones|Ringtones|Pop|Malaysian Pop',
        ),
        8244 => array(
            'Id' => 8244,
            'Label' => 'Tones|Ringtones|Pop|Mandopop',
        ),
        8245 => array(
            'Id' => 8245,
            'Label' => 'Tones|Ringtones|Pop|Manilla Sound',
        ),
        8246 => array(
            'Id' => 8246,
            'Label' => 'Tones|Ringtones|Pop|Oldies',
        ),
        8247 => array(
            'Id' => 8247,
            'Label' => 'Tones|Ringtones|Pop|Original Pilipino Music',
        ),
        8248 => array(
            'Id' => 8248,
            'Label' => 'Tones|Ringtones|Pop|Pinoy Pop',
        ),
        8249 => array(
            'Id' => 8249,
            'Label' => 'Tones|Ringtones|Pop|Pop/Rock',
        ),
        8250 => array(
            'Id' => 8250,
            'Label' => 'Tones|Ringtones|Pop|Soft Rock',
        ),
        8251 => array(
            'Id' => 8251,
            'Label' => 'Tones|Ringtones|Pop|Tai-Pop',
        ),
        8252 => array(
            'Id' => 8252,
            'Label' => 'Tones|Ringtones|Pop|Teen Pop',
        ),
        8253 => array(
            'Id' => 8253,
            'Label' => 'Tones|Ringtones|Pop|Thai Pop',
        ),
        8254 => array(
            'Id' => 8254,
            'Label' => 'Tones|Ringtones|R&B/Soul|Contemporary R&B',
        ),
        8255 => array(
            'Id' => 8255,
            'Label' => 'Tones|Ringtones|R&B/Soul|Disco',
        ),
        8256 => array(
            'Id' => 8256,
            'Label' => 'Tones|Ringtones|R&B/Soul|Doo Wop',
        ),
        8257 => array(
            'Id' => 8257,
            'Label' => 'Tones|Ringtones|R&B/Soul|Funk',
        ),
        8258 => array(
            'Id' => 8258,
            'Label' => 'Tones|Ringtones|R&B/Soul|Motown',
        ),
        8259 => array(
            'Id' => 8259,
            'Label' => 'Tones|Ringtones|R&B/Soul|Neo-Soul',
        ),
        8260 => array(
            'Id' => 8260,
            'Label' => 'Tones|Ringtones|R&B/Soul|Soul',
        ),
        8261 => array(
            'Id' => 8261,
            'Label' => 'Tones|Ringtones|Reggae|Modern Dancehall',
        ),
        8262 => array(
            'Id' => 8262,
            'Label' => 'Tones|Ringtones|Reggae|Dub',
        ),
        8263 => array(
            'Id' => 8263,
            'Label' => 'Tones|Ringtones|Reggae|Roots Reggae',
        ),
        8264 => array(
            'Id' => 8264,
            'Label' => 'Tones|Ringtones|Reggae|Ska',
        ),
        8265 => array(
            'Id' => 8265,
            'Label' => 'Tones|Ringtones|Rock|Adult Alternative',
        ),
        8266 => array(
            'Id' => 8266,
            'Label' => 'Tones|Ringtones|Rock|American Trad Rock',
        ),
        8267 => array(
            'Id' => 8267,
            'Label' => 'Tones|Ringtones|Rock|Arena Rock',
        ),
        8268 => array(
            'Id' => 8268,
            'Label' => 'Tones|Ringtones|Rock|Blues-Rock',
        ),
        8269 => array(
            'Id' => 8269,
            'Label' => 'Tones|Ringtones|Rock|British Invasion',
        ),
        8270 => array(
            'Id' => 8270,
            'Label' => 'Tones|Ringtones|Rock|Chinese Rock',
        ),
        8271 => array(
            'Id' => 8271,
            'Label' => 'Tones|Ringtones|Rock|Death Metal/Black Metal',
        ),
        8272 => array(
            'Id' => 8272,
            'Label' => 'Tones|Ringtones|Rock|Glam Rock',
        ),
        8273 => array(
            'Id' => 8273,
            'Label' => 'Tones|Ringtones|Rock|Hair Metal',
        ),
        8274 => array(
            'Id' => 8274,
            'Label' => 'Tones|Ringtones|Rock|Hard Rock',
        ),
        8275 => array(
            'Id' => 8275,
            'Label' => 'Tones|Ringtones|Rock|Metal',
        ),
        8276 => array(
            'Id' => 8276,
            'Label' => 'Tones|Ringtones|Rock|Jam Bands',
        ),
        8277 => array(
            'Id' => 8277,
            'Label' => 'Tones|Ringtones|Rock|Korean Rock',
        ),
        8278 => array(
            'Id' => 8278,
            'Label' => 'Tones|Ringtones|Rock|Prog-Rock/Art Rock',
        ),
        8279 => array(
            'Id' => 8279,
            'Label' => 'Tones|Ringtones|Rock|Psychedelic',
        ),
        8280 => array(
            'Id' => 8280,
            'Label' => 'Tones|Ringtones|Rock|Rock & Roll',
        ),
        8281 => array(
            'Id' => 8281,
            'Label' => 'Tones|Ringtones|Rock|Rockabilly',
        ),
        8282 => array(
            'Id' => 8282,
            'Label' => 'Tones|Ringtones|Rock|Roots Rock',
        ),
        8283 => array(
            'Id' => 8283,
            'Label' => 'Tones|Ringtones|Rock|Singer/Songwriter',
        ),
        8284 => array(
            'Id' => 8284,
            'Label' => 'Tones|Ringtones|Rock|Southern Rock',
        ),
        8285 => array(
            'Id' => 8285,
            'Label' => 'Tones|Ringtones|Rock|Surf',
        ),
        8286 => array(
            'Id' => 8286,
            'Label' => 'Tones|Ringtones|Rock|Tex-Mex',
        ),
        8287 => array(
            'Id' => 8287,
            'Label' => 'Tones|Ringtones|Singer/Songwriter|Alternative Folk',
        ),
        8288 => array(
            'Id' => 8288,
            'Label' => 'Tones|Ringtones|Singer/Songwriter|Contemporary Folk',
        ),
        8289 => array(
            'Id' => 8289,
            'Label' => 'Tones|Ringtones|Singer/Songwriter|Contemporary Singer/Songwriter',
        ),
        8290 => array(
            'Id' => 8290,
            'Label' => 'Tones|Ringtones|Singer/Songwriter|Folk-Rock',
        ),
        8291 => array(
            'Id' => 8291,
            'Label' => 'Tones|Ringtones|Singer/Songwriter|New Acoustic',
        ),
        8292 => array(
            'Id' => 8292,
            'Label' => 'Tones|Ringtones|Singer/Songwriter|Traditional Folk',
        ),
        8293 => array(
            'Id' => 8293,
            'Label' => 'Tones|Ringtones|Soundtrack|Foreign Cinema',
        ),
        8294 => array(
            'Id' => 8294,
            'Label' => 'Tones|Ringtones|Soundtrack|Musicals',
        ),
        8295 => array(
            'Id' => 8295,
            'Label' => 'Tones|Ringtones|Soundtrack|Original Score',
        ),
        8296 => array(
            'Id' => 8296,
            'Label' => 'Tones|Ringtones|Soundtrack|Sound Effects',
        ),
        8297 => array(
            'Id' => 8297,
            'Label' => 'Tones|Ringtones|Soundtrack|Soundtrack',
        ),
        8298 => array(
            'Id' => 8298,
            'Label' => 'Tones|Ringtones|Soundtrack|TV Soundtrack',
        ),
        8299 => array(
            'Id' => 8299,
            'Label' => 'Tones|Ringtones|Vocal|Standards',
        ),
        8300 => array(
            'Id' => 8300,
            'Label' => 'Tones|Ringtones|Vocal|Traditional Pop',
        ),
        8301 => array(
            'Id' => 8301,
            'Label' => 'Tones|Ringtones|Vocal|Trot',
        ),
        8302 => array(
            'Id' => 8302,
            'Label' => 'Tones|Ringtones|Jazz|Vocal Jazz',
        ),
        8303 => array(
            'Id' => 8303,
            'Label' => 'Tones|Ringtones|Vocal|Vocal Pop',
        ),
        8304 => array(
            'Id' => 8304,
            'Label' => 'Tones|Ringtones|World|Africa',
        ),
        8305 => array(
            'Id' => 8305,
            'Label' => 'Tones|Ringtones|World|Afrikaans',
        ),
        8306 => array(
            'Id' => 8306,
            'Label' => 'Tones|Ringtones|World|Afro-Beat',
        ),
        8307 => array(
            'Id' => 8307,
            'Label' => 'Tones|Ringtones|World|Afro-Pop',
        ),
        8308 => array(
            'Id' => 8308,
            'Label' => 'Tones|Ringtones|World|Arabesque',
        ),
        8309 => array(
            'Id' => 8309,
            'Label' => 'Tones|Ringtones|World|Asia',
        ),
        8310 => array(
            'Id' => 8310,
            'Label' => 'Tones|Ringtones|World|Australia',
        ),
        8311 => array(
            'Id' => 8311,
            'Label' => 'Tones|Ringtones|World|Cajun',
        ),
        8312 => array(
            'Id' => 8312,
            'Label' => 'Tones|Ringtones|World|Calypso',
        ),
        8313 => array(
            'Id' => 8313,
            'Label' => 'Tones|Ringtones|World|Caribbean',
        ),
        8314 => array(
            'Id' => 8314,
            'Label' => 'Tones|Ringtones|World|Celtic',
        ),
        8315 => array(
            'Id' => 8315,
            'Label' => 'Tones|Ringtones|World|Celtic Folk',
        ),
        8316 => array(
            'Id' => 8316,
            'Label' => 'Tones|Ringtones|World|Contemporary Celtic',
        ),
        8317 => array(
            'Id' => 8317,
            'Label' => 'Tones|Ringtones|World|Dangdut',
        ),
        8318 => array(
            'Id' => 8318,
            'Label' => 'Tones|Ringtones|World|Dini',
        ),
        8319 => array(
            'Id' => 8319,
            'Label' => 'Tones|Ringtones|World|Europe',
        ),
        8320 => array(
            'Id' => 8320,
            'Label' => 'Tones|Ringtones|World|Fado',
        ),
        8321 => array(
            'Id' => 8321,
            'Label' => 'Tones|Ringtones|World|Farsi',
        ),
        8322 => array(
            'Id' => 8322,
            'Label' => 'Tones|Ringtones|World|Flamenco',
        ),
        8323 => array(
            'Id' => 8323,
            'Label' => 'Tones|Ringtones|World|France',
        ),
        8324 => array(
            'Id' => 8324,
            'Label' => 'Tones|Ringtones|World|Halk',
        ),
        8325 => array(
            'Id' => 8325,
            'Label' => 'Tones|Ringtones|World|Hawaii',
        ),
        8326 => array(
            'Id' => 8326,
            'Label' => 'Tones|Ringtones|World|Iberia',
        ),
        8327 => array(
            'Id' => 8327,
            'Label' => 'Tones|Ringtones|World|Indonesian Religious',
        ),
        8328 => array(
            'Id' => 8328,
            'Label' => 'Tones|Ringtones|World|Israeli',
        ),
        8329 => array(
            'Id' => 8329,
            'Label' => 'Tones|Ringtones|World|Japan',
        ),
        8330 => array(
            'Id' => 8330,
            'Label' => 'Tones|Ringtones|World|Klezmer',
        ),
        8331 => array(
            'Id' => 8331,
            'Label' => 'Tones|Ringtones|World|North America',
        ),
        8332 => array(
            'Id' => 8332,
            'Label' => 'Tones|Ringtones|World|Polka',
        ),
        8333 => array(
            'Id' => 8333,
            'Label' => 'Tones|Ringtones|World|Russian',
        ),
        8334 => array(
            'Id' => 8334,
            'Label' => 'Tones|Ringtones|World|Russian Chanson',
        ),
        8335 => array(
            'Id' => 8335,
            'Label' => 'Tones|Ringtones|World|Sanat',
        ),
        8336 => array(
            'Id' => 8336,
            'Label' => 'Tones|Ringtones|World|Soca',
        ),
        8337 => array(
            'Id' => 8337,
            'Label' => 'Tones|Ringtones|World|South Africa',
        ),
        8338 => array(
            'Id' => 8338,
            'Label' => 'Tones|Ringtones|World|South America',
        ),
        8339 => array(
            'Id' => 8339,
            'Label' => 'Tones|Ringtones|World|Tango',
        ),
        8340 => array(
            'Id' => 8340,
            'Label' => 'Tones|Ringtones|World|Traditional Celtic',
        ),
        8341 => array(
            'Id' => 8341,
            'Label' => 'Tones|Ringtones|World|Turkish',
        ),
        8342 => array(
            'Id' => 8342,
            'Label' => 'Tones|Ringtones|World|Worldbeat',
        ),
        8343 => array(
            'Id' => 8343,
            'Label' => 'Tones|Ringtones|World|Zydeco',
        ),
        8345 => array(
            'Id' => 8345,
            'Label' => 'Tones|Ringtones|Classical|Art Song',
        ),
        8346 => array(
            'Id' => 8346,
            'Label' => 'Tones|Ringtones|Classical|Brass & Woodwinds',
        ),
        8347 => array(
            'Id' => 8347,
            'Label' => 'Tones|Ringtones|Classical|Solo Instrumental',
        ),
        8348 => array(
            'Id' => 8348,
            'Label' => 'Tones|Ringtones|Classical|Contemporary Era',
        ),
        8349 => array(
            'Id' => 8349,
            'Label' => 'Tones|Ringtones|Classical|Oratorio',
        ),
        8350 => array(
            'Id' => 8350,
            'Label' => 'Tones|Ringtones|Classical|Cantata',
        ),
        8351 => array(
            'Id' => 8351,
            'Label' => 'Tones|Ringtones|Classical|Electronic',
        ),
        8352 => array(
            'Id' => 8352,
            'Label' => 'Tones|Ringtones|Classical|Sacred',
        ),
        8353 => array(
            'Id' => 8353,
            'Label' => 'Tones|Ringtones|Classical|Guitar',
        ),
        8354 => array(
            'Id' => 8354,
            'Label' => 'Tones|Ringtones|Classical|Piano',
        ),
        8355 => array(
            'Id' => 8355,
            'Label' => 'Tones|Ringtones|Classical|Violin',
        ),
        8356 => array(
            'Id' => 8356,
            'Label' => 'Tones|Ringtones|Classical|Cello',
        ),
        8357 => array(
            'Id' => 8357,
            'Label' => 'Tones|Ringtones|Classical|Percussion',
        ),
        8358 => array(
            'Id' => 8358,
            'Label' => 'Tones|Ringtones|Electronic|Dubstep',
        ),
        8359 => array(
            'Id' => 8359,
            'Label' => 'Tones|Ringtones|Electronic|Bass',
        ),
        8360 => array(
            'Id' => 8360,
            'Label' => 'Tones|Ringtones|Hip-Hop/Rap|UK Hip Hop',
        ),
        8361 => array(
            'Id' => 8361,
            'Label' => 'Tones|Ringtones|Reggae|Lovers Rock',
        ),
        8362 => array(
            'Id' => 8362,
            'Label' => 'Tones|Ringtones|Alternative|EMO',
        ),
        8363 => array(
            'Id' => 8363,
            'Label' => 'Tones|Ringtones|Alternative|Pop Punk',
        ),
        8364 => array(
            'Id' => 8364,
            'Label' => 'Tones|Ringtones|Alternative|Indie Pop',
        ),
        8365 => array(
            'Id' => 8365,
            'Label' => 'Tones|Ringtones|New Age|Yoga',
        ),
        8366 => array(
            'Id' => 8366,
            'Label' => 'Tones|Ringtones|Pop|Tribute',
        ),
        9002 => array(
            'Id' => 9002,
            'Label' => 'Books|Nonfiction',
        ),
        9003 => array(
            'Id' => 9003,
            'Label' => 'Books|Romance',
        ),
        9004 => array(
            'Id' => 9004,
            'Label' => 'Books|Travel & Adventure',
        ),
        9007 => array(
            'Id' => 9007,
            'Label' => 'Books|Arts & Entertainment',
        ),
        9008 => array(
            'Id' => 9008,
            'Label' => 'Books|Biographies & Memoirs',
        ),
        9009 => array(
            'Id' => 9009,
            'Label' => 'Books|Business & Personal Finance',
        ),
        9010 => array(
            'Id' => 9010,
            'Label' => 'Books|Children & Teens',
        ),
        9012 => array(
            'Id' => 9012,
            'Label' => 'Books|Humor',
        ),
        9015 => array(
            'Id' => 9015,
            'Label' => 'Books|History',
        ),
        9018 => array(
            'Id' => 9018,
            'Label' => 'Books|Religion & Spirituality',
        ),
        9019 => array(
            'Id' => 9019,
            'Label' => 'Books|Science & Nature',
        ),
        9020 => array(
            'Id' => 9020,
            'Label' => 'Books|Sci-Fi & Fantasy',
        ),
        9024 => array(
            'Id' => 9024,
            'Label' => 'Books|Lifestyle & Home',
        ),
        9025 => array(
            'Id' => 9025,
            'Label' => 'Books|Health, Mind & Body',
        ),
        9026 => array(
            'Id' => 9026,
            'Label' => 'Books|Comics & Graphic Novels',
        ),
        9027 => array(
            'Id' => 9027,
            'Label' => 'Books|Computers & Internet',
        ),
        9028 => array(
            'Id' => 9028,
            'Label' => 'Books|Cookbooks, Food & Wine',
        ),
        9029 => array(
            'Id' => 9029,
            'Label' => 'Books|Professional & Technical',
        ),
        9030 => array(
            'Id' => 9030,
            'Label' => 'Books|Parenting',
        ),
        9031 => array(
            'Id' => 9031,
            'Label' => 'Books|Fiction & Literature',
        ),
        9032 => array(
            'Id' => 9032,
            'Label' => 'Books|Mysteries & Thrillers',
        ),
        9033 => array(
            'Id' => 9033,
            'Label' => 'Books|Reference',
        ),
        9034 => array(
            'Id' => 9034,
            'Label' => 'Books|Politics & Current Events',
        ),
        9035 => array(
            'Id' => 9035,
            'Label' => 'Books|Sports & Outdoors',
        ),
        10001 => array(
            'Id' => 10001,
            'Label' => 'Books|Lifestyle & Home|Antiques & Collectibles',
        ),
        10002 => array(
            'Id' => 10002,
            'Label' => 'Books|Arts & Entertainment|Art & Architecture',
        ),
        10003 => array(
            'Id' => 10003,
            'Label' => 'Books|Religion & Spirituality|Bibles',
        ),
        10004 => array(
            'Id' => 10004,
            'Label' => 'Books|Health, Mind & Body|Spirituality',
        ),
        10005 => array(
            'Id' => 10005,
            'Label' => 'Books|Business & Personal Finance|Industries & Professions',
        ),
        10006 => array(
            'Id' => 10006,
            'Label' => 'Books|Business & Personal Finance|Marketing & Sales',
        ),
        10007 => array(
            'Id' => 10007,
            'Label' => 'Books|Business & Personal Finance|Small Business & Entrepreneurship',
        ),
        10008 => array(
            'Id' => 10008,
            'Label' => 'Books|Business & Personal Finance|Personal Finance',
        ),
        10009 => array(
            'Id' => 10009,
            'Label' => 'Books|Business & Personal Finance|Reference',
        ),
        10010 => array(
            'Id' => 10010,
            'Label' => 'Books|Business & Personal Finance|Careers',
        ),
        10011 => array(
            'Id' => 10011,
            'Label' => 'Books|Business & Personal Finance|Economics',
        ),
        10012 => array(
            'Id' => 10012,
            'Label' => 'Books|Business & Personal Finance|Investing',
        ),
        10013 => array(
            'Id' => 10013,
            'Label' => 'Books|Business & Personal Finance|Finance',
        ),
        10014 => array(
            'Id' => 10014,
            'Label' => 'Books|Business & Personal Finance|Management & Leadership',
        ),
        10015 => array(
            'Id' => 10015,
            'Label' => 'Books|Comics & Graphic Novels|Graphic Novels',
        ),
        10016 => array(
            'Id' => 10016,
            'Label' => 'Books|Comics & Graphic Novels|Manga',
        ),
        10017 => array(
            'Id' => 10017,
            'Label' => 'Books|Computers & Internet|Computers',
        ),
        10018 => array(
            'Id' => 10018,
            'Label' => 'Books|Computers & Internet|Databases',
        ),
        10019 => array(
            'Id' => 10019,
            'Label' => 'Books|Computers & Internet|Digital Media',
        ),
        10020 => array(
            'Id' => 10020,
            'Label' => 'Books|Computers & Internet|Internet',
        ),
        10021 => array(
            'Id' => 10021,
            'Label' => 'Books|Computers & Internet|Network',
        ),
        10022 => array(
            'Id' => 10022,
            'Label' => 'Books|Computers & Internet|Operating Systems',
        ),
        10023 => array(
            'Id' => 10023,
            'Label' => 'Books|Computers & Internet|Programming',
        ),
        10024 => array(
            'Id' => 10024,
            'Label' => 'Books|Computers & Internet|Software',
        ),
        10025 => array(
            'Id' => 10025,
            'Label' => 'Books|Computers & Internet|System Administration',
        ),
        10026 => array(
            'Id' => 10026,
            'Label' => 'Books|Cookbooks, Food & Wine|Beverages',
        ),
        10027 => array(
            'Id' => 10027,
            'Label' => 'Books|Cookbooks, Food & Wine|Courses & Dishes',
        ),
        10028 => array(
            'Id' => 10028,
            'Label' => 'Books|Cookbooks, Food & Wine|Special Diet',
        ),
        10029 => array(
            'Id' => 10029,
            'Label' => 'Books|Cookbooks, Food & Wine|Special Occasions',
        ),
        10030 => array(
            'Id' => 10030,
            'Label' => 'Books|Cookbooks, Food & Wine|Methods',
        ),
        10031 => array(
            'Id' => 10031,
            'Label' => 'Books|Cookbooks, Food & Wine|Reference',
        ),
        10032 => array(
            'Id' => 10032,
            'Label' => 'Books|Cookbooks, Food & Wine|Regional & Ethnic',
        ),
        10033 => array(
            'Id' => 10033,
            'Label' => 'Books|Cookbooks, Food & Wine|Specific Ingredients',
        ),
        10034 => array(
            'Id' => 10034,
            'Label' => 'Books|Lifestyle & Home|Crafts & Hobbies',
        ),
        10035 => array(
            'Id' => 10035,
            'Label' => 'Books|Professional & Technical|Design',
        ),
        10036 => array(
            'Id' => 10036,
            'Label' => 'Books|Arts & Entertainment|Theater',
        ),
        10037 => array(
            'Id' => 10037,
            'Label' => 'Books|Professional & Technical|Education',
        ),
        10038 => array(
            'Id' => 10038,
            'Label' => 'Books|Nonfiction|Family & Relationships',
        ),
        10039 => array(
            'Id' => 10039,
            'Label' => 'Books|Fiction & Literature|Action & Adventure',
        ),
        10040 => array(
            'Id' => 10040,
            'Label' => 'Books|Fiction & Literature|African American',
        ),
        10041 => array(
            'Id' => 10041,
            'Label' => 'Books|Fiction & Literature|Religious',
        ),
        10042 => array(
            'Id' => 10042,
            'Label' => 'Books|Fiction & Literature|Classics',
        ),
        10043 => array(
            'Id' => 10043,
            'Label' => 'Books|Fiction & Literature|Erotica',
        ),
        10044 => array(
            'Id' => 10044,
            'Label' => 'Books|Sci-Fi & Fantasy|Fantasy',
        ),
        10045 => array(
            'Id' => 10045,
            'Label' => 'Books|Fiction & Literature|Gay',
        ),
        10046 => array(
            'Id' => 10046,
            'Label' => 'Books|Fiction & Literature|Ghost',
        ),
        10047 => array(
            'Id' => 10047,
            'Label' => 'Books|Fiction & Literature|Historical',
        ),
        10048 => array(
            'Id' => 10048,
            'Label' => 'Books|Fiction & Literature|Horror',
        ),
        10049 => array(
            'Id' => 10049,
            'Label' => 'Books|Fiction & Literature|Literary',
        ),
        10050 => array(
            'Id' => 10050,
            'Label' => 'Books|Mysteries & Thrillers|Hard-Boiled',
        ),
        10051 => array(
            'Id' => 10051,
            'Label' => 'Books|Mysteries & Thrillers|Historical',
        ),
        10052 => array(
            'Id' => 10052,
            'Label' => 'Books|Mysteries & Thrillers|Police Procedural',
        ),
        10053 => array(
            'Id' => 10053,
            'Label' => 'Books|Mysteries & Thrillers|Short Stories',
        ),
        10054 => array(
            'Id' => 10054,
            'Label' => 'Books|Mysteries & Thrillers|British Detectives',
        ),
        10055 => array(
            'Id' => 10055,
            'Label' => 'Books|Mysteries & Thrillers|Women Sleuths',
        ),
        10056 => array(
            'Id' => 10056,
            'Label' => 'Books|Romance|Erotic Romance',
        ),
        10057 => array(
            'Id' => 10057,
            'Label' => 'Books|Romance|Contemporary',
        ),
        10058 => array(
            'Id' => 10058,
            'Label' => 'Books|Romance|Paranormal',
        ),
        10059 => array(
            'Id' => 10059,
            'Label' => 'Books|Romance|Historical',
        ),
        10060 => array(
            'Id' => 10060,
            'Label' => 'Books|Romance|Short Stories',
        ),
        10061 => array(
            'Id' => 10061,
            'Label' => 'Books|Romance|Suspense',
        ),
        10062 => array(
            'Id' => 10062,
            'Label' => 'Books|Romance|Western',
        ),
        10063 => array(
            'Id' => 10063,
            'Label' => 'Books|Sci-Fi & Fantasy|Science Fiction',
        ),
        10064 => array(
            'Id' => 10064,
            'Label' => 'Books|Sci-Fi & Fantasy|Science Fiction & Literature',
        ),
        10065 => array(
            'Id' => 10065,
            'Label' => 'Books|Fiction & Literature|Short Stories',
        ),
        10066 => array(
            'Id' => 10066,
            'Label' => 'Books|Reference|Foreign Languages',
        ),
        10067 => array(
            'Id' => 10067,
            'Label' => 'Books|Arts & Entertainment|Games',
        ),
        10068 => array(
            'Id' => 10068,
            'Label' => 'Books|Lifestyle & Home|Gardening',
        ),
        10069 => array(
            'Id' => 10069,
            'Label' => 'Books|Health, Mind & Body|Health & Fitness',
        ),
        10070 => array(
            'Id' => 10070,
            'Label' => 'Books|History|Africa',
        ),
        10071 => array(
            'Id' => 10071,
            'Label' => 'Books|History|Americas',
        ),
        10072 => array(
            'Id' => 10072,
            'Label' => 'Books|History|Ancient',
        ),
        10073 => array(
            'Id' => 10073,
            'Label' => 'Books|History|Asia',
        ),
        10074 => array(
            'Id' => 10074,
            'Label' => 'Books|History|Australia & Oceania',
        ),
        10075 => array(
            'Id' => 10075,
            'Label' => 'Books|History|Europe',
        ),
        10076 => array(
            'Id' => 10076,
            'Label' => 'Books|History|Latin America',
        ),
        10077 => array(
            'Id' => 10077,
            'Label' => 'Books|History|Middle East',
        ),
        10078 => array(
            'Id' => 10078,
            'Label' => 'Books|History|Military',
        ),
        10079 => array(
            'Id' => 10079,
            'Label' => 'Books|History|United States',
        ),
        10080 => array(
            'Id' => 10080,
            'Label' => 'Books|History|World',
        ),
        10081 => array(
            'Id' => 10081,
            'Label' => 'Books|Children & Teens|Children\'s Fiction',
        ),
        10082 => array(
            'Id' => 10082,
            'Label' => 'Books|Children & Teens|Children\'s Nonfiction',
        ),
        10083 => array(
            'Id' => 10083,
            'Label' => 'Books|Professional & Technical|Law',
        ),
        10084 => array(
            'Id' => 10084,
            'Label' => 'Books|Fiction & Literature|Literary Criticism',
        ),
        10085 => array(
            'Id' => 10085,
            'Label' => 'Books|Science & Nature|Mathematics',
        ),
        10086 => array(
            'Id' => 10086,
            'Label' => 'Books|Professional & Technical|Medical',
        ),
        10087 => array(
            'Id' => 10087,
            'Label' => 'Books|Arts & Entertainment|Music',
        ),
        10088 => array(
            'Id' => 10088,
            'Label' => 'Books|Science & Nature|Nature',
        ),
        10089 => array(
            'Id' => 10089,
            'Label' => 'Books|Arts & Entertainment|Performing Arts',
        ),
        10090 => array(
            'Id' => 10090,
            'Label' => 'Books|Lifestyle & Home|Pets',
        ),
        10091 => array(
            'Id' => 10091,
            'Label' => 'Books|Nonfiction|Philosophy',
        ),
        10092 => array(
            'Id' => 10092,
            'Label' => 'Books|Arts & Entertainment|Photography',
        ),
        10093 => array(
            'Id' => 10093,
            'Label' => 'Books|Fiction & Literature|Poetry',
        ),
        10094 => array(
            'Id' => 10094,
            'Label' => 'Books|Health, Mind & Body|Psychology',
        ),
        10095 => array(
            'Id' => 10095,
            'Label' => 'Books|Reference|Almanacs & Yearbooks',
        ),
        10096 => array(
            'Id' => 10096,
            'Label' => 'Books|Reference|Atlases & Maps',
        ),
        10097 => array(
            'Id' => 10097,
            'Label' => 'Books|Reference|Catalogs & Directories',
        ),
        10098 => array(
            'Id' => 10098,
            'Label' => 'Books|Reference|Consumer Guides',
        ),
        10099 => array(
            'Id' => 10099,
            'Label' => 'Books|Reference|Dictionaries & Thesauruses',
        ),
        10100 => array(
            'Id' => 10100,
            'Label' => 'Books|Reference|Encyclopedias',
        ),
        10101 => array(
            'Id' => 10101,
            'Label' => 'Books|Reference|Etiquette',
        ),
        10102 => array(
            'Id' => 10102,
            'Label' => 'Books|Reference|Quotations',
        ),
        10103 => array(
            'Id' => 10103,
            'Label' => 'Books|Reference|Words & Language',
        ),
        10104 => array(
            'Id' => 10104,
            'Label' => 'Books|Reference|Writing',
        ),
        10105 => array(
            'Id' => 10105,
            'Label' => 'Books|Religion & Spirituality|Bible Studies',
        ),
        10106 => array(
            'Id' => 10106,
            'Label' => 'Books|Religion & Spirituality|Buddhism',
        ),
        10107 => array(
            'Id' => 10107,
            'Label' => 'Books|Religion & Spirituality|Christianity',
        ),
        10108 => array(
            'Id' => 10108,
            'Label' => 'Books|Religion & Spirituality|Hinduism',
        ),
        10109 => array(
            'Id' => 10109,
            'Label' => 'Books|Religion & Spirituality|Islam',
        ),
        10110 => array(
            'Id' => 10110,
            'Label' => 'Books|Religion & Spirituality|Judaism',
        ),
        10111 => array(
            'Id' => 10111,
            'Label' => 'Books|Science & Nature|Astronomy',
        ),
        10112 => array(
            'Id' => 10112,
            'Label' => 'Books|Science & Nature|Chemistry',
        ),
        10113 => array(
            'Id' => 10113,
            'Label' => 'Books|Science & Nature|Earth Sciences',
        ),
        10114 => array(
            'Id' => 10114,
            'Label' => 'Books|Science & Nature|Essays',
        ),
        10115 => array(
            'Id' => 10115,
            'Label' => 'Books|Science & Nature|History',
        ),
        10116 => array(
            'Id' => 10116,
            'Label' => 'Books|Science & Nature|Life Sciences',
        ),
        10117 => array(
            'Id' => 10117,
            'Label' => 'Books|Science & Nature|Physics',
        ),
        10118 => array(
            'Id' => 10118,
            'Label' => 'Books|Science & Nature|Reference',
        ),
        10119 => array(
            'Id' => 10119,
            'Label' => 'Books|Health, Mind & Body|Self-Improvement',
        ),
        10120 => array(
            'Id' => 10120,
            'Label' => 'Books|Nonfiction|Social Science',
        ),
        10121 => array(
            'Id' => 10121,
            'Label' => 'Books|Sports & Outdoors|Baseball',
        ),
        10122 => array(
            'Id' => 10122,
            'Label' => 'Books|Sports & Outdoors|Basketball',
        ),
        10123 => array(
            'Id' => 10123,
            'Label' => 'Books|Sports & Outdoors|Coaching',
        ),
        10124 => array(
            'Id' => 10124,
            'Label' => 'Books|Sports & Outdoors|Extreme Sports',
        ),
        10125 => array(
            'Id' => 10125,
            'Label' => 'Books|Sports & Outdoors|Football',
        ),
        10126 => array(
            'Id' => 10126,
            'Label' => 'Books|Sports & Outdoors|Golf',
        ),
        10127 => array(
            'Id' => 10127,
            'Label' => 'Books|Sports & Outdoors|Hockey',
        ),
        10128 => array(
            'Id' => 10128,
            'Label' => 'Books|Sports & Outdoors|Mountaineering',
        ),
        10129 => array(
            'Id' => 10129,
            'Label' => 'Books|Sports & Outdoors|Outdoors',
        ),
        10130 => array(
            'Id' => 10130,
            'Label' => 'Books|Sports & Outdoors|Racket Sports',
        ),
        10131 => array(
            'Id' => 10131,
            'Label' => 'Books|Sports & Outdoors|Reference',
        ),
        10132 => array(
            'Id' => 10132,
            'Label' => 'Books|Sports & Outdoors|Soccer',
        ),
        10133 => array(
            'Id' => 10133,
            'Label' => 'Books|Sports & Outdoors|Training',
        ),
        10134 => array(
            'Id' => 10134,
            'Label' => 'Books|Sports & Outdoors|Water Sports',
        ),
        10135 => array(
            'Id' => 10135,
            'Label' => 'Books|Sports & Outdoors|Winter Sports',
        ),
        10136 => array(
            'Id' => 10136,
            'Label' => 'Books|Reference|Study Aids',
        ),
        10137 => array(
            'Id' => 10137,
            'Label' => 'Books|Professional & Technical|Engineering',
        ),
        10138 => array(
            'Id' => 10138,
            'Label' => 'Books|Nonfiction|Transportation',
        ),
        10139 => array(
            'Id' => 10139,
            'Label' => 'Books|Travel & Adventure|Africa',
        ),
        10140 => array(
            'Id' => 10140,
            'Label' => 'Books|Travel & Adventure|Asia',
        ),
        10141 => array(
            'Id' => 10141,
            'Label' => 'Books|Travel & Adventure|Specialty Travel',
        ),
        10142 => array(
            'Id' => 10142,
            'Label' => 'Books|Travel & Adventure|Canada',
        ),
        10143 => array(
            'Id' => 10143,
            'Label' => 'Books|Travel & Adventure|Caribbean',
        ),
        10144 => array(
            'Id' => 10144,
            'Label' => 'Books|Travel & Adventure|Latin America',
        ),
        10145 => array(
            'Id' => 10145,
            'Label' => 'Books|Travel & Adventure|Essays & Memoirs',
        ),
        10146 => array(
            'Id' => 10146,
            'Label' => 'Books|Travel & Adventure|Europe',
        ),
        10147 => array(
            'Id' => 10147,
            'Label' => 'Books|Travel & Adventure|Middle East',
        ),
        10148 => array(
            'Id' => 10148,
            'Label' => 'Books|Travel & Adventure|United States',
        ),
        10149 => array(
            'Id' => 10149,
            'Label' => 'Books|Nonfiction|True Crime',
        ),
        11001 => array(
            'Id' => 11001,
            'Label' => 'Books|Sci-Fi & Fantasy|Fantasy|Contemporary',
        ),
        11002 => array(
            'Id' => 11002,
            'Label' => 'Books|Sci-Fi & Fantasy|Fantasy|Epic',
        ),
        11003 => array(
            'Id' => 11003,
            'Label' => 'Books|Sci-Fi & Fantasy|Fantasy|Historical',
        ),
        11004 => array(
            'Id' => 11004,
            'Label' => 'Books|Sci-Fi & Fantasy|Fantasy|Paranormal',
        ),
        11005 => array(
            'Id' => 11005,
            'Label' => 'Books|Sci-Fi & Fantasy|Fantasy|Short Stories',
        ),
        11006 => array(
            'Id' => 11006,
            'Label' => 'Books|Sci-Fi & Fantasy|Science Fiction & Literature|Adventure',
        ),
        11007 => array(
            'Id' => 11007,
            'Label' => 'Books|Sci-Fi & Fantasy|Science Fiction & Literature|High Tech',
        ),
        11008 => array(
            'Id' => 11008,
            'Label' => 'Books|Sci-Fi & Fantasy|Science Fiction & Literature|Short Stories',
        ),
        11009 => array(
            'Id' => 11009,
            'Label' => 'Books|Professional & Technical|Education|Language Arts & Disciplines',
        ),
        11010 => array(
            'Id' => 11010,
            'Label' => 'Books|Communications & Media',
        ),
        11011 => array(
            'Id' => 11011,
            'Label' => 'Books|Communications & Media|Broadcasting',
        ),
        11012 => array(
            'Id' => 11012,
            'Label' => 'Books|Communications & Media|Digital Media',
        ),
        11013 => array(
            'Id' => 11013,
            'Label' => 'Books|Communications & Media|Journalism',
        ),
        11014 => array(
            'Id' => 11014,
            'Label' => 'Books|Communications & Media|Photojournalism',
        ),
        11015 => array(
            'Id' => 11015,
            'Label' => 'Books|Communications & Media|Print',
        ),
        11016 => array(
            'Id' => 11016,
            'Label' => 'Books|Communications & Media|Speech',
        ),
        11017 => array(
            'Id' => 11017,
            'Label' => 'Books|Communications & Media|Writing',
        ),
        11018 => array(
            'Id' => 11018,
            'Label' => 'Books|Arts & Entertainment|Art & Architecture|Urban Planning',
        ),
        11019 => array(
            'Id' => 11019,
            'Label' => 'Books|Arts & Entertainment|Dance',
        ),
        11020 => array(
            'Id' => 11020,
            'Label' => 'Books|Arts & Entertainment|Fashion',
        ),
        11021 => array(
            'Id' => 11021,
            'Label' => 'Books|Arts & Entertainment|Film',
        ),
        11022 => array(
            'Id' => 11022,
            'Label' => 'Books|Arts & Entertainment|Interior Design',
        ),
        11023 => array(
            'Id' => 11023,
            'Label' => 'Books|Arts & Entertainment|Media Arts',
        ),
        11024 => array(
            'Id' => 11024,
            'Label' => 'Books|Arts & Entertainment|Radio',
        ),
        11025 => array(
            'Id' => 11025,
            'Label' => 'Books|Arts & Entertainment|TV',
        ),
        11026 => array(
            'Id' => 11026,
            'Label' => 'Books|Arts & Entertainment|Visual Arts',
        ),
        11027 => array(
            'Id' => 11027,
            'Label' => 'Books|Biographies & Memoirs|Arts & Entertainment',
        ),
        11028 => array(
            'Id' => 11028,
            'Label' => 'Books|Biographies & Memoirs|Business',
        ),
        11029 => array(
            'Id' => 11029,
            'Label' => 'Books|Biographies & Memoirs|Culinary',
        ),
        11030 => array(
            'Id' => 11030,
            'Label' => 'Books|Biographies & Memoirs|Gay & Lesbian',
        ),
        11031 => array(
            'Id' => 11031,
            'Label' => 'Books|Biographies & Memoirs|Historical',
        ),
        11032 => array(
            'Id' => 11032,
            'Label' => 'Books|Biographies & Memoirs|Literary',
        ),
        11033 => array(
            'Id' => 11033,
            'Label' => 'Books|Biographies & Memoirs|Media & Journalism',
        ),
        11034 => array(
            'Id' => 11034,
            'Label' => 'Books|Biographies & Memoirs|Military',
        ),
        11035 => array(
            'Id' => 11035,
            'Label' => 'Books|Biographies & Memoirs|Politics',
        ),
        11036 => array(
            'Id' => 11036,
            'Label' => 'Books|Biographies & Memoirs|Religious',
        ),
        11037 => array(
            'Id' => 11037,
            'Label' => 'Books|Biographies & Memoirs|Science & Technology',
        ),
        11038 => array(
            'Id' => 11038,
            'Label' => 'Books|Biographies & Memoirs|Sports',
        ),
        11039 => array(
            'Id' => 11039,
            'Label' => 'Books|Biographies & Memoirs|Women',
        ),
        11040 => array(
            'Id' => 11040,
            'Label' => 'Books|Romance|New Adult',
        ),
        11042 => array(
            'Id' => 11042,
            'Label' => 'Books|Romance|Romantic Comedy',
        ),
        11043 => array(
            'Id' => 11043,
            'Label' => 'Books|Romance|Gay & Lesbian',
        ),
        11044 => array(
            'Id' => 11044,
            'Label' => 'Books|Fiction & Literature|Essays',
        ),
        11045 => array(
            'Id' => 11045,
            'Label' => 'Books|Fiction & Literature|Anthologies',
        ),
        11046 => array(
            'Id' => 11046,
            'Label' => 'Books|Fiction & Literature|Comparative Literature',
        ),
        11047 => array(
            'Id' => 11047,
            'Label' => 'Books|Fiction & Literature|Drama',
        ),
        11049 => array(
            'Id' => 11049,
            'Label' => 'Books|Fiction & Literature|Fairy Tales, Myths & Fables',
        ),
        11050 => array(
            'Id' => 11050,
            'Label' => 'Books|Fiction & Literature|Family',
        ),
        11051 => array(
            'Id' => 11051,
            'Label' => 'Books|Comics & Graphic Novels|Manga|School Drama',
        ),
        11052 => array(
            'Id' => 11052,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Human Drama',
        ),
        11053 => array(
            'Id' => 11053,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Family Drama',
        ),
        11054 => array(
            'Id' => 11054,
            'Label' => 'Books|Sports & Outdoors|Boxing',
        ),
        11055 => array(
            'Id' => 11055,
            'Label' => 'Books|Sports & Outdoors|Cricket',
        ),
        11056 => array(
            'Id' => 11056,
            'Label' => 'Books|Sports & Outdoors|Cycling',
        ),
        11057 => array(
            'Id' => 11057,
            'Label' => 'Books|Sports & Outdoors|Equestrian',
        ),
        11058 => array(
            'Id' => 11058,
            'Label' => 'Books|Sports & Outdoors|Martial Arts & Self Defense',
        ),
        11059 => array(
            'Id' => 11059,
            'Label' => 'Books|Sports & Outdoors|Motor Sports',
        ),
        11060 => array(
            'Id' => 11060,
            'Label' => 'Books|Sports & Outdoors|Rugby',
        ),
        11061 => array(
            'Id' => 11061,
            'Label' => 'Books|Sports & Outdoors|Running',
        ),
        11062 => array(
            'Id' => 11062,
            'Label' => 'Books|Health, Mind & Body|Diet & Nutrition',
        ),
        11063 => array(
            'Id' => 11063,
            'Label' => 'Books|Science & Nature|Agriculture',
        ),
        11064 => array(
            'Id' => 11064,
            'Label' => 'Books|Science & Nature|Atmosphere',
        ),
        11065 => array(
            'Id' => 11065,
            'Label' => 'Books|Science & Nature|Biology',
        ),
        11066 => array(
            'Id' => 11066,
            'Label' => 'Books|Science & Nature|Ecology',
        ),
        11067 => array(
            'Id' => 11067,
            'Label' => 'Books|Science & Nature|Environment',
        ),
        11068 => array(
            'Id' => 11068,
            'Label' => 'Books|Science & Nature|Geography',
        ),
        11069 => array(
            'Id' => 11069,
            'Label' => 'Books|Science & Nature|Geology',
        ),
        11070 => array(
            'Id' => 11070,
            'Label' => 'Books|Nonfiction|Social Science|Anthropology',
        ),
        11071 => array(
            'Id' => 11071,
            'Label' => 'Books|Nonfiction|Social Science|Archaeology',
        ),
        11072 => array(
            'Id' => 11072,
            'Label' => 'Books|Nonfiction|Social Science|Civics',
        ),
        11073 => array(
            'Id' => 11073,
            'Label' => 'Books|Nonfiction|Social Science|Government',
        ),
        11074 => array(
            'Id' => 11074,
            'Label' => 'Books|Nonfiction|Social Science|Social Studies',
        ),
        11075 => array(
            'Id' => 11075,
            'Label' => 'Books|Nonfiction|Social Science|Social Welfare',
        ),
        11076 => array(
            'Id' => 11076,
            'Label' => 'Books|Nonfiction|Social Science|Society',
        ),
        11077 => array(
            'Id' => 11077,
            'Label' => 'Books|Nonfiction|Philosophy|Aesthetics',
        ),
        11078 => array(
            'Id' => 11078,
            'Label' => 'Books|Nonfiction|Philosophy|Epistemology',
        ),
        11079 => array(
            'Id' => 11079,
            'Label' => 'Books|Nonfiction|Philosophy|Ethics',
        ),
        11080 => array(
            'Id' => 11080,
            'Label' => 'Books|Nonfiction|Philosophy|Language',
        ),
        11081 => array(
            'Id' => 11081,
            'Label' => 'Books|Nonfiction|Philosophy|Logic',
        ),
        11082 => array(
            'Id' => 11082,
            'Label' => 'Books|Nonfiction|Philosophy|Metaphysics',
        ),
        11083 => array(
            'Id' => 11083,
            'Label' => 'Books|Nonfiction|Philosophy|Political',
        ),
        11084 => array(
            'Id' => 11084,
            'Label' => 'Books|Nonfiction|Philosophy|Religion',
        ),
        11085 => array(
            'Id' => 11085,
            'Label' => 'Books|Reference|Manuals',
        ),
        11086 => array(
            'Id' => 11086,
            'Label' => 'Books|Kids',
        ),
        11087 => array(
            'Id' => 11087,
            'Label' => 'Books|Kids|Animals',
        ),
        11088 => array(
            'Id' => 11088,
            'Label' => 'Books|Kids|Basic Concepts',
        ),
        11089 => array(
            'Id' => 11089,
            'Label' => 'Books|Kids|Basic Concepts|Alphabet',
        ),
        11090 => array(
            'Id' => 11090,
            'Label' => 'Books|Kids|Basic Concepts|Body',
        ),
        11091 => array(
            'Id' => 11091,
            'Label' => 'Books|Kids|Basic Concepts|Colors',
        ),
        11092 => array(
            'Id' => 11092,
            'Label' => 'Books|Kids|Basic Concepts|Counting & Numbers',
        ),
        11093 => array(
            'Id' => 11093,
            'Label' => 'Books|Kids|Basic Concepts|Date & Time',
        ),
        11094 => array(
            'Id' => 11094,
            'Label' => 'Books|Kids|Basic Concepts|General',
        ),
        11095 => array(
            'Id' => 11095,
            'Label' => 'Books|Kids|Basic Concepts|Money',
        ),
        11096 => array(
            'Id' => 11096,
            'Label' => 'Books|Kids|Basic Concepts|Opposites',
        ),
        11097 => array(
            'Id' => 11097,
            'Label' => 'Books|Kids|Basic Concepts|Seasons',
        ),
        11098 => array(
            'Id' => 11098,
            'Label' => 'Books|Kids|Basic Concepts|Senses & Sensation',
        ),
        11099 => array(
            'Id' => 11099,
            'Label' => 'Books|Kids|Basic Concepts|Size & Shape',
        ),
        11100 => array(
            'Id' => 11100,
            'Label' => 'Books|Kids|Basic Concepts|Sounds',
        ),
        11101 => array(
            'Id' => 11101,
            'Label' => 'Books|Kids|Basic Concepts|Words',
        ),
        11102 => array(
            'Id' => 11102,
            'Label' => 'Books|Kids|Biography',
        ),
        11103 => array(
            'Id' => 11103,
            'Label' => 'Books|Kids|Careers & Occupations',
        ),
        11104 => array(
            'Id' => 11104,
            'Label' => 'Books|Kids|Computers & Technology',
        ),
        11105 => array(
            'Id' => 11105,
            'Label' => 'Books|Kids|Cooking & Food',
        ),
        11106 => array(
            'Id' => 11106,
            'Label' => 'Books|Kids|Arts & Entertainment',
        ),
        11107 => array(
            'Id' => 11107,
            'Label' => 'Books|Kids|Arts & Entertainment|Art',
        ),
        11108 => array(
            'Id' => 11108,
            'Label' => 'Books|Kids|Arts & Entertainment|Crafts',
        ),
        11109 => array(
            'Id' => 11109,
            'Label' => 'Books|Kids|Arts & Entertainment|Music',
        ),
        11110 => array(
            'Id' => 11110,
            'Label' => 'Books|Kids|Arts & Entertainment|Performing Arts',
        ),
        11111 => array(
            'Id' => 11111,
            'Label' => 'Books|Kids|Family',
        ),
        11112 => array(
            'Id' => 11112,
            'Label' => 'Books|Kids|Fiction',
        ),
        11113 => array(
            'Id' => 11113,
            'Label' => 'Books|Kids|Fiction|Action & Adventure',
        ),
        11114 => array(
            'Id' => 11114,
            'Label' => 'Books|Kids|Fiction|Animals',
        ),
        11115 => array(
            'Id' => 11115,
            'Label' => 'Books|Kids|Fiction|Classics',
        ),
        11116 => array(
            'Id' => 11116,
            'Label' => 'Books|Kids|Fiction|Comics & Graphic Novels',
        ),
        11117 => array(
            'Id' => 11117,
            'Label' => 'Books|Kids|Fiction|Culture, Places & People',
        ),
        11118 => array(
            'Id' => 11118,
            'Label' => 'Books|Kids|Fiction|Family & Relationships',
        ),
        11119 => array(
            'Id' => 11119,
            'Label' => 'Books|Kids|Fiction|Fantasy',
        ),
        11120 => array(
            'Id' => 11120,
            'Label' => 'Books|Kids|Fiction|Fairy Tales, Myths & Fables',
        ),
        11121 => array(
            'Id' => 11121,
            'Label' => 'Books|Kids|Fiction|Favorite Characters',
        ),
        11122 => array(
            'Id' => 11122,
            'Label' => 'Books|Kids|Fiction|Historical',
        ),
        11123 => array(
            'Id' => 11123,
            'Label' => 'Books|Kids|Fiction|Holidays & Celebrations',
        ),
        11124 => array(
            'Id' => 11124,
            'Label' => 'Books|Kids|Fiction|Monsters & Ghosts',
        ),
        11125 => array(
            'Id' => 11125,
            'Label' => 'Books|Kids|Fiction|Mysteries',
        ),
        11126 => array(
            'Id' => 11126,
            'Label' => 'Books|Kids|Fiction|Nature',
        ),
        11127 => array(
            'Id' => 11127,
            'Label' => 'Books|Kids|Fiction|Religion',
        ),
        11128 => array(
            'Id' => 11128,
            'Label' => 'Books|Kids|Fiction|Sci-Fi',
        ),
        11129 => array(
            'Id' => 11129,
            'Label' => 'Books|Kids|Fiction|Social Issues',
        ),
        11130 => array(
            'Id' => 11130,
            'Label' => 'Books|Kids|Fiction|Sports & Recreation',
        ),
        11131 => array(
            'Id' => 11131,
            'Label' => 'Books|Kids|Fiction|Transportation',
        ),
        11132 => array(
            'Id' => 11132,
            'Label' => 'Books|Kids|Games & Activities',
        ),
        11133 => array(
            'Id' => 11133,
            'Label' => 'Books|Kids|General Nonfiction',
        ),
        11134 => array(
            'Id' => 11134,
            'Label' => 'Books|Kids|Health',
        ),
        11135 => array(
            'Id' => 11135,
            'Label' => 'Books|Kids|History',
        ),
        11136 => array(
            'Id' => 11136,
            'Label' => 'Books|Kids|Holidays & Celebrations',
        ),
        11137 => array(
            'Id' => 11137,
            'Label' => 'Books|Kids|Holidays & Celebrations|Birthdays',
        ),
        11138 => array(
            'Id' => 11138,
            'Label' => 'Books|Kids|Holidays & Celebrations|Christmas & Advent',
        ),
        11139 => array(
            'Id' => 11139,
            'Label' => 'Books|Kids|Holidays & Celebrations|Easter & Lent',
        ),
        11140 => array(
            'Id' => 11140,
            'Label' => 'Books|Kids|Holidays & Celebrations|General',
        ),
        11141 => array(
            'Id' => 11141,
            'Label' => 'Books|Kids|Holidays & Celebrations|Halloween',
        ),
        11142 => array(
            'Id' => 11142,
            'Label' => 'Books|Kids|Holidays & Celebrations|Hanukkah',
        ),
        11143 => array(
            'Id' => 11143,
            'Label' => 'Books|Kids|Holidays & Celebrations|Other',
        ),
        11144 => array(
            'Id' => 11144,
            'Label' => 'Books|Kids|Holidays & Celebrations|Passover',
        ),
        11145 => array(
            'Id' => 11145,
            'Label' => 'Books|Kids|Holidays & Celebrations|Patriotic Holidays',
        ),
        11146 => array(
            'Id' => 11146,
            'Label' => 'Books|Kids|Holidays & Celebrations|Ramadan',
        ),
        11147 => array(
            'Id' => 11147,
            'Label' => 'Books|Kids|Holidays & Celebrations|Thanksgiving',
        ),
        11148 => array(
            'Id' => 11148,
            'Label' => 'Books|Kids|Holidays & Celebrations|Valentine\'s Day',
        ),
        11149 => array(
            'Id' => 11149,
            'Label' => 'Books|Kids|Humor',
        ),
        11150 => array(
            'Id' => 11150,
            'Label' => 'Books|Kids|Humor|Jokes & Riddles',
        ),
        11151 => array(
            'Id' => 11151,
            'Label' => 'Books|Kids|Poetry',
        ),
        11152 => array(
            'Id' => 11152,
            'Label' => 'Books|Kids|Learning to Read',
        ),
        11153 => array(
            'Id' => 11153,
            'Label' => 'Books|Kids|Learning to Read|Chapter Books',
        ),
        11154 => array(
            'Id' => 11154,
            'Label' => 'Books|Kids|Learning to Read|Early Readers',
        ),
        11155 => array(
            'Id' => 11155,
            'Label' => 'Books|Kids|Learning to Read|Intermediate Readers',
        ),
        11156 => array(
            'Id' => 11156,
            'Label' => 'Books|Kids|Nursery Rhymes',
        ),
        11157 => array(
            'Id' => 11157,
            'Label' => 'Books|Kids|Government',
        ),
        11158 => array(
            'Id' => 11158,
            'Label' => 'Books|Kids|Reference',
        ),
        11159 => array(
            'Id' => 11159,
            'Label' => 'Books|Kids|Religion',
        ),
        11160 => array(
            'Id' => 11160,
            'Label' => 'Books|Kids|Science & Nature',
        ),
        11161 => array(
            'Id' => 11161,
            'Label' => 'Books|Kids|Social Issues',
        ),
        11162 => array(
            'Id' => 11162,
            'Label' => 'Books|Kids|Social Studies',
        ),
        11163 => array(
            'Id' => 11163,
            'Label' => 'Books|Kids|Sports & Recreation',
        ),
        11164 => array(
            'Id' => 11164,
            'Label' => 'Books|Kids|Transportation',
        ),
        11165 => array(
            'Id' => 11165,
            'Label' => 'Books|Young Adult',
        ),
        11166 => array(
            'Id' => 11166,
            'Label' => 'Books|Young Adult|Animals',
        ),
        11167 => array(
            'Id' => 11167,
            'Label' => 'Books|Young Adult|Biography',
        ),
        11168 => array(
            'Id' => 11168,
            'Label' => 'Books|Young Adult|Careers & Occupations',
        ),
        11169 => array(
            'Id' => 11169,
            'Label' => 'Books|Young Adult|Computers & Technology',
        ),
        11170 => array(
            'Id' => 11170,
            'Label' => 'Books|Young Adult|Cooking & Food',
        ),
        11171 => array(
            'Id' => 11171,
            'Label' => 'Books|Young Adult|Arts & Entertainment',
        ),
        11172 => array(
            'Id' => 11172,
            'Label' => 'Books|Young Adult|Arts & Entertainment|Art',
        ),
        11173 => array(
            'Id' => 11173,
            'Label' => 'Books|Young Adult|Arts & Entertainment|Crafts',
        ),
        11174 => array(
            'Id' => 11174,
            'Label' => 'Books|Young Adult|Arts & Entertainment|Music',
        ),
        11175 => array(
            'Id' => 11175,
            'Label' => 'Books|Young Adult|Arts & Entertainment|Performing Arts',
        ),
        11176 => array(
            'Id' => 11176,
            'Label' => 'Books|Young Adult|Family',
        ),
        11177 => array(
            'Id' => 11177,
            'Label' => 'Books|Young Adult|Fiction',
        ),
        11178 => array(
            'Id' => 11178,
            'Label' => 'Books|Young Adult|Fiction|Action & Adventure',
        ),
        11179 => array(
            'Id' => 11179,
            'Label' => 'Books|Young Adult|Fiction|Animals',
        ),
        11180 => array(
            'Id' => 11180,
            'Label' => 'Books|Young Adult|Fiction|Classics',
        ),
        11181 => array(
            'Id' => 11181,
            'Label' => 'Books|Young Adult|Fiction|Comics & Graphic Novels',
        ),
        11182 => array(
            'Id' => 11182,
            'Label' => 'Books|Young Adult|Fiction|Culture, Places & People',
        ),
        11183 => array(
            'Id' => 11183,
            'Label' => 'Books|Young Adult|Fiction|Dystopian',
        ),
        11184 => array(
            'Id' => 11184,
            'Label' => 'Books|Young Adult|Fiction|Family & Relationships',
        ),
        11185 => array(
            'Id' => 11185,
            'Label' => 'Books|Young Adult|Fiction|Fantasy',
        ),
        11186 => array(
            'Id' => 11186,
            'Label' => 'Books|Young Adult|Fiction|Fairy Tales, Myths & Fables',
        ),
        11187 => array(
            'Id' => 11187,
            'Label' => 'Books|Young Adult|Fiction|Favorite Characters',
        ),
        11188 => array(
            'Id' => 11188,
            'Label' => 'Books|Young Adult|Fiction|Historical',
        ),
        11189 => array(
            'Id' => 11189,
            'Label' => 'Books|Young Adult|Fiction|Holidays & Celebrations',
        ),
        11190 => array(
            'Id' => 11190,
            'Label' => 'Books|Young Adult|Fiction|Horror, Monsters & Ghosts',
        ),
        11191 => array(
            'Id' => 11191,
            'Label' => 'Books|Young Adult|Fiction|Crime & Mystery',
        ),
        11192 => array(
            'Id' => 11192,
            'Label' => 'Books|Young Adult|Fiction|Nature',
        ),
        11193 => array(
            'Id' => 11193,
            'Label' => 'Books|Young Adult|Fiction|Religion',
        ),
        11194 => array(
            'Id' => 11194,
            'Label' => 'Books|Young Adult|Fiction|Romance',
        ),
        11195 => array(
            'Id' => 11195,
            'Label' => 'Books|Young Adult|Fiction|Sci-Fi',
        ),
        11196 => array(
            'Id' => 11196,
            'Label' => 'Books|Young Adult|Fiction|Coming of Age',
        ),
        11197 => array(
            'Id' => 11197,
            'Label' => 'Books|Young Adult|Fiction|Sports & Recreation',
        ),
        11198 => array(
            'Id' => 11198,
            'Label' => 'Books|Young Adult|Fiction|Transportation',
        ),
        11199 => array(
            'Id' => 11199,
            'Label' => 'Books|Young Adult|Games & Activities',
        ),
        11200 => array(
            'Id' => 11200,
            'Label' => 'Books|Young Adult|General Nonfiction',
        ),
        11201 => array(
            'Id' => 11201,
            'Label' => 'Books|Young Adult|Health',
        ),
        11202 => array(
            'Id' => 11202,
            'Label' => 'Books|Young Adult|History',
        ),
        11203 => array(
            'Id' => 11203,
            'Label' => 'Books|Young Adult|Holidays & Celebrations',
        ),
        11204 => array(
            'Id' => 11204,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Birthdays',
        ),
        11205 => array(
            'Id' => 11205,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Christmas & Advent',
        ),
        11206 => array(
            'Id' => 11206,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Easter & Lent',
        ),
        11207 => array(
            'Id' => 11207,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|General',
        ),
        11208 => array(
            'Id' => 11208,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Halloween',
        ),
        11209 => array(
            'Id' => 11209,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Hanukkah',
        ),
        11210 => array(
            'Id' => 11210,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Other',
        ),
        11211 => array(
            'Id' => 11211,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Passover',
        ),
        11212 => array(
            'Id' => 11212,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Patriotic Holidays',
        ),
        11213 => array(
            'Id' => 11213,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Ramadan',
        ),
        11214 => array(
            'Id' => 11214,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Thanksgiving',
        ),
        11215 => array(
            'Id' => 11215,
            'Label' => 'Books|Young Adult|Holidays & Celebrations|Valentine\'s Day',
        ),
        11216 => array(
            'Id' => 11216,
            'Label' => 'Books|Young Adult|Humor',
        ),
        11217 => array(
            'Id' => 11217,
            'Label' => 'Books|Young Adult|Humor|Jokes & Riddles',
        ),
        11218 => array(
            'Id' => 11218,
            'Label' => 'Books|Young Adult|Poetry',
        ),
        11219 => array(
            'Id' => 11219,
            'Label' => 'Books|Young Adult|Politics & Government',
        ),
        11220 => array(
            'Id' => 11220,
            'Label' => 'Books|Young Adult|Reference',
        ),
        11221 => array(
            'Id' => 11221,
            'Label' => 'Books|Young Adult|Religion',
        ),
        11222 => array(
            'Id' => 11222,
            'Label' => 'Books|Young Adult|Science & Nature',
        ),
        11223 => array(
            'Id' => 11223,
            'Label' => 'Books|Young Adult|Coming of Age',
        ),
        11224 => array(
            'Id' => 11224,
            'Label' => 'Books|Young Adult|Social Studies',
        ),
        11225 => array(
            'Id' => 11225,
            'Label' => 'Books|Young Adult|Sports & Recreation',
        ),
        11226 => array(
            'Id' => 11226,
            'Label' => 'Books|Young Adult|Transportation',
        ),
        11227 => array(
            'Id' => 11227,
            'Label' => 'Books|Communications & Media',
        ),
        11228 => array(
            'Id' => 11228,
            'Label' => 'Books|Military & Warfare',
        ),
        11229 => array(
            'Id' => 11229,
            'Label' => 'Books|Romance|Inspirational',
        ),
        11231 => array(
            'Id' => 11231,
            'Label' => 'Books|Romance|Holiday',
        ),
        11232 => array(
            'Id' => 11232,
            'Label' => 'Books|Romance|Wholesome',
        ),
        11233 => array(
            'Id' => 11233,
            'Label' => 'Books|Romance|Military',
        ),
        11234 => array(
            'Id' => 11234,
            'Label' => 'Books|Arts & Entertainment|Art History',
        ),
        11236 => array(
            'Id' => 11236,
            'Label' => 'Books|Arts & Entertainment|Design',
        ),
        11243 => array(
            'Id' => 11243,
            'Label' => 'Books|Business & Personal Finance|Accounting',
        ),
        11244 => array(
            'Id' => 11244,
            'Label' => 'Books|Business & Personal Finance|Hospitality',
        ),
        11245 => array(
            'Id' => 11245,
            'Label' => 'Books|Business & Personal Finance|Real Estate',
        ),
        11246 => array(
            'Id' => 11246,
            'Label' => 'Books|Humor|Jokes & Riddles',
        ),
        11247 => array(
            'Id' => 11247,
            'Label' => 'Books|Religion & Spirituality|Comparative Religion',
        ),
        11255 => array(
            'Id' => 11255,
            'Label' => 'Books|Cookbooks, Food & Wine|Culinary Arts',
        ),
        11259 => array(
            'Id' => 11259,
            'Label' => 'Books|Mysteries & Thrillers|Cozy',
        ),
        11260 => array(
            'Id' => 11260,
            'Label' => 'Books|Politics & Current Events|Current Events',
        ),
        11261 => array(
            'Id' => 11261,
            'Label' => 'Books|Politics & Current Events|Foreign Policy & International Relations',
        ),
        11262 => array(
            'Id' => 11262,
            'Label' => 'Books|Politics & Current Events|Local Government',
        ),
        11263 => array(
            'Id' => 11263,
            'Label' => 'Books|Politics & Current Events|National Government',
        ),
        11264 => array(
            'Id' => 11264,
            'Label' => 'Books|Politics & Current Events|Political Science',
        ),
        11265 => array(
            'Id' => 11265,
            'Label' => 'Books|Politics & Current Events|Public Administration',
        ),
        11266 => array(
            'Id' => 11266,
            'Label' => 'Books|Politics & Current Events|World Affairs',
        ),
        11273 => array(
            'Id' => 11273,
            'Label' => 'Books|Nonfiction|Family & Relationships|Family & Childcare',
        ),
        11274 => array(
            'Id' => 11274,
            'Label' => 'Books|Nonfiction|Family & Relationships|Love & Romance',
        ),
        11275 => array(
            'Id' => 11275,
            'Label' => 'Books|Sci-Fi & Fantasy|Fantasy|Urban',
        ),
        11276 => array(
            'Id' => 11276,
            'Label' => 'Books|Reference|Foreign Languages|Arabic',
        ),
        11277 => array(
            'Id' => 11277,
            'Label' => 'Books|Reference|Foreign Languages|Bilingual Editions',
        ),
        11278 => array(
            'Id' => 11278,
            'Label' => 'Books|Reference|Foreign Languages|African Languages',
        ),
        11279 => array(
            'Id' => 11279,
            'Label' => 'Books|Reference|Foreign Languages|Ancient Languages',
        ),
        11280 => array(
            'Id' => 11280,
            'Label' => 'Books|Reference|Foreign Languages|Chinese',
        ),
        11281 => array(
            'Id' => 11281,
            'Label' => 'Books|Reference|Foreign Languages|English',
        ),
        11282 => array(
            'Id' => 11282,
            'Label' => 'Books|Reference|Foreign Languages|French',
        ),
        11283 => array(
            'Id' => 11283,
            'Label' => 'Books|Reference|Foreign Languages|German',
        ),
        11284 => array(
            'Id' => 11284,
            'Label' => 'Books|Reference|Foreign Languages|Hebrew',
        ),
        11285 => array(
            'Id' => 11285,
            'Label' => 'Books|Reference|Foreign Languages|Hindi',
        ),
        11286 => array(
            'Id' => 11286,
            'Label' => 'Books|Reference|Foreign Languages|Italian',
        ),
        11287 => array(
            'Id' => 11287,
            'Label' => 'Books|Reference|Foreign Languages|Japanese',
        ),
        11288 => array(
            'Id' => 11288,
            'Label' => 'Books|Reference|Foreign Languages|Korean',
        ),
        11289 => array(
            'Id' => 11289,
            'Label' => 'Books|Reference|Foreign Languages|Linguistics',
        ),
        11290 => array(
            'Id' => 11290,
            'Label' => 'Books|Reference|Foreign Languages|Other Languages',
        ),
        11291 => array(
            'Id' => 11291,
            'Label' => 'Books|Reference|Foreign Languages|Portuguese',
        ),
        11292 => array(
            'Id' => 11292,
            'Label' => 'Books|Reference|Foreign Languages|Russian',
        ),
        11293 => array(
            'Id' => 11293,
            'Label' => 'Books|Reference|Foreign Languages|Spanish',
        ),
        11294 => array(
            'Id' => 11294,
            'Label' => 'Books|Reference|Foreign Languages|Speech Pathology',
        ),
        11295 => array(
            'Id' => 11295,
            'Label' => 'Books|Science & Nature|Mathematics|Advanced Mathematics',
        ),
        11296 => array(
            'Id' => 11296,
            'Label' => 'Books|Science & Nature|Mathematics|Algebra',
        ),
        11297 => array(
            'Id' => 11297,
            'Label' => 'Books|Science & Nature|Mathematics|Arithmetic',
        ),
        11298 => array(
            'Id' => 11298,
            'Label' => 'Books|Science & Nature|Mathematics|Calculus',
        ),
        11299 => array(
            'Id' => 11299,
            'Label' => 'Books|Science & Nature|Mathematics|Geometry',
        ),
        11300 => array(
            'Id' => 11300,
            'Label' => 'Books|Science & Nature|Mathematics|Statistics',
        ),
        11301 => array(
            'Id' => 11301,
            'Label' => 'Books|Professional & Technical|Medical|Veterinary',
        ),
        11302 => array(
            'Id' => 11302,
            'Label' => 'Books|Professional & Technical|Medical|Neuroscience',
        ),
        11303 => array(
            'Id' => 11303,
            'Label' => 'Books|Professional & Technical|Medical|Immunology',
        ),
        11304 => array(
            'Id' => 11304,
            'Label' => 'Books|Professional & Technical|Medical|Nursing',
        ),
        11305 => array(
            'Id' => 11305,
            'Label' => 'Books|Professional & Technical|Medical|Pharmacology & Toxicology',
        ),
        11306 => array(
            'Id' => 11306,
            'Label' => 'Books|Professional & Technical|Medical|Anatomy & Physiology',
        ),
        11307 => array(
            'Id' => 11307,
            'Label' => 'Books|Professional & Technical|Medical|Dentistry',
        ),
        11308 => array(
            'Id' => 11308,
            'Label' => 'Books|Professional & Technical|Medical|Emergency Medicine',
        ),
        11309 => array(
            'Id' => 11309,
            'Label' => 'Books|Professional & Technical|Medical|Genetics',
        ),
        11310 => array(
            'Id' => 11310,
            'Label' => 'Books|Professional & Technical|Medical|Psychiatry',
        ),
        11311 => array(
            'Id' => 11311,
            'Label' => 'Books|Professional & Technical|Medical|Radiology',
        ),
        11312 => array(
            'Id' => 11312,
            'Label' => 'Books|Professional & Technical|Medical|Alternative Medicine',
        ),
        11317 => array(
            'Id' => 11317,
            'Label' => 'Books|Nonfiction|Philosophy|Political Philosophy',
        ),
        11319 => array(
            'Id' => 11319,
            'Label' => 'Books|Nonfiction|Philosophy|Philosophy of Language',
        ),
        11320 => array(
            'Id' => 11320,
            'Label' => 'Books|Nonfiction|Philosophy|Philosophy of Religion',
        ),
        11327 => array(
            'Id' => 11327,
            'Label' => 'Books|Nonfiction|Social Science|Sociology',
        ),
        11329 => array(
            'Id' => 11329,
            'Label' => 'Books|Professional & Technical|Engineering|Aeronautics',
        ),
        11330 => array(
            'Id' => 11330,
            'Label' => 'Books|Professional & Technical|Engineering|Chemical & Petroleum Engineering',
        ),
        11331 => array(
            'Id' => 11331,
            'Label' => 'Books|Professional & Technical|Engineering|Civil Engineering',
        ),
        11332 => array(
            'Id' => 11332,
            'Label' => 'Books|Professional & Technical|Engineering|Computer Science',
        ),
        11333 => array(
            'Id' => 11333,
            'Label' => 'Books|Professional & Technical|Engineering|Electrical Engineering',
        ),
        11334 => array(
            'Id' => 11334,
            'Label' => 'Books|Professional & Technical|Engineering|Environmental Engineering',
        ),
        11335 => array(
            'Id' => 11335,
            'Label' => 'Books|Professional & Technical|Engineering|Mechanical Engineering',
        ),
        11336 => array(
            'Id' => 11336,
            'Label' => 'Books|Professional & Technical|Engineering|Power Resources',
        ),
        11337 => array(
            'Id' => 11337,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Boys',
        ),
        11338 => array(
            'Id' => 11338,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Men',
        ),
        11339 => array(
            'Id' => 11339,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Girls',
        ),
        11340 => array(
            'Id' => 11340,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Women',
        ),
        11341 => array(
            'Id' => 11341,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Other',
        ),
        12001 => array(
            'Id' => 12001,
            'Label' => 'Mac App Store|Business',
        ),
        12002 => array(
            'Id' => 12002,
            'Label' => 'Mac App Store|Developer Tools',
        ),
        12003 => array(
            'Id' => 12003,
            'Label' => 'Mac App Store|Education',
        ),
        12004 => array(
            'Id' => 12004,
            'Label' => 'Mac App Store|Entertainment',
        ),
        12005 => array(
            'Id' => 12005,
            'Label' => 'Mac App Store|Finance',
        ),
        12006 => array(
            'Id' => 12006,
            'Label' => 'Mac App Store|Games',
        ),
        12007 => array(
            'Id' => 12007,
            'Label' => 'Mac App Store|Health & Fitness',
        ),
        12008 => array(
            'Id' => 12008,
            'Label' => 'Mac App Store|Lifestyle',
        ),
        12010 => array(
            'Id' => 12010,
            'Label' => 'Mac App Store|Medical',
        ),
        12011 => array(
            'Id' => 12011,
            'Label' => 'Mac App Store|Music',
        ),
        12012 => array(
            'Id' => 12012,
            'Label' => 'Mac App Store|News',
        ),
        12013 => array(
            'Id' => 12013,
            'Label' => 'Mac App Store|Photography',
        ),
        12014 => array(
            'Id' => 12014,
            'Label' => 'Mac App Store|Productivity',
        ),
        12015 => array(
            'Id' => 12015,
            'Label' => 'Mac App Store|Reference',
        ),
        12016 => array(
            'Id' => 12016,
            'Label' => 'Mac App Store|Social Networking',
        ),
        12017 => array(
            'Id' => 12017,
            'Label' => 'Mac App Store|Sports',
        ),
        12018 => array(
            'Id' => 12018,
            'Label' => 'Mac App Store|Travel',
        ),
        12019 => array(
            'Id' => 12019,
            'Label' => 'Mac App Store|Utilities',
        ),
        12020 => array(
            'Id' => 12020,
            'Label' => 'Mac App Store|Video',
        ),
        12021 => array(
            'Id' => 12021,
            'Label' => 'Mac App Store|Weather',
        ),
        12022 => array(
            'Id' => 12022,
            'Label' => 'Mac App Store|Graphics & Design',
        ),
        12201 => array(
            'Id' => 12201,
            'Label' => 'Mac App Store|Games|Action',
        ),
        12202 => array(
            'Id' => 12202,
            'Label' => 'Mac App Store|Games|Adventure',
        ),
        12203 => array(
            'Id' => 12203,
            'Label' => 'Mac App Store|Games|Arcade',
        ),
        12204 => array(
            'Id' => 12204,
            'Label' => 'Mac App Store|Games|Board',
        ),
        12205 => array(
            'Id' => 12205,
            'Label' => 'Mac App Store|Games|Card',
        ),
        12206 => array(
            'Id' => 12206,
            'Label' => 'Mac App Store|Games|Casino',
        ),
        12207 => array(
            'Id' => 12207,
            'Label' => 'Mac App Store|Games|Dice',
        ),
        12208 => array(
            'Id' => 12208,
            'Label' => 'Mac App Store|Games|Educational',
        ),
        12209 => array(
            'Id' => 12209,
            'Label' => 'Mac App Store|Games|Family',
        ),
        12210 => array(
            'Id' => 12210,
            'Label' => 'Mac App Store|Games|Kids',
        ),
        12211 => array(
            'Id' => 12211,
            'Label' => 'Mac App Store|Games|Music',
        ),
        12212 => array(
            'Id' => 12212,
            'Label' => 'Mac App Store|Games|Puzzle',
        ),
        12213 => array(
            'Id' => 12213,
            'Label' => 'Mac App Store|Games|Racing',
        ),
        12214 => array(
            'Id' => 12214,
            'Label' => 'Mac App Store|Games|Role Playing',
        ),
        12215 => array(
            'Id' => 12215,
            'Label' => 'Mac App Store|Games|Simulation',
        ),
        12216 => array(
            'Id' => 12216,
            'Label' => 'Mac App Store|Games|Sports',
        ),
        12217 => array(
            'Id' => 12217,
            'Label' => 'Mac App Store|Games|Strategy',
        ),
        12218 => array(
            'Id' => 12218,
            'Label' => 'Mac App Store|Games|Trivia',
        ),
        12219 => array(
            'Id' => 12219,
            'Label' => 'Mac App Store|Games|Word',
        ),
        13001 => array(
            'Id' => 13001,
            'Label' => 'App Store|Newsstand|News & Politics',
        ),
        13002 => array(
            'Id' => 13002,
            'Label' => 'App Store|Newsstand|Fashion & Style',
        ),
        13003 => array(
            'Id' => 13003,
            'Label' => 'App Store|Newsstand|Home & Garden',
        ),
        13004 => array(
            'Id' => 13004,
            'Label' => 'App Store|Newsstand|Outdoors & Nature',
        ),
        13005 => array(
            'Id' => 13005,
            'Label' => 'App Store|Newsstand|Sports & Leisure',
        ),
        13006 => array(
            'Id' => 13006,
            'Label' => 'App Store|Newsstand|Automotive',
        ),
        13007 => array(
            'Id' => 13007,
            'Label' => 'App Store|Newsstand|Arts & Photography',
        ),
        13008 => array(
            'Id' => 13008,
            'Label' => 'App Store|Newsstand|Brides & Weddings',
        ),
        13009 => array(
            'Id' => 13009,
            'Label' => 'App Store|Newsstand|Business & Investing',
        ),
        13010 => array(
            'Id' => 13010,
            'Label' => 'App Store|Newsstand|Children\'s Magazines',
        ),
        13011 => array(
            'Id' => 13011,
            'Label' => 'App Store|Newsstand|Computers & Internet',
        ),
        13012 => array(
            'Id' => 13012,
            'Label' => 'App Store|Newsstand|Cooking, Food & Drink',
        ),
        13013 => array(
            'Id' => 13013,
            'Label' => 'App Store|Newsstand|Crafts & Hobbies',
        ),
        13014 => array(
            'Id' => 13014,
            'Label' => 'App Store|Newsstand|Electronics & Audio',
        ),
        13015 => array(
            'Id' => 13015,
            'Label' => 'App Store|Newsstand|Entertainment',
        ),
        13017 => array(
            'Id' => 13017,
            'Label' => 'App Store|Newsstand|Health, Mind & Body',
        ),
        13018 => array(
            'Id' => 13018,
            'Label' => 'App Store|Newsstand|History',
        ),
        13019 => array(
            'Id' => 13019,
            'Label' => 'App Store|Newsstand|Literary Magazines & Journals',
        ),
        13020 => array(
            'Id' => 13020,
            'Label' => 'App Store|Newsstand|Men\'s Interest',
        ),
        13021 => array(
            'Id' => 13021,
            'Label' => 'App Store|Newsstand|Movies & Music',
        ),
        13023 => array(
            'Id' => 13023,
            'Label' => 'App Store|Newsstand|Parenting & Family',
        ),
        13024 => array(
            'Id' => 13024,
            'Label' => 'App Store|Newsstand|Pets',
        ),
        13025 => array(
            'Id' => 13025,
            'Label' => 'App Store|Newsstand|Professional & Trade',
        ),
        13026 => array(
            'Id' => 13026,
            'Label' => 'App Store|Newsstand|Regional News',
        ),
        13027 => array(
            'Id' => 13027,
            'Label' => 'App Store|Newsstand|Science',
        ),
        13028 => array(
            'Id' => 13028,
            'Label' => 'App Store|Newsstand|Teens',
        ),
        13029 => array(
            'Id' => 13029,
            'Label' => 'App Store|Newsstand|Travel & Regional',
        ),
        13030 => array(
            'Id' => 13030,
            'Label' => 'App Store|Newsstand|Women\'s Interest',
        ),
        15000 => array(
            'Id' => 15000,
            'Label' => 'Textbooks|Arts & Entertainment',
        ),
        15001 => array(
            'Id' => 15001,
            'Label' => 'Textbooks|Arts & Entertainment|Art & Architecture',
        ),
        15002 => array(
            'Id' => 15002,
            'Label' => 'Textbooks|Arts & Entertainment|Art & Architecture|Urban Planning',
        ),
        15003 => array(
            'Id' => 15003,
            'Label' => 'Textbooks|Arts & Entertainment|Art History',
        ),
        15004 => array(
            'Id' => 15004,
            'Label' => 'Textbooks|Arts & Entertainment|Dance',
        ),
        15005 => array(
            'Id' => 15005,
            'Label' => 'Textbooks|Arts & Entertainment|Design',
        ),
        15006 => array(
            'Id' => 15006,
            'Label' => 'Textbooks|Arts & Entertainment|Fashion',
        ),
        15007 => array(
            'Id' => 15007,
            'Label' => 'Textbooks|Arts & Entertainment|Film',
        ),
        15008 => array(
            'Id' => 15008,
            'Label' => 'Textbooks|Arts & Entertainment|Games',
        ),
        15009 => array(
            'Id' => 15009,
            'Label' => 'Textbooks|Arts & Entertainment|Interior Design',
        ),
        15010 => array(
            'Id' => 15010,
            'Label' => 'Textbooks|Arts & Entertainment|Media Arts',
        ),
        15011 => array(
            'Id' => 15011,
            'Label' => 'Textbooks|Arts & Entertainment|Music',
        ),
        15012 => array(
            'Id' => 15012,
            'Label' => 'Textbooks|Arts & Entertainment|Performing Arts',
        ),
        15013 => array(
            'Id' => 15013,
            'Label' => 'Textbooks|Arts & Entertainment|Photography',
        ),
        15014 => array(
            'Id' => 15014,
            'Label' => 'Textbooks|Arts & Entertainment|Theater',
        ),
        15015 => array(
            'Id' => 15015,
            'Label' => 'Textbooks|Arts & Entertainment|TV',
        ),
        15016 => array(
            'Id' => 15016,
            'Label' => 'Textbooks|Arts & Entertainment|Visual Arts',
        ),
        15017 => array(
            'Id' => 15017,
            'Label' => 'Textbooks|Biographies & Memoirs',
        ),
        15018 => array(
            'Id' => 15018,
            'Label' => 'Textbooks|Business & Personal Finance',
        ),
        15019 => array(
            'Id' => 15019,
            'Label' => 'Textbooks|Business & Personal Finance|Accounting',
        ),
        15020 => array(
            'Id' => 15020,
            'Label' => 'Textbooks|Business & Personal Finance|Careers',
        ),
        15021 => array(
            'Id' => 15021,
            'Label' => 'Textbooks|Business & Personal Finance|Economics',
        ),
        15022 => array(
            'Id' => 15022,
            'Label' => 'Textbooks|Business & Personal Finance|Finance',
        ),
        15023 => array(
            'Id' => 15023,
            'Label' => 'Textbooks|Business & Personal Finance|Hospitality',
        ),
        15024 => array(
            'Id' => 15024,
            'Label' => 'Textbooks|Business & Personal Finance|Industries & Professions',
        ),
        15025 => array(
            'Id' => 15025,
            'Label' => 'Textbooks|Business & Personal Finance|Investing',
        ),
        15026 => array(
            'Id' => 15026,
            'Label' => 'Textbooks|Business & Personal Finance|Management & Leadership',
        ),
        15027 => array(
            'Id' => 15027,
            'Label' => 'Textbooks|Business & Personal Finance|Marketing & Sales',
        ),
        15028 => array(
            'Id' => 15028,
            'Label' => 'Textbooks|Business & Personal Finance|Personal Finance',
        ),
        15029 => array(
            'Id' => 15029,
            'Label' => 'Textbooks|Business & Personal Finance|Real Estate',
        ),
        15030 => array(
            'Id' => 15030,
            'Label' => 'Textbooks|Business & Personal Finance|Reference',
        ),
        15031 => array(
            'Id' => 15031,
            'Label' => 'Textbooks|Business & Personal Finance|Small Business & Entrepreneurship',
        ),
        15032 => array(
            'Id' => 15032,
            'Label' => 'Textbooks|Children & Teens',
        ),
        15033 => array(
            'Id' => 15033,
            'Label' => 'Textbooks|Children & Teens|Fiction',
        ),
        15034 => array(
            'Id' => 15034,
            'Label' => 'Textbooks|Children & Teens|Nonfiction',
        ),
        15035 => array(
            'Id' => 15035,
            'Label' => 'Textbooks|Comics & Graphic Novels',
        ),
        15036 => array(
            'Id' => 15036,
            'Label' => 'Textbooks|Comics & Graphic Novels|Graphic Novels',
        ),
        15037 => array(
            'Id' => 15037,
            'Label' => 'Textbooks|Comics & Graphic Novels|Manga',
        ),
        15038 => array(
            'Id' => 15038,
            'Label' => 'Textbooks|Communications & Media',
        ),
        15039 => array(
            'Id' => 15039,
            'Label' => 'Textbooks|Communications & Media|Broadcasting',
        ),
        15040 => array(
            'Id' => 15040,
            'Label' => 'Textbooks|Communications & Media|Digital Media',
        ),
        15041 => array(
            'Id' => 15041,
            'Label' => 'Textbooks|Communications & Media|Journalism',
        ),
        15042 => array(
            'Id' => 15042,
            'Label' => 'Textbooks|Communications & Media|Photojournalism',
        ),
        15043 => array(
            'Id' => 15043,
            'Label' => 'Textbooks|Communications & Media|Print',
        ),
        15044 => array(
            'Id' => 15044,
            'Label' => 'Textbooks|Communications & Media|Speech',
        ),
        15045 => array(
            'Id' => 15045,
            'Label' => 'Textbooks|Communications & Media|Writing',
        ),
        15046 => array(
            'Id' => 15046,
            'Label' => 'Textbooks|Computers & Internet',
        ),
        15047 => array(
            'Id' => 15047,
            'Label' => 'Textbooks|Computers & Internet|Computers',
        ),
        15048 => array(
            'Id' => 15048,
            'Label' => 'Textbooks|Computers & Internet|Databases',
        ),
        15049 => array(
            'Id' => 15049,
            'Label' => 'Textbooks|Computers & Internet|Digital Media',
        ),
        15050 => array(
            'Id' => 15050,
            'Label' => 'Textbooks|Computers & Internet|Internet',
        ),
        15051 => array(
            'Id' => 15051,
            'Label' => 'Textbooks|Computers & Internet|Network',
        ),
        15052 => array(
            'Id' => 15052,
            'Label' => 'Textbooks|Computers & Internet|Operating Systems',
        ),
        15053 => array(
            'Id' => 15053,
            'Label' => 'Textbooks|Computers & Internet|Programming',
        ),
        15054 => array(
            'Id' => 15054,
            'Label' => 'Textbooks|Computers & Internet|Software',
        ),
        15055 => array(
            'Id' => 15055,
            'Label' => 'Textbooks|Computers & Internet|System Administration',
        ),
        15056 => array(
            'Id' => 15056,
            'Label' => 'Textbooks|Cookbooks, Food & Wine',
        ),
        15057 => array(
            'Id' => 15057,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Beverages',
        ),
        15058 => array(
            'Id' => 15058,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Courses & Dishes',
        ),
        15059 => array(
            'Id' => 15059,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Culinary Arts',
        ),
        15060 => array(
            'Id' => 15060,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Methods',
        ),
        15061 => array(
            'Id' => 15061,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Reference',
        ),
        15062 => array(
            'Id' => 15062,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Regional & Ethnic',
        ),
        15063 => array(
            'Id' => 15063,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Special Diet',
        ),
        15064 => array(
            'Id' => 15064,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Special Occasions',
        ),
        15065 => array(
            'Id' => 15065,
            'Label' => 'Textbooks|Cookbooks, Food & Wine|Specific Ingredients',
        ),
        15066 => array(
            'Id' => 15066,
            'Label' => 'Textbooks|Engineering',
        ),
        15067 => array(
            'Id' => 15067,
            'Label' => 'Textbooks|Engineering|Aeronautics',
        ),
        15068 => array(
            'Id' => 15068,
            'Label' => 'Textbooks|Engineering|Chemical & Petroleum Engineering',
        ),
        15069 => array(
            'Id' => 15069,
            'Label' => 'Textbooks|Engineering|Civil Engineering',
        ),
        15070 => array(
            'Id' => 15070,
            'Label' => 'Textbooks|Engineering|Computer Science',
        ),
        15071 => array(
            'Id' => 15071,
            'Label' => 'Textbooks|Engineering|Electrical Engineering',
        ),
        15072 => array(
            'Id' => 15072,
            'Label' => 'Textbooks|Engineering|Environmental Engineering',
        ),
        15073 => array(
            'Id' => 15073,
            'Label' => 'Textbooks|Engineering|Mechanical Engineering',
        ),
        15074 => array(
            'Id' => 15074,
            'Label' => 'Textbooks|Engineering|Power Resources',
        ),
        15075 => array(
            'Id' => 15075,
            'Label' => 'Textbooks|Fiction & Literature',
        ),
        15076 => array(
            'Id' => 15076,
            'Label' => 'Textbooks|Fiction & Literature|Latino',
        ),
        15077 => array(
            'Id' => 15077,
            'Label' => 'Textbooks|Fiction & Literature|Action & Adventure',
        ),
        15078 => array(
            'Id' => 15078,
            'Label' => 'Textbooks|Fiction & Literature|African American',
        ),
        15079 => array(
            'Id' => 15079,
            'Label' => 'Textbooks|Fiction & Literature|Anthologies',
        ),
        15080 => array(
            'Id' => 15080,
            'Label' => 'Textbooks|Fiction & Literature|Classics',
        ),
        15081 => array(
            'Id' => 15081,
            'Label' => 'Textbooks|Fiction & Literature|Comparative Literature',
        ),
        15082 => array(
            'Id' => 15082,
            'Label' => 'Textbooks|Fiction & Literature|Erotica',
        ),
        15083 => array(
            'Id' => 15083,
            'Label' => 'Textbooks|Fiction & Literature|Gay',
        ),
        15084 => array(
            'Id' => 15084,
            'Label' => 'Textbooks|Fiction & Literature|Ghost',
        ),
        15085 => array(
            'Id' => 15085,
            'Label' => 'Textbooks|Fiction & Literature|Historical',
        ),
        15086 => array(
            'Id' => 15086,
            'Label' => 'Textbooks|Fiction & Literature|Horror',
        ),
        15087 => array(
            'Id' => 15087,
            'Label' => 'Textbooks|Fiction & Literature|Literary',
        ),
        15088 => array(
            'Id' => 15088,
            'Label' => 'Textbooks|Fiction & Literature|Literary Criticism',
        ),
        15089 => array(
            'Id' => 15089,
            'Label' => 'Textbooks|Fiction & Literature|Poetry',
        ),
        15090 => array(
            'Id' => 15090,
            'Label' => 'Textbooks|Fiction & Literature|Religious',
        ),
        15091 => array(
            'Id' => 15091,
            'Label' => 'Textbooks|Fiction & Literature|Short Stories',
        ),
        15092 => array(
            'Id' => 15092,
            'Label' => 'Textbooks|Health, Mind & Body',
        ),
        15093 => array(
            'Id' => 15093,
            'Label' => 'Textbooks|Health, Mind & Body|Fitness',
        ),
        15094 => array(
            'Id' => 15094,
            'Label' => 'Textbooks|Health, Mind & Body|Self-Improvement',
        ),
        15095 => array(
            'Id' => 15095,
            'Label' => 'Textbooks|History',
        ),
        15096 => array(
            'Id' => 15096,
            'Label' => 'Textbooks|History|Africa',
        ),
        15097 => array(
            'Id' => 15097,
            'Label' => 'Textbooks|History|Americas',
        ),
        15098 => array(
            'Id' => 15098,
            'Label' => 'Textbooks|History|Americas|Canada',
        ),
        15099 => array(
            'Id' => 15099,
            'Label' => 'Textbooks|History|Americas|Latin America',
        ),
        15100 => array(
            'Id' => 15100,
            'Label' => 'Textbooks|History|Americas|United States',
        ),
        15101 => array(
            'Id' => 15101,
            'Label' => 'Textbooks|History|Ancient',
        ),
        15102 => array(
            'Id' => 15102,
            'Label' => 'Textbooks|History|Asia',
        ),
        15103 => array(
            'Id' => 15103,
            'Label' => 'Textbooks|History|Australia & Oceania',
        ),
        15104 => array(
            'Id' => 15104,
            'Label' => 'Textbooks|History|Europe',
        ),
        15105 => array(
            'Id' => 15105,
            'Label' => 'Textbooks|History|Middle East',
        ),
        15106 => array(
            'Id' => 15106,
            'Label' => 'Textbooks|History|Military',
        ),
        15107 => array(
            'Id' => 15107,
            'Label' => 'Textbooks|History|World',
        ),
        15108 => array(
            'Id' => 15108,
            'Label' => 'Textbooks|Humor',
        ),
        15109 => array(
            'Id' => 15109,
            'Label' => 'Textbooks|Language Studies',
        ),
        15110 => array(
            'Id' => 15110,
            'Label' => 'Textbooks|Language Studies|African Languages',
        ),
        15111 => array(
            'Id' => 15111,
            'Label' => 'Textbooks|Language Studies|Ancient Languages',
        ),
        15112 => array(
            'Id' => 15112,
            'Label' => 'Textbooks|Language Studies|Arabic',
        ),
        15113 => array(
            'Id' => 15113,
            'Label' => 'Textbooks|Language Studies|Bilingual Editions',
        ),
        15114 => array(
            'Id' => 15114,
            'Label' => 'Textbooks|Language Studies|Chinese',
        ),
        15115 => array(
            'Id' => 15115,
            'Label' => 'Textbooks|Language Studies|English',
        ),
        15116 => array(
            'Id' => 15116,
            'Label' => 'Textbooks|Language Studies|French',
        ),
        15117 => array(
            'Id' => 15117,
            'Label' => 'Textbooks|Language Studies|German',
        ),
        15118 => array(
            'Id' => 15118,
            'Label' => 'Textbooks|Language Studies|Hebrew',
        ),
        15119 => array(
            'Id' => 15119,
            'Label' => 'Textbooks|Language Studies|Hindi',
        ),
        15120 => array(
            'Id' => 15120,
            'Label' => 'Textbooks|Language Studies|Indigenous Languages',
        ),
        15121 => array(
            'Id' => 15121,
            'Label' => 'Textbooks|Language Studies|Italian',
        ),
        15122 => array(
            'Id' => 15122,
            'Label' => 'Textbooks|Language Studies|Japanese',
        ),
        15123 => array(
            'Id' => 15123,
            'Label' => 'Textbooks|Language Studies|Korean',
        ),
        15124 => array(
            'Id' => 15124,
            'Label' => 'Textbooks|Language Studies|Linguistics',
        ),
        15125 => array(
            'Id' => 15125,
            'Label' => 'Textbooks|Language Studies|Other Language',
        ),
        15126 => array(
            'Id' => 15126,
            'Label' => 'Textbooks|Language Studies|Portuguese',
        ),
        15127 => array(
            'Id' => 15127,
            'Label' => 'Textbooks|Language Studies|Russian',
        ),
        15128 => array(
            'Id' => 15128,
            'Label' => 'Textbooks|Language Studies|Spanish',
        ),
        15129 => array(
            'Id' => 15129,
            'Label' => 'Textbooks|Language Studies|Speech Pathology',
        ),
        15130 => array(
            'Id' => 15130,
            'Label' => 'Textbooks|Lifestyle & Home',
        ),
        15131 => array(
            'Id' => 15131,
            'Label' => 'Textbooks|Lifestyle & Home|Antiques & Collectibles',
        ),
        15132 => array(
            'Id' => 15132,
            'Label' => 'Textbooks|Lifestyle & Home|Crafts & Hobbies',
        ),
        15133 => array(
            'Id' => 15133,
            'Label' => 'Textbooks|Lifestyle & Home|Gardening',
        ),
        15134 => array(
            'Id' => 15134,
            'Label' => 'Textbooks|Lifestyle & Home|Pets',
        ),
        15135 => array(
            'Id' => 15135,
            'Label' => 'Textbooks|Mathematics',
        ),
        15136 => array(
            'Id' => 15136,
            'Label' => 'Textbooks|Mathematics|Advanced Mathematics',
        ),
        15137 => array(
            'Id' => 15137,
            'Label' => 'Textbooks|Mathematics|Algebra',
        ),
        15138 => array(
            'Id' => 15138,
            'Label' => 'Textbooks|Mathematics|Arithmetic',
        ),
        15139 => array(
            'Id' => 15139,
            'Label' => 'Textbooks|Mathematics|Calculus',
        ),
        15140 => array(
            'Id' => 15140,
            'Label' => 'Textbooks|Mathematics|Geometry',
        ),
        15141 => array(
            'Id' => 15141,
            'Label' => 'Textbooks|Mathematics|Statistics',
        ),
        15142 => array(
            'Id' => 15142,
            'Label' => 'Textbooks|Medicine',
        ),
        15143 => array(
            'Id' => 15143,
            'Label' => 'Textbooks|Medicine|Anatomy & Physiology',
        ),
        15144 => array(
            'Id' => 15144,
            'Label' => 'Textbooks|Medicine|Dentistry',
        ),
        15145 => array(
            'Id' => 15145,
            'Label' => 'Textbooks|Medicine|Emergency Medicine',
        ),
        15146 => array(
            'Id' => 15146,
            'Label' => 'Textbooks|Medicine|Genetics',
        ),
        15147 => array(
            'Id' => 15147,
            'Label' => 'Textbooks|Medicine|Immunology',
        ),
        15148 => array(
            'Id' => 15148,
            'Label' => 'Textbooks|Medicine|Neuroscience',
        ),
        15149 => array(
            'Id' => 15149,
            'Label' => 'Textbooks|Medicine|Nursing',
        ),
        15150 => array(
            'Id' => 15150,
            'Label' => 'Textbooks|Medicine|Pharmacology & Toxicology',
        ),
        15151 => array(
            'Id' => 15151,
            'Label' => 'Textbooks|Medicine|Psychiatry',
        ),
        15152 => array(
            'Id' => 15152,
            'Label' => 'Textbooks|Medicine|Psychology',
        ),
        15153 => array(
            'Id' => 15153,
            'Label' => 'Textbooks|Medicine|Radiology',
        ),
        15154 => array(
            'Id' => 15154,
            'Label' => 'Textbooks|Medicine|Veterinary',
        ),
        15155 => array(
            'Id' => 15155,
            'Label' => 'Textbooks|Mysteries & Thrillers',
        ),
        15156 => array(
            'Id' => 15156,
            'Label' => 'Textbooks|Mysteries & Thrillers|British Detectives',
        ),
        15157 => array(
            'Id' => 15157,
            'Label' => 'Textbooks|Mysteries & Thrillers|Hard-Boiled',
        ),
        15158 => array(
            'Id' => 15158,
            'Label' => 'Textbooks|Mysteries & Thrillers|Historical',
        ),
        15159 => array(
            'Id' => 15159,
            'Label' => 'Textbooks|Mysteries & Thrillers|Police Procedural',
        ),
        15160 => array(
            'Id' => 15160,
            'Label' => 'Textbooks|Mysteries & Thrillers|Short Stories',
        ),
        15161 => array(
            'Id' => 15161,
            'Label' => 'Textbooks|Mysteries & Thrillers|Women Sleuths',
        ),
        15162 => array(
            'Id' => 15162,
            'Label' => 'Textbooks|Nonfiction',
        ),
        15163 => array(
            'Id' => 15163,
            'Label' => 'Textbooks|Nonfiction|Family & Relationships',
        ),
        15164 => array(
            'Id' => 15164,
            'Label' => 'Textbooks|Nonfiction|Transportation',
        ),
        15165 => array(
            'Id' => 15165,
            'Label' => 'Textbooks|Nonfiction|True Crime',
        ),
        15166 => array(
            'Id' => 15166,
            'Label' => 'Textbooks|Parenting',
        ),
        15167 => array(
            'Id' => 15167,
            'Label' => 'Textbooks|Philosophy',
        ),
        15168 => array(
            'Id' => 15168,
            'Label' => 'Textbooks|Philosophy|Aesthetics',
        ),
        15169 => array(
            'Id' => 15169,
            'Label' => 'Textbooks|Philosophy|Epistemology',
        ),
        15170 => array(
            'Id' => 15170,
            'Label' => 'Textbooks|Philosophy|Ethics',
        ),
        15171 => array(
            'Id' => 15171,
            'Label' => 'Textbooks|Philosophy|Philosophy of Language',
        ),
        15172 => array(
            'Id' => 15172,
            'Label' => 'Textbooks|Philosophy|Logic',
        ),
        15173 => array(
            'Id' => 15173,
            'Label' => 'Textbooks|Philosophy|Metaphysics',
        ),
        15174 => array(
            'Id' => 15174,
            'Label' => 'Textbooks|Philosophy|Political Philosophy',
        ),
        15175 => array(
            'Id' => 15175,
            'Label' => 'Textbooks|Philosophy|Philosophy of Religion',
        ),
        15176 => array(
            'Id' => 15176,
            'Label' => 'Textbooks|Politics & Current Events',
        ),
        15177 => array(
            'Id' => 15177,
            'Label' => 'Textbooks|Politics & Current Events|Current Events',
        ),
        15178 => array(
            'Id' => 15178,
            'Label' => 'Textbooks|Politics & Current Events|Foreign Policy & International Relations',
        ),
        15179 => array(
            'Id' => 15179,
            'Label' => 'Textbooks|Politics & Current Events|Local Governments',
        ),
        15180 => array(
            'Id' => 15180,
            'Label' => 'Textbooks|Politics & Current Events|National Governments',
        ),
        15181 => array(
            'Id' => 15181,
            'Label' => 'Textbooks|Politics & Current Events|Political Science',
        ),
        15182 => array(
            'Id' => 15182,
            'Label' => 'Textbooks|Politics & Current Events|Public Administration',
        ),
        15183 => array(
            'Id' => 15183,
            'Label' => 'Textbooks|Politics & Current Events|World Affairs',
        ),
        15184 => array(
            'Id' => 15184,
            'Label' => 'Textbooks|Professional & Technical',
        ),
        15185 => array(
            'Id' => 15185,
            'Label' => 'Textbooks|Professional & Technical|Design',
        ),
        15186 => array(
            'Id' => 15186,
            'Label' => 'Textbooks|Professional & Technical|Language Arts & Disciplines',
        ),
        15187 => array(
            'Id' => 15187,
            'Label' => 'Textbooks|Professional & Technical|Engineering',
        ),
        15188 => array(
            'Id' => 15188,
            'Label' => 'Textbooks|Professional & Technical|Law',
        ),
        15189 => array(
            'Id' => 15189,
            'Label' => 'Textbooks|Professional & Technical|Medical',
        ),
        15190 => array(
            'Id' => 15190,
            'Label' => 'Textbooks|Reference',
        ),
        15191 => array(
            'Id' => 15191,
            'Label' => 'Textbooks|Reference|Almanacs & Yearbooks',
        ),
        15192 => array(
            'Id' => 15192,
            'Label' => 'Textbooks|Reference|Atlases & Maps',
        ),
        15193 => array(
            'Id' => 15193,
            'Label' => 'Textbooks|Reference|Catalogs & Directories',
        ),
        15194 => array(
            'Id' => 15194,
            'Label' => 'Textbooks|Reference|Consumer Guides',
        ),
        15195 => array(
            'Id' => 15195,
            'Label' => 'Textbooks|Reference|Dictionaries & Thesauruses',
        ),
        15196 => array(
            'Id' => 15196,
            'Label' => 'Textbooks|Reference|Encyclopedias',
        ),
        15197 => array(
            'Id' => 15197,
            'Label' => 'Textbooks|Reference|Etiquette',
        ),
        15198 => array(
            'Id' => 15198,
            'Label' => 'Textbooks|Reference|Quotations',
        ),
        15199 => array(
            'Id' => 15199,
            'Label' => 'Textbooks|Reference|Study Aids',
        ),
        15200 => array(
            'Id' => 15200,
            'Label' => 'Textbooks|Reference|Words & Language',
        ),
        15201 => array(
            'Id' => 15201,
            'Label' => 'Textbooks|Reference|Writing',
        ),
        15202 => array(
            'Id' => 15202,
            'Label' => 'Textbooks|Religion & Spirituality',
        ),
        15203 => array(
            'Id' => 15203,
            'Label' => 'Textbooks|Religion & Spirituality|Bible Studies',
        ),
        15204 => array(
            'Id' => 15204,
            'Label' => 'Textbooks|Religion & Spirituality|Bibles',
        ),
        15205 => array(
            'Id' => 15205,
            'Label' => 'Textbooks|Religion & Spirituality|Buddhism',
        ),
        15206 => array(
            'Id' => 15206,
            'Label' => 'Textbooks|Religion & Spirituality|Christianity',
        ),
        15207 => array(
            'Id' => 15207,
            'Label' => 'Textbooks|Religion & Spirituality|Comparative Religion',
        ),
        15208 => array(
            'Id' => 15208,
            'Label' => 'Textbooks|Religion & Spirituality|Hinduism',
        ),
        15209 => array(
            'Id' => 15209,
            'Label' => 'Textbooks|Religion & Spirituality|Islam',
        ),
        15210 => array(
            'Id' => 15210,
            'Label' => 'Textbooks|Religion & Spirituality|Judaism',
        ),
        15211 => array(
            'Id' => 15211,
            'Label' => 'Textbooks|Religion & Spirituality|Spirituality',
        ),
        15212 => array(
            'Id' => 15212,
            'Label' => 'Textbooks|Romance',
        ),
        15213 => array(
            'Id' => 15213,
            'Label' => 'Textbooks|Romance|Contemporary',
        ),
        15214 => array(
            'Id' => 15214,
            'Label' => 'Textbooks|Romance|Erotic Romance',
        ),
        15215 => array(
            'Id' => 15215,
            'Label' => 'Textbooks|Romance|Paranormal',
        ),
        15216 => array(
            'Id' => 15216,
            'Label' => 'Textbooks|Romance|Historical',
        ),
        15217 => array(
            'Id' => 15217,
            'Label' => 'Textbooks|Romance|Short Stories',
        ),
        15218 => array(
            'Id' => 15218,
            'Label' => 'Textbooks|Romance|Suspense',
        ),
        15219 => array(
            'Id' => 15219,
            'Label' => 'Textbooks|Romance|Western',
        ),
        15220 => array(
            'Id' => 15220,
            'Label' => 'Textbooks|Sci-Fi & Fantasy',
        ),
        15221 => array(
            'Id' => 15221,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Fantasy',
        ),
        15222 => array(
            'Id' => 15222,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Fantasy|Contemporary',
        ),
        15223 => array(
            'Id' => 15223,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Fantasy|Epic',
        ),
        15224 => array(
            'Id' => 15224,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Fantasy|Historical',
        ),
        15225 => array(
            'Id' => 15225,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Fantasy|Paranormal',
        ),
        15226 => array(
            'Id' => 15226,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Fantasy|Short Stories',
        ),
        15227 => array(
            'Id' => 15227,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Science Fiction',
        ),
        15228 => array(
            'Id' => 15228,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Science Fiction & Literature',
        ),
        15229 => array(
            'Id' => 15229,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Science Fiction & Literature|Adventure',
        ),
        15230 => array(
            'Id' => 15230,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Science Fiction & Literature|High Tech',
        ),
        15231 => array(
            'Id' => 15231,
            'Label' => 'Textbooks|Sci-Fi & Fantasy|Science Fiction & Literature|Short Stories',
        ),
        15232 => array(
            'Id' => 15232,
            'Label' => 'Textbooks|Science & Nature',
        ),
        15233 => array(
            'Id' => 15233,
            'Label' => 'Textbooks|Science & Nature|Agriculture',
        ),
        15234 => array(
            'Id' => 15234,
            'Label' => 'Textbooks|Science & Nature|Astronomy',
        ),
        15235 => array(
            'Id' => 15235,
            'Label' => 'Textbooks|Science & Nature|Atmosphere',
        ),
        15236 => array(
            'Id' => 15236,
            'Label' => 'Textbooks|Science & Nature|Biology',
        ),
        15237 => array(
            'Id' => 15237,
            'Label' => 'Textbooks|Science & Nature|Chemistry',
        ),
        15238 => array(
            'Id' => 15238,
            'Label' => 'Textbooks|Science & Nature|Earth Sciences',
        ),
        15239 => array(
            'Id' => 15239,
            'Label' => 'Textbooks|Science & Nature|Ecology',
        ),
        15240 => array(
            'Id' => 15240,
            'Label' => 'Textbooks|Science & Nature|Environment',
        ),
        15241 => array(
            'Id' => 15241,
            'Label' => 'Textbooks|Science & Nature|Essays',
        ),
        15242 => array(
            'Id' => 15242,
            'Label' => 'Textbooks|Science & Nature|Geography',
        ),
        15243 => array(
            'Id' => 15243,
            'Label' => 'Textbooks|Science & Nature|Geology',
        ),
        15244 => array(
            'Id' => 15244,
            'Label' => 'Textbooks|Science & Nature|History',
        ),
        15245 => array(
            'Id' => 15245,
            'Label' => 'Textbooks|Science & Nature|Life Sciences',
        ),
        15246 => array(
            'Id' => 15246,
            'Label' => 'Textbooks|Science & Nature|Nature',
        ),
        15247 => array(
            'Id' => 15247,
            'Label' => 'Textbooks|Science & Nature|Physics',
        ),
        15248 => array(
            'Id' => 15248,
            'Label' => 'Textbooks|Science & Nature|Reference',
        ),
        15249 => array(
            'Id' => 15249,
            'Label' => 'Textbooks|Social Science',
        ),
        15250 => array(
            'Id' => 15250,
            'Label' => 'Textbooks|Social Science|Anthropology',
        ),
        15251 => array(
            'Id' => 15251,
            'Label' => 'Textbooks|Social Science|Archaeology',
        ),
        15252 => array(
            'Id' => 15252,
            'Label' => 'Textbooks|Social Science|Civics',
        ),
        15253 => array(
            'Id' => 15253,
            'Label' => 'Textbooks|Social Science|Government',
        ),
        15254 => array(
            'Id' => 15254,
            'Label' => 'Textbooks|Social Science|Social Studies',
        ),
        15255 => array(
            'Id' => 15255,
            'Label' => 'Textbooks|Social Science|Social Welfare',
        ),
        15256 => array(
            'Id' => 15256,
            'Label' => 'Textbooks|Social Science|Society',
        ),
        15257 => array(
            'Id' => 15257,
            'Label' => 'Textbooks|Social Science|Society|African Studies',
        ),
        15258 => array(
            'Id' => 15258,
            'Label' => 'Textbooks|Social Science|Society|American Studies',
        ),
        15259 => array(
            'Id' => 15259,
            'Label' => 'Textbooks|Social Science|Society|Asia Pacific Studies',
        ),
        15260 => array(
            'Id' => 15260,
            'Label' => 'Textbooks|Social Science|Society|Cross-Cultural Studies',
        ),
        15261 => array(
            'Id' => 15261,
            'Label' => 'Textbooks|Social Science|Society|European Studies',
        ),
        15262 => array(
            'Id' => 15262,
            'Label' => 'Textbooks|Social Science|Society|Immigration & Emigration',
        ),
        15263 => array(
            'Id' => 15263,
            'Label' => 'Textbooks|Social Science|Society|Indigenous Studies',
        ),
        15264 => array(
            'Id' => 15264,
            'Label' => 'Textbooks|Social Science|Society|Latin & Caribbean Studies',
        ),
        15265 => array(
            'Id' => 15265,
            'Label' => 'Textbooks|Social Science|Society|Middle Eastern Studies',
        ),
        15266 => array(
            'Id' => 15266,
            'Label' => 'Textbooks|Social Science|Society|Race & Ethnicity Studies',
        ),
        15267 => array(
            'Id' => 15267,
            'Label' => 'Textbooks|Social Science|Society|Sexuality Studies',
        ),
        15268 => array(
            'Id' => 15268,
            'Label' => 'Textbooks|Social Science|Society|Women\'s Studies',
        ),
        15269 => array(
            'Id' => 15269,
            'Label' => 'Textbooks|Social Science|Sociology',
        ),
        15270 => array(
            'Id' => 15270,
            'Label' => 'Textbooks|Sports & Outdoors',
        ),
        15271 => array(
            'Id' => 15271,
            'Label' => 'Textbooks|Sports & Outdoors|Baseball',
        ),
        15272 => array(
            'Id' => 15272,
            'Label' => 'Textbooks|Sports & Outdoors|Basketball',
        ),
        15273 => array(
            'Id' => 15273,
            'Label' => 'Textbooks|Sports & Outdoors|Coaching',
        ),
        15274 => array(
            'Id' => 15274,
            'Label' => 'Textbooks|Sports & Outdoors|Equestrian',
        ),
        15275 => array(
            'Id' => 15275,
            'Label' => 'Textbooks|Sports & Outdoors|Extreme Sports',
        ),
        15276 => array(
            'Id' => 15276,
            'Label' => 'Textbooks|Sports & Outdoors|Football',
        ),
        15277 => array(
            'Id' => 15277,
            'Label' => 'Textbooks|Sports & Outdoors|Golf',
        ),
        15278 => array(
            'Id' => 15278,
            'Label' => 'Textbooks|Sports & Outdoors|Hockey',
        ),
        15279 => array(
            'Id' => 15279,
            'Label' => 'Textbooks|Sports & Outdoors|Motor Sports',
        ),
        15280 => array(
            'Id' => 15280,
            'Label' => 'Textbooks|Sports & Outdoors|Mountaineering',
        ),
        15281 => array(
            'Id' => 15281,
            'Label' => 'Textbooks|Sports & Outdoors|Outdoors',
        ),
        15282 => array(
            'Id' => 15282,
            'Label' => 'Textbooks|Sports & Outdoors|Racket Sports',
        ),
        15283 => array(
            'Id' => 15283,
            'Label' => 'Textbooks|Sports & Outdoors|Reference',
        ),
        15284 => array(
            'Id' => 15284,
            'Label' => 'Textbooks|Sports & Outdoors|Soccer',
        ),
        15285 => array(
            'Id' => 15285,
            'Label' => 'Textbooks|Sports & Outdoors|Training',
        ),
        15286 => array(
            'Id' => 15286,
            'Label' => 'Textbooks|Sports & Outdoors|Water Sports',
        ),
        15287 => array(
            'Id' => 15287,
            'Label' => 'Textbooks|Sports & Outdoors|Winter Sports',
        ),
        15288 => array(
            'Id' => 15288,
            'Label' => 'Textbooks|Teaching & Learning',
        ),
        15289 => array(
            'Id' => 15289,
            'Label' => 'Textbooks|Teaching & Learning|Adult Education',
        ),
        15290 => array(
            'Id' => 15290,
            'Label' => 'Textbooks|Teaching & Learning|Curriculum & Teaching',
        ),
        15291 => array(
            'Id' => 15291,
            'Label' => 'Textbooks|Teaching & Learning|Educational Leadership',
        ),
        15292 => array(
            'Id' => 15292,
            'Label' => 'Textbooks|Teaching & Learning|Educational Technology',
        ),
        15293 => array(
            'Id' => 15293,
            'Label' => 'Textbooks|Teaching & Learning|Family & Childcare',
        ),
        15294 => array(
            'Id' => 15294,
            'Label' => 'Textbooks|Teaching & Learning|Information & Library Science',
        ),
        15295 => array(
            'Id' => 15295,
            'Label' => 'Textbooks|Teaching & Learning|Learning Resources',
        ),
        15296 => array(
            'Id' => 15296,
            'Label' => 'Textbooks|Teaching & Learning|Psychology & Research',
        ),
        15297 => array(
            'Id' => 15297,
            'Label' => 'Textbooks|Teaching & Learning|Special Education',
        ),
        15298 => array(
            'Id' => 15298,
            'Label' => 'Textbooks|Travel & Adventure',
        ),
        15299 => array(
            'Id' => 15299,
            'Label' => 'Textbooks|Travel & Adventure|Africa',
        ),
        15300 => array(
            'Id' => 15300,
            'Label' => 'Textbooks|Travel & Adventure|Americas',
        ),
        15301 => array(
            'Id' => 15301,
            'Label' => 'Textbooks|Travel & Adventure|Americas|Canada',
        ),
        15302 => array(
            'Id' => 15302,
            'Label' => 'Textbooks|Travel & Adventure|Americas|Latin America',
        ),
        15303 => array(
            'Id' => 15303,
            'Label' => 'Textbooks|Travel & Adventure|Americas|United States',
        ),
        15304 => array(
            'Id' => 15304,
            'Label' => 'Textbooks|Travel & Adventure|Asia',
        ),
        15305 => array(
            'Id' => 15305,
            'Label' => 'Textbooks|Travel & Adventure|Caribbean',
        ),
        15306 => array(
            'Id' => 15306,
            'Label' => 'Textbooks|Travel & Adventure|Essays & Memoirs',
        ),
        15307 => array(
            'Id' => 15307,
            'Label' => 'Textbooks|Travel & Adventure|Europe',
        ),
        15308 => array(
            'Id' => 15308,
            'Label' => 'Textbooks|Travel & Adventure|Middle East',
        ),
        15309 => array(
            'Id' => 15309,
            'Label' => 'Textbooks|Travel & Adventure|Oceania',
        ),
        15310 => array(
            'Id' => 15310,
            'Label' => 'Textbooks|Travel & Adventure|Specialty Travel',
        ),
        15311 => array(
            'Id' => 15311,
            'Label' => 'Textbooks|Comics & Graphic Novels|Comics',
        ),
        15312 => array(
            'Id' => 15312,
            'Label' => 'Textbooks|Reference|Manuals',
        ),
        100000 => array(
            'Id' => 100000,
            'Label' => 'Music|Christian & Gospel',
        ),
        100001 => array(
            'Id' => 100001,
            'Label' => 'Music|Classical|Art Song',
        ),
        100002 => array(
            'Id' => 100002,
            'Label' => 'Music|Classical|Brass & Woodwinds',
        ),
        100003 => array(
            'Id' => 100003,
            'Label' => 'Music|Classical|Solo Instrumental',
        ),
        100004 => array(
            'Id' => 100004,
            'Label' => 'Music|Classical|Contemporary Era',
        ),
        100005 => array(
            'Id' => 100005,
            'Label' => 'Music|Classical|Oratorio',
        ),
        100006 => array(
            'Id' => 100006,
            'Label' => 'Music|Classical|Cantata',
        ),
        100007 => array(
            'Id' => 100007,
            'Label' => 'Music|Classical|Electronic',
        ),
        100008 => array(
            'Id' => 100008,
            'Label' => 'Music|Classical|Sacred',
        ),
        100009 => array(
            'Id' => 100009,
            'Label' => 'Music|Classical|Guitar',
        ),
        100010 => array(
            'Id' => 100010,
            'Label' => 'Music|Classical|Piano',
        ),
        100011 => array(
            'Id' => 100011,
            'Label' => 'Music|Classical|Violin',
        ),
        100012 => array(
            'Id' => 100012,
            'Label' => 'Music|Classical|Cello',
        ),
        100013 => array(
            'Id' => 100013,
            'Label' => 'Music|Classical|Percussion',
        ),
        100014 => array(
            'Id' => 100014,
            'Label' => 'Music|Electronic|Dubstep',
        ),
        100015 => array(
            'Id' => 100015,
            'Label' => 'Music|Electronic|Bass',
        ),
        100016 => array(
            'Id' => 100016,
            'Label' => 'Music|Hip-Hop/Rap|UK Hip-Hop',
        ),
        100017 => array(
            'Id' => 100017,
            'Label' => 'Music|Reggae|Lovers Rock',
        ),
        100018 => array(
            'Id' => 100018,
            'Label' => 'Music|Alternative|EMO',
        ),
        100019 => array(
            'Id' => 100019,
            'Label' => 'Music|Alternative|Pop Punk',
        ),
        100020 => array(
            'Id' => 100020,
            'Label' => 'Music|Alternative|Indie Pop',
        ),
        100021 => array(
            'Id' => 100021,
            'Label' => 'Music|New Age|Yoga',
        ),
        100022 => array(
            'Id' => 100022,
            'Label' => 'Music|Pop|Tribute',
        ),
        40000000 => array(
            'Id' => 40000000,
            'Label' => 'iTunes U',
        ),
        40000001 => array(
            'Id' => 40000001,
            'Label' => 'iTunes U|Business',
        ),
        40000002 => array(
            'Id' => 40000002,
            'Label' => 'iTunes U|Business|Economics',
        ),
        40000003 => array(
            'Id' => 40000003,
            'Label' => 'iTunes U|Business|Finance',
        ),
        40000004 => array(
            'Id' => 40000004,
            'Label' => 'iTunes U|Business|Hospitality',
        ),
        40000005 => array(
            'Id' => 40000005,
            'Label' => 'iTunes U|Business|Management',
        ),
        40000006 => array(
            'Id' => 40000006,
            'Label' => 'iTunes U|Business|Marketing',
        ),
        40000007 => array(
            'Id' => 40000007,
            'Label' => 'iTunes U|Business|Personal Finance',
        ),
        40000008 => array(
            'Id' => 40000008,
            'Label' => 'iTunes U|Business|Real Estate',
        ),
        40000009 => array(
            'Id' => 40000009,
            'Label' => 'iTunes U|Engineering',
        ),
        40000010 => array(
            'Id' => 40000010,
            'Label' => 'iTunes U|Engineering|Chemical & Petroleum Engineering',
        ),
        40000011 => array(
            'Id' => 40000011,
            'Label' => 'iTunes U|Engineering|Civil Engineering',
        ),
        40000012 => array(
            'Id' => 40000012,
            'Label' => 'iTunes U|Engineering|Computer Science',
        ),
        40000013 => array(
            'Id' => 40000013,
            'Label' => 'iTunes U|Engineering|Electrical Engineering',
        ),
        40000014 => array(
            'Id' => 40000014,
            'Label' => 'iTunes U|Engineering|Environmental Engineering',
        ),
        40000015 => array(
            'Id' => 40000015,
            'Label' => 'iTunes U|Engineering|Mechanical Engineering',
        ),
        40000016 => array(
            'Id' => 40000016,
            'Label' => 'iTunes U|Art & Architecture',
        ),
        40000017 => array(
            'Id' => 40000017,
            'Label' => 'iTunes U|Art & Architecture|Architecture',
        ),
        40000019 => array(
            'Id' => 40000019,
            'Label' => 'iTunes U|Art & Architecture|Art History',
        ),
        40000020 => array(
            'Id' => 40000020,
            'Label' => 'iTunes U|Art & Architecture|Dance',
        ),
        40000021 => array(
            'Id' => 40000021,
            'Label' => 'iTunes U|Art & Architecture|Film',
        ),
        40000022 => array(
            'Id' => 40000022,
            'Label' => 'iTunes U|Art & Architecture|Design',
        ),
        40000023 => array(
            'Id' => 40000023,
            'Label' => 'iTunes U|Art & Architecture|Interior Design',
        ),
        40000024 => array(
            'Id' => 40000024,
            'Label' => 'iTunes U|Art & Architecture|Music',
        ),
        40000025 => array(
            'Id' => 40000025,
            'Label' => 'iTunes U|Art & Architecture|Theater',
        ),
        40000026 => array(
            'Id' => 40000026,
            'Label' => 'iTunes U|Health & Medicine',
        ),
        40000027 => array(
            'Id' => 40000027,
            'Label' => 'iTunes U|Health & Medicine|Anatomy & Physiology',
        ),
        40000028 => array(
            'Id' => 40000028,
            'Label' => 'iTunes U|Health & Medicine|Behavioral Science',
        ),
        40000029 => array(
            'Id' => 40000029,
            'Label' => 'iTunes U|Health & Medicine|Dentistry',
        ),
        40000030 => array(
            'Id' => 40000030,
            'Label' => 'iTunes U|Health & Medicine|Diet & Nutrition',
        ),
        40000031 => array(
            'Id' => 40000031,
            'Label' => 'iTunes U|Health & Medicine|Emergency Medicine',
        ),
        40000032 => array(
            'Id' => 40000032,
            'Label' => 'iTunes U|Health & Medicine|Genetics',
        ),
        40000033 => array(
            'Id' => 40000033,
            'Label' => 'iTunes U|Health & Medicine|Gerontology',
        ),
        40000034 => array(
            'Id' => 40000034,
            'Label' => 'iTunes U|Health & Medicine|Health & Exercise Science',
        ),
        40000035 => array(
            'Id' => 40000035,
            'Label' => 'iTunes U|Health & Medicine|Immunology',
        ),
        40000036 => array(
            'Id' => 40000036,
            'Label' => 'iTunes U|Health & Medicine|Neuroscience',
        ),
        40000037 => array(
            'Id' => 40000037,
            'Label' => 'iTunes U|Health & Medicine|Pharmacology & Toxicology',
        ),
        40000038 => array(
            'Id' => 40000038,
            'Label' => 'iTunes U|Health & Medicine|Psychiatry',
        ),
        40000039 => array(
            'Id' => 40000039,
            'Label' => 'iTunes U|Health & Medicine|Global Health',
        ),
        40000040 => array(
            'Id' => 40000040,
            'Label' => 'iTunes U|Health & Medicine|Radiology',
        ),
        40000041 => array(
            'Id' => 40000041,
            'Label' => 'iTunes U|History',
        ),
        40000042 => array(
            'Id' => 40000042,
            'Label' => 'iTunes U|History|Ancient History',
        ),
        40000043 => array(
            'Id' => 40000043,
            'Label' => 'iTunes U|History|Medieval History',
        ),
        40000044 => array(
            'Id' => 40000044,
            'Label' => 'iTunes U|History|Military History',
        ),
        40000045 => array(
            'Id' => 40000045,
            'Label' => 'iTunes U|History|Modern History',
        ),
        40000046 => array(
            'Id' => 40000046,
            'Label' => 'iTunes U|History|African History',
        ),
        40000047 => array(
            'Id' => 40000047,
            'Label' => 'iTunes U|History|Asia-Pacific History',
        ),
        40000048 => array(
            'Id' => 40000048,
            'Label' => 'iTunes U|History|European History',
        ),
        40000049 => array(
            'Id' => 40000049,
            'Label' => 'iTunes U|History|Middle Eastern History',
        ),
        40000050 => array(
            'Id' => 40000050,
            'Label' => 'iTunes U|History|North American History',
        ),
        40000051 => array(
            'Id' => 40000051,
            'Label' => 'iTunes U|History|South American History',
        ),
        40000053 => array(
            'Id' => 40000053,
            'Label' => 'iTunes U|Communications & Media',
        ),
        40000054 => array(
            'Id' => 40000054,
            'Label' => 'iTunes U|Philosophy',
        ),
        40000055 => array(
            'Id' => 40000055,
            'Label' => 'iTunes U|Religion & Spirituality',
        ),
        40000056 => array(
            'Id' => 40000056,
            'Label' => 'iTunes U|Language',
        ),
        40000057 => array(
            'Id' => 40000057,
            'Label' => 'iTunes U|Language|African Languages',
        ),
        40000058 => array(
            'Id' => 40000058,
            'Label' => 'iTunes U|Language|Ancient Languages',
        ),
        40000061 => array(
            'Id' => 40000061,
            'Label' => 'iTunes U|Language|English',
        ),
        40000063 => array(
            'Id' => 40000063,
            'Label' => 'iTunes U|Language|French',
        ),
        40000064 => array(
            'Id' => 40000064,
            'Label' => 'iTunes U|Language|German',
        ),
        40000065 => array(
            'Id' => 40000065,
            'Label' => 'iTunes U|Language|Italian',
        ),
        40000066 => array(
            'Id' => 40000066,
            'Label' => 'iTunes U|Language|Linguistics',
        ),
        40000068 => array(
            'Id' => 40000068,
            'Label' => 'iTunes U|Language|Spanish',
        ),
        40000069 => array(
            'Id' => 40000069,
            'Label' => 'iTunes U|Language|Speech Pathology',
        ),
        40000070 => array(
            'Id' => 40000070,
            'Label' => 'iTunes U|Literature',
        ),
        40000071 => array(
            'Id' => 40000071,
            'Label' => 'iTunes U|Literature|Anthologies',
        ),
        40000072 => array(
            'Id' => 40000072,
            'Label' => 'iTunes U|Literature|Biography',
        ),
        40000073 => array(
            'Id' => 40000073,
            'Label' => 'iTunes U|Literature|Classics',
        ),
        40000074 => array(
            'Id' => 40000074,
            'Label' => 'iTunes U|Literature|Literary Criticism',
        ),
        40000075 => array(
            'Id' => 40000075,
            'Label' => 'iTunes U|Literature|Fiction',
        ),
        40000076 => array(
            'Id' => 40000076,
            'Label' => 'iTunes U|Literature|Poetry',
        ),
        40000077 => array(
            'Id' => 40000077,
            'Label' => 'iTunes U|Mathematics',
        ),
        40000078 => array(
            'Id' => 40000078,
            'Label' => 'iTunes U|Mathematics|Advanced Mathematics',
        ),
        40000079 => array(
            'Id' => 40000079,
            'Label' => 'iTunes U|Mathematics|Algebra',
        ),
        40000080 => array(
            'Id' => 40000080,
            'Label' => 'iTunes U|Mathematics|Arithmetic',
        ),
        40000081 => array(
            'Id' => 40000081,
            'Label' => 'iTunes U|Mathematics|Calculus',
        ),
        40000082 => array(
            'Id' => 40000082,
            'Label' => 'iTunes U|Mathematics|Geometry',
        ),
        40000083 => array(
            'Id' => 40000083,
            'Label' => 'iTunes U|Mathematics|Statistics',
        ),
        40000084 => array(
            'Id' => 40000084,
            'Label' => 'iTunes U|Science',
        ),
        40000085 => array(
            'Id' => 40000085,
            'Label' => 'iTunes U|Science|Agricultural',
        ),
        40000086 => array(
            'Id' => 40000086,
            'Label' => 'iTunes U|Science|Astronomy',
        ),
        40000087 => array(
            'Id' => 40000087,
            'Label' => 'iTunes U|Science|Atmosphere',
        ),
        40000088 => array(
            'Id' => 40000088,
            'Label' => 'iTunes U|Science|Biology',
        ),
        40000089 => array(
            'Id' => 40000089,
            'Label' => 'iTunes U|Science|Chemistry',
        ),
        40000090 => array(
            'Id' => 40000090,
            'Label' => 'iTunes U|Science|Ecology',
        ),
        40000091 => array(
            'Id' => 40000091,
            'Label' => 'iTunes U|Science|Geography',
        ),
        40000092 => array(
            'Id' => 40000092,
            'Label' => 'iTunes U|Science|Geology',
        ),
        40000093 => array(
            'Id' => 40000093,
            'Label' => 'iTunes U|Science|Physics',
        ),
        40000094 => array(
            'Id' => 40000094,
            'Label' => 'iTunes U|Psychology & Social Science',
        ),
        40000095 => array(
            'Id' => 40000095,
            'Label' => 'iTunes U|Law & Politics|Law',
        ),
        40000096 => array(
            'Id' => 40000096,
            'Label' => 'iTunes U|Law & Politics|Political Science',
        ),
        40000097 => array(
            'Id' => 40000097,
            'Label' => 'iTunes U|Law & Politics|Public Administration',
        ),
        40000098 => array(
            'Id' => 40000098,
            'Label' => 'iTunes U|Psychology & Social Science|Psychology',
        ),
        40000099 => array(
            'Id' => 40000099,
            'Label' => 'iTunes U|Psychology & Social Science|Social Welfare',
        ),
        40000100 => array(
            'Id' => 40000100,
            'Label' => 'iTunes U|Psychology & Social Science|Sociology',
        ),
        40000101 => array(
            'Id' => 40000101,
            'Label' => 'iTunes U|Society',
        ),
        40000103 => array(
            'Id' => 40000103,
            'Label' => 'iTunes U|Society|Asia Pacific Studies',
        ),
        40000104 => array(
            'Id' => 40000104,
            'Label' => 'iTunes U|Society|European Studies',
        ),
        40000105 => array(
            'Id' => 40000105,
            'Label' => 'iTunes U|Society|Indigenous Studies',
        ),
        40000106 => array(
            'Id' => 40000106,
            'Label' => 'iTunes U|Society|Latin & Caribbean Studies',
        ),
        40000107 => array(
            'Id' => 40000107,
            'Label' => 'iTunes U|Society|Middle Eastern Studies',
        ),
        40000108 => array(
            'Id' => 40000108,
            'Label' => 'iTunes U|Society|Women\'s Studies',
        ),
        40000109 => array(
            'Id' => 40000109,
            'Label' => 'iTunes U|Teaching & Learning',
        ),
        40000110 => array(
            'Id' => 40000110,
            'Label' => 'iTunes U|Teaching & Learning|Curriculum & Teaching',
        ),
        40000111 => array(
            'Id' => 40000111,
            'Label' => 'iTunes U|Teaching & Learning|Educational Leadership',
        ),
        40000112 => array(
            'Id' => 40000112,
            'Label' => 'iTunes U|Teaching & Learning|Family & Childcare',
        ),
        40000113 => array(
            'Id' => 40000113,
            'Label' => 'iTunes U|Teaching & Learning|Learning Resources',
        ),
        40000114 => array(
            'Id' => 40000114,
            'Label' => 'iTunes U|Teaching & Learning|Psychology & Research',
        ),
        40000115 => array(
            'Id' => 40000115,
            'Label' => 'iTunes U|Teaching & Learning|Special Education',
        ),
        40000116 => array(
            'Id' => 40000116,
            'Label' => 'iTunes U|Art & Architecture|Culinary Arts',
        ),
        40000117 => array(
            'Id' => 40000117,
            'Label' => 'iTunes U|Art & Architecture|Fashion',
        ),
        40000118 => array(
            'Id' => 40000118,
            'Label' => 'iTunes U|Art & Architecture|Media Arts',
        ),
        40000119 => array(
            'Id' => 40000119,
            'Label' => 'iTunes U|Art & Architecture|Photography',
        ),
        40000120 => array(
            'Id' => 40000120,
            'Label' => 'iTunes U|Art & Architecture|Visual Art',
        ),
        40000121 => array(
            'Id' => 40000121,
            'Label' => 'iTunes U|Business|Entrepreneurship',
        ),
        40000122 => array(
            'Id' => 40000122,
            'Label' => 'iTunes U|Communications & Media|Broadcasting',
        ),
        40000123 => array(
            'Id' => 40000123,
            'Label' => 'iTunes U|Communications & Media|Digital Media',
        ),
        40000124 => array(
            'Id' => 40000124,
            'Label' => 'iTunes U|Communications & Media|Journalism',
        ),
        40000125 => array(
            'Id' => 40000125,
            'Label' => 'iTunes U|Communications & Media|Photojournalism',
        ),
        40000126 => array(
            'Id' => 40000126,
            'Label' => 'iTunes U|Communications & Media|Print',
        ),
        40000127 => array(
            'Id' => 40000127,
            'Label' => 'iTunes U|Communications & Media|Speech',
        ),
        40000128 => array(
            'Id' => 40000128,
            'Label' => 'iTunes U|Communications & Media|Writing',
        ),
        40000129 => array(
            'Id' => 40000129,
            'Label' => 'iTunes U|Health & Medicine|Nursing',
        ),
        40000130 => array(
            'Id' => 40000130,
            'Label' => 'iTunes U|Language|Arabic',
        ),
        40000131 => array(
            'Id' => 40000131,
            'Label' => 'iTunes U|Language|Chinese',
        ),
        40000132 => array(
            'Id' => 40000132,
            'Label' => 'iTunes U|Language|Hebrew',
        ),
        40000133 => array(
            'Id' => 40000133,
            'Label' => 'iTunes U|Language|Hindi',
        ),
        40000134 => array(
            'Id' => 40000134,
            'Label' => 'iTunes U|Language|Indigenous Languages',
        ),
        40000135 => array(
            'Id' => 40000135,
            'Label' => 'iTunes U|Language|Japanese',
        ),
        40000136 => array(
            'Id' => 40000136,
            'Label' => 'iTunes U|Language|Korean',
        ),
        40000137 => array(
            'Id' => 40000137,
            'Label' => 'iTunes U|Language|Other Languages',
        ),
        40000138 => array(
            'Id' => 40000138,
            'Label' => 'iTunes U|Language|Portuguese',
        ),
        40000139 => array(
            'Id' => 40000139,
            'Label' => 'iTunes U|Language|Russian',
        ),
        40000140 => array(
            'Id' => 40000140,
            'Label' => 'iTunes U|Law & Politics',
        ),
        40000141 => array(
            'Id' => 40000141,
            'Label' => 'iTunes U|Law & Politics|Foreign Policy & International Relations',
        ),
        40000142 => array(
            'Id' => 40000142,
            'Label' => 'iTunes U|Law & Politics|Local Governments',
        ),
        40000143 => array(
            'Id' => 40000143,
            'Label' => 'iTunes U|Law & Politics|National Governments',
        ),
        40000144 => array(
            'Id' => 40000144,
            'Label' => 'iTunes U|Law & Politics|World Affairs',
        ),
        40000145 => array(
            'Id' => 40000145,
            'Label' => 'iTunes U|Literature|Comparative Literature',
        ),
        40000146 => array(
            'Id' => 40000146,
            'Label' => 'iTunes U|Philosophy|Aesthetics',
        ),
        40000147 => array(
            'Id' => 40000147,
            'Label' => 'iTunes U|Philosophy|Epistemology',
        ),
        40000148 => array(
            'Id' => 40000148,
            'Label' => 'iTunes U|Philosophy|Ethics',
        ),
        40000149 => array(
            'Id' => 40000149,
            'Label' => 'iTunes U|Philosophy|Metaphysics',
        ),
        40000150 => array(
            'Id' => 40000150,
            'Label' => 'iTunes U|Philosophy|Political Philosophy',
        ),
        40000151 => array(
            'Id' => 40000151,
            'Label' => 'iTunes U|Philosophy|Logic',
        ),
        40000152 => array(
            'Id' => 40000152,
            'Label' => 'iTunes U|Philosophy|Philosophy of Language',
        ),
        40000153 => array(
            'Id' => 40000153,
            'Label' => 'iTunes U|Philosophy|Philosophy of Religion',
        ),
        40000154 => array(
            'Id' => 40000154,
            'Label' => 'iTunes U|Psychology & Social Science|Archaeology',
        ),
        40000155 => array(
            'Id' => 40000155,
            'Label' => 'iTunes U|Psychology & Social Science|Anthropology',
        ),
        40000156 => array(
            'Id' => 40000156,
            'Label' => 'iTunes U|Religion & Spirituality|Buddhism',
        ),
        40000157 => array(
            'Id' => 40000157,
            'Label' => 'iTunes U|Religion & Spirituality|Christianity',
        ),
        40000158 => array(
            'Id' => 40000158,
            'Label' => 'iTunes U|Religion & Spirituality|Comparative Religion',
        ),
        40000159 => array(
            'Id' => 40000159,
            'Label' => 'iTunes U|Religion & Spirituality|Hinduism',
        ),
        40000160 => array(
            'Id' => 40000160,
            'Label' => 'iTunes U|Religion & Spirituality|Islam',
        ),
        40000161 => array(
            'Id' => 40000161,
            'Label' => 'iTunes U|Religion & Spirituality|Judaism',
        ),
        40000162 => array(
            'Id' => 40000162,
            'Label' => 'iTunes U|Religion & Spirituality|Other Religions',
        ),
        40000163 => array(
            'Id' => 40000163,
            'Label' => 'iTunes U|Religion & Spirituality|Spirituality',
        ),
        40000164 => array(
            'Id' => 40000164,
            'Label' => 'iTunes U|Science|Environment',
        ),
        40000165 => array(
            'Id' => 40000165,
            'Label' => 'iTunes U|Society|African Studies',
        ),
        40000166 => array(
            'Id' => 40000166,
            'Label' => 'iTunes U|Society|American Studies',
        ),
        40000167 => array(
            'Id' => 40000167,
            'Label' => 'iTunes U|Society|Cross-cultural Studies',
        ),
        40000168 => array(
            'Id' => 40000168,
            'Label' => 'iTunes U|Society|Immigration & Emigration',
        ),
        40000169 => array(
            'Id' => 40000169,
            'Label' => 'iTunes U|Society|Race & Ethnicity Studies',
        ),
        40000170 => array(
            'Id' => 40000170,
            'Label' => 'iTunes U|Society|Sexuality Studies',
        ),
        40000171 => array(
            'Id' => 40000171,
            'Label' => 'iTunes U|Teaching & Learning|Educational Technology',
        ),
        40000172 => array(
            'Id' => 40000172,
            'Label' => 'iTunes U|Teaching & Learning|Information/Library Science',
        ),
        40000173 => array(
            'Id' => 40000173,
            'Label' => 'iTunes U|Language|Dutch',
        ),
        40000174 => array(
            'Id' => 40000174,
            'Label' => 'iTunes U|Language|Luxembourgish',
        ),
        40000175 => array(
            'Id' => 40000175,
            'Label' => 'iTunes U|Language|Swedish',
        ),
        40000176 => array(
            'Id' => 40000176,
            'Label' => 'iTunes U|Language|Norwegian',
        ),
        40000177 => array(
            'Id' => 40000177,
            'Label' => 'iTunes U|Language|Finnish',
        ),
        40000178 => array(
            'Id' => 40000178,
            'Label' => 'iTunes U|Language|Danish',
        ),
        40000179 => array(
            'Id' => 40000179,
            'Label' => 'iTunes U|Language|Polish',
        ),
        40000180 => array(
            'Id' => 40000180,
            'Label' => 'iTunes U|Language|Turkish',
        ),
        40000181 => array(
            'Id' => 40000181,
            'Label' => 'iTunes U|Language|Flemish',
        ),
        50000024 => array(
            'Id' => 50000024,
            'Label' => 'Audiobooks',
        ),
        50000040 => array(
            'Id' => 50000040,
            'Label' => 'Audiobooks|Fiction',
        ),
        50000041 => array(
            'Id' => 50000041,
            'Label' => 'Audiobooks|Arts & Entertainment',
        ),
        50000042 => array(
            'Id' => 50000042,
            'Label' => 'Audiobooks|Biography & Memoir',
        ),
        50000043 => array(
            'Id' => 50000043,
            'Label' => 'Audiobooks|Business',
        ),
        50000044 => array(
            'Id' => 50000044,
            'Label' => 'Audiobooks|Kids & Young Adults',
        ),
        50000045 => array(
            'Id' => 50000045,
            'Label' => 'Audiobooks|Classics',
        ),
        50000046 => array(
            'Id' => 50000046,
            'Label' => 'Audiobooks|Comedy',
        ),
        50000047 => array(
            'Id' => 50000047,
            'Label' => 'Audiobooks|Drama & Poetry',
        ),
        50000048 => array(
            'Id' => 50000048,
            'Label' => 'Audiobooks|Speakers & Storytellers',
        ),
        50000049 => array(
            'Id' => 50000049,
            'Label' => 'Audiobooks|History',
        ),
        50000050 => array(
            'Id' => 50000050,
            'Label' => 'Audiobooks|Languages',
        ),
        50000051 => array(
            'Id' => 50000051,
            'Label' => 'Audiobooks|Mystery',
        ),
        50000052 => array(
            'Id' => 50000052,
            'Label' => 'Audiobooks|Nonfiction',
        ),
        50000053 => array(
            'Id' => 50000053,
            'Label' => 'Audiobooks|Religion & Spirituality',
        ),
        50000054 => array(
            'Id' => 50000054,
            'Label' => 'Audiobooks|Science',
        ),
        50000055 => array(
            'Id' => 50000055,
            'Label' => 'Audiobooks|Sci Fi & Fantasy',
        ),
        50000056 => array(
            'Id' => 50000056,
            'Label' => 'Audiobooks|Self Development',
        ),
        50000057 => array(
            'Id' => 50000057,
            'Label' => 'Audiobooks|Sports',
        ),
        50000058 => array(
            'Id' => 50000058,
            'Label' => 'Audiobooks|Technology',
        ),
        50000059 => array(
            'Id' => 50000059,
            'Label' => 'Audiobooks|Travel & Adventure',
        ),
        50000061 => array(
            'Id' => 50000061,
            'Label' => 'Music|Spoken Word',
        ),
        50000063 => array(
            'Id' => 50000063,
            'Label' => 'Music|Disney',
        ),
        50000064 => array(
            'Id' => 50000064,
            'Label' => 'Music|French Pop',
        ),
        50000066 => array(
            'Id' => 50000066,
            'Label' => 'Music|German Pop',
        ),
        50000068 => array(
            'Id' => 50000068,
            'Label' => 'Music|German Folk',
        ),
        50000069 => array(
            'Id' => 50000069,
            'Label' => 'Audiobooks|Romance',
        ),
        50000070 => array(
            'Id' => 50000070,
            'Label' => 'Audiobooks|Audiobooks Latino',
        ),
        50000071 => array(
            'Id' => 50000071,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Action',
        ),
        50000072 => array(
            'Id' => 50000072,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Comedy',
        ),
        50000073 => array(
            'Id' => 50000073,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Erotica',
        ),
        50000074 => array(
            'Id' => 50000074,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Fantasy',
        ),
        50000075 => array(
            'Id' => 50000075,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Four Cell Manga',
        ),
        50000076 => array(
            'Id' => 50000076,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Gay & Lesbian',
        ),
        50000077 => array(
            'Id' => 50000077,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Hard-Boiled',
        ),
        50000078 => array(
            'Id' => 50000078,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Heroes',
        ),
        50000079 => array(
            'Id' => 50000079,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Historical Fiction',
        ),
        50000080 => array(
            'Id' => 50000080,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Mecha',
        ),
        50000081 => array(
            'Id' => 50000081,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Mystery',
        ),
        50000082 => array(
            'Id' => 50000082,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Nonfiction',
        ),
        50000083 => array(
            'Id' => 50000083,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Religious',
        ),
        50000084 => array(
            'Id' => 50000084,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Romance',
        ),
        50000085 => array(
            'Id' => 50000085,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Romantic Comedy',
        ),
        50000086 => array(
            'Id' => 50000086,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Science Fiction',
        ),
        50000087 => array(
            'Id' => 50000087,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Sports',
        ),
        50000088 => array(
            'Id' => 50000088,
            'Label' => 'Books|Fiction & Literature|Light Novels',
        ),
        50000089 => array(
            'Id' => 50000089,
            'Label' => 'Books|Comics & Graphic Novels|Manga|Horror',
        ),
        50000090 => array(
            'Id' => 50000090,
            'Label' => 'Books|Comics & Graphic Novels|Comics',
        ),
    );

}
