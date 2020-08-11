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
class ContrastAuto extends AbstractTag
{

    protected $Id = 144;

    protected $Name = 'ContrastAuto';

    protected $FullName = 'Canon::PSInfo2';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'Contrast Auto';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-559038737' => array(
            'Id' => '-559038737',
            'Label' => 'n/a',
        ),
    );

}
