<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RealRA5;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BytesPerMinute extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'BytesPerMinute';

    protected $FullName = 'Real::AudioV5';

    protected $GroupName = 'Real-RA5';

    protected $g0 = 'Real';

    protected $g1 = 'Real-RA5';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Bytes Per Minute';

}
