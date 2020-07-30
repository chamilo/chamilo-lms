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
class ImageTemperatureMax extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'ImageTemperatureMax';

    protected $FullName = 'FLIR::Main';

    protected $GroupName = 'FLIR';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FLIR';

    protected $g2 = 'Camera';

    protected $Type = 'rational64u';

    protected $Writable = true;

    protected $Description = 'Image Temperature Max';

    protected $flag_Permanent = true;

}
