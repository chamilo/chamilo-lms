<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPTiff;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PlanarConfiguration extends AbstractTag
{

    protected $Id = 'PlanarConfiguration';

    protected $Name = 'PlanarConfiguration';

    protected $FullName = 'XMP::tiff';

    protected $GroupName = 'XMP-tiff';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-tiff';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Planar Configuration';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Chunky',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Planar',
        ),
    );

}
