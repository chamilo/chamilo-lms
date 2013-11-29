<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCapture;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBAdjMode extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'WBAdjMode';

    protected $FullName = 'NikonCapture::WBAdjData';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'WB Adj Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Use Gray Point',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Recorded Value',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Use Temperature',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Calculate Automatically',
        ),
    );

}
