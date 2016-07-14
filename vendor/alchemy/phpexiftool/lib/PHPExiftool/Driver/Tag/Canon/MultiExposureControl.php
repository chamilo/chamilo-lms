<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MultiExposureControl extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'MultiExposureControl';

    protected $FullName = 'Canon::MultiExp';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Image';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Multi Exposure Control';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Additive',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Average',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bright (comparative)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Dark (comparative)',
        ),
    );

}
