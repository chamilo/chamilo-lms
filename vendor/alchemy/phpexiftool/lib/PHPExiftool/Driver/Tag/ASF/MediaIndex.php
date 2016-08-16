<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ASF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MediaIndex extends AbstractTag
{

    protected $Id = 'FEB103F8-12AD-4C64-840F-2A1D2F7AD48C';

    protected $Name = 'MediaIndex';

    protected $FullName = 'ASF::Main';

    protected $GroupName = 'ASF';

    protected $g0 = 'ASF';

    protected $g1 = 'ASF';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Media Index';

}
