<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FlashPix;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Flash extends AbstractTag
{

    protected $Id = 620757003;

    protected $Name = 'Flash';

    protected $FullName = 'FlashPix::ImageInfo';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Flash';

    protected $local_g2 = 'Camera';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'No Flash',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Flash Fired',
        ),
    );

}
