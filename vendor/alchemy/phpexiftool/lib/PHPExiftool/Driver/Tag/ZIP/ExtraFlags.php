<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ZIP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExtraFlags extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'ExtraFlags';

    protected $FullName = 'ZIP::GZIP';

    protected $GroupName = 'ZIP';

    protected $g0 = 'ZIP';

    protected $g1 = 'ZIP';

    protected $g2 = 'Other';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Extra Flags';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Maximum Compression',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Fastest Algorithm',
        ),
    );

}
