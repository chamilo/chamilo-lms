<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Composite;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DriveMode extends AbstractTag
{

    protected $Id = 'DriveMode';

    protected $Name = 'DriveMode';

    protected $FullName = 'Composite';

    protected $GroupName = 'Composite';

    protected $g0 = 'Composite';

    protected $g1 = 'Composite';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Drive Mode';

    protected $local_g2 = 'Camera';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Continuous Shooting',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Self-timer Operation',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Single-frame Shooting',
        ),
    );

}
