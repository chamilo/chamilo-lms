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
class SubimageNumericalFormat extends AbstractTag
{

    protected $Id = 33554435;

    protected $Name = 'SubimageNumericalFormat';

    protected $FullName = 'FlashPix::Image';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Subimage Numerical Format';

    protected $Values = array(
        17 => array(
            'Id' => 17,
            'Label' => '8-bit, Unsigned',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '16-bit, Unsigned',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '32-bit, Unsigned',
        ),
    );

}
