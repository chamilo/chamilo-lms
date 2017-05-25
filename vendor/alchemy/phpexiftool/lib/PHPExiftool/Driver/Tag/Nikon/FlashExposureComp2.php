<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashExposureComp2 extends AbstractTag
{

    protected $Id = 1234;

    protected $Name = 'FlashExposureComp2';

    protected $FullName = 'Nikon::ShotInfoD800';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8s';

    protected $Writable = true;

    protected $Description = 'Flash Exposure Comp 2';

    protected $flag_Permanent = true;

}
