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
class SensorLeftBorder extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'SensorLeftBorder';

    protected $FullName = 'Canon::SensorInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = false;

    protected $Description = 'Sensor Left Border';

    protected $flag_Permanent = true;

}
