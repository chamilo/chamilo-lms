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
class DecimationMethod extends AbstractTag
{

    protected $Id = 33554436;

    protected $Name = 'DecimationMethod';

    protected $FullName = 'FlashPix::Image';

    protected $GroupName = 'FlashPix';

    protected $g0 = 'FlashPix';

    protected $g1 = 'FlashPix';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Decimation Method';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None (Full-sized Image)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '8-point Prefilter',
        ),
    );

}
