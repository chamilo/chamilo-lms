<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\LNK;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NetProviderType extends AbstractTag
{

    protected $Id = 'NetProviderType';

    protected $Name = 'NetProviderType';

    protected $FullName = 'LNK::LinkInfo';

    protected $GroupName = 'LNK';

    protected $g0 = 'LNK';

    protected $g1 = 'LNK';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Net Provider Type';

    protected $Values = array(
        1703936 => array(
            'Id' => 1703936,
            'Label' => 'AVID',
        ),
        1769472 => array(
            'Id' => 1769472,
            'Label' => 'DOCUSPACE',
        ),
        1835008 => array(
            'Id' => 1835008,
            'Label' => 'MANGOSOFT',
        ),
        1900544 => array(
            'Id' => 1900544,
            'Label' => 'SERNET',
        ),
        1966080 => array(
            'Id' => 1966080,
            'Label' => 'RIVERFRONT1',
        ),
        2031616 => array(
            'Id' => 2031616,
            'Label' => 'RIVERFRONT2',
        ),
        2097152 => array(
            'Id' => 2097152,
            'Label' => 'DECORB',
        ),
        2162688 => array(
            'Id' => 2162688,
            'Label' => 'PROTSTOR',
        ),
        2228224 => array(
            'Id' => 2228224,
            'Label' => 'FJ_REDIR',
        ),
        2293760 => array(
            'Id' => 2293760,
            'Label' => 'DISTINCT',
        ),
        2359296 => array(
            'Id' => 2359296,
            'Label' => 'TWINS',
        ),
        2424832 => array(
            'Id' => 2424832,
            'Label' => 'RDR2SAMPLE',
        ),
        2490368 => array(
            'Id' => 2490368,
            'Label' => 'CSC',
        ),
        2555904 => array(
            'Id' => 2555904,
            'Label' => '3IN1',
        ),
        2686976 => array(
            'Id' => 2686976,
            'Label' => 'EXTENDNET',
        ),
        2752512 => array(
            'Id' => 2752512,
            'Label' => 'STAC',
        ),
        2818048 => array(
            'Id' => 2818048,
            'Label' => 'FOXBAT',
        ),
        2883584 => array(
            'Id' => 2883584,
            'Label' => 'YAHOO',
        ),
        2949120 => array(
            'Id' => 2949120,
            'Label' => 'EXIFS',
        ),
        3014656 => array(
            'Id' => 3014656,
            'Label' => 'DAV',
        ),
        3080192 => array(
            'Id' => 3080192,
            'Label' => 'KNOWARE',
        ),
        3145728 => array(
            'Id' => 3145728,
            'Label' => 'OBJECT_DIRE',
        ),
        3211264 => array(
            'Id' => 3211264,
            'Label' => 'MASFAX',
        ),
        3276800 => array(
            'Id' => 3276800,
            'Label' => 'HOB_NFS',
        ),
        3342336 => array(
            'Id' => 3342336,
            'Label' => 'SHIVA',
        ),
        3407872 => array(
            'Id' => 3407872,
            'Label' => 'IBMAL',
        ),
        3473408 => array(
            'Id' => 3473408,
            'Label' => 'LOCK',
        ),
        3538944 => array(
            'Id' => 3538944,
            'Label' => 'TERMSRV',
        ),
        3604480 => array(
            'Id' => 3604480,
            'Label' => 'SRT',
        ),
        3670016 => array(
            'Id' => 3670016,
            'Label' => 'QUINCY',
        ),
        3735552 => array(
            'Id' => 3735552,
            'Label' => 'OPENAFS',
        ),
        3801088 => array(
            'Id' => 3801088,
            'Label' => 'AVID1',
        ),
        3866624 => array(
            'Id' => 3866624,
            'Label' => 'DFS',
        ),
    );

}
