<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GammaUnsharpMaskStrength extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'GammaUnsharpMaskStrength';

    protected $FullName = 'CanonVRD::GammaInfo';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'double';

    protected $Writable = true;

    protected $Description = 'Gamma Unsharp Mask Strength';

}
