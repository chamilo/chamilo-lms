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
class TL84ConvChrmKCr extends AbstractTag
{

    protected $Id = 'tl84_conv_chrm_k_cr';

    protected $Name = 'TL84ConvChrmKCr';

    protected $FullName = 'Qualcomm::Main';

    protected $GroupName = 'Qualcomm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Qualcomm';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'TL84 Conv Chrm K Cr';

    protected $flag_Permanent = true;

}
