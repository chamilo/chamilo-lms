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
class UnknownSerial4 extends AbstractTag
{

    protected $Id = 111;

    protected $Name = 'UnknownSerial4';

    protected $FullName = 'FLIR::SerialNums';

    protected $GroupName = 'FLIR';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FLIR';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Unknown Serial 4';

    protected $flag_Permanent = true;

    protected $MaxLength = 11;

}
