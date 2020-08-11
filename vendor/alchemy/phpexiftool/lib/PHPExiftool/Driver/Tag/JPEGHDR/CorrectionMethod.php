<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\JPEGHDR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CorrectionMethod extends AbstractTag
{

    protected $Id = 'cor';

    protected $Name = 'CorrectionMethod';

    protected $FullName = 'JPEG::HDR';

    protected $GroupName = 'JPEG-HDR';

    protected $g0 = 'APP11';

    protected $g1 = 'JPEG-HDR';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Correction Method';

}
