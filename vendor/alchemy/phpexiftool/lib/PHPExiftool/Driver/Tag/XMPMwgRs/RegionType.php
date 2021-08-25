<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPMwgRs;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RegionType extends AbstractTag
{

    protected $Id = 'RegionsRegionListType';

    protected $Name = 'RegionType';

    protected $FullName = 'MWG::Regions';

    protected $GroupName = 'XMP-mwg-rs';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-mwg-rs';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Region Type';

    protected $flag_List = true;

    protected $Values = array(
        'BarCode' => array(
            'Id' => 'BarCode',
            'Label' => 'BarCode',
        ),
        'Face' => array(
            'Id' => 'Face',
            'Label' => 'Face',
        ),
        'Focus' => array(
            'Id' => 'Focus',
            'Label' => 'Focus',
        ),
        'Pet' => array(
            'Id' => 'Pet',
            'Label' => 'Pet',
        ),
    );

}
