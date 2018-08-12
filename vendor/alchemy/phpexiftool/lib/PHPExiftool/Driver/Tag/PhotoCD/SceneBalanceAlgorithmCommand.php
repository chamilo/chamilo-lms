<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhotoCD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SceneBalanceAlgorithmCommand extends AbstractTag
{

    protected $Id = 230;

    protected $Name = 'SceneBalanceAlgorithmCommand';

    protected $FullName = 'PhotoCD::Main';

    protected $GroupName = 'PhotoCD';

    protected $g0 = 'PhotoCD';

    protected $g1 = 'PhotoCD';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Scene Balance Algorithm Command';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Neutral SBA On, Color SBA On',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Neutral SBA Off, Color SBA Off',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Neutral SBA On, Color SBA Off',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Neutral SBA Off, Color SBA On',
        ),
    );

}
