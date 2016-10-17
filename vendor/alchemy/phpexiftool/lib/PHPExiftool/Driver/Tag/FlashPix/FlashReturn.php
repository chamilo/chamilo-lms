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
class FlashReturn extends AbstractTag
{

    protected $Id = 620757005;

    protected $Name = 'FlashReturn';

    protected $FullName = 'FlashPix::ImageInfo';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Flash Return';

    protected $local_g2 = 'Camera';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Subject Outside Flash Range',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Subject Inside Flash Range',
        ),
    );

}
