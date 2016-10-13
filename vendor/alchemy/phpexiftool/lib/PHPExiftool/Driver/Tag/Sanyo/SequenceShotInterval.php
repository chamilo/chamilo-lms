<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sanyo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SequenceShotInterval extends AbstractTag
{

    protected $Id = 548;

    protected $Name = 'SequenceShotInterval';

    protected $FullName = 'Sanyo::Main';

    protected $GroupName = 'Sanyo';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sanyo';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Sequence Shot Interval';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '5 frames/s',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '10 frames/s',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '15 frames/s',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '20 frames/s',
        ),
    );

}
