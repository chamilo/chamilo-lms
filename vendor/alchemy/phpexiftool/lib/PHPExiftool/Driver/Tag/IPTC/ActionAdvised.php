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
class ActionAdvised extends AbstractTag
{

    protected $Id = 42;

    protected $Name = 'ActionAdvised';

    protected $FullName = 'IPTC::ApplicationRecord';

    protected $GroupName = 'IPTC';

    protected $g0 = 'IPTC';

    protected $g1 = 'IPTC';

    protected $g2 = 'Other';

    protected $Type = 'digits';

    protected $Writable = true;

    protected $Description = 'Action Advised';

    protected $MaxLength = 2;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Object Kill',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Object Replace',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Object Append',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Object Reference',
        ),
        '' => array(
            'Id' => '',
            'Label' => '',
        ),
    );

}
