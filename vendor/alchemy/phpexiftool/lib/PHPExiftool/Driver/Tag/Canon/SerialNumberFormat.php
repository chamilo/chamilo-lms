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
class SerialNumberFormat extends AbstractTag
{

    protected $Id = 21;

    protected $Name = 'SerialNumberFormat';

    protected $FullName = 'Canon::Main';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Serial Number Format';

    protected $flag_Permanent = true;

    protected $Values = array(
        '2415919104' => array(
            'Id' => '2415919104',
            'Label' => 'Format 1',
        ),
        '2684354560' => array(
            'Id' => '2684354560',
            'Label' => 'Format 2',
        ),
    );

}
