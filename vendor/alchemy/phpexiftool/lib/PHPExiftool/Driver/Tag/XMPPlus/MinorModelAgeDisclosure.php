<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPlus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MinorModelAgeDisclosure extends AbstractTag
{

    protected $Id = 'MinorModelAgeDisclosure';

    protected $Name = 'MinorModelAgeDisclosure';

    protected $FullName = 'XMP::plus';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Minor Model Age Disclosure';

    protected $Values = array(
        'AG-A15' => array(
            'Id' => 'AG-A15',
            'Label' => 'Age 15',
        ),
        'AG-A16' => array(
            'Id' => 'AG-A16',
            'Label' => 'Age 16',
        ),
        'AG-A17' => array(
            'Id' => 'AG-A17',
            'Label' => 'Age 17',
        ),
        'AG-A18' => array(
            'Id' => 'AG-A18',
            'Label' => 'Age 18',
        ),
        'AG-A19' => array(
            'Id' => 'AG-A19',
            'Label' => 'Age 19',
        ),
        'AG-A20' => array(
            'Id' => 'AG-A20',
            'Label' => 'Age 20',
        ),
        'AG-A21' => array(
            'Id' => 'AG-A21',
            'Label' => 'Age 21',
        ),
        'AG-A22' => array(
            'Id' => 'AG-A22',
            'Label' => 'Age 22',
        ),
        'AG-A23' => array(
            'Id' => 'AG-A23',
            'Label' => 'Age 23',
        ),
        'AG-A24' => array(
            'Id' => 'AG-A24',
            'Label' => 'Age 24',
        ),
        'AG-A25' => array(
            'Id' => 'AG-A25',
            'Label' => 'Age 25 or Over',
        ),
        'AG-U14' => array(
            'Id' => 'AG-U14',
            'Label' => 'Age 14 or Under',
        ),
        'AG-UNK' => array(
            'Id' => 'AG-UNK',
            'Label' => 'Age Unknown',
        ),
    );

}
