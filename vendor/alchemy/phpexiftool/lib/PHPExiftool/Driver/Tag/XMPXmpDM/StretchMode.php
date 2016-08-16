<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmpDM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class StretchMode extends AbstractTag
{

    protected $Id = 'stretchMode';

    protected $Name = 'StretchMode';

    protected $FullName = 'XMP::xmpDM';

    protected $GroupName = 'XMP-xmpDM';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpDM';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Stretch Mode';

    protected $Values = array(
        'Beat Splice' => array(
            'Id' => 'Beat Splice',
            'Label' => 'Beat Splice',
        ),
        'Fixed length' => array(
            'Id' => 'Fixed length',
            'Label' => 'Fixed length',
        ),
        'Hybrid' => array(
            'Id' => 'Hybrid',
            'Label' => 'Hybrid',
        ),
        'Resample' => array(
            'Id' => 'Resample',
            'Label' => 'Resample',
        ),
        'Time-Scale' => array(
            'Id' => 'Time-Scale',
            'Label' => 'Time-Scale',
        ),
    );

}
