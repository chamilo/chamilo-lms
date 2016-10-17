<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leaf;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ToneSpaceFlow extends AbstractTag
{

    protected $Id = 'icc_camera_to_tone_space_flow';

    protected $Name = 'ToneSpaceFlow';

    protected $FullName = 'Leaf::Main';

    protected $GroupName = 'Leaf';

    protected $g0 = 'Leaf';

    protected $g1 = 'Leaf';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'ICC To Tone Space Flow';

}
