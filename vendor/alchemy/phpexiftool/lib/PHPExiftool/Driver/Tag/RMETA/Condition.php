<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RMETA;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Condition extends AbstractTag
{

    protected $Id = 'Condition';

    protected $Name = 'Condition';

    protected $FullName = 'Ricoh::RMETA';

    protected $GroupName = 'RMETA';

    protected $g0 = 'APP5';

    protected $g1 = 'RMETA';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Condition';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Good',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Fair',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Poor',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Damaged',
        ),
    );

}
