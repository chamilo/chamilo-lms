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
class AFPointsInFocus extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointsInFocus';

    protected $FullName = 'mixed';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'mixed';

    protected $Type = 'int16s';

    protected $Writable = false;

    protected $Description = 'AF Points In Focus';

    protected $flag_Permanent = true;

    protected $local_g2 = 'mixed';

    protected $Values = array(
        12288 => array(
            'Id' => 12288,
            'Label' => 'None (MF)',
        ),
        12289 => array(
            'Id' => 12289,
            'Label' => 'Right',
        ),
        12290 => array(
            'Id' => 12290,
            'Label' => 'Center',
        ),
        12291 => array(
            'Id' => 12291,
            'Label' => 'Center+Right',
        ),
        12292 => array(
            'Id' => 12292,
            'Label' => 'Left',
        ),
        12293 => array(
            'Id' => 12293,
            'Label' => 'Left+Right',
        ),
        12294 => array(
            'Id' => 12294,
            'Label' => 'Left+Center',
        ),
        12295 => array(
            'Id' => 12295,
            'Label' => 'All',
        ),
    );

}
