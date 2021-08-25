<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GeoTiff;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ProjectedCSType extends AbstractTag
{

    protected $Id = 3072;

    protected $Name = 'ProjectedCSType';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Projected CS Type';

    protected $Values = array(
        2100 => array(
            'Id' => 2100,
            'Label' => 'GGRS87 Greek Grid',
        ),
        2176 => array(
            'Id' => 2176,
            'Label' => 'ETRS89 Poland CS2000 zone 5',
        ),
        2177 => array(
            'Id' => 2177,
            'Label' => 'ETRS89 Poland CS2000 zone 7',
        ),
        2178 => array(
            'Id' => 2178,
            'Label' => 'ETRS89 Poland CS2000 zone 8',
        ),
        2180 => array(
            'Id' => 2180,
            'Label' => 'ETRS89 Poland CS92',
        ),
        2204 => array(
            'Id' => 2204,
            'Label' => 'NAD27 Tennessee',
        ),
        2205 => array(
            'Id' => 2205,
            'Label' => 'NAD83 Kentucky North',
        ),
        2391 => array(
            'Id' => 2391,
            'Label' => 'KKJ Finland zone 1',
        ),
        2392 => array(
            'Id' => 2392,
            'Label' => 'KKJ Finland zone 2',
        ),
        2393 => array(
            'Id' => 2393,
            'Label' => 'KKJ Finland zone 3',
        ),
        2394 => array(
            'Id' => 2394,
            'Label' => 'KKJ Finland zone 4',
        ),
        2400 => array(
            'Id' => 2400,
            'Label' => 'RT90 2 5 gon W',
        ),
        2600 => array(
            'Id' => 2600,
            'Label' => 'Lietuvos Koordinoei Sistema 1994',
        ),
        3053 => array(
            'Id' => 3053,
            'Label' => 'Hjorsey 1955 Lambert',
        ),
        3057 => array(
            'Id' => 3057,
            'Label' => 'ISN93 Lambert 1993',
        ),
        3300 => array(
            'Id' => 3300,
            'Label' => 'Estonian Coordinate System of 1992',
        ),
        20137 => array(
            'Id' => 20137,
            'Label' => 'Adindan UTM zone 37N',
        ),
        20138 => array(
            'Id' => 20138,
            'Label' => 'Adindan UTM zone 38N',
        ),
        20248 => array(
            'Id' => 20248,
            'Label' => 'AGD66 AMG zone 48',
        ),
        20249 => array(
            'Id' => 20249,
            'Label' => 'AGD66 AMG zone 49',
        ),
        20250 => array(
            'Id' => 20250,
            'Label' => 'AGD66 AMG zone 50',
        ),
        20251 => array(
            'Id' => 20251,
            'Label' => 'AGD66 AMG zone 51',
        ),
        20252 => array(
            'Id' => 20252,
            'Label' => 'AGD66 AMG zone 52',
        ),
        20253 => array(
            'Id' => 20253,
            'Label' => 'AGD66 AMG zone 53',
        ),
        20254 => array(
            'Id' => 20254,
            'Label' => 'AGD66 AMG zone 54',
        ),
        20255 => array(
            'Id' => 20255,
            'Label' => 'AGD66 AMG zone 55',
        ),
        20256 => array(
            'Id' => 20256,
            'Label' => 'AGD66 AMG zone 56',
        ),
        20257 => array(
            'Id' => 20257,
            'Label' => 'AGD66 AMG zone 57',
        ),
        20258 => array(
            'Id' => 20258,
            'Label' => 'AGD66 AMG zone 58',
        ),
        20348 => array(
            'Id' => 20348,
            'Label' => 'AGD84 AMG zone 48',
        ),
        20349 => array(
            'Id' => 20349,
            'Label' => 'AGD84 AMG zone 49',
        ),
        20350 => array(
            'Id' => 20350,
            'Label' => 'AGD84 AMG zone 50',
        ),
        20351 => array(
            'Id' => 20351,
            'Label' => 'AGD84 AMG zone 51',
        ),
        20352 => array(
            'Id' => 20352,
            'Label' => 'AGD84 AMG zone 52',
        ),
        20353 => array(
            'Id' => 20353,
            'Label' => 'AGD84 AMG zone 53',
        ),
        20354 => array(
            'Id' => 20354,
            'Label' => 'AGD84 AMG zone 54',
        ),
        20355 => array(
            'Id' => 20355,
            'Label' => 'AGD84 AMG zone 55',
        ),
        20356 => array(
            'Id' => 20356,
            'Label' => 'AGD84 AMG zone 56',
        ),
        20357 => array(
            'Id' => 20357,
            'Label' => 'AGD84 AMG zone 57',
        ),
        20358 => array(
            'Id' => 20358,
            'Label' => 'AGD84 AMG zone 58',
        ),
        20437 => array(
            'Id' => 20437,
            'Label' => 'Ain el Abd UTM zone 37N',
        ),
        20438 => array(
            'Id' => 20438,
            'Label' => 'Ain el Abd UTM zone 38N',
        ),
        20439 => array(
            'Id' => 20439,
            'Label' => 'Ain el Abd UTM zone 39N',
        ),
        20499 => array(
            'Id' => 20499,
            'Label' => 'Ain el Abd Bahrain Grid',
        ),
        20538 => array(
            'Id' => 20538,
            'Label' => 'Afgooye UTM zone 38N',
        ),
        20539 => array(
            'Id' => 20539,
            'Label' => 'Afgooye UTM zone 39N',
        ),
        20700 => array(
            'Id' => 20700,
            'Label' => 'Lisbon Portugese Grid',
        ),
        20822 => array(
            'Id' => 20822,
            'Label' => 'Aratu UTM zone 22S',
        ),
        20823 => array(
            'Id' => 20823,
            'Label' => 'Aratu UTM zone 23S',
        ),
        20824 => array(
            'Id' => 20824,
            'Label' => 'Aratu UTM zone 24S',
        ),
        20973 => array(
            'Id' => 20973,
            'Label' => 'Arc 1950 Lo13',
        ),
        20975 => array(
            'Id' => 20975,
            'Label' => 'Arc 1950 Lo15',
        ),
        20977 => array(
            'Id' => 20977,
            'Label' => 'Arc 1950 Lo17',
        ),
        20979 => array(
            'Id' => 20979,
            'Label' => 'Arc 1950 Lo19',
        ),
        20981 => array(
            'Id' => 20981,
            'Label' => 'Arc 1950 Lo21',
        ),
        20983 => array(
            'Id' => 20983,
            'Label' => 'Arc 1950 Lo23',
        ),
        20985 => array(
            'Id' => 20985,
            'Label' => 'Arc 1950 Lo25',
        ),
        20987 => array(
            'Id' => 20987,
            'Label' => 'Arc 1950 Lo27',
        ),
        20989 => array(
            'Id' => 20989,
            'Label' => 'Arc 1950 Lo29',
        ),
        20991 => array(
            'Id' => 20991,
            'Label' => 'Arc 1950 Lo31',
        ),
        20993 => array(
            'Id' => 20993,
            'Label' => 'Arc 1950 Lo33',
        ),
        20995 => array(
            'Id' => 20995,
            'Label' => 'Arc 1950 Lo35',
        ),
        21100 => array(
            'Id' => 21100,
            'Label' => 'Batavia NEIEZ',
        ),
        21148 => array(
            'Id' => 21148,
            'Label' => 'Batavia UTM zone 48S',
        ),
        21149 => array(
            'Id' => 21149,
            'Label' => 'Batavia UTM zone 49S',
        ),
        21150 => array(
            'Id' => 21150,
            'Label' => 'Batavia UTM zone 50S',
        ),
        21413 => array(
            'Id' => 21413,
            'Label' => 'Beijing Gauss zone 13',
        ),
        21414 => array(
            'Id' => 21414,
            'Label' => 'Beijing Gauss zone 14',
        ),
        21415 => array(
            'Id' => 21415,
            'Label' => 'Beijing Gauss zone 15',
        ),
        21416 => array(
            'Id' => 21416,
            'Label' => 'Beijing Gauss zone 16',
        ),
        21417 => array(
            'Id' => 21417,
            'Label' => 'Beijing Gauss zone 17',
        ),
        21418 => array(
            'Id' => 21418,
            'Label' => 'Beijing Gauss zone 18',
        ),
        21419 => array(
            'Id' => 21419,
            'Label' => 'Beijing Gauss zone 19',
        ),
        21420 => array(
            'Id' => 21420,
            'Label' => 'Beijing Gauss zone 20',
        ),
        21421 => array(
            'Id' => 21421,
            'Label' => 'Beijing Gauss zone 21',
        ),
        21422 => array(
            'Id' => 21422,
            'Label' => 'Beijing Gauss zone 22',
        ),
        21423 => array(
            'Id' => 21423,
            'Label' => 'Beijing Gauss zone 23',
        ),
        21473 => array(
            'Id' => 21473,
            'Label' => 'Beijing Gauss 13N',
        ),
        21474 => array(
            'Id' => 21474,
            'Label' => 'Beijing Gauss 14N',
        ),
        21475 => array(
            'Id' => 21475,
            'Label' => 'Beijing Gauss 15N',
        ),
        21476 => array(
            'Id' => 21476,
            'Label' => 'Beijing Gauss 16N',
        ),
        21477 => array(
            'Id' => 21477,
            'Label' => 'Beijing Gauss 17N',
        ),
        21478 => array(
            'Id' => 21478,
            'Label' => 'Beijing Gauss 18N',
        ),
        21479 => array(
            'Id' => 21479,
            'Label' => 'Beijing Gauss 19N',
        ),
        21480 => array(
            'Id' => 21480,
            'Label' => 'Beijing Gauss 20N',
        ),
        21481 => array(
            'Id' => 21481,
            'Label' => 'Beijing Gauss 21N',
        ),
        21482 => array(
            'Id' => 21482,
            'Label' => 'Beijing Gauss 22N',
        ),
        21483 => array(
            'Id' => 21483,
            'Label' => 'Beijing Gauss 23N',
        ),
        21500 => array(
            'Id' => 21500,
            'Label' => 'Belge Lambert 50',
        ),
        21790 => array(
            'Id' => 21790,
            'Label' => 'Bern 1898 Swiss Old',
        ),
        21817 => array(
            'Id' => 21817,
            'Label' => 'Bogota UTM zone 17N',
        ),
        21818 => array(
            'Id' => 21818,
            'Label' => 'Bogota UTM zone 18N',
        ),
        21891 => array(
            'Id' => 21891,
            'Label' => 'Bogota Colombia 3W',
        ),
        21892 => array(
            'Id' => 21892,
            'Label' => 'Bogota Colombia Bogota',
        ),
        21893 => array(
            'Id' => 21893,
            'Label' => 'Bogota Colombia 3E',
        ),
        21894 => array(
            'Id' => 21894,
            'Label' => 'Bogota Colombia 6E',
        ),
        22032 => array(
            'Id' => 22032,
            'Label' => 'Camacupa UTM 32S',
        ),
        22033 => array(
            'Id' => 22033,
            'Label' => 'Camacupa UTM 33S',
        ),
        22191 => array(
            'Id' => 22191,
            'Label' => 'C Inchauspe Argentina 1',
        ),
        22192 => array(
            'Id' => 22192,
            'Label' => 'C Inchauspe Argentina 2',
        ),
        22193 => array(
            'Id' => 22193,
            'Label' => 'C Inchauspe Argentina 3',
        ),
        22194 => array(
            'Id' => 22194,
            'Label' => 'C Inchauspe Argentina 4',
        ),
        22195 => array(
            'Id' => 22195,
            'Label' => 'C Inchauspe Argentina 5',
        ),
        22196 => array(
            'Id' => 22196,
            'Label' => 'C Inchauspe Argentina 6',
        ),
        22197 => array(
            'Id' => 22197,
            'Label' => 'C Inchauspe Argentina 7',
        ),
        22332 => array(
            'Id' => 22332,
            'Label' => 'Carthage UTM zone 32N',
        ),
        22391 => array(
            'Id' => 22391,
            'Label' => 'Carthage Nord Tunisie',
        ),
        22392 => array(
            'Id' => 22392,
            'Label' => 'Carthage Sud Tunisie',
        ),
        22523 => array(
            'Id' => 22523,
            'Label' => 'Corrego Alegre UTM 23S',
        ),
        22524 => array(
            'Id' => 22524,
            'Label' => 'Corrego Alegre UTM 24S',
        ),
        22832 => array(
            'Id' => 22832,
            'Label' => 'Douala UTM zone 32N',
        ),
        22992 => array(
            'Id' => 22992,
            'Label' => 'Egypt 1907 Red Belt',
        ),
        22993 => array(
            'Id' => 22993,
            'Label' => 'Egypt 1907 Purple Belt',
        ),
        22994 => array(
            'Id' => 22994,
            'Label' => 'Egypt 1907 Ext Purple',
        ),
        23028 => array(
            'Id' => 23028,
            'Label' => 'ED50 UTM zone 28N',
        ),
        23029 => array(
            'Id' => 23029,
            'Label' => 'ED50 UTM zone 29N',
        ),
        23030 => array(
            'Id' => 23030,
            'Label' => 'ED50 UTM zone 30N',
        ),
        23031 => array(
            'Id' => 23031,
            'Label' => 'ED50 UTM zone 31N',
        ),
        23032 => array(
            'Id' => 23032,
            'Label' => 'ED50 UTM zone 32N',
        ),
        23033 => array(
            'Id' => 23033,
            'Label' => 'ED50 UTM zone 33N',
        ),
        23034 => array(
            'Id' => 23034,
            'Label' => 'ED50 UTM zone 34N',
        ),
        23035 => array(
            'Id' => 23035,
            'Label' => 'ED50 UTM zone 35N',
        ),
        23036 => array(
            'Id' => 23036,
            'Label' => 'ED50 UTM zone 36N',
        ),
        23037 => array(
            'Id' => 23037,
            'Label' => 'ED50 UTM zone 37N',
        ),
        23038 => array(
            'Id' => 23038,
            'Label' => 'ED50 UTM zone 38N',
        ),
        23239 => array(
            'Id' => 23239,
            'Label' => 'Fahud UTM zone 39N',
        ),
        23240 => array(
            'Id' => 23240,
            'Label' => 'Fahud UTM zone 40N',
        ),
        23433 => array(
            'Id' => 23433,
            'Label' => 'Garoua UTM zone 33N',
        ),
        23700 => array(
            'Id' => 23700,
            'Label' => 'HD72 EOV',
        ),
        23846 => array(
            'Id' => 23846,
            'Label' => 'ID74 UTM zone 46N',
        ),
        23847 => array(
            'Id' => 23847,
            'Label' => 'ID74 UTM zone 47N',
        ),
        23848 => array(
            'Id' => 23848,
            'Label' => 'ID74 UTM zone 48N',
        ),
        23849 => array(
            'Id' => 23849,
            'Label' => 'ID74 UTM zone 49N',
        ),
        23850 => array(
            'Id' => 23850,
            'Label' => 'ID74 UTM zone 50N',
        ),
        23851 => array(
            'Id' => 23851,
            'Label' => 'ID74 UTM zone 51N',
        ),
        23852 => array(
            'Id' => 23852,
            'Label' => 'ID74 UTM zone 52N',
        ),
        23853 => array(
            'Id' => 23853,
            'Label' => 'ID74 UTM zone 53N',
        ),
        23886 => array(
            'Id' => 23886,
            'Label' => 'ID74 UTM zone 46S',
        ),
        23887 => array(
            'Id' => 23887,
            'Label' => 'ID74 UTM zone 47S',
        ),
        23888 => array(
            'Id' => 23888,
            'Label' => 'ID74 UTM zone 48S',
        ),
        23889 => array(
            'Id' => 23889,
            'Label' => 'ID74 UTM zone 49S',
        ),
        23890 => array(
            'Id' => 23890,
            'Label' => 'ID74 UTM zone 50S',
        ),
        23891 => array(
            'Id' => 23891,
            'Label' => 'ID74 UTM zone 51S',
        ),
        23892 => array(
            'Id' => 23892,
            'Label' => 'ID74 UTM zone 52S',
        ),
        23893 => array(
            'Id' => 23893,
            'Label' => 'ID74 UTM zone 53S',
        ),
        23894 => array(
            'Id' => 23894,
            'Label' => 'ID74 UTM zone 54S',
        ),
        23947 => array(
            'Id' => 23947,
            'Label' => 'Indian 1954 UTM 47N',
        ),
        23948 => array(
            'Id' => 23948,
            'Label' => 'Indian 1954 UTM 48N',
        ),
        24047 => array(
            'Id' => 24047,
            'Label' => 'Indian 1975 UTM 47N',
        ),
        24048 => array(
            'Id' => 24048,
            'Label' => 'Indian 1975 UTM 48N',
        ),
        24100 => array(
            'Id' => 24100,
            'Label' => 'Jamaica 1875 Old Grid',
        ),
        24200 => array(
            'Id' => 24200,
            'Label' => 'JAD69 Jamaica Grid',
        ),
        24370 => array(
            'Id' => 24370,
            'Label' => 'Kalianpur India 0',
        ),
        24371 => array(
            'Id' => 24371,
            'Label' => 'Kalianpur India I',
        ),
        24372 => array(
            'Id' => 24372,
            'Label' => 'Kalianpur India IIa',
        ),
        24373 => array(
            'Id' => 24373,
            'Label' => 'Kalianpur India IIIa',
        ),
        24374 => array(
            'Id' => 24374,
            'Label' => 'Kalianpur India IVa',
        ),
        24382 => array(
            'Id' => 24382,
            'Label' => 'Kalianpur India IIb',
        ),
        24383 => array(
            'Id' => 24383,
            'Label' => 'Kalianpur India IIIb',
        ),
        24384 => array(
            'Id' => 24384,
            'Label' => 'Kalianpur India IVb',
        ),
        24500 => array(
            'Id' => 24500,
            'Label' => 'Kertau Singapore Grid',
        ),
        24547 => array(
            'Id' => 24547,
            'Label' => 'Kertau UTM zone 47N',
        ),
        24548 => array(
            'Id' => 24548,
            'Label' => 'Kertau UTM zone 48N',
        ),
        24720 => array(
            'Id' => 24720,
            'Label' => 'La Canoa UTM zone 20N',
        ),
        24721 => array(
            'Id' => 24721,
            'Label' => 'La Canoa UTM zone 21N',
        ),
        24818 => array(
            'Id' => 24818,
            'Label' => 'PSAD56 UTM zone 18N',
        ),
        24819 => array(
            'Id' => 24819,
            'Label' => 'PSAD56 UTM zone 19N',
        ),
        24820 => array(
            'Id' => 24820,
            'Label' => 'PSAD56 UTM zone 20N',
        ),
        24821 => array(
            'Id' => 24821,
            'Label' => 'PSAD56 UTM zone 21N',
        ),
        24877 => array(
            'Id' => 24877,
            'Label' => 'PSAD56 UTM zone 17S',
        ),
        24878 => array(
            'Id' => 24878,
            'Label' => 'PSAD56 UTM zone 18S',
        ),
        24879 => array(
            'Id' => 24879,
            'Label' => 'PSAD56 UTM zone 19S',
        ),
        24880 => array(
            'Id' => 24880,
            'Label' => 'PSAD56 UTM zone 20S',
        ),
        24891 => array(
            'Id' => 24891,
            'Label' => 'PSAD56 Peru west zone',
        ),
        24892 => array(
            'Id' => 24892,
            'Label' => 'PSAD56 Peru central',
        ),
        24893 => array(
            'Id' => 24893,
            'Label' => 'PSAD56 Peru east zone',
        ),
        25000 => array(
            'Id' => 25000,
            'Label' => 'Leigon Ghana Grid',
        ),
        25231 => array(
            'Id' => 25231,
            'Label' => 'Lome UTM zone 31N',
        ),
        25391 => array(
            'Id' => 25391,
            'Label' => 'Luzon Philippines I',
        ),
        25392 => array(
            'Id' => 25392,
            'Label' => 'Luzon Philippines II',
        ),
        25393 => array(
            'Id' => 25393,
            'Label' => 'Luzon Philippines III',
        ),
        25394 => array(
            'Id' => 25394,
            'Label' => 'Luzon Philippines IV',
        ),
        25395 => array(
            'Id' => 25395,
            'Label' => 'Luzon Philippines V',
        ),
        25700 => array(
            'Id' => 25700,
            'Label' => 'Makassar NEIEZ',
        ),
        25932 => array(
            'Id' => 25932,
            'Label' => 'Malongo 1987 UTM 32S',
        ),
        26191 => array(
            'Id' => 26191,
            'Label' => 'Merchich Nord Maroc',
        ),
        26192 => array(
            'Id' => 26192,
            'Label' => 'Merchich Sud Maroc',
        ),
        26193 => array(
            'Id' => 26193,
            'Label' => 'Merchich Sahara',
        ),
        26237 => array(
            'Id' => 26237,
            'Label' => 'Massawa UTM zone 37N',
        ),
        26331 => array(
            'Id' => 26331,
            'Label' => 'Minna UTM zone 31N',
        ),
        26332 => array(
            'Id' => 26332,
            'Label' => 'Minna UTM zone 32N',
        ),
        26391 => array(
            'Id' => 26391,
            'Label' => 'Minna Nigeria West',
        ),
        26392 => array(
            'Id' => 26392,
            'Label' => 'Minna Nigeria Mid Belt',
        ),
        26393 => array(
            'Id' => 26393,
            'Label' => 'Minna Nigeria East',
        ),
        26432 => array(
            'Id' => 26432,
            'Label' => 'Mhast UTM zone 32S',
        ),
        26591 => array(
            'Id' => 26591,
            'Label' => 'Monte Mario Italy 1',
        ),
        26592 => array(
            'Id' => 26592,
            'Label' => 'Monte Mario Italy 2',
        ),
        26632 => array(
            'Id' => 26632,
            'Label' => 'M poraloko UTM 32N',
        ),
        26692 => array(
            'Id' => 26692,
            'Label' => 'M poraloko UTM 32S',
        ),
        26703 => array(
            'Id' => 26703,
            'Label' => 'NAD27 UTM zone 3N',
        ),
        26704 => array(
            'Id' => 26704,
            'Label' => 'NAD27 UTM zone 4N',
        ),
        26705 => array(
            'Id' => 26705,
            'Label' => 'NAD27 UTM zone 5N',
        ),
        26706 => array(
            'Id' => 26706,
            'Label' => 'NAD27 UTM zone 6N',
        ),
        26707 => array(
            'Id' => 26707,
            'Label' => 'NAD27 UTM zone 7N',
        ),
        26708 => array(
            'Id' => 26708,
            'Label' => 'NAD27 UTM zone 8N',
        ),
        26709 => array(
            'Id' => 26709,
            'Label' => 'NAD27 UTM zone 9N',
        ),
        26710 => array(
            'Id' => 26710,
            'Label' => 'NAD27 UTM zone 10N',
        ),
        26711 => array(
            'Id' => 26711,
            'Label' => 'NAD27 UTM zone 11N',
        ),
        26712 => array(
            'Id' => 26712,
            'Label' => 'NAD27 UTM zone 12N',
        ),
        26713 => array(
            'Id' => 26713,
            'Label' => 'NAD27 UTM zone 13N',
        ),
        26714 => array(
            'Id' => 26714,
            'Label' => 'NAD27 UTM zone 14N',
        ),
        26715 => array(
            'Id' => 26715,
            'Label' => 'NAD27 UTM zone 15N',
        ),
        26716 => array(
            'Id' => 26716,
            'Label' => 'NAD27 UTM zone 16N',
        ),
        26717 => array(
            'Id' => 26717,
            'Label' => 'NAD27 UTM zone 17N',
        ),
        26718 => array(
            'Id' => 26718,
            'Label' => 'NAD27 UTM zone 18N',
        ),
        26719 => array(
            'Id' => 26719,
            'Label' => 'NAD27 UTM zone 19N',
        ),
        26720 => array(
            'Id' => 26720,
            'Label' => 'NAD27 UTM zone 20N',
        ),
        26721 => array(
            'Id' => 26721,
            'Label' => 'NAD27 UTM zone 21N',
        ),
        26722 => array(
            'Id' => 26722,
            'Label' => 'NAD27 UTM zone 22N',
        ),
        26729 => array(
            'Id' => 26729,
            'Label' => 'NAD27 Alabama East',
        ),
        26730 => array(
            'Id' => 26730,
            'Label' => 'NAD27 Alabama West',
        ),
        26731 => array(
            'Id' => 26731,
            'Label' => 'NAD27 Alaska zone 1',
        ),
        26732 => array(
            'Id' => 26732,
            'Label' => 'NAD27 Alaska zone 2',
        ),
        26733 => array(
            'Id' => 26733,
            'Label' => 'NAD27 Alaska zone 3',
        ),
        26734 => array(
            'Id' => 26734,
            'Label' => 'NAD27 Alaska zone 4',
        ),
        26735 => array(
            'Id' => 26735,
            'Label' => 'NAD27 Alaska zone 5',
        ),
        26736 => array(
            'Id' => 26736,
            'Label' => 'NAD27 Alaska zone 6',
        ),
        26737 => array(
            'Id' => 26737,
            'Label' => 'NAD27 Alaska zone 7',
        ),
        26738 => array(
            'Id' => 26738,
            'Label' => 'NAD27 Alaska zone 8',
        ),
        26739 => array(
            'Id' => 26739,
            'Label' => 'NAD27 Alaska zone 9',
        ),
        26740 => array(
            'Id' => 26740,
            'Label' => 'NAD27 Alaska zone 10',
        ),
        26741 => array(
            'Id' => 26741,
            'Label' => 'NAD27 California I',
        ),
        26742 => array(
            'Id' => 26742,
            'Label' => 'NAD27 California II',
        ),
        26743 => array(
            'Id' => 26743,
            'Label' => 'NAD27 California III',
        ),
        26744 => array(
            'Id' => 26744,
            'Label' => 'NAD27 California IV',
        ),
        26745 => array(
            'Id' => 26745,
            'Label' => 'NAD27 California V',
        ),
        26746 => array(
            'Id' => 26746,
            'Label' => 'NAD27 California VI',
        ),
        26747 => array(
            'Id' => 26747,
            'Label' => 'NAD27 California VII',
        ),
        26748 => array(
            'Id' => 26748,
            'Label' => 'NAD27 Arizona East',
        ),
        26749 => array(
            'Id' => 26749,
            'Label' => 'NAD27 Arizona Central',
        ),
        26750 => array(
            'Id' => 26750,
            'Label' => 'NAD27 Arizona West',
        ),
        26751 => array(
            'Id' => 26751,
            'Label' => 'NAD27 Arkansas North',
        ),
        26752 => array(
            'Id' => 26752,
            'Label' => 'NAD27 Arkansas South',
        ),
        26753 => array(
            'Id' => 26753,
            'Label' => 'NAD27 Colorado North',
        ),
        26754 => array(
            'Id' => 26754,
            'Label' => 'NAD27 Colorado Central',
        ),
        26755 => array(
            'Id' => 26755,
            'Label' => 'NAD27 Colorado South',
        ),
        26756 => array(
            'Id' => 26756,
            'Label' => 'NAD27 Connecticut',
        ),
        26757 => array(
            'Id' => 26757,
            'Label' => 'NAD27 Delaware',
        ),
        26758 => array(
            'Id' => 26758,
            'Label' => 'NAD27 Florida East',
        ),
        26759 => array(
            'Id' => 26759,
            'Label' => 'NAD27 Florida West',
        ),
        26760 => array(
            'Id' => 26760,
            'Label' => 'NAD27 Florida North',
        ),
        26761 => array(
            'Id' => 26761,
            'Label' => 'NAD27 Hawaii zone 1',
        ),
        26762 => array(
            'Id' => 26762,
            'Label' => 'NAD27 Hawaii zone 2',
        ),
        26763 => array(
            'Id' => 26763,
            'Label' => 'NAD27 Hawaii zone 3',
        ),
        26764 => array(
            'Id' => 26764,
            'Label' => 'NAD27 Hawaii zone 4',
        ),
        26765 => array(
            'Id' => 26765,
            'Label' => 'NAD27 Hawaii zone 5',
        ),
        26766 => array(
            'Id' => 26766,
            'Label' => 'NAD27 Georgia East',
        ),
        26767 => array(
            'Id' => 26767,
            'Label' => 'NAD27 Georgia West',
        ),
        26768 => array(
            'Id' => 26768,
            'Label' => 'NAD27 Idaho East',
        ),
        26769 => array(
            'Id' => 26769,
            'Label' => 'NAD27 Idaho Central',
        ),
        26770 => array(
            'Id' => 26770,
            'Label' => 'NAD27 Idaho West',
        ),
        26771 => array(
            'Id' => 26771,
            'Label' => 'NAD27 Illinois East',
        ),
        26772 => array(
            'Id' => 26772,
            'Label' => 'NAD27 Illinois West',
        ),
        26773 => array(
            'Id' => 26773,
            'Label' => 'NAD27 Indiana East',
        ),
        26774 => array(
            'Id' => 26774,
            'Label' => 'NAD27 Indiana West',
        ),
        26775 => array(
            'Id' => 26775,
            'Label' => 'NAD27 Iowa North',
        ),
        26776 => array(
            'Id' => 26776,
            'Label' => 'NAD27 Iowa South',
        ),
        26777 => array(
            'Id' => 26777,
            'Label' => 'NAD27 Kansas North',
        ),
        26778 => array(
            'Id' => 26778,
            'Label' => 'NAD27 Kansas South',
        ),
        26779 => array(
            'Id' => 26779,
            'Label' => 'NAD27 Kentucky North',
        ),
        26780 => array(
            'Id' => 26780,
            'Label' => 'NAD27 Kentucky South',
        ),
        26781 => array(
            'Id' => 26781,
            'Label' => 'NAD27 Louisiana North',
        ),
        26782 => array(
            'Id' => 26782,
            'Label' => 'NAD27 Louisiana South',
        ),
        26783 => array(
            'Id' => 26783,
            'Label' => 'NAD27 Maine East',
        ),
        26784 => array(
            'Id' => 26784,
            'Label' => 'NAD27 Maine West',
        ),
        26785 => array(
            'Id' => 26785,
            'Label' => 'NAD27 Maryland',
        ),
        26786 => array(
            'Id' => 26786,
            'Label' => 'NAD27 Massachusetts',
        ),
        26787 => array(
            'Id' => 26787,
            'Label' => 'NAD27 Massachusetts Is',
        ),
        26788 => array(
            'Id' => 26788,
            'Label' => 'NAD27 Michigan North',
        ),
        26789 => array(
            'Id' => 26789,
            'Label' => 'NAD27 Michigan Central',
        ),
        26790 => array(
            'Id' => 26790,
            'Label' => 'NAD27 Michigan South',
        ),
        26791 => array(
            'Id' => 26791,
            'Label' => 'NAD27 Minnesota North',
        ),
        26792 => array(
            'Id' => 26792,
            'Label' => 'NAD27 Minnesota Cent',
        ),
        26793 => array(
            'Id' => 26793,
            'Label' => 'NAD27 Minnesota South',
        ),
        26794 => array(
            'Id' => 26794,
            'Label' => 'NAD27 Mississippi East',
        ),
        26795 => array(
            'Id' => 26795,
            'Label' => 'NAD27 Mississippi West',
        ),
        26796 => array(
            'Id' => 26796,
            'Label' => 'NAD27 Missouri East',
        ),
        26797 => array(
            'Id' => 26797,
            'Label' => 'NAD27 Missouri Central',
        ),
        26798 => array(
            'Id' => 26798,
            'Label' => 'NAD27 Missouri West',
        ),
        26801 => array(
            'Id' => 26801,
            'Label' => 'NAD Michigan Michigan East',
        ),
        26802 => array(
            'Id' => 26802,
            'Label' => 'NAD Michigan Michigan Old Central',
        ),
        26803 => array(
            'Id' => 26803,
            'Label' => 'NAD Michigan Michigan West',
        ),
        26903 => array(
            'Id' => 26903,
            'Label' => 'NAD83 UTM zone 3N',
        ),
        26904 => array(
            'Id' => 26904,
            'Label' => 'NAD83 UTM zone 4N',
        ),
        26905 => array(
            'Id' => 26905,
            'Label' => 'NAD83 UTM zone 5N',
        ),
        26906 => array(
            'Id' => 26906,
            'Label' => 'NAD83 UTM zone 6N',
        ),
        26907 => array(
            'Id' => 26907,
            'Label' => 'NAD83 UTM zone 7N',
        ),
        26908 => array(
            'Id' => 26908,
            'Label' => 'NAD83 UTM zone 8N',
        ),
        26909 => array(
            'Id' => 26909,
            'Label' => 'NAD83 UTM zone 9N',
        ),
        26910 => array(
            'Id' => 26910,
            'Label' => 'NAD83 UTM zone 10N',
        ),
        26911 => array(
            'Id' => 26911,
            'Label' => 'NAD83 UTM zone 11N',
        ),
        26912 => array(
            'Id' => 26912,
            'Label' => 'NAD83 UTM zone 12N',
        ),
        26913 => array(
            'Id' => 26913,
            'Label' => 'NAD83 UTM zone 13N',
        ),
        26914 => array(
            'Id' => 26914,
            'Label' => 'NAD83 UTM zone 14N',
        ),
        26915 => array(
            'Id' => 26915,
            'Label' => 'NAD83 UTM zone 15N',
        ),
        26916 => array(
            'Id' => 26916,
            'Label' => 'NAD83 UTM zone 16N',
        ),
        26917 => array(
            'Id' => 26917,
            'Label' => 'NAD83 UTM zone 17N',
        ),
        26918 => array(
            'Id' => 26918,
            'Label' => 'NAD83 UTM zone 18N',
        ),
        26919 => array(
            'Id' => 26919,
            'Label' => 'NAD83 UTM zone 19N',
        ),
        26920 => array(
            'Id' => 26920,
            'Label' => 'NAD83 UTM zone 20N',
        ),
        26921 => array(
            'Id' => 26921,
            'Label' => 'NAD83 UTM zone 21N',
        ),
        26922 => array(
            'Id' => 26922,
            'Label' => 'NAD83 UTM zone 22N',
        ),
        26923 => array(
            'Id' => 26923,
            'Label' => 'NAD83 UTM zone 23N',
        ),
        26929 => array(
            'Id' => 26929,
            'Label' => 'NAD83 Alabama East',
        ),
        26930 => array(
            'Id' => 26930,
            'Label' => 'NAD83 Alabama West',
        ),
        26931 => array(
            'Id' => 26931,
            'Label' => 'NAD83 Alaska zone 1',
        ),
        26932 => array(
            'Id' => 26932,
            'Label' => 'NAD83 Alaska zone 2',
        ),
        26933 => array(
            'Id' => 26933,
            'Label' => 'NAD83 Alaska zone 3',
        ),
        26934 => array(
            'Id' => 26934,
            'Label' => 'NAD83 Alaska zone 4',
        ),
        26935 => array(
            'Id' => 26935,
            'Label' => 'NAD83 Alaska zone 5',
        ),
        26936 => array(
            'Id' => 26936,
            'Label' => 'NAD83 Alaska zone 6',
        ),
        26937 => array(
            'Id' => 26937,
            'Label' => 'NAD83 Alaska zone 7',
        ),
        26938 => array(
            'Id' => 26938,
            'Label' => 'NAD83 Alaska zone 8',
        ),
        26939 => array(
            'Id' => 26939,
            'Label' => 'NAD83 Alaska zone 9',
        ),
        26940 => array(
            'Id' => 26940,
            'Label' => 'NAD83 Alaska zone 10',
        ),
        26941 => array(
            'Id' => 26941,
            'Label' => 'NAD83 California 1',
        ),
        26942 => array(
            'Id' => 26942,
            'Label' => 'NAD83 California 2',
        ),
        26943 => array(
            'Id' => 26943,
            'Label' => 'NAD83 California 3',
        ),
        26944 => array(
            'Id' => 26944,
            'Label' => 'NAD83 California 4',
        ),
        26945 => array(
            'Id' => 26945,
            'Label' => 'NAD83 California 5',
        ),
        26946 => array(
            'Id' => 26946,
            'Label' => 'NAD83 California 6',
        ),
        26948 => array(
            'Id' => 26948,
            'Label' => 'NAD83 Arizona East',
        ),
        26949 => array(
            'Id' => 26949,
            'Label' => 'NAD83 Arizona Central',
        ),
        26950 => array(
            'Id' => 26950,
            'Label' => 'NAD83 Arizona West',
        ),
        26951 => array(
            'Id' => 26951,
            'Label' => 'NAD83 Arkansas North',
        ),
        26952 => array(
            'Id' => 26952,
            'Label' => 'NAD83 Arkansas South',
        ),
        26953 => array(
            'Id' => 26953,
            'Label' => 'NAD83 Colorado North',
        ),
        26954 => array(
            'Id' => 26954,
            'Label' => 'NAD83 Colorado Central',
        ),
        26955 => array(
            'Id' => 26955,
            'Label' => 'NAD83 Colorado South',
        ),
        26956 => array(
            'Id' => 26956,
            'Label' => 'NAD83 Connecticut',
        ),
        26957 => array(
            'Id' => 26957,
            'Label' => 'NAD83 Delaware',
        ),
        26958 => array(
            'Id' => 26958,
            'Label' => 'NAD83 Florida East',
        ),
        26959 => array(
            'Id' => 26959,
            'Label' => 'NAD83 Florida West',
        ),
        26960 => array(
            'Id' => 26960,
            'Label' => 'NAD83 Florida North',
        ),
        26961 => array(
            'Id' => 26961,
            'Label' => 'NAD83 Hawaii zone 1',
        ),
        26962 => array(
            'Id' => 26962,
            'Label' => 'NAD83 Hawaii zone 2',
        ),
        26963 => array(
            'Id' => 26963,
            'Label' => 'NAD83 Hawaii zone 3',
        ),
        26964 => array(
            'Id' => 26964,
            'Label' => 'NAD83 Hawaii zone 4',
        ),
        26965 => array(
            'Id' => 26965,
            'Label' => 'NAD83 Hawaii zone 5',
        ),
        26966 => array(
            'Id' => 26966,
            'Label' => 'NAD83 Georgia East',
        ),
        26967 => array(
            'Id' => 26967,
            'Label' => 'NAD83 Georgia West',
        ),
        26968 => array(
            'Id' => 26968,
            'Label' => 'NAD83 Idaho East',
        ),
        26969 => array(
            'Id' => 26969,
            'Label' => 'NAD83 Idaho Central',
        ),
        26970 => array(
            'Id' => 26970,
            'Label' => 'NAD83 Idaho West',
        ),
        26971 => array(
            'Id' => 26971,
            'Label' => 'NAD83 Illinois East',
        ),
        26972 => array(
            'Id' => 26972,
            'Label' => 'NAD83 Illinois West',
        ),
        26973 => array(
            'Id' => 26973,
            'Label' => 'NAD83 Indiana East',
        ),
        26974 => array(
            'Id' => 26974,
            'Label' => 'NAD83 Indiana West',
        ),
        26975 => array(
            'Id' => 26975,
            'Label' => 'NAD83 Iowa North',
        ),
        26976 => array(
            'Id' => 26976,
            'Label' => 'NAD83 Iowa South',
        ),
        26977 => array(
            'Id' => 26977,
            'Label' => 'NAD83 Kansas North',
        ),
        26978 => array(
            'Id' => 26978,
            'Label' => 'NAD83 Kansas South',
        ),
        26979 => array(
            'Id' => 26979,
            'Label' => 'NAD83 Kentucky North',
        ),
        26980 => array(
            'Id' => 26980,
            'Label' => 'NAD83 Kentucky South',
        ),
        26981 => array(
            'Id' => 26981,
            'Label' => 'NAD83 Louisiana North',
        ),
        26982 => array(
            'Id' => 26982,
            'Label' => 'NAD83 Louisiana South',
        ),
        26983 => array(
            'Id' => 26983,
            'Label' => 'NAD83 Maine East',
        ),
        26984 => array(
            'Id' => 26984,
            'Label' => 'NAD83 Maine West',
        ),
        26985 => array(
            'Id' => 26985,
            'Label' => 'NAD83 Maryland',
        ),
        26986 => array(
            'Id' => 26986,
            'Label' => 'NAD83 Massachusetts',
        ),
        26987 => array(
            'Id' => 26987,
            'Label' => 'NAD83 Massachusetts Is',
        ),
        26988 => array(
            'Id' => 26988,
            'Label' => 'NAD83 Michigan North',
        ),
        26989 => array(
            'Id' => 26989,
            'Label' => 'NAD83 Michigan Central',
        ),
        26990 => array(
            'Id' => 26990,
            'Label' => 'NAD83 Michigan South',
        ),
        26991 => array(
            'Id' => 26991,
            'Label' => 'NAD83 Minnesota North',
        ),
        26992 => array(
            'Id' => 26992,
            'Label' => 'NAD83 Minnesota Cent',
        ),
        26993 => array(
            'Id' => 26993,
            'Label' => 'NAD83 Minnesota South',
        ),
        26994 => array(
            'Id' => 26994,
            'Label' => 'NAD83 Mississippi East',
        ),
        26995 => array(
            'Id' => 26995,
            'Label' => 'NAD83 Mississippi West',
        ),
        26996 => array(
            'Id' => 26996,
            'Label' => 'NAD83 Missouri East',
        ),
        26997 => array(
            'Id' => 26997,
            'Label' => 'NAD83 Missouri Central',
        ),
        26998 => array(
            'Id' => 26998,
            'Label' => 'NAD83 Missouri West',
        ),
        27038 => array(
            'Id' => 27038,
            'Label' => 'Nahrwan 1967 UTM 38N',
        ),
        27039 => array(
            'Id' => 27039,
            'Label' => 'Nahrwan 1967 UTM 39N',
        ),
        27040 => array(
            'Id' => 27040,
            'Label' => 'Nahrwan 1967 UTM 40N',
        ),
        27120 => array(
            'Id' => 27120,
            'Label' => 'Naparima UTM 20N',
        ),
        27200 => array(
            'Id' => 27200,
            'Label' => 'GD49 NZ Map Grid',
        ),
        27291 => array(
            'Id' => 27291,
            'Label' => 'GD49 North Island Grid',
        ),
        27292 => array(
            'Id' => 27292,
            'Label' => 'GD49 South Island Grid',
        ),
        27429 => array(
            'Id' => 27429,
            'Label' => 'Datum 73 UTM zone 29N',
        ),
        27500 => array(
            'Id' => 27500,
            'Label' => 'ATF Nord de Guerre',
        ),
        27581 => array(
            'Id' => 27581,
            'Label' => 'NTF France I',
        ),
        27582 => array(
            'Id' => 27582,
            'Label' => 'NTF France II',
        ),
        27583 => array(
            'Id' => 27583,
            'Label' => 'NTF France III',
        ),
        27591 => array(
            'Id' => 27591,
            'Label' => 'NTF Nord France',
        ),
        27592 => array(
            'Id' => 27592,
            'Label' => 'NTF Centre France',
        ),
        27593 => array(
            'Id' => 27593,
            'Label' => 'NTF Sud France',
        ),
        27700 => array(
            'Id' => 27700,
            'Label' => 'British National Grid',
        ),
        28232 => array(
            'Id' => 28232,
            'Label' => 'Point Noire UTM 32S',
        ),
        28348 => array(
            'Id' => 28348,
            'Label' => 'GDA94 MGA zone 48',
        ),
        28349 => array(
            'Id' => 28349,
            'Label' => 'GDA94 MGA zone 49',
        ),
        28350 => array(
            'Id' => 28350,
            'Label' => 'GDA94 MGA zone 50',
        ),
        28351 => array(
            'Id' => 28351,
            'Label' => 'GDA94 MGA zone 51',
        ),
        28352 => array(
            'Id' => 28352,
            'Label' => 'GDA94 MGA zone 52',
        ),
        28353 => array(
            'Id' => 28353,
            'Label' => 'GDA94 MGA zone 53',
        ),
        28354 => array(
            'Id' => 28354,
            'Label' => 'GDA94 MGA zone 54',
        ),
        28355 => array(
            'Id' => 28355,
            'Label' => 'GDA94 MGA zone 55',
        ),
        28356 => array(
            'Id' => 28356,
            'Label' => 'GDA94 MGA zone 56',
        ),
        28357 => array(
            'Id' => 28357,
            'Label' => 'GDA94 MGA zone 57',
        ),
        28358 => array(
            'Id' => 28358,
            'Label' => 'GDA94 MGA zone 58',
        ),
        28404 => array(
            'Id' => 28404,
            'Label' => 'Pulkovo Gauss zone 4',
        ),
        28405 => array(
            'Id' => 28405,
            'Label' => 'Pulkovo Gauss zone 5',
        ),
        28406 => array(
            'Id' => 28406,
            'Label' => 'Pulkovo Gauss zone 6',
        ),
        28407 => array(
            'Id' => 28407,
            'Label' => 'Pulkovo Gauss zone 7',
        ),
        28408 => array(
            'Id' => 28408,
            'Label' => 'Pulkovo Gauss zone 8',
        ),
        28409 => array(
            'Id' => 28409,
            'Label' => 'Pulkovo Gauss zone 9',
        ),
        28410 => array(
            'Id' => 28410,
            'Label' => 'Pulkovo Gauss zone 10',
        ),
        28411 => array(
            'Id' => 28411,
            'Label' => 'Pulkovo Gauss zone 11',
        ),
        28412 => array(
            'Id' => 28412,
            'Label' => 'Pulkovo Gauss zone 12',
        ),
        28413 => array(
            'Id' => 28413,
            'Label' => 'Pulkovo Gauss zone 13',
        ),
        28414 => array(
            'Id' => 28414,
            'Label' => 'Pulkovo Gauss zone 14',
        ),
        28415 => array(
            'Id' => 28415,
            'Label' => 'Pulkovo Gauss zone 15',
        ),
        28416 => array(
            'Id' => 28416,
            'Label' => 'Pulkovo Gauss zone 16',
        ),
        28417 => array(
            'Id' => 28417,
            'Label' => 'Pulkovo Gauss zone 17',
        ),
        28418 => array(
            'Id' => 28418,
            'Label' => 'Pulkovo Gauss zone 18',
        ),
        28419 => array(
            'Id' => 28419,
            'Label' => 'Pulkovo Gauss zone 19',
        ),
        28420 => array(
            'Id' => 28420,
            'Label' => 'Pulkovo Gauss zone 20',
        ),
        28421 => array(
            'Id' => 28421,
            'Label' => 'Pulkovo Gauss zone 21',
        ),
        28422 => array(
            'Id' => 28422,
            'Label' => 'Pulkovo Gauss zone 22',
        ),
        28423 => array(
            'Id' => 28423,
            'Label' => 'Pulkovo Gauss zone 23',
        ),
        28424 => array(
            'Id' => 28424,
            'Label' => 'Pulkovo Gauss zone 24',
        ),
        28425 => array(
            'Id' => 28425,
            'Label' => 'Pulkovo Gauss zone 25',
        ),
        28426 => array(
            'Id' => 28426,
            'Label' => 'Pulkovo Gauss zone 26',
        ),
        28427 => array(
            'Id' => 28427,
            'Label' => 'Pulkovo Gauss zone 27',
        ),
        28428 => array(
            'Id' => 28428,
            'Label' => 'Pulkovo Gauss zone 28',
        ),
        28429 => array(
            'Id' => 28429,
            'Label' => 'Pulkovo Gauss zone 29',
        ),
        28430 => array(
            'Id' => 28430,
            'Label' => 'Pulkovo Gauss zone 30',
        ),
        28431 => array(
            'Id' => 28431,
            'Label' => 'Pulkovo Gauss zone 31',
        ),
        28432 => array(
            'Id' => 28432,
            'Label' => 'Pulkovo Gauss zone 32',
        ),
        28464 => array(
            'Id' => 28464,
            'Label' => 'Pulkovo Gauss 4N',
        ),
        28465 => array(
            'Id' => 28465,
            'Label' => 'Pulkovo Gauss 5N',
        ),
        28466 => array(
            'Id' => 28466,
            'Label' => 'Pulkovo Gauss 6N',
        ),
        28467 => array(
            'Id' => 28467,
            'Label' => 'Pulkovo Gauss 7N',
        ),
        28468 => array(
            'Id' => 28468,
            'Label' => 'Pulkovo Gauss 8N',
        ),
        28469 => array(
            'Id' => 28469,
            'Label' => 'Pulkovo Gauss 9N',
        ),
        28470 => array(
            'Id' => 28470,
            'Label' => 'Pulkovo Gauss 10N',
        ),
        28471 => array(
            'Id' => 28471,
            'Label' => 'Pulkovo Gauss 11N',
        ),
        28472 => array(
            'Id' => 28472,
            'Label' => 'Pulkovo Gauss 12N',
        ),
        28473 => array(
            'Id' => 28473,
            'Label' => 'Pulkovo Gauss 13N',
        ),
        28474 => array(
            'Id' => 28474,
            'Label' => 'Pulkovo Gauss 14N',
        ),
        28475 => array(
            'Id' => 28475,
            'Label' => 'Pulkovo Gauss 15N',
        ),
        28476 => array(
            'Id' => 28476,
            'Label' => 'Pulkovo Gauss 16N',
        ),
        28477 => array(
            'Id' => 28477,
            'Label' => 'Pulkovo Gauss 17N',
        ),
        28478 => array(
            'Id' => 28478,
            'Label' => 'Pulkovo Gauss 18N',
        ),
        28479 => array(
            'Id' => 28479,
            'Label' => 'Pulkovo Gauss 19N',
        ),
        28480 => array(
            'Id' => 28480,
            'Label' => 'Pulkovo Gauss 20N',
        ),
        28481 => array(
            'Id' => 28481,
            'Label' => 'Pulkovo Gauss 21N',
        ),
        28482 => array(
            'Id' => 28482,
            'Label' => 'Pulkovo Gauss 22N',
        ),
        28483 => array(
            'Id' => 28483,
            'Label' => 'Pulkovo Gauss 23N',
        ),
        28484 => array(
            'Id' => 28484,
            'Label' => 'Pulkovo Gauss 24N',
        ),
        28485 => array(
            'Id' => 28485,
            'Label' => 'Pulkovo Gauss 25N',
        ),
        28486 => array(
            'Id' => 28486,
            'Label' => 'Pulkovo Gauss 26N',
        ),
        28487 => array(
            'Id' => 28487,
            'Label' => 'Pulkovo Gauss 27N',
        ),
        28488 => array(
            'Id' => 28488,
            'Label' => 'Pulkovo Gauss 28N',
        ),
        28489 => array(
            'Id' => 28489,
            'Label' => 'Pulkovo Gauss 29N',
        ),
        28490 => array(
            'Id' => 28490,
            'Label' => 'Pulkovo Gauss 30N',
        ),
        28491 => array(
            'Id' => 28491,
            'Label' => 'Pulkovo Gauss 31N',
        ),
        28492 => array(
            'Id' => 28492,
            'Label' => 'Pulkovo Gauss 32N',
        ),
        28600 => array(
            'Id' => 28600,
            'Label' => 'Qatar National Grid',
        ),
        28991 => array(
            'Id' => 28991,
            'Label' => 'RD Netherlands Old',
        ),
        28992 => array(
            'Id' => 28992,
            'Label' => 'RD Netherlands New',
        ),
        29118 => array(
            'Id' => 29118,
            'Label' => 'SAD69 UTM zone 18N',
        ),
        29119 => array(
            'Id' => 29119,
            'Label' => 'SAD69 UTM zone 19N',
        ),
        29120 => array(
            'Id' => 29120,
            'Label' => 'SAD69 UTM zone 20N',
        ),
        29121 => array(
            'Id' => 29121,
            'Label' => 'SAD69 UTM zone 21N',
        ),
        29122 => array(
            'Id' => 29122,
            'Label' => 'SAD69 UTM zone 22N',
        ),
        29177 => array(
            'Id' => 29177,
            'Label' => 'SAD69 UTM zone 17S',
        ),
        29178 => array(
            'Id' => 29178,
            'Label' => 'SAD69 UTM zone 18S',
        ),
        29179 => array(
            'Id' => 29179,
            'Label' => 'SAD69 UTM zone 19S',
        ),
        29180 => array(
            'Id' => 29180,
            'Label' => 'SAD69 UTM zone 20S',
        ),
        29181 => array(
            'Id' => 29181,
            'Label' => 'SAD69 UTM zone 21S',
        ),
        29182 => array(
            'Id' => 29182,
            'Label' => 'SAD69 UTM zone 22S',
        ),
        29183 => array(
            'Id' => 29183,
            'Label' => 'SAD69 UTM zone 23S',
        ),
        29184 => array(
            'Id' => 29184,
            'Label' => 'SAD69 UTM zone 24S',
        ),
        29185 => array(
            'Id' => 29185,
            'Label' => 'SAD69 UTM zone 25S',
        ),
        29220 => array(
            'Id' => 29220,
            'Label' => 'Sapper Hill UTM 20S',
        ),
        29221 => array(
            'Id' => 29221,
            'Label' => 'Sapper Hill UTM 21S',
        ),
        29333 => array(
            'Id' => 29333,
            'Label' => 'Schwarzeck UTM 33S',
        ),
        29635 => array(
            'Id' => 29635,
            'Label' => 'Sudan UTM zone 35N',
        ),
        29636 => array(
            'Id' => 29636,
            'Label' => 'Sudan UTM zone 36N',
        ),
        29700 => array(
            'Id' => 29700,
            'Label' => 'Tananarive Laborde',
        ),
        29738 => array(
            'Id' => 29738,
            'Label' => 'Tananarive UTM 38S',
        ),
        29739 => array(
            'Id' => 29739,
            'Label' => 'Tananarive UTM 39S',
        ),
        29800 => array(
            'Id' => 29800,
            'Label' => 'Timbalai 1948 Borneo',
        ),
        29849 => array(
            'Id' => 29849,
            'Label' => 'Timbalai 1948 UTM 49N',
        ),
        29850 => array(
            'Id' => 29850,
            'Label' => 'Timbalai 1948 UTM 50N',
        ),
        29900 => array(
            'Id' => 29900,
            'Label' => 'TM65 Irish Nat Grid',
        ),
        30200 => array(
            'Id' => 30200,
            'Label' => 'Trinidad 1903 Trinidad',
        ),
        30339 => array(
            'Id' => 30339,
            'Label' => 'TC 1948 UTM zone 39N',
        ),
        30340 => array(
            'Id' => 30340,
            'Label' => 'TC 1948 UTM zone 40N',
        ),
        30491 => array(
            'Id' => 30491,
            'Label' => 'Voirol N Algerie ancien',
        ),
        30492 => array(
            'Id' => 30492,
            'Label' => 'Voirol S Algerie ancien',
        ),
        30591 => array(
            'Id' => 30591,
            'Label' => 'Voirol Unifie N Algerie',
        ),
        30592 => array(
            'Id' => 30592,
            'Label' => 'Voirol Unifie S Algerie',
        ),
        30600 => array(
            'Id' => 30600,
            'Label' => 'Bern 1938 Swiss New',
        ),
        30729 => array(
            'Id' => 30729,
            'Label' => 'Nord Sahara UTM 29N',
        ),
        30730 => array(
            'Id' => 30730,
            'Label' => 'Nord Sahara UTM 30N',
        ),
        30731 => array(
            'Id' => 30731,
            'Label' => 'Nord Sahara UTM 31N',
        ),
        30732 => array(
            'Id' => 30732,
            'Label' => 'Nord Sahara UTM 32N',
        ),
        31028 => array(
            'Id' => 31028,
            'Label' => 'Yoff UTM zone 28N',
        ),
        31121 => array(
            'Id' => 31121,
            'Label' => 'Zanderij UTM zone 21N',
        ),
        31291 => array(
            'Id' => 31291,
            'Label' => 'MGI Austria West',
        ),
        31292 => array(
            'Id' => 31292,
            'Label' => 'MGI Austria Central',
        ),
        31293 => array(
            'Id' => 31293,
            'Label' => 'MGI Austria East',
        ),
        31300 => array(
            'Id' => 31300,
            'Label' => 'Belge Lambert 72',
        ),
        31491 => array(
            'Id' => 31491,
            'Label' => 'DHDN Germany zone 1',
        ),
        31492 => array(
            'Id' => 31492,
            'Label' => 'DHDN Germany zone 2',
        ),
        31493 => array(
            'Id' => 31493,
            'Label' => 'DHDN Germany zone 3',
        ),
        31494 => array(
            'Id' => 31494,
            'Label' => 'DHDN Germany zone 4',
        ),
        31495 => array(
            'Id' => 31495,
            'Label' => 'DHDN Germany zone 5',
        ),
        31700 => array(
            'Id' => 31700,
            'Label' => 'Dealul Piscului 1970 Stereo 70',
        ),
        32001 => array(
            'Id' => 32001,
            'Label' => 'NAD27 Montana North',
        ),
        32002 => array(
            'Id' => 32002,
            'Label' => 'NAD27 Montana Central',
        ),
        32003 => array(
            'Id' => 32003,
            'Label' => 'NAD27 Montana South',
        ),
        32005 => array(
            'Id' => 32005,
            'Label' => 'NAD27 Nebraska North',
        ),
        32006 => array(
            'Id' => 32006,
            'Label' => 'NAD27 Nebraska South',
        ),
        32007 => array(
            'Id' => 32007,
            'Label' => 'NAD27 Nevada East',
        ),
        32008 => array(
            'Id' => 32008,
            'Label' => 'NAD27 Nevada Central',
        ),
        32009 => array(
            'Id' => 32009,
            'Label' => 'NAD27 Nevada West',
        ),
        32010 => array(
            'Id' => 32010,
            'Label' => 'NAD27 New Hampshire',
        ),
        32011 => array(
            'Id' => 32011,
            'Label' => 'NAD27 New Jersey',
        ),
        32012 => array(
            'Id' => 32012,
            'Label' => 'NAD27 New Mexico East',
        ),
        32013 => array(
            'Id' => 32013,
            'Label' => 'NAD27 New Mexico Cent',
        ),
        32014 => array(
            'Id' => 32014,
            'Label' => 'NAD27 New Mexico West',
        ),
        32015 => array(
            'Id' => 32015,
            'Label' => 'NAD27 New York East',
        ),
        32016 => array(
            'Id' => 32016,
            'Label' => 'NAD27 New York Central',
        ),
        32017 => array(
            'Id' => 32017,
            'Label' => 'NAD27 New York West',
        ),
        32018 => array(
            'Id' => 32018,
            'Label' => 'NAD27 New York Long Is',
        ),
        32019 => array(
            'Id' => 32019,
            'Label' => 'NAD27 North Carolina',
        ),
        32020 => array(
            'Id' => 32020,
            'Label' => 'NAD27 North Dakota N',
        ),
        32021 => array(
            'Id' => 32021,
            'Label' => 'NAD27 North Dakota S',
        ),
        32022 => array(
            'Id' => 32022,
            'Label' => 'NAD27 Ohio North',
        ),
        32023 => array(
            'Id' => 32023,
            'Label' => 'NAD27 Ohio South',
        ),
        32024 => array(
            'Id' => 32024,
            'Label' => 'NAD27 Oklahoma North',
        ),
        32025 => array(
            'Id' => 32025,
            'Label' => 'NAD27 Oklahoma South',
        ),
        32026 => array(
            'Id' => 32026,
            'Label' => 'NAD27 Oregon North',
        ),
        32027 => array(
            'Id' => 32027,
            'Label' => 'NAD27 Oregon South',
        ),
        32028 => array(
            'Id' => 32028,
            'Label' => 'NAD27 Pennsylvania N',
        ),
        32029 => array(
            'Id' => 32029,
            'Label' => 'NAD27 Pennsylvania S',
        ),
        32030 => array(
            'Id' => 32030,
            'Label' => 'NAD27 Rhode Island',
        ),
        32031 => array(
            'Id' => 32031,
            'Label' => 'NAD27 South Carolina N',
        ),
        32033 => array(
            'Id' => 32033,
            'Label' => 'NAD27 South Carolina S',
        ),
        32034 => array(
            'Id' => 32034,
            'Label' => 'NAD27 South Dakota N',
        ),
        32035 => array(
            'Id' => 32035,
            'Label' => 'NAD27 South Dakota S',
        ),
        32036 => array(
            'Id' => 32036,
            'Label' => 'NAD27 Tennessee',
        ),
        32037 => array(
            'Id' => 32037,
            'Label' => 'NAD27 Texas North',
        ),
        32038 => array(
            'Id' => 32038,
            'Label' => 'NAD27 Texas North Cen',
        ),
        32039 => array(
            'Id' => 32039,
            'Label' => 'NAD27 Texas Central',
        ),
        32040 => array(
            'Id' => 32040,
            'Label' => 'NAD27 Texas South Cen',
        ),
        32041 => array(
            'Id' => 32041,
            'Label' => 'NAD27 Texas South',
        ),
        32042 => array(
            'Id' => 32042,
            'Label' => 'NAD27 Utah North',
        ),
        32043 => array(
            'Id' => 32043,
            'Label' => 'NAD27 Utah Central',
        ),
        32044 => array(
            'Id' => 32044,
            'Label' => 'NAD27 Utah South',
        ),
        32045 => array(
            'Id' => 32045,
            'Label' => 'NAD27 Vermont',
        ),
        32046 => array(
            'Id' => 32046,
            'Label' => 'NAD27 Virginia North',
        ),
        32047 => array(
            'Id' => 32047,
            'Label' => 'NAD27 Virginia South',
        ),
        32048 => array(
            'Id' => 32048,
            'Label' => 'NAD27 Washington North',
        ),
        32049 => array(
            'Id' => 32049,
            'Label' => 'NAD27 Washington South',
        ),
        32050 => array(
            'Id' => 32050,
            'Label' => 'NAD27 West Virginia N',
        ),
        32051 => array(
            'Id' => 32051,
            'Label' => 'NAD27 West Virginia S',
        ),
        32052 => array(
            'Id' => 32052,
            'Label' => 'NAD27 Wisconsin North',
        ),
        32053 => array(
            'Id' => 32053,
            'Label' => 'NAD27 Wisconsin Cen',
        ),
        32054 => array(
            'Id' => 32054,
            'Label' => 'NAD27 Wisconsin South',
        ),
        32055 => array(
            'Id' => 32055,
            'Label' => 'NAD27 Wyoming East',
        ),
        32056 => array(
            'Id' => 32056,
            'Label' => 'NAD27 Wyoming E Cen',
        ),
        32057 => array(
            'Id' => 32057,
            'Label' => 'NAD27 Wyoming W Cen',
        ),
        32058 => array(
            'Id' => 32058,
            'Label' => 'NAD27 Wyoming West',
        ),
        32059 => array(
            'Id' => 32059,
            'Label' => 'NAD27 Puerto Rico',
        ),
        32060 => array(
            'Id' => 32060,
            'Label' => 'NAD27 St Croix',
        ),
        32100 => array(
            'Id' => 32100,
            'Label' => 'NAD83 Montana',
        ),
        32104 => array(
            'Id' => 32104,
            'Label' => 'NAD83 Nebraska',
        ),
        32107 => array(
            'Id' => 32107,
            'Label' => 'NAD83 Nevada East',
        ),
        32108 => array(
            'Id' => 32108,
            'Label' => 'NAD83 Nevada Central',
        ),
        32109 => array(
            'Id' => 32109,
            'Label' => 'NAD83 Nevada West',
        ),
        32110 => array(
            'Id' => 32110,
            'Label' => 'NAD83 New Hampshire',
        ),
        32111 => array(
            'Id' => 32111,
            'Label' => 'NAD83 New Jersey',
        ),
        32112 => array(
            'Id' => 32112,
            'Label' => 'NAD83 New Mexico East',
        ),
        32113 => array(
            'Id' => 32113,
            'Label' => 'NAD83 New Mexico Cent',
        ),
        32114 => array(
            'Id' => 32114,
            'Label' => 'NAD83 New Mexico West',
        ),
        32115 => array(
            'Id' => 32115,
            'Label' => 'NAD83 New York East',
        ),
        32116 => array(
            'Id' => 32116,
            'Label' => 'NAD83 New York Central',
        ),
        32117 => array(
            'Id' => 32117,
            'Label' => 'NAD83 New York West',
        ),
        32118 => array(
            'Id' => 32118,
            'Label' => 'NAD83 New York Long Is',
        ),
        32119 => array(
            'Id' => 32119,
            'Label' => 'NAD83 North Carolina',
        ),
        32120 => array(
            'Id' => 32120,
            'Label' => 'NAD83 North Dakota N',
        ),
        32121 => array(
            'Id' => 32121,
            'Label' => 'NAD83 North Dakota S',
        ),
        32122 => array(
            'Id' => 32122,
            'Label' => 'NAD83 Ohio North',
        ),
        32123 => array(
            'Id' => 32123,
            'Label' => 'NAD83 Ohio South',
        ),
        32124 => array(
            'Id' => 32124,
            'Label' => 'NAD83 Oklahoma North',
        ),
        32125 => array(
            'Id' => 32125,
            'Label' => 'NAD83 Oklahoma South',
        ),
        32126 => array(
            'Id' => 32126,
            'Label' => 'NAD83 Oregon North',
        ),
        32127 => array(
            'Id' => 32127,
            'Label' => 'NAD83 Oregon South',
        ),
        32128 => array(
            'Id' => 32128,
            'Label' => 'NAD83 Pennsylvania N',
        ),
        32129 => array(
            'Id' => 32129,
            'Label' => 'NAD83 Pennsylvania S',
        ),
        32130 => array(
            'Id' => 32130,
            'Label' => 'NAD83 Rhode Island',
        ),
        32133 => array(
            'Id' => 32133,
            'Label' => 'NAD83 South Carolina',
        ),
        32134 => array(
            'Id' => 32134,
            'Label' => 'NAD83 South Dakota N',
        ),
        32135 => array(
            'Id' => 32135,
            'Label' => 'NAD83 South Dakota S',
        ),
        32136 => array(
            'Id' => 32136,
            'Label' => 'NAD83 Tennessee',
        ),
        32137 => array(
            'Id' => 32137,
            'Label' => 'NAD83 Texas North',
        ),
        32138 => array(
            'Id' => 32138,
            'Label' => 'NAD83 Texas North Cen',
        ),
        32139 => array(
            'Id' => 32139,
            'Label' => 'NAD83 Texas Central',
        ),
        32140 => array(
            'Id' => 32140,
            'Label' => 'NAD83 Texas South Cen',
        ),
        32141 => array(
            'Id' => 32141,
            'Label' => 'NAD83 Texas South',
        ),
        32142 => array(
            'Id' => 32142,
            'Label' => 'NAD83 Utah North',
        ),
        32143 => array(
            'Id' => 32143,
            'Label' => 'NAD83 Utah Central',
        ),
        32144 => array(
            'Id' => 32144,
            'Label' => 'NAD83 Utah South',
        ),
        32145 => array(
            'Id' => 32145,
            'Label' => 'NAD83 Vermont',
        ),
        32146 => array(
            'Id' => 32146,
            'Label' => 'NAD83 Virginia North',
        ),
        32147 => array(
            'Id' => 32147,
            'Label' => 'NAD83 Virginia South',
        ),
        32148 => array(
            'Id' => 32148,
            'Label' => 'NAD83 Washington North',
        ),
        32149 => array(
            'Id' => 32149,
            'Label' => 'NAD83 Washington South',
        ),
        32150 => array(
            'Id' => 32150,
            'Label' => 'NAD83 West Virginia N',
        ),
        32151 => array(
            'Id' => 32151,
            'Label' => 'NAD83 West Virginia S',
        ),
        32152 => array(
            'Id' => 32152,
            'Label' => 'NAD83 Wisconsin North',
        ),
        32153 => array(
            'Id' => 32153,
            'Label' => 'NAD83 Wisconsin Cen',
        ),
        32154 => array(
            'Id' => 32154,
            'Label' => 'NAD83 Wisconsin South',
        ),
        32155 => array(
            'Id' => 32155,
            'Label' => 'NAD83 Wyoming East',
        ),
        32156 => array(
            'Id' => 32156,
            'Label' => 'NAD83 Wyoming E Cen',
        ),
        32157 => array(
            'Id' => 32157,
            'Label' => 'NAD83 Wyoming W Cen',
        ),
        32158 => array(
            'Id' => 32158,
            'Label' => 'NAD83 Wyoming West',
        ),
        32161 => array(
            'Id' => 32161,
            'Label' => 'NAD83 Puerto Rico Virgin Is',
        ),
        32201 => array(
            'Id' => 32201,
            'Label' => 'WGS72 UTM zone 1N',
        ),
        32202 => array(
            'Id' => 32202,
            'Label' => 'WGS72 UTM zone 2N',
        ),
        32203 => array(
            'Id' => 32203,
            'Label' => 'WGS72 UTM zone 3N',
        ),
        32204 => array(
            'Id' => 32204,
            'Label' => 'WGS72 UTM zone 4N',
        ),
        32205 => array(
            'Id' => 32205,
            'Label' => 'WGS72 UTM zone 5N',
        ),
        32206 => array(
            'Id' => 32206,
            'Label' => 'WGS72 UTM zone 6N',
        ),
        32207 => array(
            'Id' => 32207,
            'Label' => 'WGS72 UTM zone 7N',
        ),
        32208 => array(
            'Id' => 32208,
            'Label' => 'WGS72 UTM zone 8N',
        ),
        32209 => array(
            'Id' => 32209,
            'Label' => 'WGS72 UTM zone 9N',
        ),
        32210 => array(
            'Id' => 32210,
            'Label' => 'WGS72 UTM zone 10N',
        ),
        32211 => array(
            'Id' => 32211,
            'Label' => 'WGS72 UTM zone 11N',
        ),
        32212 => array(
            'Id' => 32212,
            'Label' => 'WGS72 UTM zone 12N',
        ),
        32213 => array(
            'Id' => 32213,
            'Label' => 'WGS72 UTM zone 13N',
        ),
        32214 => array(
            'Id' => 32214,
            'Label' => 'WGS72 UTM zone 14N',
        ),
        32215 => array(
            'Id' => 32215,
            'Label' => 'WGS72 UTM zone 15N',
        ),
        32216 => array(
            'Id' => 32216,
            'Label' => 'WGS72 UTM zone 16N',
        ),
        32217 => array(
            'Id' => 32217,
            'Label' => 'WGS72 UTM zone 17N',
        ),
        32218 => array(
            'Id' => 32218,
            'Label' => 'WGS72 UTM zone 18N',
        ),
        32219 => array(
            'Id' => 32219,
            'Label' => 'WGS72 UTM zone 19N',
        ),
        32220 => array(
            'Id' => 32220,
            'Label' => 'WGS72 UTM zone 20N',
        ),
        32221 => array(
            'Id' => 32221,
            'Label' => 'WGS72 UTM zone 21N',
        ),
        32222 => array(
            'Id' => 32222,
            'Label' => 'WGS72 UTM zone 22N',
        ),
        32223 => array(
            'Id' => 32223,
            'Label' => 'WGS72 UTM zone 23N',
        ),
        32224 => array(
            'Id' => 32224,
            'Label' => 'WGS72 UTM zone 24N',
        ),
        32225 => array(
            'Id' => 32225,
            'Label' => 'WGS72 UTM zone 25N',
        ),
        32226 => array(
            'Id' => 32226,
            'Label' => 'WGS72 UTM zone 26N',
        ),
        32227 => array(
            'Id' => 32227,
            'Label' => 'WGS72 UTM zone 27N',
        ),
        32228 => array(
            'Id' => 32228,
            'Label' => 'WGS72 UTM zone 28N',
        ),
        32229 => array(
            'Id' => 32229,
            'Label' => 'WGS72 UTM zone 29N',
        ),
        32230 => array(
            'Id' => 32230,
            'Label' => 'WGS72 UTM zone 30N',
        ),
        32231 => array(
            'Id' => 32231,
            'Label' => 'WGS72 UTM zone 31N',
        ),
        32232 => array(
            'Id' => 32232,
            'Label' => 'WGS72 UTM zone 32N',
        ),
        32233 => array(
            'Id' => 32233,
            'Label' => 'WGS72 UTM zone 33N',
        ),
        32234 => array(
            'Id' => 32234,
            'Label' => 'WGS72 UTM zone 34N',
        ),
        32235 => array(
            'Id' => 32235,
            'Label' => 'WGS72 UTM zone 35N',
        ),
        32236 => array(
            'Id' => 32236,
            'Label' => 'WGS72 UTM zone 36N',
        ),
        32237 => array(
            'Id' => 32237,
            'Label' => 'WGS72 UTM zone 37N',
        ),
        32238 => array(
            'Id' => 32238,
            'Label' => 'WGS72 UTM zone 38N',
        ),
        32239 => array(
            'Id' => 32239,
            'Label' => 'WGS72 UTM zone 39N',
        ),
        32240 => array(
            'Id' => 32240,
            'Label' => 'WGS72 UTM zone 40N',
        ),
        32241 => array(
            'Id' => 32241,
            'Label' => 'WGS72 UTM zone 41N',
        ),
        32242 => array(
            'Id' => 32242,
            'Label' => 'WGS72 UTM zone 42N',
        ),
        32243 => array(
            'Id' => 32243,
            'Label' => 'WGS72 UTM zone 43N',
        ),
        32244 => array(
            'Id' => 32244,
            'Label' => 'WGS72 UTM zone 44N',
        ),
        32245 => array(
            'Id' => 32245,
            'Label' => 'WGS72 UTM zone 45N',
        ),
        32246 => array(
            'Id' => 32246,
            'Label' => 'WGS72 UTM zone 46N',
        ),
        32247 => array(
            'Id' => 32247,
            'Label' => 'WGS72 UTM zone 47N',
        ),
        32248 => array(
            'Id' => 32248,
            'Label' => 'WGS72 UTM zone 48N',
        ),
        32249 => array(
            'Id' => 32249,
            'Label' => 'WGS72 UTM zone 49N',
        ),
        32250 => array(
            'Id' => 32250,
            'Label' => 'WGS72 UTM zone 50N',
        ),
        32251 => array(
            'Id' => 32251,
            'Label' => 'WGS72 UTM zone 51N',
        ),
        32252 => array(
            'Id' => 32252,
            'Label' => 'WGS72 UTM zone 52N',
        ),
        32253 => array(
            'Id' => 32253,
            'Label' => 'WGS72 UTM zone 53N',
        ),
        32254 => array(
            'Id' => 32254,
            'Label' => 'WGS72 UTM zone 54N',
        ),
        32255 => array(
            'Id' => 32255,
            'Label' => 'WGS72 UTM zone 55N',
        ),
        32256 => array(
            'Id' => 32256,
            'Label' => 'WGS72 UTM zone 56N',
        ),
        32257 => array(
            'Id' => 32257,
            'Label' => 'WGS72 UTM zone 57N',
        ),
        32258 => array(
            'Id' => 32258,
            'Label' => 'WGS72 UTM zone 58N',
        ),
        32259 => array(
            'Id' => 32259,
            'Label' => 'WGS72 UTM zone 59N',
        ),
        32260 => array(
            'Id' => 32260,
            'Label' => 'WGS72 UTM zone 60N',
        ),
        32301 => array(
            'Id' => 32301,
            'Label' => 'WGS72 UTM zone 1S',
        ),
        32302 => array(
            'Id' => 32302,
            'Label' => 'WGS72 UTM zone 2S',
        ),
        32303 => array(
            'Id' => 32303,
            'Label' => 'WGS72 UTM zone 3S',
        ),
        32304 => array(
            'Id' => 32304,
            'Label' => 'WGS72 UTM zone 4S',
        ),
        32305 => array(
            'Id' => 32305,
            'Label' => 'WGS72 UTM zone 5S',
        ),
        32306 => array(
            'Id' => 32306,
            'Label' => 'WGS72 UTM zone 6S',
        ),
        32307 => array(
            'Id' => 32307,
            'Label' => 'WGS72 UTM zone 7S',
        ),
        32308 => array(
            'Id' => 32308,
            'Label' => 'WGS72 UTM zone 8S',
        ),
        32309 => array(
            'Id' => 32309,
            'Label' => 'WGS72 UTM zone 9S',
        ),
        32310 => array(
            'Id' => 32310,
            'Label' => 'WGS72 UTM zone 10S',
        ),
        32311 => array(
            'Id' => 32311,
            'Label' => 'WGS72 UTM zone 11S',
        ),
        32312 => array(
            'Id' => 32312,
            'Label' => 'WGS72 UTM zone 12S',
        ),
        32313 => array(
            'Id' => 32313,
            'Label' => 'WGS72 UTM zone 13S',
        ),
        32314 => array(
            'Id' => 32314,
            'Label' => 'WGS72 UTM zone 14S',
        ),
        32315 => array(
            'Id' => 32315,
            'Label' => 'WGS72 UTM zone 15S',
        ),
        32316 => array(
            'Id' => 32316,
            'Label' => 'WGS72 UTM zone 16S',
        ),
        32317 => array(
            'Id' => 32317,
            'Label' => 'WGS72 UTM zone 17S',
        ),
        32318 => array(
            'Id' => 32318,
            'Label' => 'WGS72 UTM zone 18S',
        ),
        32319 => array(
            'Id' => 32319,
            'Label' => 'WGS72 UTM zone 19S',
        ),
        32320 => array(
            'Id' => 32320,
            'Label' => 'WGS72 UTM zone 20S',
        ),
        32321 => array(
            'Id' => 32321,
            'Label' => 'WGS72 UTM zone 21S',
        ),
        32322 => array(
            'Id' => 32322,
            'Label' => 'WGS72 UTM zone 22S',
        ),
        32323 => array(
            'Id' => 32323,
            'Label' => 'WGS72 UTM zone 23S',
        ),
        32324 => array(
            'Id' => 32324,
            'Label' => 'WGS72 UTM zone 24S',
        ),
        32325 => array(
            'Id' => 32325,
            'Label' => 'WGS72 UTM zone 25S',
        ),
        32326 => array(
            'Id' => 32326,
            'Label' => 'WGS72 UTM zone 26S',
        ),
        32327 => array(
            'Id' => 32327,
            'Label' => 'WGS72 UTM zone 27S',
        ),
        32328 => array(
            'Id' => 32328,
            'Label' => 'WGS72 UTM zone 28S',
        ),
        32329 => array(
            'Id' => 32329,
            'Label' => 'WGS72 UTM zone 29S',
        ),
        32330 => array(
            'Id' => 32330,
            'Label' => 'WGS72 UTM zone 30S',
        ),
        32331 => array(
            'Id' => 32331,
            'Label' => 'WGS72 UTM zone 31S',
        ),
        32332 => array(
            'Id' => 32332,
            'Label' => 'WGS72 UTM zone 32S',
        ),
        32333 => array(
            'Id' => 32333,
            'Label' => 'WGS72 UTM zone 33S',
        ),
        32334 => array(
            'Id' => 32334,
            'Label' => 'WGS72 UTM zone 34S',
        ),
        32335 => array(
            'Id' => 32335,
            'Label' => 'WGS72 UTM zone 35S',
        ),
        32336 => array(
            'Id' => 32336,
            'Label' => 'WGS72 UTM zone 36S',
        ),
        32337 => array(
            'Id' => 32337,
            'Label' => 'WGS72 UTM zone 37S',
        ),
        32338 => array(
            'Id' => 32338,
            'Label' => 'WGS72 UTM zone 38S',
        ),
        32339 => array(
            'Id' => 32339,
            'Label' => 'WGS72 UTM zone 39S',
        ),
        32340 => array(
            'Id' => 32340,
            'Label' => 'WGS72 UTM zone 40S',
        ),
        32341 => array(
            'Id' => 32341,
            'Label' => 'WGS72 UTM zone 41S',
        ),
        32342 => array(
            'Id' => 32342,
            'Label' => 'WGS72 UTM zone 42S',
        ),
        32343 => array(
            'Id' => 32343,
            'Label' => 'WGS72 UTM zone 43S',
        ),
        32344 => array(
            'Id' => 32344,
            'Label' => 'WGS72 UTM zone 44S',
        ),
        32345 => array(
            'Id' => 32345,
            'Label' => 'WGS72 UTM zone 45S',
        ),
        32346 => array(
            'Id' => 32346,
            'Label' => 'WGS72 UTM zone 46S',
        ),
        32347 => array(
            'Id' => 32347,
            'Label' => 'WGS72 UTM zone 47S',
        ),
        32348 => array(
            'Id' => 32348,
            'Label' => 'WGS72 UTM zone 48S',
        ),
        32349 => array(
            'Id' => 32349,
            'Label' => 'WGS72 UTM zone 49S',
        ),
        32350 => array(
            'Id' => 32350,
            'Label' => 'WGS72 UTM zone 50S',
        ),
        32351 => array(
            'Id' => 32351,
            'Label' => 'WGS72 UTM zone 51S',
        ),
        32352 => array(
            'Id' => 32352,
            'Label' => 'WGS72 UTM zone 52S',
        ),
        32353 => array(
            'Id' => 32353,
            'Label' => 'WGS72 UTM zone 53S',
        ),
        32354 => array(
            'Id' => 32354,
            'Label' => 'WGS72 UTM zone 54S',
        ),
        32355 => array(
            'Id' => 32355,
            'Label' => 'WGS72 UTM zone 55S',
        ),
        32356 => array(
            'Id' => 32356,
            'Label' => 'WGS72 UTM zone 56S',
        ),
        32357 => array(
            'Id' => 32357,
            'Label' => 'WGS72 UTM zone 57S',
        ),
        32358 => array(
            'Id' => 32358,
            'Label' => 'WGS72 UTM zone 58S',
        ),
        32359 => array(
            'Id' => 32359,
            'Label' => 'WGS72 UTM zone 59S',
        ),
        32360 => array(
            'Id' => 32360,
            'Label' => 'WGS72 UTM zone 60S',
        ),
        32401 => array(
            'Id' => 32401,
            'Label' => 'WGS72BE UTM zone 1N',
        ),
        32402 => array(
            'Id' => 32402,
            'Label' => 'WGS72BE UTM zone 2N',
        ),
        32403 => array(
            'Id' => 32403,
            'Label' => 'WGS72BE UTM zone 3N',
        ),
        32404 => array(
            'Id' => 32404,
            'Label' => 'WGS72BE UTM zone 4N',
        ),
        32405 => array(
            'Id' => 32405,
            'Label' => 'WGS72BE UTM zone 5N',
        ),
        32406 => array(
            'Id' => 32406,
            'Label' => 'WGS72BE UTM zone 6N',
        ),
        32407 => array(
            'Id' => 32407,
            'Label' => 'WGS72BE UTM zone 7N',
        ),
        32408 => array(
            'Id' => 32408,
            'Label' => 'WGS72BE UTM zone 8N',
        ),
        32409 => array(
            'Id' => 32409,
            'Label' => 'WGS72BE UTM zone 9N',
        ),
        32410 => array(
            'Id' => 32410,
            'Label' => 'WGS72BE UTM zone 10N',
        ),
        32411 => array(
            'Id' => 32411,
            'Label' => 'WGS72BE UTM zone 11N',
        ),
        32412 => array(
            'Id' => 32412,
            'Label' => 'WGS72BE UTM zone 12N',
        ),
        32413 => array(
            'Id' => 32413,
            'Label' => 'WGS72BE UTM zone 13N',
        ),
        32414 => array(
            'Id' => 32414,
            'Label' => 'WGS72BE UTM zone 14N',
        ),
        32415 => array(
            'Id' => 32415,
            'Label' => 'WGS72BE UTM zone 15N',
        ),
        32416 => array(
            'Id' => 32416,
            'Label' => 'WGS72BE UTM zone 16N',
        ),
        32417 => array(
            'Id' => 32417,
            'Label' => 'WGS72BE UTM zone 17N',
        ),
        32418 => array(
            'Id' => 32418,
            'Label' => 'WGS72BE UTM zone 18N',
        ),
        32419 => array(
            'Id' => 32419,
            'Label' => 'WGS72BE UTM zone 19N',
        ),
        32420 => array(
            'Id' => 32420,
            'Label' => 'WGS72BE UTM zone 20N',
        ),
        32421 => array(
            'Id' => 32421,
            'Label' => 'WGS72BE UTM zone 21N',
        ),
        32422 => array(
            'Id' => 32422,
            'Label' => 'WGS72BE UTM zone 22N',
        ),
        32423 => array(
            'Id' => 32423,
            'Label' => 'WGS72BE UTM zone 23N',
        ),
        32424 => array(
            'Id' => 32424,
            'Label' => 'WGS72BE UTM zone 24N',
        ),
        32425 => array(
            'Id' => 32425,
            'Label' => 'WGS72BE UTM zone 25N',
        ),
        32426 => array(
            'Id' => 32426,
            'Label' => 'WGS72BE UTM zone 26N',
        ),
        32427 => array(
            'Id' => 32427,
            'Label' => 'WGS72BE UTM zone 27N',
        ),
        32428 => array(
            'Id' => 32428,
            'Label' => 'WGS72BE UTM zone 28N',
        ),
        32429 => array(
            'Id' => 32429,
            'Label' => 'WGS72BE UTM zone 29N',
        ),
        32430 => array(
            'Id' => 32430,
            'Label' => 'WGS72BE UTM zone 30N',
        ),
        32431 => array(
            'Id' => 32431,
            'Label' => 'WGS72BE UTM zone 31N',
        ),
        32432 => array(
            'Id' => 32432,
            'Label' => 'WGS72BE UTM zone 32N',
        ),
        32433 => array(
            'Id' => 32433,
            'Label' => 'WGS72BE UTM zone 33N',
        ),
        32434 => array(
            'Id' => 32434,
            'Label' => 'WGS72BE UTM zone 34N',
        ),
        32435 => array(
            'Id' => 32435,
            'Label' => 'WGS72BE UTM zone 35N',
        ),
        32436 => array(
            'Id' => 32436,
            'Label' => 'WGS72BE UTM zone 36N',
        ),
        32437 => array(
            'Id' => 32437,
            'Label' => 'WGS72BE UTM zone 37N',
        ),
        32438 => array(
            'Id' => 32438,
            'Label' => 'WGS72BE UTM zone 38N',
        ),
        32439 => array(
            'Id' => 32439,
            'Label' => 'WGS72BE UTM zone 39N',
        ),
        32440 => array(
            'Id' => 32440,
            'Label' => 'WGS72BE UTM zone 40N',
        ),
        32441 => array(
            'Id' => 32441,
            'Label' => 'WGS72BE UTM zone 41N',
        ),
        32442 => array(
            'Id' => 32442,
            'Label' => 'WGS72BE UTM zone 42N',
        ),
        32443 => array(
            'Id' => 32443,
            'Label' => 'WGS72BE UTM zone 43N',
        ),
        32444 => array(
            'Id' => 32444,
            'Label' => 'WGS72BE UTM zone 44N',
        ),
        32445 => array(
            'Id' => 32445,
            'Label' => 'WGS72BE UTM zone 45N',
        ),
        32446 => array(
            'Id' => 32446,
            'Label' => 'WGS72BE UTM zone 46N',
        ),
        32447 => array(
            'Id' => 32447,
            'Label' => 'WGS72BE UTM zone 47N',
        ),
        32448 => array(
            'Id' => 32448,
            'Label' => 'WGS72BE UTM zone 48N',
        ),
        32449 => array(
            'Id' => 32449,
            'Label' => 'WGS72BE UTM zone 49N',
        ),
        32450 => array(
            'Id' => 32450,
            'Label' => 'WGS72BE UTM zone 50N',
        ),
        32451 => array(
            'Id' => 32451,
            'Label' => 'WGS72BE UTM zone 51N',
        ),
        32452 => array(
            'Id' => 32452,
            'Label' => 'WGS72BE UTM zone 52N',
        ),
        32453 => array(
            'Id' => 32453,
            'Label' => 'WGS72BE UTM zone 53N',
        ),
        32454 => array(
            'Id' => 32454,
            'Label' => 'WGS72BE UTM zone 54N',
        ),
        32455 => array(
            'Id' => 32455,
            'Label' => 'WGS72BE UTM zone 55N',
        ),
        32456 => array(
            'Id' => 32456,
            'Label' => 'WGS72BE UTM zone 56N',
        ),
        32457 => array(
            'Id' => 32457,
            'Label' => 'WGS72BE UTM zone 57N',
        ),
        32458 => array(
            'Id' => 32458,
            'Label' => 'WGS72BE UTM zone 58N',
        ),
        32459 => array(
            'Id' => 32459,
            'Label' => 'WGS72BE UTM zone 59N',
        ),
        32460 => array(
            'Id' => 32460,
            'Label' => 'WGS72BE UTM zone 60N',
        ),
        32501 => array(
            'Id' => 32501,
            'Label' => 'WGS72BE UTM zone 1S',
        ),
        32502 => array(
            'Id' => 32502,
            'Label' => 'WGS72BE UTM zone 2S',
        ),
        32503 => array(
            'Id' => 32503,
            'Label' => 'WGS72BE UTM zone 3S',
        ),
        32504 => array(
            'Id' => 32504,
            'Label' => 'WGS72BE UTM zone 4S',
        ),
        32505 => array(
            'Id' => 32505,
            'Label' => 'WGS72BE UTM zone 5S',
        ),
        32506 => array(
            'Id' => 32506,
            'Label' => 'WGS72BE UTM zone 6S',
        ),
        32507 => array(
            'Id' => 32507,
            'Label' => 'WGS72BE UTM zone 7S',
        ),
        32508 => array(
            'Id' => 32508,
            'Label' => 'WGS72BE UTM zone 8S',
        ),
        32509 => array(
            'Id' => 32509,
            'Label' => 'WGS72BE UTM zone 9S',
        ),
        32510 => array(
            'Id' => 32510,
            'Label' => 'WGS72BE UTM zone 10S',
        ),
        32511 => array(
            'Id' => 32511,
            'Label' => 'WGS72BE UTM zone 11S',
        ),
        32512 => array(
            'Id' => 32512,
            'Label' => 'WGS72BE UTM zone 12S',
        ),
        32513 => array(
            'Id' => 32513,
            'Label' => 'WGS72BE UTM zone 13S',
        ),
        32514 => array(
            'Id' => 32514,
            'Label' => 'WGS72BE UTM zone 14S',
        ),
        32515 => array(
            'Id' => 32515,
            'Label' => 'WGS72BE UTM zone 15S',
        ),
        32516 => array(
            'Id' => 32516,
            'Label' => 'WGS72BE UTM zone 16S',
        ),
        32517 => array(
            'Id' => 32517,
            'Label' => 'WGS72BE UTM zone 17S',
        ),
        32518 => array(
            'Id' => 32518,
            'Label' => 'WGS72BE UTM zone 18S',
        ),
        32519 => array(
            'Id' => 32519,
            'Label' => 'WGS72BE UTM zone 19S',
        ),
        32520 => array(
            'Id' => 32520,
            'Label' => 'WGS72BE UTM zone 20S',
        ),
        32521 => array(
            'Id' => 32521,
            'Label' => 'WGS72BE UTM zone 21S',
        ),
        32522 => array(
            'Id' => 32522,
            'Label' => 'WGS72BE UTM zone 22S',
        ),
        32523 => array(
            'Id' => 32523,
            'Label' => 'WGS72BE UTM zone 23S',
        ),
        32524 => array(
            'Id' => 32524,
            'Label' => 'WGS72BE UTM zone 24S',
        ),
        32525 => array(
            'Id' => 32525,
            'Label' => 'WGS72BE UTM zone 25S',
        ),
        32526 => array(
            'Id' => 32526,
            'Label' => 'WGS72BE UTM zone 26S',
        ),
        32527 => array(
            'Id' => 32527,
            'Label' => 'WGS72BE UTM zone 27S',
        ),
        32528 => array(
            'Id' => 32528,
            'Label' => 'WGS72BE UTM zone 28S',
        ),
        32529 => array(
            'Id' => 32529,
            'Label' => 'WGS72BE UTM zone 29S',
        ),
        32530 => array(
            'Id' => 32530,
            'Label' => 'WGS72BE UTM zone 30S',
        ),
        32531 => array(
            'Id' => 32531,
            'Label' => 'WGS72BE UTM zone 31S',
        ),
        32532 => array(
            'Id' => 32532,
            'Label' => 'WGS72BE UTM zone 32S',
        ),
        32533 => array(
            'Id' => 32533,
            'Label' => 'WGS72BE UTM zone 33S',
        ),
        32534 => array(
            'Id' => 32534,
            'Label' => 'WGS72BE UTM zone 34S',
        ),
        32535 => array(
            'Id' => 32535,
            'Label' => 'WGS72BE UTM zone 35S',
        ),
        32536 => array(
            'Id' => 32536,
            'Label' => 'WGS72BE UTM zone 36S',
        ),
        32537 => array(
            'Id' => 32537,
            'Label' => 'WGS72BE UTM zone 37S',
        ),
        32538 => array(
            'Id' => 32538,
            'Label' => 'WGS72BE UTM zone 38S',
        ),
        32539 => array(
            'Id' => 32539,
            'Label' => 'WGS72BE UTM zone 39S',
        ),
        32540 => array(
            'Id' => 32540,
            'Label' => 'WGS72BE UTM zone 40S',
        ),
        32541 => array(
            'Id' => 32541,
            'Label' => 'WGS72BE UTM zone 41S',
        ),
        32542 => array(
            'Id' => 32542,
            'Label' => 'WGS72BE UTM zone 42S',
        ),
        32543 => array(
            'Id' => 32543,
            'Label' => 'WGS72BE UTM zone 43S',
        ),
        32544 => array(
            'Id' => 32544,
            'Label' => 'WGS72BE UTM zone 44S',
        ),
        32545 => array(
            'Id' => 32545,
            'Label' => 'WGS72BE UTM zone 45S',
        ),
        32546 => array(
            'Id' => 32546,
            'Label' => 'WGS72BE UTM zone 46S',
        ),
        32547 => array(
            'Id' => 32547,
            'Label' => 'WGS72BE UTM zone 47S',
        ),
        32548 => array(
            'Id' => 32548,
            'Label' => 'WGS72BE UTM zone 48S',
        ),
        32549 => array(
            'Id' => 32549,
            'Label' => 'WGS72BE UTM zone 49S',
        ),
        32550 => array(
            'Id' => 32550,
            'Label' => 'WGS72BE UTM zone 50S',
        ),
        32551 => array(
            'Id' => 32551,
            'Label' => 'WGS72BE UTM zone 51S',
        ),
        32552 => array(
            'Id' => 32552,
            'Label' => 'WGS72BE UTM zone 52S',
        ),
        32553 => array(
            'Id' => 32553,
            'Label' => 'WGS72BE UTM zone 53S',
        ),
        32554 => array(
            'Id' => 32554,
            'Label' => 'WGS72BE UTM zone 54S',
        ),
        32555 => array(
            'Id' => 32555,
            'Label' => 'WGS72BE UTM zone 55S',
        ),
        32556 => array(
            'Id' => 32556,
            'Label' => 'WGS72BE UTM zone 56S',
        ),
        32557 => array(
            'Id' => 32557,
            'Label' => 'WGS72BE UTM zone 57S',
        ),
        32558 => array(
            'Id' => 32558,
            'Label' => 'WGS72BE UTM zone 58S',
        ),
        32559 => array(
            'Id' => 32559,
            'Label' => 'WGS72BE UTM zone 59S',
        ),
        32560 => array(
            'Id' => 32560,
            'Label' => 'WGS72BE UTM zone 60S',
        ),
        32601 => array(
            'Id' => 32601,
            'Label' => 'WGS84 UTM zone 1N',
        ),
        32602 => array(
            'Id' => 32602,
            'Label' => 'WGS84 UTM zone 2N',
        ),
        32603 => array(
            'Id' => 32603,
            'Label' => 'WGS84 UTM zone 3N',
        ),
        32604 => array(
            'Id' => 32604,
            'Label' => 'WGS84 UTM zone 4N',
        ),
        32605 => array(
            'Id' => 32605,
            'Label' => 'WGS84 UTM zone 5N',
        ),
        32606 => array(
            'Id' => 32606,
            'Label' => 'WGS84 UTM zone 6N',
        ),
        32607 => array(
            'Id' => 32607,
            'Label' => 'WGS84 UTM zone 7N',
        ),
        32608 => array(
            'Id' => 32608,
            'Label' => 'WGS84 UTM zone 8N',
        ),
        32609 => array(
            'Id' => 32609,
            'Label' => 'WGS84 UTM zone 9N',
        ),
        32610 => array(
            'Id' => 32610,
            'Label' => 'WGS84 UTM zone 10N',
        ),
        32611 => array(
            'Id' => 32611,
            'Label' => 'WGS84 UTM zone 11N',
        ),
        32612 => array(
            'Id' => 32612,
            'Label' => 'WGS84 UTM zone 12N',
        ),
        32613 => array(
            'Id' => 32613,
            'Label' => 'WGS84 UTM zone 13N',
        ),
        32614 => array(
            'Id' => 32614,
            'Label' => 'WGS84 UTM zone 14N',
        ),
        32615 => array(
            'Id' => 32615,
            'Label' => 'WGS84 UTM zone 15N',
        ),
        32616 => array(
            'Id' => 32616,
            'Label' => 'WGS84 UTM zone 16N',
        ),
        32617 => array(
            'Id' => 32617,
            'Label' => 'WGS84 UTM zone 17N',
        ),
        32618 => array(
            'Id' => 32618,
            'Label' => 'WGS84 UTM zone 18N',
        ),
        32619 => array(
            'Id' => 32619,
            'Label' => 'WGS84 UTM zone 19N',
        ),
        32620 => array(
            'Id' => 32620,
            'Label' => 'WGS84 UTM zone 20N',
        ),
        32621 => array(
            'Id' => 32621,
            'Label' => 'WGS84 UTM zone 21N',
        ),
        32622 => array(
            'Id' => 32622,
            'Label' => 'WGS84 UTM zone 22N',
        ),
        32623 => array(
            'Id' => 32623,
            'Label' => 'WGS84 UTM zone 23N',
        ),
        32624 => array(
            'Id' => 32624,
            'Label' => 'WGS84 UTM zone 24N',
        ),
        32625 => array(
            'Id' => 32625,
            'Label' => 'WGS84 UTM zone 25N',
        ),
        32626 => array(
            'Id' => 32626,
            'Label' => 'WGS84 UTM zone 26N',
        ),
        32627 => array(
            'Id' => 32627,
            'Label' => 'WGS84 UTM zone 27N',
        ),
        32628 => array(
            'Id' => 32628,
            'Label' => 'WGS84 UTM zone 28N',
        ),
        32629 => array(
            'Id' => 32629,
            'Label' => 'WGS84 UTM zone 29N',
        ),
        32630 => array(
            'Id' => 32630,
            'Label' => 'WGS84 UTM zone 30N',
        ),
        32631 => array(
            'Id' => 32631,
            'Label' => 'WGS84 UTM zone 31N',
        ),
        32632 => array(
            'Id' => 32632,
            'Label' => 'WGS84 UTM zone 32N',
        ),
        32633 => array(
            'Id' => 32633,
            'Label' => 'WGS84 UTM zone 33N',
        ),
        32634 => array(
            'Id' => 32634,
            'Label' => 'WGS84 UTM zone 34N',
        ),
        32635 => array(
            'Id' => 32635,
            'Label' => 'WGS84 UTM zone 35N',
        ),
        32636 => array(
            'Id' => 32636,
            'Label' => 'WGS84 UTM zone 36N',
        ),
        32637 => array(
            'Id' => 32637,
            'Label' => 'WGS84 UTM zone 37N',
        ),
        32638 => array(
            'Id' => 32638,
            'Label' => 'WGS84 UTM zone 38N',
        ),
        32639 => array(
            'Id' => 32639,
            'Label' => 'WGS84 UTM zone 39N',
        ),
        32640 => array(
            'Id' => 32640,
            'Label' => 'WGS84 UTM zone 40N',
        ),
        32641 => array(
            'Id' => 32641,
            'Label' => 'WGS84 UTM zone 41N',
        ),
        32642 => array(
            'Id' => 32642,
            'Label' => 'WGS84 UTM zone 42N',
        ),
        32643 => array(
            'Id' => 32643,
            'Label' => 'WGS84 UTM zone 43N',
        ),
        32644 => array(
            'Id' => 32644,
            'Label' => 'WGS84 UTM zone 44N',
        ),
        32645 => array(
            'Id' => 32645,
            'Label' => 'WGS84 UTM zone 45N',
        ),
        32646 => array(
            'Id' => 32646,
            'Label' => 'WGS84 UTM zone 46N',
        ),
        32647 => array(
            'Id' => 32647,
            'Label' => 'WGS84 UTM zone 47N',
        ),
        32648 => array(
            'Id' => 32648,
            'Label' => 'WGS84 UTM zone 48N',
        ),
        32649 => array(
            'Id' => 32649,
            'Label' => 'WGS84 UTM zone 49N',
        ),
        32650 => array(
            'Id' => 32650,
            'Label' => 'WGS84 UTM zone 50N',
        ),
        32651 => array(
            'Id' => 32651,
            'Label' => 'WGS84 UTM zone 51N',
        ),
        32652 => array(
            'Id' => 32652,
            'Label' => 'WGS84 UTM zone 52N',
        ),
        32653 => array(
            'Id' => 32653,
            'Label' => 'WGS84 UTM zone 53N',
        ),
        32654 => array(
            'Id' => 32654,
            'Label' => 'WGS84 UTM zone 54N',
        ),
        32655 => array(
            'Id' => 32655,
            'Label' => 'WGS84 UTM zone 55N',
        ),
        32656 => array(
            'Id' => 32656,
            'Label' => 'WGS84 UTM zone 56N',
        ),
        32657 => array(
            'Id' => 32657,
            'Label' => 'WGS84 UTM zone 57N',
        ),
        32658 => array(
            'Id' => 32658,
            'Label' => 'WGS84 UTM zone 58N',
        ),
        32659 => array(
            'Id' => 32659,
            'Label' => 'WGS84 UTM zone 59N',
        ),
        32660 => array(
            'Id' => 32660,
            'Label' => 'WGS84 UTM zone 60N',
        ),
        32701 => array(
            'Id' => 32701,
            'Label' => 'WGS84 UTM zone 1S',
        ),
        32702 => array(
            'Id' => 32702,
            'Label' => 'WGS84 UTM zone 2S',
        ),
        32703 => array(
            'Id' => 32703,
            'Label' => 'WGS84 UTM zone 3S',
        ),
        32704 => array(
            'Id' => 32704,
            'Label' => 'WGS84 UTM zone 4S',
        ),
        32705 => array(
            'Id' => 32705,
            'Label' => 'WGS84 UTM zone 5S',
        ),
        32706 => array(
            'Id' => 32706,
            'Label' => 'WGS84 UTM zone 6S',
        ),
        32707 => array(
            'Id' => 32707,
            'Label' => 'WGS84 UTM zone 7S',
        ),
        32708 => array(
            'Id' => 32708,
            'Label' => 'WGS84 UTM zone 8S',
        ),
        32709 => array(
            'Id' => 32709,
            'Label' => 'WGS84 UTM zone 9S',
        ),
        32710 => array(
            'Id' => 32710,
            'Label' => 'WGS84 UTM zone 10S',
        ),
        32711 => array(
            'Id' => 32711,
            'Label' => 'WGS84 UTM zone 11S',
        ),
        32712 => array(
            'Id' => 32712,
            'Label' => 'WGS84 UTM zone 12S',
        ),
        32713 => array(
            'Id' => 32713,
            'Label' => 'WGS84 UTM zone 13S',
        ),
        32714 => array(
            'Id' => 32714,
            'Label' => 'WGS84 UTM zone 14S',
        ),
        32715 => array(
            'Id' => 32715,
            'Label' => 'WGS84 UTM zone 15S',
        ),
        32716 => array(
            'Id' => 32716,
            'Label' => 'WGS84 UTM zone 16S',
        ),
        32717 => array(
            'Id' => 32717,
            'Label' => 'WGS84 UTM zone 17S',
        ),
        32718 => array(
            'Id' => 32718,
            'Label' => 'WGS84 UTM zone 18S',
        ),
        32719 => array(
            'Id' => 32719,
            'Label' => 'WGS84 UTM zone 19S',
        ),
        32720 => array(
            'Id' => 32720,
            'Label' => 'WGS84 UTM zone 20S',
        ),
        32721 => array(
            'Id' => 32721,
            'Label' => 'WGS84 UTM zone 21S',
        ),
        32722 => array(
            'Id' => 32722,
            'Label' => 'WGS84 UTM zone 22S',
        ),
        32723 => array(
            'Id' => 32723,
            'Label' => 'WGS84 UTM zone 23S',
        ),
        32724 => array(
            'Id' => 32724,
            'Label' => 'WGS84 UTM zone 24S',
        ),
        32725 => array(
            'Id' => 32725,
            'Label' => 'WGS84 UTM zone 25S',
        ),
        32726 => array(
            'Id' => 32726,
            'Label' => 'WGS84 UTM zone 26S',
        ),
        32727 => array(
            'Id' => 32727,
            'Label' => 'WGS84 UTM zone 27S',
        ),
        32728 => array(
            'Id' => 32728,
            'Label' => 'WGS84 UTM zone 28S',
        ),
        32729 => array(
            'Id' => 32729,
            'Label' => 'WGS84 UTM zone 29S',
        ),
        32730 => array(
            'Id' => 32730,
            'Label' => 'WGS84 UTM zone 30S',
        ),
        32731 => array(
            'Id' => 32731,
            'Label' => 'WGS84 UTM zone 31S',
        ),
        32732 => array(
            'Id' => 32732,
            'Label' => 'WGS84 UTM zone 32S',
        ),
        32733 => array(
            'Id' => 32733,
            'Label' => 'WGS84 UTM zone 33S',
        ),
        32734 => array(
            'Id' => 32734,
            'Label' => 'WGS84 UTM zone 34S',
        ),
        32735 => array(
            'Id' => 32735,
            'Label' => 'WGS84 UTM zone 35S',
        ),
        32736 => array(
            'Id' => 32736,
            'Label' => 'WGS84 UTM zone 36S',
        ),
        32737 => array(
            'Id' => 32737,
            'Label' => 'WGS84 UTM zone 37S',
        ),
        32738 => array(
            'Id' => 32738,
            'Label' => 'WGS84 UTM zone 38S',
        ),
        32739 => array(
            'Id' => 32739,
            'Label' => 'WGS84 UTM zone 39S',
        ),
        32740 => array(
            'Id' => 32740,
            'Label' => 'WGS84 UTM zone 40S',
        ),
        32741 => array(
            'Id' => 32741,
            'Label' => 'WGS84 UTM zone 41S',
        ),
        32742 => array(
            'Id' => 32742,
            'Label' => 'WGS84 UTM zone 42S',
        ),
        32743 => array(
            'Id' => 32743,
            'Label' => 'WGS84 UTM zone 43S',
        ),
        32744 => array(
            'Id' => 32744,
            'Label' => 'WGS84 UTM zone 44S',
        ),
        32745 => array(
            'Id' => 32745,
            'Label' => 'WGS84 UTM zone 45S',
        ),
        32746 => array(
            'Id' => 32746,
            'Label' => 'WGS84 UTM zone 46S',
        ),
        32747 => array(
            'Id' => 32747,
            'Label' => 'WGS84 UTM zone 47S',
        ),
        32748 => array(
            'Id' => 32748,
            'Label' => 'WGS84 UTM zone 48S',
        ),
        32749 => array(
            'Id' => 32749,
            'Label' => 'WGS84 UTM zone 49S',
        ),
        32750 => array(
            'Id' => 32750,
            'Label' => 'WGS84 UTM zone 50S',
        ),
        32751 => array(
            'Id' => 32751,
            'Label' => 'WGS84 UTM zone 51S',
        ),
        32752 => array(
            'Id' => 32752,
            'Label' => 'WGS84 UTM zone 52S',
        ),
        32753 => array(
            'Id' => 32753,
            'Label' => 'WGS84 UTM zone 53S',
        ),
        32754 => array(
            'Id' => 32754,
            'Label' => 'WGS84 UTM zone 54S',
        ),
        32755 => array(
            'Id' => 32755,
            'Label' => 'WGS84 UTM zone 55S',
        ),
        32756 => array(
            'Id' => 32756,
            'Label' => 'WGS84 UTM zone 56S',
        ),
        32757 => array(
            'Id' => 32757,
            'Label' => 'WGS84 UTM zone 57S',
        ),
        32758 => array(
            'Id' => 32758,
            'Label' => 'WGS84 UTM zone 58S',
        ),
        32759 => array(
            'Id' => 32759,
            'Label' => 'WGS84 UTM zone 59S',
        ),
        32760 => array(
            'Id' => 32760,
            'Label' => 'WGS84 UTM zone 60S',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
