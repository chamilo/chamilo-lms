<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Model extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Model';

    protected $FullName = 'mixed';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'mixed';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'mixed';

    protected $flag_Permanent = true;

    protected $MaxLength = 'mixed';

    protected $Values = array(
        'D4028' => array(
            'Id' => 'D4028',
            'Label' => 'X-2,C-50Z',
        ),
        'D4029' => array(
            'Id' => 'D4029',
            'Label' => 'E-20,E-20N,E-20P',
        ),
        'D4034' => array(
            'Id' => 'D4034',
            'Label' => 'C720UZ',
        ),
        'D4040' => array(
            'Id' => 'D4040',
            'Label' => 'E-1',
        ),
        'D4041' => array(
            'Id' => 'D4041',
            'Label' => 'E-300',
        ),
        'D4083' => array(
            'Id' => 'D4083',
            'Label' => 'C2Z,D520Z,C220Z',
        ),
        'D4106' => array(
            'Id' => 'D4106',
            'Label' => 'u20D,S400D,u400D',
        ),
        'D4120' => array(
            'Id' => 'D4120',
            'Label' => 'X-1',
        ),
        'D4122' => array(
            'Id' => 'D4122',
            'Label' => 'u10D,S300D,u300D',
        ),
        'D4125' => array(
            'Id' => 'D4125',
            'Label' => 'AZ-1',
        ),
        'D4141' => array(
            'Id' => 'D4141',
            'Label' => 'C150,D390',
        ),
        'D4193' => array(
            'Id' => 'D4193',
            'Label' => 'C-5000Z',
        ),
        'D4194' => array(
            'Id' => 'D4194',
            'Label' => 'X-3,C-60Z',
        ),
        'D4199' => array(
            'Id' => 'D4199',
            'Label' => 'u30D,S410D,u410D',
        ),
        'D4205' => array(
            'Id' => 'D4205',
            'Label' => 'X450,D535Z,C370Z',
        ),
        'D4210' => array(
            'Id' => 'D4210',
            'Label' => 'C160,D395',
        ),
        'D4211' => array(
            'Id' => 'D4211',
            'Label' => 'C725UZ',
        ),
        'D4213' => array(
            'Id' => 'D4213',
            'Label' => 'FerrariMODEL2003',
        ),
        'D4216' => array(
            'Id' => 'D4216',
            'Label' => 'u15D',
        ),
        'D4217' => array(
            'Id' => 'D4217',
            'Label' => 'u25D',
        ),
        'D4220' => array(
            'Id' => 'D4220',
            'Label' => 'u-miniD,Stylus V',
        ),
        'D4221' => array(
            'Id' => 'D4221',
            'Label' => 'u40D,S500,uD500',
        ),
        'D4231' => array(
            'Id' => 'D4231',
            'Label' => 'FerrariMODEL2004',
        ),
        'D4240' => array(
            'Id' => 'D4240',
            'Label' => 'X500,D590Z,C470Z',
        ),
        'D4244' => array(
            'Id' => 'D4244',
            'Label' => 'uD800,S800',
        ),
        'D4256' => array(
            'Id' => 'D4256',
            'Label' => 'u720SW,S720SW',
        ),
        'D4261' => array(
            'Id' => 'D4261',
            'Label' => 'X600,D630,FE5500',
        ),
        'D4262' => array(
            'Id' => 'D4262',
            'Label' => 'uD600,S600',
        ),
        'D4301' => array(
            'Id' => 'D4301',
            'Label' => 'u810/S810',
        ),
        'D4302' => array(
            'Id' => 'D4302',
            'Label' => 'u710,S710',
        ),
        'D4303' => array(
            'Id' => 'D4303',
            'Label' => 'u700,S700',
        ),
        'D4304' => array(
            'Id' => 'D4304',
            'Label' => 'FE100,X710',
        ),
        'D4305' => array(
            'Id' => 'D4305',
            'Label' => 'FE110,X705',
        ),
        'D4310' => array(
            'Id' => 'D4310',
            'Label' => 'FE-130,X-720',
        ),
        'D4311' => array(
            'Id' => 'D4311',
            'Label' => 'FE-140,X-725',
        ),
        'D4312' => array(
            'Id' => 'D4312',
            'Label' => 'FE150,X730',
        ),
        'D4313' => array(
            'Id' => 'D4313',
            'Label' => 'FE160,X735',
        ),
        'D4314' => array(
            'Id' => 'D4314',
            'Label' => 'u740,S740',
        ),
        'D4315' => array(
            'Id' => 'D4315',
            'Label' => 'u750,S750',
        ),
        'D4316' => array(
            'Id' => 'D4316',
            'Label' => 'u730/S730',
        ),
        'D4317' => array(
            'Id' => 'D4317',
            'Label' => 'FE115,X715',
        ),
        'D4321' => array(
            'Id' => 'D4321',
            'Label' => 'SP550UZ',
        ),
        'D4322' => array(
            'Id' => 'D4322',
            'Label' => 'SP510UZ',
        ),
        'D4324' => array(
            'Id' => 'D4324',
            'Label' => 'FE170,X760',
        ),
        'D4326' => array(
            'Id' => 'D4326',
            'Label' => 'FE200',
        ),
        'D4327' => array(
            'Id' => 'D4327',
            'Label' => 'FE190/X750',
        ),
        'D4328' => array(
            'Id' => 'D4328',
            'Label' => 'u760,S760',
        ),
        'D4330' => array(
            'Id' => 'D4330',
            'Label' => 'FE180/X745',
        ),
        'D4331' => array(
            'Id' => 'D4331',
            'Label' => 'u1000/S1000',
        ),
        'D4332' => array(
            'Id' => 'D4332',
            'Label' => 'u770SW,S770SW',
        ),
        'D4333' => array(
            'Id' => 'D4333',
            'Label' => 'FE240/X795',
        ),
        'D4334' => array(
            'Id' => 'D4334',
            'Label' => 'FE210,X775',
        ),
        'D4336' => array(
            'Id' => 'D4336',
            'Label' => 'FE230/X790',
        ),
        'D4337' => array(
            'Id' => 'D4337',
            'Label' => 'FE220,X785',
        ),
        'D4338' => array(
            'Id' => 'D4338',
            'Label' => 'u725SW,S725SW',
        ),
        'D4339' => array(
            'Id' => 'D4339',
            'Label' => 'FE250/X800',
        ),
        'D4341' => array(
            'Id' => 'D4341',
            'Label' => 'u780,S780',
        ),
        'D4343' => array(
            'Id' => 'D4343',
            'Label' => 'u790SW,S790SW',
        ),
        'D4344' => array(
            'Id' => 'D4344',
            'Label' => 'u1020,S1020',
        ),
        'D4346' => array(
            'Id' => 'D4346',
            'Label' => 'FE15,X10',
        ),
        'D4348' => array(
            'Id' => 'D4348',
            'Label' => 'FE280,X820,C520',
        ),
        'D4349' => array(
            'Id' => 'D4349',
            'Label' => 'FE300,X830',
        ),
        'D4350' => array(
            'Id' => 'D4350',
            'Label' => 'u820,S820',
        ),
        'D4351' => array(
            'Id' => 'D4351',
            'Label' => 'u1200,S1200',
        ),
        'D4352' => array(
            'Id' => 'D4352',
            'Label' => 'FE270,X815,C510',
        ),
        'D4353' => array(
            'Id' => 'D4353',
            'Label' => 'u795SW,S795SW',
        ),
        'D4354' => array(
            'Id' => 'D4354',
            'Label' => 'u1030SW,S1030SW',
        ),
        'D4355' => array(
            'Id' => 'D4355',
            'Label' => 'SP560UZ',
        ),
        'D4356' => array(
            'Id' => 'D4356',
            'Label' => 'u1010,S1010',
        ),
        'D4357' => array(
            'Id' => 'D4357',
            'Label' => 'u830,S830',
        ),
        'D4359' => array(
            'Id' => 'D4359',
            'Label' => 'u840,S840',
        ),
        'D4360' => array(
            'Id' => 'D4360',
            'Label' => 'FE350WIDE,X865',
        ),
        'D4361' => array(
            'Id' => 'D4361',
            'Label' => 'u850SW,S850SW',
        ),
        'D4362' => array(
            'Id' => 'D4362',
            'Label' => 'FE340,X855,C560',
        ),
        'D4363' => array(
            'Id' => 'D4363',
            'Label' => 'FE320,X835,C540',
        ),
        'D4364' => array(
            'Id' => 'D4364',
            'Label' => 'SP570UZ',
        ),
        'D4366' => array(
            'Id' => 'D4366',
            'Label' => 'FE330,X845,C550',
        ),
        'D4368' => array(
            'Id' => 'D4368',
            'Label' => 'FE310,X840,C530',
        ),
        'D4370' => array(
            'Id' => 'D4370',
            'Label' => 'u1050SW,S1050SW',
        ),
        'D4371' => array(
            'Id' => 'D4371',
            'Label' => 'u1060,S1060',
        ),
        'D4372' => array(
            'Id' => 'D4372',
            'Label' => 'FE370,X880,C575',
        ),
        'D4374' => array(
            'Id' => 'D4374',
            'Label' => 'SP565UZ',
        ),
        'D4377' => array(
            'Id' => 'D4377',
            'Label' => 'u1040,S1040',
        ),
        'D4378' => array(
            'Id' => 'D4378',
            'Label' => 'FE360,X875,C570',
        ),
        'D4379' => array(
            'Id' => 'D4379',
            'Label' => 'FE20,X15,C25',
        ),
        'D4380' => array(
            'Id' => 'D4380',
            'Label' => 'uT6000,ST6000',
        ),
        'D4381' => array(
            'Id' => 'D4381',
            'Label' => 'uT8000,ST8000',
        ),
        'D4382' => array(
            'Id' => 'D4382',
            'Label' => 'u9000,S9000',
        ),
        'D4384' => array(
            'Id' => 'D4384',
            'Label' => 'SP590UZ',
        ),
        'D4385' => array(
            'Id' => 'D4385',
            'Label' => 'FE3010,X895',
        ),
        'D4386' => array(
            'Id' => 'D4386',
            'Label' => 'FE3000,X890',
        ),
        'D4387' => array(
            'Id' => 'D4387',
            'Label' => 'FE35,X30',
        ),
        'D4388' => array(
            'Id' => 'D4388',
            'Label' => 'u550WP,S550WP',
        ),
        'D4390' => array(
            'Id' => 'D4390',
            'Label' => 'FE5000,X905',
        ),
        'D4391' => array(
            'Id' => 'D4391',
            'Label' => 'u5000',
        ),
        'D4392' => array(
            'Id' => 'D4392',
            'Label' => 'u7000,S7000',
        ),
        'D4396' => array(
            'Id' => 'D4396',
            'Label' => 'FE5010,X915',
        ),
        'D4397' => array(
            'Id' => 'D4397',
            'Label' => 'FE25,X20',
        ),
        'D4398' => array(
            'Id' => 'D4398',
            'Label' => 'FE45,X40',
        ),
        'D4401' => array(
            'Id' => 'D4401',
            'Label' => 'XZ-1',
        ),
        'D4402' => array(
            'Id' => 'D4402',
            'Label' => 'uT6010,ST6010',
        ),
        'D4406' => array(
            'Id' => 'D4406',
            'Label' => 'u7010,S7010 / u7020,S7020',
        ),
        'D4407' => array(
            'Id' => 'D4407',
            'Label' => 'FE4010,X930',
        ),
        'D4408' => array(
            'Id' => 'D4408',
            'Label' => 'X560WP',
        ),
        'D4409' => array(
            'Id' => 'D4409',
            'Label' => 'FE26,X21',
        ),
        'D4410' => array(
            'Id' => 'D4410',
            'Label' => 'FE4000,X920,X925',
        ),
        'D4411' => array(
            'Id' => 'D4411',
            'Label' => 'FE46,X41,X42',
        ),
        'D4412' => array(
            'Id' => 'D4412',
            'Label' => 'FE5020,X935',
        ),
        'D4413' => array(
            'Id' => 'D4413',
            'Label' => 'uTough-3000',
        ),
        'D4414' => array(
            'Id' => 'D4414',
            'Label' => 'StylusTough-6020',
        ),
        'D4415' => array(
            'Id' => 'D4415',
            'Label' => 'StylusTough-8010',
        ),
        'D4417' => array(
            'Id' => 'D4417',
            'Label' => 'u5010,S5010',
        ),
        'D4418' => array(
            'Id' => 'D4418',
            'Label' => 'u7040,S7040',
        ),
        'D4419' => array(
            'Id' => 'D4419',
            'Label' => 'u9010,S9010',
        ),
        'D4423' => array(
            'Id' => 'D4423',
            'Label' => 'FE4040',
        ),
        'D4424' => array(
            'Id' => 'D4424',
            'Label' => 'FE47,X43',
        ),
        'D4426' => array(
            'Id' => 'D4426',
            'Label' => 'FE4030,X950',
        ),
        'D4428' => array(
            'Id' => 'D4428',
            'Label' => 'FE5030,X965,X960',
        ),
        'D4430' => array(
            'Id' => 'D4430',
            'Label' => 'u7030,S7030',
        ),
        'D4432' => array(
            'Id' => 'D4432',
            'Label' => 'SP600UZ',
        ),
        'D4434' => array(
            'Id' => 'D4434',
            'Label' => 'SP800UZ',
        ),
        'D4439' => array(
            'Id' => 'D4439',
            'Label' => 'FE4020,X940',
        ),
        'D4442' => array(
            'Id' => 'D4442',
            'Label' => 'FE5035',
        ),
        'D4448' => array(
            'Id' => 'D4448',
            'Label' => 'FE4050,X970',
        ),
        'D4450' => array(
            'Id' => 'D4450',
            'Label' => 'FE5050,X985',
        ),
        'D4454' => array(
            'Id' => 'D4454',
            'Label' => 'u-7050',
        ),
        'D4464' => array(
            'Id' => 'D4464',
            'Label' => 'T10,X27',
        ),
        'D4470' => array(
            'Id' => 'D4470',
            'Label' => 'FE5040,X980',
        ),
        'D4472' => array(
            'Id' => 'D4472',
            'Label' => 'TG-310',
        ),
        'D4474' => array(
            'Id' => 'D4474',
            'Label' => 'TG-610',
        ),
        'D4476' => array(
            'Id' => 'D4476',
            'Label' => 'TG-810',
        ),
        'D4478' => array(
            'Id' => 'D4478',
            'Label' => 'VG145,VG140,D715',
        ),
        'D4479' => array(
            'Id' => 'D4479',
            'Label' => 'VG130,D710',
        ),
        'D4480' => array(
            'Id' => 'D4480',
            'Label' => 'VG120,D705',
        ),
        'D4482' => array(
            'Id' => 'D4482',
            'Label' => 'VR310,D720',
        ),
        'D4484' => array(
            'Id' => 'D4484',
            'Label' => 'VR320,D725',
        ),
        'D4486' => array(
            'Id' => 'D4486',
            'Label' => 'VR330,D730',
        ),
        'D4488' => array(
            'Id' => 'D4488',
            'Label' => 'VG110,D700',
        ),
        'D4490' => array(
            'Id' => 'D4490',
            'Label' => 'SP-610UZ',
        ),
        'D4492' => array(
            'Id' => 'D4492',
            'Label' => 'SZ-10',
        ),
        'D4494' => array(
            'Id' => 'D4494',
            'Label' => 'SZ-20',
        ),
        'D4496' => array(
            'Id' => 'D4496',
            'Label' => 'SZ-30MR',
        ),
        'D4498' => array(
            'Id' => 'D4498',
            'Label' => 'SP-810UZ',
        ),
        'D4500' => array(
            'Id' => 'D4500',
            'Label' => 'SZ-11',
        ),
        'D4504' => array(
            'Id' => 'D4504',
            'Label' => 'TG-615',
        ),
        'D4508' => array(
            'Id' => 'D4508',
            'Label' => 'TG-620',
        ),
        'D4510' => array(
            'Id' => 'D4510',
            'Label' => 'TG-820',
        ),
        'D4512' => array(
            'Id' => 'D4512',
            'Label' => 'TG-1',
        ),
        'D4516' => array(
            'Id' => 'D4516',
            'Label' => 'SH-21',
        ),
        'D4519' => array(
            'Id' => 'D4519',
            'Label' => 'SZ-14',
        ),
        'D4520' => array(
            'Id' => 'D4520',
            'Label' => 'SZ-31MR',
        ),
        'D4521' => array(
            'Id' => 'D4521',
            'Label' => 'SH-25MR',
        ),
        'D4523' => array(
            'Id' => 'D4523',
            'Label' => 'SP-720UZ',
        ),
        'D4529' => array(
            'Id' => 'D4529',
            'Label' => 'VG170',
        ),
        'D4531' => array(
            'Id' => 'D4531',
            'Label' => 'XZ-2',
        ),
        'D4535' => array(
            'Id' => 'D4535',
            'Label' => 'SP-620UZ',
        ),
        'D4536' => array(
            'Id' => 'D4536',
            'Label' => 'TG-320',
        ),
        'D4537' => array(
            'Id' => 'D4537',
            'Label' => 'VR340,D750',
        ),
        'D4538' => array(
            'Id' => 'D4538',
            'Label' => 'VG160,X990,D745',
        ),
        'D4541' => array(
            'Id' => 'D4541',
            'Label' => 'SZ-12',
        ),
        'D4545' => array(
            'Id' => 'D4545',
            'Label' => 'VH410',
        ),
        'D4546' => array(
            'Id' => 'D4546',
            'Label' => 'XZ-10',
        ),
        'D4547' => array(
            'Id' => 'D4547',
            'Label' => 'TG-2',
        ),
        'D4548' => array(
            'Id' => 'D4548',
            'Label' => 'TG-830',
        ),
        'D4549' => array(
            'Id' => 'D4549',
            'Label' => 'TG-630',
        ),
        'D4550' => array(
            'Id' => 'D4550',
            'Label' => 'SH-50',
        ),
        'D4553' => array(
            'Id' => 'D4553',
            'Label' => 'SZ-16,DZ-105',
        ),
        'D4562' => array(
            'Id' => 'D4562',
            'Label' => 'SP-820UZ',
        ),
        'D4566' => array(
            'Id' => 'D4566',
            'Label' => 'SZ-15',
        ),
        'D4572' => array(
            'Id' => 'D4572',
            'Label' => 'STYLUS1',
        ),
        'D4574' => array(
            'Id' => 'D4574',
            'Label' => 'TG-3',
        ),
        'D4575' => array(
            'Id' => 'D4575',
            'Label' => 'TG-850',
        ),
        'D4579' => array(
            'Id' => 'D4579',
            'Label' => 'SP-100EE',
        ),
        'D4580' => array(
            'Id' => 'D4580',
            'Label' => 'SH-60',
        ),
        'D4581' => array(
            'Id' => 'D4581',
            'Label' => 'SH-1',
        ),
        'D4582' => array(
            'Id' => 'D4582',
            'Label' => 'TG-835',
        ),
        'D4585' => array(
            'Id' => 'D4585',
            'Label' => 'SH-2',
        ),
        'D4586' => array(
            'Id' => 'D4586',
            'Label' => 'TG-4',
        ),
        'D4587' => array(
            'Id' => 'D4587',
            'Label' => 'TG-860',
        ),
        'D4809' => array(
            'Id' => 'D4809',
            'Label' => 'C2500L',
        ),
        'D4842' => array(
            'Id' => 'D4842',
            'Label' => 'E-10',
        ),
        'D4856' => array(
            'Id' => 'D4856',
            'Label' => 'C-1',
        ),
        'D4857' => array(
            'Id' => 'D4857',
            'Label' => 'C-1Z,D-150Z',
        ),
        'DCHC' => array(
            'Id' => 'DCHC',
            'Label' => 'D500L',
        ),
        'DCHT' => array(
            'Id' => 'DCHT',
            'Label' => 'D600L / D620L',
        ),
        'K0055' => array(
            'Id' => 'K0055',
            'Label' => 'AIR-A01',
        ),
        'S0003' => array(
            'Id' => 'S0003',
            'Label' => 'E-330',
        ),
        'S0004' => array(
            'Id' => 'S0004',
            'Label' => 'E-500',
        ),
        'S0009' => array(
            'Id' => 'S0009',
            'Label' => 'E-400',
        ),
        'S0010' => array(
            'Id' => 'S0010',
            'Label' => 'E-510',
        ),
        'S0011' => array(
            'Id' => 'S0011',
            'Label' => 'E-3',
        ),
        'S0013' => array(
            'Id' => 'S0013',
            'Label' => 'E-410',
        ),
        'S0016' => array(
            'Id' => 'S0016',
            'Label' => 'E-420',
        ),
        'S0017' => array(
            'Id' => 'S0017',
            'Label' => 'E-30',
        ),
        'S0018' => array(
            'Id' => 'S0018',
            'Label' => 'E-520',
        ),
        'S0019' => array(
            'Id' => 'S0019',
            'Label' => 'E-P1',
        ),
        'S0023' => array(
            'Id' => 'S0023',
            'Label' => 'E-620',
        ),
        'S0026' => array(
            'Id' => 'S0026',
            'Label' => 'E-P2',
        ),
        'S0027' => array(
            'Id' => 'S0027',
            'Label' => 'E-PL1',
        ),
        'S0029' => array(
            'Id' => 'S0029',
            'Label' => 'E-450',
        ),
        'S0030' => array(
            'Id' => 'S0030',
            'Label' => 'E-600',
        ),
        'S0032' => array(
            'Id' => 'S0032',
            'Label' => 'E-P3',
        ),
        'S0033' => array(
            'Id' => 'S0033',
            'Label' => 'E-5',
        ),
        'S0034' => array(
            'Id' => 'S0034',
            'Label' => 'E-PL2',
        ),
        'S0036' => array(
            'Id' => 'S0036',
            'Label' => 'E-M5',
        ),
        'S0038' => array(
            'Id' => 'S0038',
            'Label' => 'E-PL3',
        ),
        'S0039' => array(
            'Id' => 'S0039',
            'Label' => 'E-PM1',
        ),
        'S0040' => array(
            'Id' => 'S0040',
            'Label' => 'E-PL1s',
        ),
        'S0042' => array(
            'Id' => 'S0042',
            'Label' => 'E-PL5',
        ),
        'S0043' => array(
            'Id' => 'S0043',
            'Label' => 'E-PM2',
        ),
        'S0044' => array(
            'Id' => 'S0044',
            'Label' => 'E-P5',
        ),
        'S0045' => array(
            'Id' => 'S0045',
            'Label' => 'E-PL6',
        ),
        'S0046' => array(
            'Id' => 'S0046',
            'Label' => 'E-PL7',
        ),
        'S0047' => array(
            'Id' => 'S0047',
            'Label' => 'E-M1',
        ),
        'S0051' => array(
            'Id' => 'S0051',
            'Label' => 'E-M10',
        ),
        'S0052' => array(
            'Id' => 'S0052',
            'Label' => 'E-M5MarkII',
        ),
        'S0059' => array(
            'Id' => 'S0059',
            'Label' => 'E-M10MarkII',
        ),
        'SR45' => array(
            'Id' => 'SR45',
            'Label' => 'D220',
        ),
        'SR55' => array(
            'Id' => 'SR55',
            'Label' => 'D320L',
        ),
        'SR83' => array(
            'Id' => 'SR83',
            'Label' => 'D340L',
        ),
        'SR85' => array(
            'Id' => 'SR85',
            'Label' => 'C830L,D340R',
        ),
        'SR852' => array(
            'Id' => 'SR852',
            'Label' => 'C860L,D360L',
        ),
        'SR872' => array(
            'Id' => 'SR872',
            'Label' => 'C900Z,D400Z',
        ),
        'SR874' => array(
            'Id' => 'SR874',
            'Label' => 'C960Z,D460Z',
        ),
        'SR951' => array(
            'Id' => 'SR951',
            'Label' => 'C2000Z',
        ),
        'SR952' => array(
            'Id' => 'SR952',
            'Label' => 'C21',
        ),
        'SR953' => array(
            'Id' => 'SR953',
            'Label' => 'C21T.commu',
        ),
        'SR954' => array(
            'Id' => 'SR954',
            'Label' => 'C2020Z',
        ),
        'SR955' => array(
            'Id' => 'SR955',
            'Label' => 'C990Z,D490Z',
        ),
        'SR956' => array(
            'Id' => 'SR956',
            'Label' => 'C211Z',
        ),
        'SR959' => array(
            'Id' => 'SR959',
            'Label' => 'C990ZS,D490Z',
        ),
        'SR95A' => array(
            'Id' => 'SR95A',
            'Label' => 'C2100UZ',
        ),
        'SR971' => array(
            'Id' => 'SR971',
            'Label' => 'C100,D370',
        ),
        'SR973' => array(
            'Id' => 'SR973',
            'Label' => 'C2,D230',
        ),
        'SX151' => array(
            'Id' => 'SX151',
            'Label' => 'E100RS',
        ),
        'SX351' => array(
            'Id' => 'SX351',
            'Label' => 'C3000Z / C3030Z',
        ),
        'SX354' => array(
            'Id' => 'SX354',
            'Label' => 'C3040Z',
        ),
        'SX355' => array(
            'Id' => 'SX355',
            'Label' => 'C2040Z',
        ),
        'SX357' => array(
            'Id' => 'SX357',
            'Label' => 'C700UZ',
        ),
        'SX358' => array(
            'Id' => 'SX358',
            'Label' => 'C200Z,D510Z',
        ),
        'SX374' => array(
            'Id' => 'SX374',
            'Label' => 'C3100Z,C3020Z',
        ),
        'SX552' => array(
            'Id' => 'SX552',
            'Label' => 'C4040Z',
        ),
        'SX553' => array(
            'Id' => 'SX553',
            'Label' => 'C40Z,D40Z',
        ),
        'SX556' => array(
            'Id' => 'SX556',
            'Label' => 'C730UZ',
        ),
        'SX558' => array(
            'Id' => 'SX558',
            'Label' => 'C5050Z',
        ),
        'SX571' => array(
            'Id' => 'SX571',
            'Label' => 'C120,D380',
        ),
        'SX574' => array(
            'Id' => 'SX574',
            'Label' => 'C300Z,D550Z',
        ),
        'SX575' => array(
            'Id' => 'SX575',
            'Label' => 'C4100Z,C4000Z',
        ),
        'SX751' => array(
            'Id' => 'SX751',
            'Label' => 'X200,D560Z,C350Z',
        ),
        'SX752' => array(
            'Id' => 'SX752',
            'Label' => 'X300,D565Z,C450Z',
        ),
        'SX753' => array(
            'Id' => 'SX753',
            'Label' => 'C750UZ',
        ),
        'SX754' => array(
            'Id' => 'SX754',
            'Label' => 'C740UZ',
        ),
        'SX755' => array(
            'Id' => 'SX755',
            'Label' => 'C755UZ',
        ),
        'SX756' => array(
            'Id' => 'SX756',
            'Label' => 'C5060WZ',
        ),
        'SX757' => array(
            'Id' => 'SX757',
            'Label' => 'C8080WZ',
        ),
        'SX758' => array(
            'Id' => 'SX758',
            'Label' => 'X350,D575Z,C360Z',
        ),
        'SX759' => array(
            'Id' => 'SX759',
            'Label' => 'X400,D580Z,C460Z',
        ),
        'SX75A' => array(
            'Id' => 'SX75A',
            'Label' => 'AZ-2ZOOM',
        ),
        'SX75B' => array(
            'Id' => 'SX75B',
            'Label' => 'D595Z,C500Z',
        ),
        'SX75C' => array(
            'Id' => 'SX75C',
            'Label' => 'X550,D545Z,C480Z',
        ),
        'SX75D' => array(
            'Id' => 'SX75D',
            'Label' => 'IR-300',
        ),
        'SX75F' => array(
            'Id' => 'SX75F',
            'Label' => 'C55Z,C5500Z',
        ),
        'SX75G' => array(
            'Id' => 'SX75G',
            'Label' => 'C170,D425',
        ),
        'SX75J' => array(
            'Id' => 'SX75J',
            'Label' => 'C180,D435',
        ),
        'SX771' => array(
            'Id' => 'SX771',
            'Label' => 'C760UZ',
        ),
        'SX772' => array(
            'Id' => 'SX772',
            'Label' => 'C770UZ',
        ),
        'SX773' => array(
            'Id' => 'SX773',
            'Label' => 'C745UZ',
        ),
        'SX774' => array(
            'Id' => 'SX774',
            'Label' => 'X250,D560Z,C350Z',
        ),
        'SX775' => array(
            'Id' => 'SX775',
            'Label' => 'X100,D540Z,C310Z',
        ),
        'SX776' => array(
            'Id' => 'SX776',
            'Label' => 'C460ZdelSol',
        ),
        'SX777' => array(
            'Id' => 'SX777',
            'Label' => 'C765UZ',
        ),
        'SX77A' => array(
            'Id' => 'SX77A',
            'Label' => 'D555Z,C315Z',
        ),
        'SX851' => array(
            'Id' => 'SX851',
            'Label' => 'C7070WZ',
        ),
        'SX852' => array(
            'Id' => 'SX852',
            'Label' => 'C70Z,C7000Z',
        ),
        'SX853' => array(
            'Id' => 'SX853',
            'Label' => 'SP500UZ',
        ),
        'SX854' => array(
            'Id' => 'SX854',
            'Label' => 'SP310',
        ),
        'SX855' => array(
            'Id' => 'SX855',
            'Label' => 'SP350',
        ),
        'SX873' => array(
            'Id' => 'SX873',
            'Label' => 'SP320',
        ),
        'SX875' => array(
            'Id' => 'SX875',
            'Label' => 'FE180/X745',
        ),
        'SX876' => array(
            'Id' => 'SX876',
            'Label' => 'FE190/X750',
        ),
        'SG472' => array(
            'Id' => 'SG472',
            'Label' => 'u7040,S7040',
        ),
        'SG473' => array(
            'Id' => 'SG473',
            'Label' => 'u9010,S9010',
        ),
        'SG475' => array(
            'Id' => 'SG475',
            'Label' => 'SP800UZ',
        ),
        'SG551' => array(
            'Id' => 'SG551',
            'Label' => 'SZ-30MR',
        ),
        'SG553' => array(
            'Id' => 'SG553',
            'Label' => 'SP-610UZ',
        ),
        'SG554' => array(
            'Id' => 'SG554',
            'Label' => 'SZ-10',
        ),
        'SG555' => array(
            'Id' => 'SG555',
            'Label' => 'SZ-20',
        ),
        'SG573' => array(
            'Id' => 'SG573',
            'Label' => 'SZ-14',
        ),
        'SG575' => array(
            'Id' => 'SG575',
            'Label' => 'SP-620UZ',
        ),
    );

}
