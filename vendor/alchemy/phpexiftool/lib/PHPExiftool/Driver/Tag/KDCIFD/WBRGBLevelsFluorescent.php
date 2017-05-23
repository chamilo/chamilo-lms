<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KDCIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBRGBLevelsFluorescent extends AbstractTag
{

    protected $Id = 64040;

    protected $Name = 'WB_RGBLevelsFluorescent';

    protected $FullName = 'Kodak::KDC_IFD';

    protected $GroupName = 'KDC_IFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KDC_IFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'WB RGB Levels Fluorescent';

    protected $flag_Permanent = true;

}
