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
class CanonAFInfo20x000d extends AbstractTag
{

    protected $Id = 13;

    protected $Name = 'Canon_AFInfo2_0x000d';

    protected $FullName = 'Canon::AFInfo2';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = false;

    protected $Description = 'Canon AF Info 2 0x000d';

    protected $flag_Permanent = true;

    protected $Index = 1;

}
