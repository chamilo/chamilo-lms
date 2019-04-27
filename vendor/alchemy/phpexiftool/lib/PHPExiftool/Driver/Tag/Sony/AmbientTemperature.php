<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AmbientTemperature extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'AmbientTemperature';

    protected $FullName = 'Sony::Tag9402';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8s';

    protected $Writable = true;

    protected $Description = 'Ambient Temperature';

    protected $flag_Permanent = true;

}
