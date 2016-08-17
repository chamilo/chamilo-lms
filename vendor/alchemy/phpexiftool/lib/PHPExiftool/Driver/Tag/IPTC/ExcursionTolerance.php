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
class ExcursionTolerance extends AbstractTag
{

    protected $Id = 130;

    protected $Name = 'ExcursionTolerance';

    protected $FullName = 'IPTC::NewsPhoto';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Excursion Tolerance';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Not Allowed',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Allowed',
        ),
    );

}
