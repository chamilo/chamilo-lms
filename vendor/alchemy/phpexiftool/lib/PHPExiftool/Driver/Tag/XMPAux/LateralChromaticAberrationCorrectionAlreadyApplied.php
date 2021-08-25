<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPAux;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LateralChromaticAberrationCorrectionAlreadyApplied extends AbstractTag
{

    protected $Id = 'LateralChromaticAberrationCorrectionAlreadyApplied';

    protected $Name = 'LateralChromaticAberrationCorrectionAlreadyApplied';

    protected $FullName = 'XMP::aux';

    protected $GroupName = 'XMP-aux';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-aux';

    protected $g2 = 'Camera';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Lateral Chromatic Aberration Correction Already Applied';

}
