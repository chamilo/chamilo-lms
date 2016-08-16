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
class DisplayUnits extends AbstractTag
{

    protected $Id = 16777222;

    protected $Name = 'DisplayUnits';

    protected $FullName = 'FlashPix::Image';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Display Units';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'inches',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'meters',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'cm',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'mm',
        ),
    );

}
