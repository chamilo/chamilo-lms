<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIELens;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MaxApertureAtMaxFocal extends AbstractTag
{

    protected $Id = 'MaxApertureAtMaxFocal';

    protected $Name = 'MaxApertureAtMaxFocal';

    protected $FullName = 'MIE::Lens';

    protected $GroupName = 'MIE-Lens';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Lens';

    protected $g2 = 'Camera';

    protected $Type = 'rational64u';

    protected $Writable = true;

    protected $Description = 'Max Aperture At Max Focal';

}
