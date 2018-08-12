<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\HP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FirmwareVersion extends AbstractTag
{

    protected $Id = 'FWRV';

    protected $Name = 'FirmwareVersion';

    protected $FullName = 'HP::TDHD';

    protected $GroupName = 'HP';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'HP';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Firmware Version';

    protected $flag_Permanent = true;

}
