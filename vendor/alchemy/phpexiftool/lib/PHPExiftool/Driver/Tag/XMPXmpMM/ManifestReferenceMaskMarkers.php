<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpMM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ManifestReferenceMaskMarkers extends AbstractTag
{

    protected $Id = 'ManifestReferenceMaskMarkers';

    protected $Name = 'ManifestReferenceMaskMarkers';

    protected $FullName = 'XMP::xmpMM';

    protected $GroupName = 'XMP-xmpMM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpMM';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Manifest Reference Mask Markers';

    protected $flag_List = true;

    protected $Values = array(
        'All' => array(
            'Id' => 'All',
            'Label' => 'All',
        ),
        'None' => array(
            'Id' => 'None',
            'Label' => 'None',
        ),
    );

}
