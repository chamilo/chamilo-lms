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
class GeogGeodeticDatum extends AbstractTag
{

    protected $Id = 2050;

    protected $Name = 'GeogGeodeticDatum';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Geog Geodetic Datum';

    protected $Values = array(
        6001 => array(
            'Id' => 6001,
            'Label' => 'Airy 1830',
        ),
        6002 => array(
            'Id' => 6002,
            'Label' => 'Airy Modified 1849',
        ),
        6003 => array(
            'Id' => 6003,
            'Label' => 'Australian National Spheroid',
        ),
        6004 => array(
            'Id' => 6004,
            'Label' => 'Bessel 1841',
        ),
        6005 => array(
            'Id' => 6005,
            'Label' => 'Bessel Modified',
        ),
        6006 => array(
            'Id' => 6006,
            'Label' => 'Bessel Namibia',
        ),
        6007 => array(
            'Id' => 6007,
            'Label' => 'Clarke 1858',
        ),
        6008 => array(
            'Id' => 6008,
            'Label' => 'Clarke 1866',
        ),
        6009 => array(
            'Id' => 6009,
            'Label' => 'Clarke 1866 Michigan',
        ),
        6010 => array(
            'Id' => 6010,
            'Label' => 'Clarke 1880 Benoit',
        ),
        6011 => array(
            'Id' => 6011,
            'Label' => 'Clarke 1880 IGN',
        ),
        6012 => array(
            'Id' => 6012,
            'Label' => 'Clarke 1880 RGS',
        ),
        6013 => array(
            'Id' => 6013,
            'Label' => 'Clarke 1880 Arc',
        ),
        6014 => array(
            'Id' => 6014,
            'Label' => 'Clarke 1880 SGA 1922',
        ),
        6015 => array(
            'Id' => 6015,
            'Label' => 'Everest 1830 1937 Adjustment',
        ),
        6016 => array(
            'Id' => 6016,
            'Label' => 'Everest 1830 1967 Definition',
        ),
        6017 => array(
            'Id' => 6017,
            'Label' => 'Everest 1830 1975 Definition',
        ),
        6018 => array(
            'Id' => 6018,
            'Label' => 'Everest 1830 Modified',
        ),
        6019 => array(
            'Id' => 6019,
            'Label' => 'GRS 1980',
        ),
        6020 => array(
            'Id' => 6020,
            'Label' => 'Helmert 1906',
        ),
        6021 => array(
            'Id' => 6021,
            'Label' => 'Indonesian National Spheroid',
        ),
        6022 => array(
            'Id' => 6022,
            'Label' => 'International 1924',
        ),
        6023 => array(
            'Id' => 6023,
            'Label' => 'International 1967',
        ),
        6024 => array(
            'Id' => 6024,
            'Label' => 'Krassowsky 1960',
        ),
        6025 => array(
            'Id' => 6025,
            'Label' => 'NWL9D',
        ),
        6026 => array(
            'Id' => 6026,
            'Label' => 'NWL10D',
        ),
        6027 => array(
            'Id' => 6027,
            'Label' => 'Plessis 1817',
        ),
        6028 => array(
            'Id' => 6028,
            'Label' => 'Struve 1860',
        ),
        6029 => array(
            'Id' => 6029,
            'Label' => 'War Office',
        ),
        6030 => array(
            'Id' => 6030,
            'Label' => 'WGS84',
        ),
        6031 => array(
            'Id' => 6031,
            'Label' => 'GEM10C',
        ),
        6032 => array(
            'Id' => 6032,
            'Label' => 'OSU86F',
        ),
        6033 => array(
            'Id' => 6033,
            'Label' => 'OSU91A',
        ),
        6034 => array(
            'Id' => 6034,
            'Label' => 'Clarke 1880',
        ),
        6035 => array(
            'Id' => 6035,
            'Label' => 'Sphere',
        ),
        6201 => array(
            'Id' => 6201,
            'Label' => 'Adindan',
        ),
        6202 => array(
            'Id' => 6202,
            'Label' => 'Australian Geodetic Datum 1966',
        ),
        6203 => array(
            'Id' => 6203,
            'Label' => 'Australian Geodetic Datum 1984',
        ),
        6204 => array(
            'Id' => 6204,
            'Label' => 'Ain el Abd 1970',
        ),
        6205 => array(
            'Id' => 6205,
            'Label' => 'Afgooye',
        ),
        6206 => array(
            'Id' => 6206,
            'Label' => 'Agadez',
        ),
        6207 => array(
            'Id' => 6207,
            'Label' => 'Lisbon',
        ),
        6208 => array(
            'Id' => 6208,
            'Label' => 'Aratu',
        ),
        6209 => array(
            'Id' => 6209,
            'Label' => 'Arc 1950',
        ),
        6210 => array(
            'Id' => 6210,
            'Label' => 'Arc 1960',
        ),
        6211 => array(
            'Id' => 6211,
            'Label' => 'Batavia',
        ),
        6212 => array(
            'Id' => 6212,
            'Label' => 'Barbados',
        ),
        6213 => array(
            'Id' => 6213,
            'Label' => 'Beduaram',
        ),
        6214 => array(
            'Id' => 6214,
            'Label' => 'Beijing 1954',
        ),
        6215 => array(
            'Id' => 6215,
            'Label' => 'Reseau National Belge 1950',
        ),
        6216 => array(
            'Id' => 6216,
            'Label' => 'Bermuda 1957',
        ),
        6217 => array(
            'Id' => 6217,
            'Label' => 'Bern 1898',
        ),
        6218 => array(
            'Id' => 6218,
            'Label' => 'Bogota',
        ),
        6219 => array(
            'Id' => 6219,
            'Label' => 'Bukit Rimpah',
        ),
        6220 => array(
            'Id' => 6220,
            'Label' => 'Camacupa',
        ),
        6221 => array(
            'Id' => 6221,
            'Label' => 'Campo Inchauspe',
        ),
        6222 => array(
            'Id' => 6222,
            'Label' => 'Cape',
        ),
        6223 => array(
            'Id' => 6223,
            'Label' => 'Carthage',
        ),
        6224 => array(
            'Id' => 6224,
            'Label' => 'Chua',
        ),
        6225 => array(
            'Id' => 6225,
            'Label' => 'Corrego Alegre',
        ),
        6226 => array(
            'Id' => 6226,
            'Label' => 'Cote d Ivoire',
        ),
        6227 => array(
            'Id' => 6227,
            'Label' => 'Deir ez Zor',
        ),
        6228 => array(
            'Id' => 6228,
            'Label' => 'Douala',
        ),
        6229 => array(
            'Id' => 6229,
            'Label' => 'Egypt 1907',
        ),
        6230 => array(
            'Id' => 6230,
            'Label' => 'European Datum 1950',
        ),
        6231 => array(
            'Id' => 6231,
            'Label' => 'European Datum 1987',
        ),
        6232 => array(
            'Id' => 6232,
            'Label' => 'Fahud',
        ),
        6233 => array(
            'Id' => 6233,
            'Label' => 'Gandajika 1970',
        ),
        6234 => array(
            'Id' => 6234,
            'Label' => 'Garoua',
        ),
        6235 => array(
            'Id' => 6235,
            'Label' => 'Guyane Francaise',
        ),
        6236 => array(
            'Id' => 6236,
            'Label' => 'Hu Tzu Shan',
        ),
        6237 => array(
            'Id' => 6237,
            'Label' => 'Hungarian Datum 1972',
        ),
        6238 => array(
            'Id' => 6238,
            'Label' => 'Indonesian Datum 1974',
        ),
        6239 => array(
            'Id' => 6239,
            'Label' => 'Indian 1954',
        ),
        6240 => array(
            'Id' => 6240,
            'Label' => 'Indian 1975',
        ),
        6241 => array(
            'Id' => 6241,
            'Label' => 'Jamaica 1875',
        ),
        6242 => array(
            'Id' => 6242,
            'Label' => 'Jamaica 1969',
        ),
        6243 => array(
            'Id' => 6243,
            'Label' => 'Kalianpur',
        ),
        6244 => array(
            'Id' => 6244,
            'Label' => 'Kandawala',
        ),
        6245 => array(
            'Id' => 6245,
            'Label' => 'Kertau',
        ),
        6246 => array(
            'Id' => 6246,
            'Label' => 'Kuwait Oil Company',
        ),
        6247 => array(
            'Id' => 6247,
            'Label' => 'La Canoa',
        ),
        6248 => array(
            'Id' => 6248,
            'Label' => 'Provisional S American Datum 1956',
        ),
        6249 => array(
            'Id' => 6249,
            'Label' => 'Lake',
        ),
        6250 => array(
            'Id' => 6250,
            'Label' => 'Leigon',
        ),
        6251 => array(
            'Id' => 6251,
            'Label' => 'Liberia 1964',
        ),
        6252 => array(
            'Id' => 6252,
            'Label' => 'Lome',
        ),
        6253 => array(
            'Id' => 6253,
            'Label' => 'Luzon 1911',
        ),
        6254 => array(
            'Id' => 6254,
            'Label' => 'Hito XVIII 1963',
        ),
        6255 => array(
            'Id' => 6255,
            'Label' => 'Herat North',
        ),
        6256 => array(
            'Id' => 6256,
            'Label' => 'Mahe 1971',
        ),
        6257 => array(
            'Id' => 6257,
            'Label' => 'Makassar',
        ),
        6258 => array(
            'Id' => 6258,
            'Label' => 'European Reference System 1989',
        ),
        6259 => array(
            'Id' => 6259,
            'Label' => 'Malongo 1987',
        ),
        6260 => array(
            'Id' => 6260,
            'Label' => 'Manoca',
        ),
        6261 => array(
            'Id' => 6261,
            'Label' => 'Merchich',
        ),
        6262 => array(
            'Id' => 6262,
            'Label' => 'Massawa',
        ),
        6263 => array(
            'Id' => 6263,
            'Label' => 'Minna',
        ),
        6264 => array(
            'Id' => 6264,
            'Label' => 'Mhast',
        ),
        6265 => array(
            'Id' => 6265,
            'Label' => 'Monte Mario',
        ),
        6266 => array(
            'Id' => 6266,
            'Label' => 'M poraloko',
        ),
        6267 => array(
            'Id' => 6267,
            'Label' => 'North American Datum 1927',
        ),
        6268 => array(
            'Id' => 6268,
            'Label' => 'NAD Michigan',
        ),
        6269 => array(
            'Id' => 6269,
            'Label' => 'North American Datum 1983',
        ),
        6270 => array(
            'Id' => 6270,
            'Label' => 'Nahrwan 1967',
        ),
        6271 => array(
            'Id' => 6271,
            'Label' => 'Naparima 1972',
        ),
        6272 => array(
            'Id' => 6272,
            'Label' => 'New Zealand Geodetic Datum 1949',
        ),
        6273 => array(
            'Id' => 6273,
            'Label' => 'NGO 1948',
        ),
        6274 => array(
            'Id' => 6274,
            'Label' => 'Datum 73',
        ),
        6275 => array(
            'Id' => 6275,
            'Label' => 'Nouvelle Triangulation Francaise',
        ),
        6276 => array(
            'Id' => 6276,
            'Label' => 'NSWC 9Z 2',
        ),
        6277 => array(
            'Id' => 6277,
            'Label' => 'OSGB 1936',
        ),
        6278 => array(
            'Id' => 6278,
            'Label' => 'OSGB 1970 SN',
        ),
        6279 => array(
            'Id' => 6279,
            'Label' => 'OS SN 1980',
        ),
        6280 => array(
            'Id' => 6280,
            'Label' => 'Padang 1884',
        ),
        6281 => array(
            'Id' => 6281,
            'Label' => 'Palestine 1923',
        ),
        6282 => array(
            'Id' => 6282,
            'Label' => 'Pointe Noire',
        ),
        6283 => array(
            'Id' => 6283,
            'Label' => 'Geocentric Datum of Australia 1994',
        ),
        6284 => array(
            'Id' => 6284,
            'Label' => 'Pulkovo 1942',
        ),
        6285 => array(
            'Id' => 6285,
            'Label' => 'Qatar',
        ),
        6286 => array(
            'Id' => 6286,
            'Label' => 'Qatar 1948',
        ),
        6287 => array(
            'Id' => 6287,
            'Label' => 'Qornoq',
        ),
        6288 => array(
            'Id' => 6288,
            'Label' => 'Loma Quintana',
        ),
        6289 => array(
            'Id' => 6289,
            'Label' => 'Amersfoort',
        ),
        6290 => array(
            'Id' => 6290,
            'Label' => 'RT38',
        ),
        6291 => array(
            'Id' => 6291,
            'Label' => 'South American Datum 1969',
        ),
        6292 => array(
            'Id' => 6292,
            'Label' => 'Sapper Hill 1943',
        ),
        6293 => array(
            'Id' => 6293,
            'Label' => 'Schwarzeck',
        ),
        6294 => array(
            'Id' => 6294,
            'Label' => 'Segora',
        ),
        6295 => array(
            'Id' => 6295,
            'Label' => 'Serindung',
        ),
        6296 => array(
            'Id' => 6296,
            'Label' => 'Sudan',
        ),
        6297 => array(
            'Id' => 6297,
            'Label' => 'Tananarive 1925',
        ),
        6298 => array(
            'Id' => 6298,
            'Label' => 'Timbalai 1948',
        ),
        6299 => array(
            'Id' => 6299,
            'Label' => 'TM65',
        ),
        6300 => array(
            'Id' => 6300,
            'Label' => 'TM75',
        ),
        6301 => array(
            'Id' => 6301,
            'Label' => 'Tokyo',
        ),
        6302 => array(
            'Id' => 6302,
            'Label' => 'Trinidad 1903',
        ),
        6303 => array(
            'Id' => 6303,
            'Label' => 'Trucial Coast 1948',
        ),
        6304 => array(
            'Id' => 6304,
            'Label' => 'Voirol 1875',
        ),
        6305 => array(
            'Id' => 6305,
            'Label' => 'Voirol Unifie 1960',
        ),
        6306 => array(
            'Id' => 6306,
            'Label' => 'Bern 1938',
        ),
        6307 => array(
            'Id' => 6307,
            'Label' => 'Nord Sahara 1959',
        ),
        6308 => array(
            'Id' => 6308,
            'Label' => 'Stockholm 1938',
        ),
        6309 => array(
            'Id' => 6309,
            'Label' => 'Yacare',
        ),
        6310 => array(
            'Id' => 6310,
            'Label' => 'Yoff',
        ),
        6311 => array(
            'Id' => 6311,
            'Label' => 'Zanderij',
        ),
        6312 => array(
            'Id' => 6312,
            'Label' => 'Militar Geographische Institut',
        ),
        6313 => array(
            'Id' => 6313,
            'Label' => 'Reseau National Belge 1972',
        ),
        6314 => array(
            'Id' => 6314,
            'Label' => 'Deutsche Hauptdreiecksnetz',
        ),
        6315 => array(
            'Id' => 6315,
            'Label' => 'Conakry 1905',
        ),
        6317 => array(
            'Id' => 6317,
            'Label' => 'Dealul Piscului 1970',
        ),
        6322 => array(
            'Id' => 6322,
            'Label' => 'WGS72',
        ),
        6324 => array(
            'Id' => 6324,
            'Label' => 'WGS72 Transit Broadcast Ephemeris',
        ),
        6326 => array(
            'Id' => 6326,
            'Label' => 'WGS84',
        ),
        6901 => array(
            'Id' => 6901,
            'Label' => 'Ancienne Triangulation Francaise',
        ),
        6902 => array(
            'Id' => 6902,
            'Label' => 'Nord de Guerre',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
