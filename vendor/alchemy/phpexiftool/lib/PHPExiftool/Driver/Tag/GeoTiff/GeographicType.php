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
class GeographicType extends AbstractTag
{

    protected $Id = 2048;

    protected $Name = 'GeographicType';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Geographic Type';

    protected $Values = array(
        4001 => array(
            'Id' => 4001,
            'Label' => 'Airy 1830',
        ),
        4002 => array(
            'Id' => 4002,
            'Label' => 'Airy Modified 1849',
        ),
        4003 => array(
            'Id' => 4003,
            'Label' => 'Australian National Spheroid',
        ),
        4004 => array(
            'Id' => 4004,
            'Label' => 'Bessel 1841',
        ),
        4005 => array(
            'Id' => 4005,
            'Label' => 'Bessel Modified',
        ),
        4006 => array(
            'Id' => 4006,
            'Label' => 'Bessel Namibia',
        ),
        4007 => array(
            'Id' => 4007,
            'Label' => 'Clarke 1858',
        ),
        4008 => array(
            'Id' => 4008,
            'Label' => 'Clarke 1866',
        ),
        4009 => array(
            'Id' => 4009,
            'Label' => 'Clarke 1866 Michigan',
        ),
        4010 => array(
            'Id' => 4010,
            'Label' => 'Clarke 1880 Benoit',
        ),
        4011 => array(
            'Id' => 4011,
            'Label' => 'Clarke 1880 IGN',
        ),
        4012 => array(
            'Id' => 4012,
            'Label' => 'Clarke 1880 RGS',
        ),
        4013 => array(
            'Id' => 4013,
            'Label' => 'Clarke 1880 Arc',
        ),
        4014 => array(
            'Id' => 4014,
            'Label' => 'Clarke 1880 SGA 1922',
        ),
        4015 => array(
            'Id' => 4015,
            'Label' => 'Everest 1830 1937 Adjustment',
        ),
        4016 => array(
            'Id' => 4016,
            'Label' => 'Everest 1830 1967 Definition',
        ),
        4017 => array(
            'Id' => 4017,
            'Label' => 'Everest 1830 1975 Definition',
        ),
        4018 => array(
            'Id' => 4018,
            'Label' => 'Everest 1830 Modified',
        ),
        4019 => array(
            'Id' => 4019,
            'Label' => 'GRS 1980',
        ),
        4020 => array(
            'Id' => 4020,
            'Label' => 'Helmert 1906',
        ),
        4021 => array(
            'Id' => 4021,
            'Label' => 'Indonesian National Spheroid',
        ),
        4022 => array(
            'Id' => 4022,
            'Label' => 'International 1924',
        ),
        4023 => array(
            'Id' => 4023,
            'Label' => 'International 1967',
        ),
        4024 => array(
            'Id' => 4024,
            'Label' => 'Krassowsky 1940',
        ),
        4025 => array(
            'Id' => 4025,
            'Label' => 'NWL9D',
        ),
        4026 => array(
            'Id' => 4026,
            'Label' => 'NWL10D',
        ),
        4027 => array(
            'Id' => 4027,
            'Label' => 'Plessis 1817',
        ),
        4028 => array(
            'Id' => 4028,
            'Label' => 'Struve 1860',
        ),
        4029 => array(
            'Id' => 4029,
            'Label' => 'War Office',
        ),
        4030 => array(
            'Id' => 4030,
            'Label' => 'WGS84',
        ),
        4031 => array(
            'Id' => 4031,
            'Label' => 'GEM10C',
        ),
        4032 => array(
            'Id' => 4032,
            'Label' => 'OSU86F',
        ),
        4033 => array(
            'Id' => 4033,
            'Label' => 'OSU91A',
        ),
        4034 => array(
            'Id' => 4034,
            'Label' => 'Clarke 1880',
        ),
        4035 => array(
            'Id' => 4035,
            'Label' => 'Sphere',
        ),
        4120 => array(
            'Id' => 4120,
            'Label' => 'Greek',
        ),
        4121 => array(
            'Id' => 4121,
            'Label' => 'GGRS87',
        ),
        4123 => array(
            'Id' => 4123,
            'Label' => 'KKJ',
        ),
        4124 => array(
            'Id' => 4124,
            'Label' => 'RT90',
        ),
        4133 => array(
            'Id' => 4133,
            'Label' => 'EST92',
        ),
        4201 => array(
            'Id' => 4201,
            'Label' => 'Adindan',
        ),
        4202 => array(
            'Id' => 4202,
            'Label' => 'AGD66',
        ),
        4203 => array(
            'Id' => 4203,
            'Label' => 'AGD84',
        ),
        4204 => array(
            'Id' => 4204,
            'Label' => 'Ain el Abd',
        ),
        4205 => array(
            'Id' => 4205,
            'Label' => 'Afgooye',
        ),
        4206 => array(
            'Id' => 4206,
            'Label' => 'Agadez',
        ),
        4207 => array(
            'Id' => 4207,
            'Label' => 'Lisbon',
        ),
        4208 => array(
            'Id' => 4208,
            'Label' => 'Aratu',
        ),
        4209 => array(
            'Id' => 4209,
            'Label' => 'Arc 1950',
        ),
        4210 => array(
            'Id' => 4210,
            'Label' => 'Arc 1960',
        ),
        4211 => array(
            'Id' => 4211,
            'Label' => 'Batavia',
        ),
        4212 => array(
            'Id' => 4212,
            'Label' => 'Barbados',
        ),
        4213 => array(
            'Id' => 4213,
            'Label' => 'Beduaram',
        ),
        4214 => array(
            'Id' => 4214,
            'Label' => 'Beijing 1954',
        ),
        4215 => array(
            'Id' => 4215,
            'Label' => 'Belge 1950',
        ),
        4216 => array(
            'Id' => 4216,
            'Label' => 'Bermuda 1957',
        ),
        4217 => array(
            'Id' => 4217,
            'Label' => 'Bern 1898',
        ),
        4218 => array(
            'Id' => 4218,
            'Label' => 'Bogota',
        ),
        4219 => array(
            'Id' => 4219,
            'Label' => 'Bukit Rimpah',
        ),
        4220 => array(
            'Id' => 4220,
            'Label' => 'Camacupa',
        ),
        4221 => array(
            'Id' => 4221,
            'Label' => 'Campo Inchauspe',
        ),
        4222 => array(
            'Id' => 4222,
            'Label' => 'Cape',
        ),
        4223 => array(
            'Id' => 4223,
            'Label' => 'Carthage',
        ),
        4224 => array(
            'Id' => 4224,
            'Label' => 'Chua',
        ),
        4225 => array(
            'Id' => 4225,
            'Label' => 'Corrego Alegre',
        ),
        4226 => array(
            'Id' => 4226,
            'Label' => 'Cote d Ivoire',
        ),
        4227 => array(
            'Id' => 4227,
            'Label' => 'Deir ez Zor',
        ),
        4228 => array(
            'Id' => 4228,
            'Label' => 'Douala',
        ),
        4229 => array(
            'Id' => 4229,
            'Label' => 'Egypt 1907',
        ),
        4230 => array(
            'Id' => 4230,
            'Label' => 'ED50',
        ),
        4231 => array(
            'Id' => 4231,
            'Label' => 'ED87',
        ),
        4232 => array(
            'Id' => 4232,
            'Label' => 'Fahud',
        ),
        4233 => array(
            'Id' => 4233,
            'Label' => 'Gandajika 1970',
        ),
        4234 => array(
            'Id' => 4234,
            'Label' => 'Garoua',
        ),
        4235 => array(
            'Id' => 4235,
            'Label' => 'Guyane Francaise',
        ),
        4236 => array(
            'Id' => 4236,
            'Label' => 'Hu Tzu Shan',
        ),
        4237 => array(
            'Id' => 4237,
            'Label' => 'HD72',
        ),
        4238 => array(
            'Id' => 4238,
            'Label' => 'ID74',
        ),
        4239 => array(
            'Id' => 4239,
            'Label' => 'Indian 1954',
        ),
        4240 => array(
            'Id' => 4240,
            'Label' => 'Indian 1975',
        ),
        4241 => array(
            'Id' => 4241,
            'Label' => 'Jamaica 1875',
        ),
        4242 => array(
            'Id' => 4242,
            'Label' => 'JAD69',
        ),
        4243 => array(
            'Id' => 4243,
            'Label' => 'Kalianpur',
        ),
        4244 => array(
            'Id' => 4244,
            'Label' => 'Kandawala',
        ),
        4245 => array(
            'Id' => 4245,
            'Label' => 'Kertau',
        ),
        4246 => array(
            'Id' => 4246,
            'Label' => 'KOC',
        ),
        4247 => array(
            'Id' => 4247,
            'Label' => 'La Canoa',
        ),
        4248 => array(
            'Id' => 4248,
            'Label' => 'PSAD56',
        ),
        4249 => array(
            'Id' => 4249,
            'Label' => 'Lake',
        ),
        4250 => array(
            'Id' => 4250,
            'Label' => 'Leigon',
        ),
        4251 => array(
            'Id' => 4251,
            'Label' => 'Liberia 1964',
        ),
        4252 => array(
            'Id' => 4252,
            'Label' => 'Lome',
        ),
        4253 => array(
            'Id' => 4253,
            'Label' => 'Luzon 1911',
        ),
        4254 => array(
            'Id' => 4254,
            'Label' => 'Hito XVIII 1963',
        ),
        4255 => array(
            'Id' => 4255,
            'Label' => 'Herat North',
        ),
        4256 => array(
            'Id' => 4256,
            'Label' => 'Mahe 1971',
        ),
        4257 => array(
            'Id' => 4257,
            'Label' => 'Makassar',
        ),
        4258 => array(
            'Id' => 4258,
            'Label' => 'EUREF89',
        ),
        4259 => array(
            'Id' => 4259,
            'Label' => 'Malongo 1987',
        ),
        4260 => array(
            'Id' => 4260,
            'Label' => 'Manoca',
        ),
        4261 => array(
            'Id' => 4261,
            'Label' => 'Merchich',
        ),
        4262 => array(
            'Id' => 4262,
            'Label' => 'Massawa',
        ),
        4263 => array(
            'Id' => 4263,
            'Label' => 'Minna',
        ),
        4264 => array(
            'Id' => 4264,
            'Label' => 'Mhast',
        ),
        4265 => array(
            'Id' => 4265,
            'Label' => 'Monte Mario',
        ),
        4266 => array(
            'Id' => 4266,
            'Label' => 'M poraloko',
        ),
        4267 => array(
            'Id' => 4267,
            'Label' => 'NAD27',
        ),
        4268 => array(
            'Id' => 4268,
            'Label' => 'NAD Michigan',
        ),
        4269 => array(
            'Id' => 4269,
            'Label' => 'NAD83',
        ),
        4270 => array(
            'Id' => 4270,
            'Label' => 'Nahrwan 1967',
        ),
        4271 => array(
            'Id' => 4271,
            'Label' => 'Naparima 1972',
        ),
        4272 => array(
            'Id' => 4272,
            'Label' => 'GD49',
        ),
        4273 => array(
            'Id' => 4273,
            'Label' => 'NGO 1948',
        ),
        4274 => array(
            'Id' => 4274,
            'Label' => 73,
        ),
        4275 => array(
            'Id' => 4275,
            'Label' => 'NTF',
        ),
        4276 => array(
            'Id' => 4276,
            'Label' => 'NSWC 9Z 2',
        ),
        4277 => array(
            'Id' => 4277,
            'Label' => 'OSGB 1936',
        ),
        4278 => array(
            'Id' => 4278,
            'Label' => 'OSGB70',
        ),
        4279 => array(
            'Id' => 4279,
            'Label' => 'OS SN80',
        ),
        4280 => array(
            'Id' => 4280,
            'Label' => 'Padang',
        ),
        4281 => array(
            'Id' => 4281,
            'Label' => 'Palestine 1923',
        ),
        4282 => array(
            'Id' => 4282,
            'Label' => 'Pointe Noire',
        ),
        4283 => array(
            'Id' => 4283,
            'Label' => 'GDA94',
        ),
        4284 => array(
            'Id' => 4284,
            'Label' => 'Pulkovo 1942',
        ),
        4285 => array(
            'Id' => 4285,
            'Label' => 'Qatar',
        ),
        4286 => array(
            'Id' => 4286,
            'Label' => 'Qatar 1948',
        ),
        4287 => array(
            'Id' => 4287,
            'Label' => 'Qornoq',
        ),
        4288 => array(
            'Id' => 4288,
            'Label' => 'Loma Quintana',
        ),
        4289 => array(
            'Id' => 4289,
            'Label' => 'Amersfoort',
        ),
        4290 => array(
            'Id' => 4290,
            'Label' => 'RT38',
        ),
        4291 => array(
            'Id' => 4291,
            'Label' => 'SAD69',
        ),
        4292 => array(
            'Id' => 4292,
            'Label' => 'Sapper Hill 1943',
        ),
        4293 => array(
            'Id' => 4293,
            'Label' => 'Schwarzeck',
        ),
        4294 => array(
            'Id' => 4294,
            'Label' => 'Segora',
        ),
        4295 => array(
            'Id' => 4295,
            'Label' => 'Serindung',
        ),
        4296 => array(
            'Id' => 4296,
            'Label' => 'Sudan',
        ),
        4297 => array(
            'Id' => 4297,
            'Label' => 'Tananarive',
        ),
        4298 => array(
            'Id' => 4298,
            'Label' => 'Timbalai 1948',
        ),
        4299 => array(
            'Id' => 4299,
            'Label' => 'TM65',
        ),
        4300 => array(
            'Id' => 4300,
            'Label' => 'TM75',
        ),
        4301 => array(
            'Id' => 4301,
            'Label' => 'Tokyo',
        ),
        4302 => array(
            'Id' => 4302,
            'Label' => 'Trinidad 1903',
        ),
        4303 => array(
            'Id' => 4303,
            'Label' => 'TC 1948',
        ),
        4304 => array(
            'Id' => 4304,
            'Label' => 'Voirol 1875',
        ),
        4305 => array(
            'Id' => 4305,
            'Label' => 'Voirol Unifie',
        ),
        4306 => array(
            'Id' => 4306,
            'Label' => 'Bern 1938',
        ),
        4307 => array(
            'Id' => 4307,
            'Label' => 'Nord Sahara 1959',
        ),
        4308 => array(
            'Id' => 4308,
            'Label' => 'Stockholm 1938',
        ),
        4309 => array(
            'Id' => 4309,
            'Label' => 'Yacare',
        ),
        4310 => array(
            'Id' => 4310,
            'Label' => 'Yoff',
        ),
        4311 => array(
            'Id' => 4311,
            'Label' => 'Zanderij',
        ),
        4312 => array(
            'Id' => 4312,
            'Label' => 'MGI',
        ),
        4313 => array(
            'Id' => 4313,
            'Label' => 'Belge 1972',
        ),
        4314 => array(
            'Id' => 4314,
            'Label' => 'DHDN',
        ),
        4315 => array(
            'Id' => 4315,
            'Label' => 'Conakry 1905',
        ),
        4317 => array(
            'Id' => 4317,
            'Label' => 'Dealul Piscului 1970',
        ),
        4322 => array(
            'Id' => 4322,
            'Label' => 'WGS 72',
        ),
        4324 => array(
            'Id' => 4324,
            'Label' => 'WGS 72BE',
        ),
        4326 => array(
            'Id' => 4326,
            'Label' => 'WGS 84',
        ),
        4801 => array(
            'Id' => 4801,
            'Label' => 'Bern 1898 Bern',
        ),
        4802 => array(
            'Id' => 4802,
            'Label' => 'Bogota Bogota',
        ),
        4803 => array(
            'Id' => 4803,
            'Label' => 'Lisbon Lisbon',
        ),
        4804 => array(
            'Id' => 4804,
            'Label' => 'Makassar Jakarta',
        ),
        4805 => array(
            'Id' => 4805,
            'Label' => 'MGI Ferro',
        ),
        4806 => array(
            'Id' => 4806,
            'Label' => 'Monte Mario Rome',
        ),
        4807 => array(
            'Id' => 4807,
            'Label' => 'NTF Paris',
        ),
        4808 => array(
            'Id' => 4808,
            'Label' => 'Padang Jakarta',
        ),
        4809 => array(
            'Id' => 4809,
            'Label' => 'Belge 1950 Brussels',
        ),
        4810 => array(
            'Id' => 4810,
            'Label' => 'Tananarive Paris',
        ),
        4811 => array(
            'Id' => 4811,
            'Label' => 'Voirol 1875 Paris',
        ),
        4812 => array(
            'Id' => 4812,
            'Label' => 'Voirol Unifie Paris',
        ),
        4813 => array(
            'Id' => 4813,
            'Label' => 'Batavia Jakarta',
        ),
        4815 => array(
            'Id' => 4815,
            'Label' => 'Greek Athens',
        ),
        4901 => array(
            'Id' => 4901,
            'Label' => 'ATF Paris',
        ),
        4902 => array(
            'Id' => 4902,
            'Label' => 'NDG Paris',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
