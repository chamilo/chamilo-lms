<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCapture;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBAdjBlueBalance extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'WBAdjBlueBalance';

    protected $FullName = 'NikonCapture::WBAdjData';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'double';

    protected $Writable = true;

    protected $Description = 'WB Adj Blue Balance';

    protected $flag_Permanent = true;

}
