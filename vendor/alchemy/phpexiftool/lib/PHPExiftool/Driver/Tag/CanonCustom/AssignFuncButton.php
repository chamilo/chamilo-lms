<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AssignFuncButton extends AbstractTag
{

    protected $Id = 1803;

    protected $Name = 'AssignFuncButton';

    protected $FullName = 'CanonCustom::Functions2';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Assign Func Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'LCD brightness',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Image quality',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Exposure comp./AEB setting',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Image jump with main dial',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Live view function settings',
        ),
    );

}
