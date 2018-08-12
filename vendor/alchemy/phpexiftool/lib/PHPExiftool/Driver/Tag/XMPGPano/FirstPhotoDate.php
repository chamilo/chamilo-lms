<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPGPano;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FirstPhotoDate extends AbstractTag
{

    protected $Id = 'FirstPhotoDate';

    protected $Name = 'FirstPhotoDate';

    protected $FullName = 'XMP::GPano';

    protected $GroupName = 'XMP-GPano';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GPano';

    protected $g2 = 'Image';

    protected $Type = 'date';

    protected $Writable = true;

    protected $Description = 'First Photo Date';

    protected $local_g2 = 'Time';

}
