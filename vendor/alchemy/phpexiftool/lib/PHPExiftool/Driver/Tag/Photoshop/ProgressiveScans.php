<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Photoshop;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ProgressiveScans extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'ProgressiveScans';

    protected $FullName = 'Photoshop::JPEG_Quality';

    protected $GroupName = 'Photoshop';

    protected $g0 = 'Photoshop';

    protected $g1 = 'Photoshop';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = false;

    protected $Description = 'Progressive Scans';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => '3 Scans',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '4 Scans',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '5 Scans',
        ),
    );

}
