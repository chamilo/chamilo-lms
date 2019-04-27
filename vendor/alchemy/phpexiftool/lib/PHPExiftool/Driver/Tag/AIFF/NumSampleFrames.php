<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\AIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NumSampleFrames extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'NumSampleFrames';

    protected $FullName = 'AIFF::Common';

    protected $GroupName = 'AIFF';

    protected $g0 = 'AIFF';

    protected $g1 = 'AIFF';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Num Sample Frames';

}
