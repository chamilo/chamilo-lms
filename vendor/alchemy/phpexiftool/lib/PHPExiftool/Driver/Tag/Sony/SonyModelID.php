<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SonyModelID extends AbstractTag
{

    protected $Id = 45057;

    protected $Name = 'SonyModelID';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Sony Model ID';

    protected $flag_Permanent = true;

    protected $Values = array(
        2 => array(
            'Id' => 2,
            'Label' => 'DSC-R1',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'DSLR-A100',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'DSLR-A900',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'DSLR-A700',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'DSLR-A200',
        ),
        260 => array(
            'Id' => 260,
            'Label' => 'DSLR-A350',
        ),
        261 => array(
            'Id' => 261,
            'Label' => 'DSLR-A300',
        ),
        262 => array(
            'Id' => 262,
            'Label' => 'DSLR-A900 (APS-C mode)',
        ),
        263 => array(
            'Id' => 263,
            'Label' => 'DSLR-A380/A390',
        ),
        264 => array(
            'Id' => 264,
            'Label' => 'DSLR-A330',
        ),
        265 => array(
            'Id' => 265,
            'Label' => 'DSLR-A230',
        ),
        266 => array(
            'Id' => 266,
            'Label' => 'DSLR-A290',
        ),
        269 => array(
            'Id' => 269,
            'Label' => 'DSLR-A850',
        ),
        270 => array(
            'Id' => 270,
            'Label' => 'DSLR-A850 (APS-C mode)',
        ),
        273 => array(
            'Id' => 273,
            'Label' => 'DSLR-A550',
        ),
        274 => array(
            'Id' => 274,
            'Label' => 'DSLR-A500',
        ),
        275 => array(
            'Id' => 275,
            'Label' => 'DSLR-A450',
        ),
        278 => array(
            'Id' => 278,
            'Label' => 'NEX-5',
        ),
        279 => array(
            'Id' => 279,
            'Label' => 'NEX-3',
        ),
        280 => array(
            'Id' => 280,
            'Label' => 'SLT-A33',
        ),
        281 => array(
            'Id' => 281,
            'Label' => 'SLT-A55 / SLT-A55V',
        ),
        282 => array(
            'Id' => 282,
            'Label' => 'DSLR-A560',
        ),
        283 => array(
            'Id' => 283,
            'Label' => 'DSLR-A580',
        ),
        284 => array(
            'Id' => 284,
            'Label' => 'NEX-C3',
        ),
        285 => array(
            'Id' => 285,
            'Label' => 'SLT-A35',
        ),
        286 => array(
            'Id' => 286,
            'Label' => 'SLT-A65 / SLT-A65V',
        ),
        287 => array(
            'Id' => 287,
            'Label' => 'SLT-A77 / SLT-A77V',
        ),
        288 => array(
            'Id' => 288,
            'Label' => 'NEX-5N',
        ),
        289 => array(
            'Id' => 289,
            'Label' => 'NEX-7',
        ),
        290 => array(
            'Id' => 290,
            'Label' => 'NEX-VG20E',
        ),
        291 => array(
            'Id' => 291,
            'Label' => 'SLT-A37',
        ),
        292 => array(
            'Id' => 292,
            'Label' => 'SLT-A57',
        ),
        293 => array(
            'Id' => 293,
            'Label' => 'NEX-F3',
        ),
        294 => array(
            'Id' => 294,
            'Label' => 'SLT-A99 / SLT-A99V',
        ),
        295 => array(
            'Id' => 295,
            'Label' => 'NEX-6',
        ),
        296 => array(
            'Id' => 296,
            'Label' => 'NEX-5R',
        ),
        297 => array(
            'Id' => 297,
            'Label' => 'DSC-RX100',
        ),
        298 => array(
            'Id' => 298,
            'Label' => 'DSC-RX1',
        ),
        299 => array(
            'Id' => 299,
            'Label' => 'NEX-VG900',
        ),
        300 => array(
            'Id' => 300,
            'Label' => 'NEX-VG30E',
        ),
        302 => array(
            'Id' => 302,
            'Label' => 'ILCE-3000 / ILCE-3500',
        ),
        303 => array(
            'Id' => 303,
            'Label' => 'SLT-A58',
        ),
        305 => array(
            'Id' => 305,
            'Label' => 'NEX-3N',
        ),
        306 => array(
            'Id' => 306,
            'Label' => 'ILCE-7',
        ),
        307 => array(
            'Id' => 307,
            'Label' => 'NEX-5T',
        ),
        308 => array(
            'Id' => 308,
            'Label' => 'DSC-RX100M2',
        ),
        309 => array(
            'Id' => 309,
            'Label' => 'DSC-RX10',
        ),
        310 => array(
            'Id' => 310,
            'Label' => 'DSC-RX1R',
        ),
        311 => array(
            'Id' => 311,
            'Label' => 'ILCE-7R',
        ),
        312 => array(
            'Id' => 312,
            'Label' => 'ILCE-6000',
        ),
        313 => array(
            'Id' => 313,
            'Label' => 'ILCE-5000',
        ),
        317 => array(
            'Id' => 317,
            'Label' => 'DSC-RX100M3',
        ),
        318 => array(
            'Id' => 318,
            'Label' => 'ILCE-7S',
        ),
        319 => array(
            'Id' => 319,
            'Label' => 'ILCA-77M2',
        ),
        339 => array(
            'Id' => 339,
            'Label' => 'ILCE-5100',
        ),
        340 => array(
            'Id' => 340,
            'Label' => 'ILCE-7M2',
        ),
        341 => array(
            'Id' => 341,
            'Label' => 'DSC-RX100M4',
        ),
        342 => array(
            'Id' => 342,
            'Label' => 'DSC-RX10M2',
        ),
        344 => array(
            'Id' => 344,
            'Label' => 'DSC-RX1RM2',
        ),
        346 => array(
            'Id' => 346,
            'Label' => 'ILCE-QX1',
        ),
        347 => array(
            'Id' => 347,
            'Label' => 'ILCE-7RM2',
        ),
        350 => array(
            'Id' => 350,
            'Label' => 'ILCE-7SM2',
        ),
    );

}
