<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Track;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class UUIDUnknown extends AbstractTag
{

    protected $Id = 'uuid';

    protected $Name = 'UUID-Unknown';

    protected $FullName = 'QuickTime::Track';

    protected $GroupName = 'Track#';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Track#';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'UUID-Unknown';

    protected $flag_Binary = true;

    protected $Index = 1;

}
