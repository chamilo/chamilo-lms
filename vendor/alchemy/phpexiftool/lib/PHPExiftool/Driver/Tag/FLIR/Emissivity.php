<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLIR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Emissivity extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Emissivity';

    protected $FullName = 'mixed';

    protected $GroupName = 'FLIR';

    protected $g0 = 'mixed';

    protected $g1 = 'FLIR';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Emissivity';

    protected $flag_Permanent = 'mixed';

}
