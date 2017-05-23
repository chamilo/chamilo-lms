<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SizeMode extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'SizeMode';

    protected $FullName = 'IPTC::PreObjectData';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Other';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Size Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Size Not Known',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Size Known',
        ),
    );

}
