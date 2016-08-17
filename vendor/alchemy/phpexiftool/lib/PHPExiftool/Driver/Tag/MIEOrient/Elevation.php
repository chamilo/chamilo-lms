<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEOrient;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Elevation extends AbstractTag
{

    protected $Id = 'Elevation';

    protected $Name = 'Elevation';

    protected $FullName = 'MIE::Orient';

    protected $GroupName = 'MIE-Orient';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Orient';

    protected $g2 = 'Camera';

    protected $Type = 'rational64s';

    protected $Writable = true;

    protected $Description = 'Elevation';

}
