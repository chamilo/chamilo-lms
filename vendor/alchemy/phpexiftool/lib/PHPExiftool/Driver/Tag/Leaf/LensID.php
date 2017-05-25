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
class LensID extends AbstractTag
{

    protected $Id = 'CameraObj_lens_ID';

    protected $Name = 'LensID';

    protected $FullName = 'Leaf::CameraSetup';

    protected $GroupName = 'Leaf';

    protected $g0 = 'Leaf';

    protected $g1 = 'Leaf';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Lens ID';

}
