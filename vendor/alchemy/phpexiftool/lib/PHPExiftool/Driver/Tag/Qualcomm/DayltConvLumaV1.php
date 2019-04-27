<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Qualcomm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DayltConvLumaV1 extends AbstractTag
{

    protected $Id = 'daylt_conv_luma_v1';

    protected $Name = 'DayltConvLumaV1';

    protected $FullName = 'Qualcomm::Main';

    protected $GroupName = 'Qualcomm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Qualcomm';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Daylt Conv Luma V1';

    protected $flag_Permanent = true;

}
