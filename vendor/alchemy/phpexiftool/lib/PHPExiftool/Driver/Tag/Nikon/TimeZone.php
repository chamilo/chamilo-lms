<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class TimeZone extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'TimeZone';

    protected $FullName = 'Nikon::WorldTime';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Time';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Time Zone';

    protected $flag_Permanent = true;

}
