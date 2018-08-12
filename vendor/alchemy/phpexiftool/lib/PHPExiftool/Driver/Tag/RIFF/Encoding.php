<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Encoding extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'Encoding';

    protected $FullName = 'RIFF::AudioFormat';

    protected $GroupName = 'RIFF';

    protected $g0 = 'RIFF';

    protected $g1 = 'RIFF';

    protected $g2 = 'Audio';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Encoding';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Microsoft PCM',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Microsoft ADPCM',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Microsoft IEEE float',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Compaq VSELP',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'IBM CVSD',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Microsoft a-Law',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Microsoft u-Law',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Microsoft DTS',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'DRM',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'WMA 9 Speech',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Microsoft Windows Media RT Voice',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'OKI-ADPCM',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Intel IMA/DVI-ADPCM',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Videologic Mediaspace ADPCM',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Sierra ADPCM',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Antex G.723 ADPCM',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'DSP Solutions DIGISTD',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'DSP Solutions DIGIFIX',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Dialoic OKI ADPCM',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Media Vision ADPCM',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'HP CU',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'HP Dynamic Voice',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Yamaha ADPCM',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'SONARC Speech Compression',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'DSP Group True Speech',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Echo Speech Corp.',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Virtual Music Audiofile AF36',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Audio Processing Tech.',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Virtual Music Audiofile AF10',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Aculab Prosody 1612',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Merging Tech. LRC',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Dolby AC2',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Microsoft GSM610',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'MSN Audio',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Antex ADPCME',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Control Resources VQLPC',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'DSP Solutions DIGIREAL',
        ),
        54 => array(
            'Id' => 54,
            'Label' => 'DSP Solutions DIGIADPCM',
        ),
        55 => array(
            'Id' => 55,
            'Label' => 'Control Resources CR10',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'Natural MicroSystems VBX ADPCM',
        ),
        57 => array(
            'Id' => 57,
            'Label' => 'Crystal Semiconductor IMA ADPCM',
        ),
        58 => array(
            'Id' => 58,
            'Label' => 'Echo Speech ECHOSC3',
        ),
        59 => array(
            'Id' => 59,
            'Label' => 'Rockwell ADPCM',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'Rockwell DIGITALK',
        ),
        61 => array(
            'Id' => 61,
            'Label' => 'Xebec Multimedia',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Antex G.721 ADPCM',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'Antex G.728 CELP',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Microsoft MSG723',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'IBM AVC ADPCM',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'ITU-T G.726',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Microsoft MPEG',
        ),
        81 => array(
            'Id' => 81,
            'Label' => 'RT23 or PAC',
        ),
        82 => array(
            'Id' => 82,
            'Label' => 'InSoft RT24',
        ),
        83 => array(
            'Id' => 83,
            'Label' => 'InSoft PAC',
        ),
        85 => array(
            'Id' => 85,
            'Label' => 'MP3',
        ),
        89 => array(
            'Id' => 89,
            'Label' => 'Cirrus',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Cirrus Logic',
        ),
        97 => array(
            'Id' => 97,
            'Label' => 'ESS Tech. PCM',
        ),
        98 => array(
            'Id' => 98,
            'Label' => 'Voxware Inc.',
        ),
        99 => array(
            'Id' => 99,
            'Label' => 'Canopus ATRAC',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'APICOM G.726 ADPCM',
        ),
        101 => array(
            'Id' => 101,
            'Label' => 'APICOM G.722 ADPCM',
        ),
        102 => array(
            'Id' => 102,
            'Label' => 'Microsoft DSAT',
        ),
        103 => array(
            'Id' => 103,
            'Label' => 'Micorsoft DSAT DISPLAY',
        ),
        105 => array(
            'Id' => 105,
            'Label' => 'Voxware Byte Aligned',
        ),
        112 => array(
            'Id' => 112,
            'Label' => 'Voxware AC8',
        ),
        113 => array(
            'Id' => 113,
            'Label' => 'Voxware AC10',
        ),
        114 => array(
            'Id' => 114,
            'Label' => 'Voxware AC16',
        ),
        115 => array(
            'Id' => 115,
            'Label' => 'Voxware AC20',
        ),
        116 => array(
            'Id' => 116,
            'Label' => 'Voxware MetaVoice',
        ),
        117 => array(
            'Id' => 117,
            'Label' => 'Voxware MetaSound',
        ),
        118 => array(
            'Id' => 118,
            'Label' => 'Voxware RT29HW',
        ),
        119 => array(
            'Id' => 119,
            'Label' => 'Voxware VR12',
        ),
        120 => array(
            'Id' => 120,
            'Label' => 'Voxware VR18',
        ),
        121 => array(
            'Id' => 121,
            'Label' => 'Voxware TQ40',
        ),
        122 => array(
            'Id' => 122,
            'Label' => 'Voxware SC3',
        ),
        123 => array(
            'Id' => 123,
            'Label' => 'Voxware SC3',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Soundsoft',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Voxware TQ60',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'Microsoft MSRT24',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'AT&T G.729A',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'Motion Pixels MVI MV12',
        ),
        133 => array(
            'Id' => 133,
            'Label' => 'DataFusion G.726',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'DataFusion GSM610',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'Iterated Systems Audio',
        ),
        137 => array(
            'Id' => 137,
            'Label' => 'Onlive',
        ),
        138 => array(
            'Id' => 138,
            'Label' => 'Multitude, Inc. FT SX20',
        ),
        139 => array(
            'Id' => 139,
            'Label' => 'Infocom ITS A/S G.721 ADPCM',
        ),
        140 => array(
            'Id' => 140,
            'Label' => 'Convedia G729',
        ),
        141 => array(
            'Id' => 141,
            'Label' => 'Not specified congruency, Inc.',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'Siemens SBC24',
        ),
        146 => array(
            'Id' => 146,
            'Label' => 'Sonic Foundry Dolby AC3 APDIF',
        ),
        147 => array(
            'Id' => 147,
            'Label' => 'MediaSonic G.723',
        ),
        148 => array(
            'Id' => 148,
            'Label' => 'Aculab Prosody 8kbps',
        ),
        151 => array(
            'Id' => 151,
            'Label' => 'ZyXEL ADPCM',
        ),
        152 => array(
            'Id' => 152,
            'Label' => 'Philips LPCBB',
        ),
        153 => array(
            'Id' => 153,
            'Label' => 'Studer Professional Audio Packed',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Malden PhonyTalk',
        ),
        161 => array(
            'Id' => 161,
            'Label' => 'Racal Recorder GSM',
        ),
        162 => array(
            'Id' => 162,
            'Label' => 'Racal Recorder G720.a',
        ),
        163 => array(
            'Id' => 163,
            'Label' => 'Racal G723.1',
        ),
        164 => array(
            'Id' => 164,
            'Label' => 'Racal Tetra ACELP',
        ),
        176 => array(
            'Id' => 176,
            'Label' => 'NEC AAC NEC Corporation',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'AAC',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Rhetorex ADPCM',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'IBM u-Law',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'IBM a-Law',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'IBM ADPCM',
        ),
        273 => array(
            'Id' => 273,
            'Label' => 'Vivo G.723',
        ),
        274 => array(
            'Id' => 274,
            'Label' => 'Vivo Siren',
        ),
        288 => array(
            'Id' => 288,
            'Label' => 'Philips Speech Processing CELP',
        ),
        289 => array(
            'Id' => 289,
            'Label' => 'Philips Speech Processing GRUNDIG',
        ),
        291 => array(
            'Id' => 291,
            'Label' => 'Digital G.723',
        ),
        293 => array(
            'Id' => 293,
            'Label' => 'Sanyo LD ADPCM',
        ),
        304 => array(
            'Id' => 304,
            'Label' => 'Sipro Lab ACEPLNET',
        ),
        305 => array(
            'Id' => 305,
            'Label' => 'Sipro Lab ACELP4800',
        ),
        306 => array(
            'Id' => 306,
            'Label' => 'Sipro Lab ACELP8V3',
        ),
        307 => array(
            'Id' => 307,
            'Label' => 'Sipro Lab G.729',
        ),
        308 => array(
            'Id' => 308,
            'Label' => 'Sipro Lab G.729A',
        ),
        309 => array(
            'Id' => 309,
            'Label' => 'Sipro Lab Kelvin',
        ),
        310 => array(
            'Id' => 310,
            'Label' => 'VoiceAge AMR',
        ),
        320 => array(
            'Id' => 320,
            'Label' => 'Dictaphone G.726 ADPCM',
        ),
        336 => array(
            'Id' => 336,
            'Label' => 'Qualcomm PureVoice',
        ),
        337 => array(
            'Id' => 337,
            'Label' => 'Qualcomm HalfRate',
        ),
        341 => array(
            'Id' => 341,
            'Label' => 'Ring Zero Systems TUBGSM',
        ),
        352 => array(
            'Id' => 352,
            'Label' => 'Microsoft Audio1',
        ),
        353 => array(
            'Id' => 353,
            'Label' => 'Windows Media Audio V2 V7 V8 V9 / DivX audio (WMA) / Alex AC3 Audio',
        ),
        354 => array(
            'Id' => 354,
            'Label' => 'Windows Media Audio Professional V9',
        ),
        355 => array(
            'Id' => 355,
            'Label' => 'Windows Media Audio Lossless V9',
        ),
        356 => array(
            'Id' => 356,
            'Label' => 'WMA Pro over S/PDIF',
        ),
        368 => array(
            'Id' => 368,
            'Label' => 'UNISYS NAP ADPCM',
        ),
        369 => array(
            'Id' => 369,
            'Label' => 'UNISYS NAP ULAW',
        ),
        370 => array(
            'Id' => 370,
            'Label' => 'UNISYS NAP ALAW',
        ),
        371 => array(
            'Id' => 371,
            'Label' => 'UNISYS NAP 16K',
        ),
        372 => array(
            'Id' => 372,
            'Label' => 'MM SYCOM ACM SYC008 SyCom Technologies',
        ),
        373 => array(
            'Id' => 373,
            'Label' => 'MM SYCOM ACM SYC701 G726L SyCom Technologies',
        ),
        374 => array(
            'Id' => 374,
            'Label' => 'MM SYCOM ACM SYC701 CELP54 SyCom Technologies',
        ),
        375 => array(
            'Id' => 375,
            'Label' => 'MM SYCOM ACM SYC701 CELP68 SyCom Technologies',
        ),
        376 => array(
            'Id' => 376,
            'Label' => 'Knowledge Adventure ADPCM',
        ),
        384 => array(
            'Id' => 384,
            'Label' => 'Fraunhofer IIS MPEG2AAC',
        ),
        400 => array(
            'Id' => 400,
            'Label' => 'Digital Theater Systems DTS DS',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Creative Labs ADPCM',
        ),
        514 => array(
            'Id' => 514,
            'Label' => 'Creative Labs FASTSPEECH8',
        ),
        515 => array(
            'Id' => 515,
            'Label' => 'Creative Labs FASTSPEECH10',
        ),
        528 => array(
            'Id' => 528,
            'Label' => 'UHER ADPCM',
        ),
        533 => array(
            'Id' => 533,
            'Label' => 'Ulead DV ACM',
        ),
        534 => array(
            'Id' => 534,
            'Label' => 'Ulead DV ACM',
        ),
        544 => array(
            'Id' => 544,
            'Label' => 'Quarterdeck Corp.',
        ),
        560 => array(
            'Id' => 560,
            'Label' => 'I-Link VC',
        ),
        576 => array(
            'Id' => 576,
            'Label' => 'Aureal Semiconductor Raw Sport',
        ),
        577 => array(
            'Id' => 577,
            'Label' => 'ESST AC3',
        ),
        592 => array(
            'Id' => 592,
            'Label' => 'Interactive Products HSX',
        ),
        593 => array(
            'Id' => 593,
            'Label' => 'Interactive Products RPELP',
        ),
        608 => array(
            'Id' => 608,
            'Label' => 'Consistent CS2',
        ),
        624 => array(
            'Id' => 624,
            'Label' => 'Sony SCX',
        ),
        625 => array(
            'Id' => 625,
            'Label' => 'Sony SCY',
        ),
        626 => array(
            'Id' => 626,
            'Label' => 'Sony ATRAC3',
        ),
        627 => array(
            'Id' => 627,
            'Label' => 'Sony SPC',
        ),
        640 => array(
            'Id' => 640,
            'Label' => 'TELUM Telum Inc.',
        ),
        641 => array(
            'Id' => 641,
            'Label' => 'TELUMIA Telum Inc.',
        ),
        645 => array(
            'Id' => 645,
            'Label' => 'Norcom Voice Systems ADPCM',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'Fujitsu FM TOWNS SND',
        ),
        769 => array(
            'Id' => 769,
            'Label' => 'Fujitsu (not specified)',
        ),
        770 => array(
            'Id' => 770,
            'Label' => 'Fujitsu (not specified)',
        ),
        771 => array(
            'Id' => 771,
            'Label' => 'Fujitsu (not specified)',
        ),
        772 => array(
            'Id' => 772,
            'Label' => 'Fujitsu (not specified)',
        ),
        773 => array(
            'Id' => 773,
            'Label' => 'Fujitsu (not specified)',
        ),
        774 => array(
            'Id' => 774,
            'Label' => 'Fujitsu (not specified)',
        ),
        775 => array(
            'Id' => 775,
            'Label' => 'Fujitsu (not specified)',
        ),
        776 => array(
            'Id' => 776,
            'Label' => 'Fujitsu (not specified)',
        ),
        848 => array(
            'Id' => 848,
            'Label' => 'Micronas Semiconductors, Inc. Development',
        ),
        849 => array(
            'Id' => 849,
            'Label' => 'Micronas Semiconductors, Inc. CELP833',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Brooktree Digital',
        ),
        1025 => array(
            'Id' => 1025,
            'Label' => 'Intel Music Coder (IMC)',
        ),
        1026 => array(
            'Id' => 1026,
            'Label' => 'Ligos Indeo Audio',
        ),
        1104 => array(
            'Id' => 1104,
            'Label' => 'QDesign Music',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'On2 VP7 On2 Technologies',
        ),
        1281 => array(
            'Id' => 1281,
            'Label' => 'On2 VP6 On2 Technologies',
        ),
        1664 => array(
            'Id' => 1664,
            'Label' => 'AT&T VME VMPCM',
        ),
        1665 => array(
            'Id' => 1665,
            'Label' => 'AT&T TCP',
        ),
        1792 => array(
            'Id' => 1792,
            'Label' => 'YMPEG Alpha (dummy for MPEG-2 compressor)',
        ),
        2222 => array(
            'Id' => 2222,
            'Label' => 'ClearJump LiteWave (lossless)',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'Olivetti GSM',
        ),
        4097 => array(
            'Id' => 4097,
            'Label' => 'Olivetti ADPCM',
        ),
        4098 => array(
            'Id' => 4098,
            'Label' => 'Olivetti CELP',
        ),
        4099 => array(
            'Id' => 4099,
            'Label' => 'Olivetti SBC',
        ),
        4100 => array(
            'Id' => 4100,
            'Label' => 'Olivetti OPR',
        ),
        4352 => array(
            'Id' => 4352,
            'Label' => 'Lernout & Hauspie',
        ),
        4353 => array(
            'Id' => 4353,
            'Label' => 'Lernout & Hauspie CELP codec',
        ),
        4354 => array(
            'Id' => 4354,
            'Label' => 'Lernout & Hauspie SBC codec',
        ),
        4355 => array(
            'Id' => 4355,
            'Label' => 'Lernout & Hauspie SBC codec',
        ),
        4356 => array(
            'Id' => 4356,
            'Label' => 'Lernout & Hauspie SBC codec',
        ),
        5120 => array(
            'Id' => 5120,
            'Label' => 'Norris Comm. Inc.',
        ),
        5121 => array(
            'Id' => 5121,
            'Label' => 'ISIAudio',
        ),
        5376 => array(
            'Id' => 5376,
            'Label' => 'AT&T Soundspace Music Compression',
        ),
        6172 => array(
            'Id' => 6172,
            'Label' => 'VoxWare RT24 speech codec',
        ),
        6174 => array(
            'Id' => 6174,
            'Label' => 'Lucent elemedia AX24000P Music codec',
        ),
        6513 => array(
            'Id' => 6513,
            'Label' => 'Sonic Foundry LOSSLESS',
        ),
        6521 => array(
            'Id' => 6521,
            'Label' => 'Innings Telecom Inc. ADPCM',
        ),
        7175 => array(
            'Id' => 7175,
            'Label' => 'Lucent SX8300P speech codec',
        ),
        7180 => array(
            'Id' => 7180,
            'Label' => 'Lucent SX5363S G.723 compliant codec',
        ),
        7939 => array(
            'Id' => 7939,
            'Label' => 'CUseeMe DigiTalk (ex-Rocwell)',
        ),
        8132 => array(
            'Id' => 8132,
            'Label' => 'NCT Soft ALF2CD ACM',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'FAST Multimedia DVM',
        ),
        8193 => array(
            'Id' => 8193,
            'Label' => 'Dolby DTS (Digital Theater System)',
        ),
        8194 => array(
            'Id' => 8194,
            'Label' => 'RealAudio 1 / 2 14.4',
        ),
        8195 => array(
            'Id' => 8195,
            'Label' => 'RealAudio 1 / 2 28.8',
        ),
        8196 => array(
            'Id' => 8196,
            'Label' => 'RealAudio G2 / 8 Cook (low bitrate)',
        ),
        8197 => array(
            'Id' => 8197,
            'Label' => 'RealAudio 3 / 4 / 5 Music (DNET)',
        ),
        8198 => array(
            'Id' => 8198,
            'Label' => 'RealAudio 10 AAC (RAAC)',
        ),
        8199 => array(
            'Id' => 8199,
            'Label' => 'RealAudio 10 AAC+ (RACP)',
        ),
        9472 => array(
            'Id' => 9472,
            'Label' => 'Reserved range to 0x2600 Microsoft',
        ),
        13075 => array(
            'Id' => 13075,
            'Label' => 'makeAVIS (ffvfw fake AVI sound from AviSynth scripts)',
        ),
        16707 => array(
            'Id' => 16707,
            'Label' => 'Divio MPEG-4 AAC audio',
        ),
        16897 => array(
            'Id' => 16897,
            'Label' => 'Nokia adaptive multirate',
        ),
        16963 => array(
            'Id' => 16963,
            'Label' => 'Divio G726 Divio, Inc.',
        ),
        17228 => array(
            'Id' => 17228,
            'Label' => 'LEAD Speech',
        ),
        22092 => array(
            'Id' => 22092,
            'Label' => 'LEAD Vorbis',
        ),
        22358 => array(
            'Id' => 22358,
            'Label' => 'WavPack Audio',
        ),
        26447 => array(
            'Id' => 26447,
            'Label' => 'Ogg Vorbis (mode 1)',
        ),
        26448 => array(
            'Id' => 26448,
            'Label' => 'Ogg Vorbis (mode 2)',
        ),
        26449 => array(
            'Id' => 26449,
            'Label' => 'Ogg Vorbis (mode 3)',
        ),
        26479 => array(
            'Id' => 26479,
            'Label' => 'Ogg Vorbis (mode 1+)',
        ),
        26480 => array(
            'Id' => 26480,
            'Label' => 'Ogg Vorbis (mode 2+)',
        ),
        26481 => array(
            'Id' => 26481,
            'Label' => 'Ogg Vorbis (mode 3+)',
        ),
        28672 => array(
            'Id' => 28672,
            'Label' => '3COM NBX 3Com Corporation',
        ),
        28781 => array(
            'Id' => 28781,
            'Label' => 'FAAD AAC',
        ),
        31265 => array(
            'Id' => 31265,
            'Label' => 'GSM-AMR (CBR, no SID)',
        ),
        31266 => array(
            'Id' => 31266,
            'Label' => 'GSM-AMR (VBR, including SID)',
        ),
        41216 => array(
            'Id' => 41216,
            'Label' => 'Comverse Infosys Ltd. G723 1',
        ),
        41217 => array(
            'Id' => 41217,
            'Label' => 'Comverse Infosys Ltd. AVQSBC',
        ),
        41218 => array(
            'Id' => 41218,
            'Label' => 'Comverse Infosys Ltd. OLDSBC',
        ),
        41219 => array(
            'Id' => 41219,
            'Label' => 'Symbol Technologies G729A',
        ),
        41220 => array(
            'Id' => 41220,
            'Label' => 'VoiceAge AMR WB VoiceAge Corporation',
        ),
        41221 => array(
            'Id' => 41221,
            'Label' => 'Ingenient Technologies Inc. G726',
        ),
        41222 => array(
            'Id' => 41222,
            'Label' => 'ISO/MPEG-4 advanced audio Coding',
        ),
        41223 => array(
            'Id' => 41223,
            'Label' => 'Encore Software Ltd G726',
        ),
        41225 => array(
            'Id' => 41225,
            'Label' => 'Speex ACM Codec xiph.org',
        ),
        57260 => array(
            'Id' => 57260,
            'Label' => 'DebugMode SonicFoundry Vegas FrameServer ACM Codec',
        ),
        59144 => array(
            'Id' => 59144,
            'Label' => 'Unknown -',
        ),
        61868 => array(
            'Id' => 61868,
            'Label' => 'Free Lossless Audio Codec FLAC',
        ),
        65534 => array(
            'Id' => 65534,
            'Label' => 'Extensible',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'Development',
        ),
    );

}
