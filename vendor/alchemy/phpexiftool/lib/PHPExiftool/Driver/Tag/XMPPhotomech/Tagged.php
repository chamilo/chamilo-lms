<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPhotomech;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Tagged extends AbstractTag
{

    protected $Id = 'Tagged';

    protected $Name = 'Tagged';

    protected $FullName = 'PhotoMechanic::XMP';

    protected $GroupName = 'XMP-photomech';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-photomech';

    protected $g2 = 'Image';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Tagged';

    protected $Values = array(
        false => array(
            'Id' => false,
            'Label' => 'No',
        ),
        true => array(
            'Id' => true,
            'Label' => 'Yes',
        ),
    );

}
