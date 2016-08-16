<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLAC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SeekTable extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'SeekTable';

    protected $FullName = 'FLAC::Main';

    protected $GroupName = 'FLAC';

    protected $g0 = 'FLAC';

    protected $g1 = 'FLAC';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Seek Table';

    protected $flag_Binary = true;

}
