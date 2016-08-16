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
class SupplementalType extends AbstractTag
{

    protected $Id = 55;

    protected $Name = 'SupplementalType';

    protected $FullName = 'IPTC::NewsPhoto';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Supplemental Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Main Image',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Reduced Resolution Image',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Logo',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Rasterized Caption',
        ),
    );

}
