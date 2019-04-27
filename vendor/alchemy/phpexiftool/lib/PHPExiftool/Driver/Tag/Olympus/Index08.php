<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Index08 extends AbstractTag
{

    protected $Id = 592;

    protected $Name = 'Index08';

    protected $FullName = 'Olympus::WAV';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Index 08';

    protected $flag_Permanent = true;

}
