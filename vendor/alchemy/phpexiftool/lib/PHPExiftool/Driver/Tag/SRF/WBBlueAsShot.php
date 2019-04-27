<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SRF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBBlueAsShot extends AbstractTag
{

    protected $Id = 210;

    protected $Name = 'WBBlueAsShot';

    protected $FullName = 'Sony::SRF2';

    protected $GroupName = 'SRF#';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'SRF#';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'WB Blue As Shot';

    protected $flag_Permanent = true;

}
