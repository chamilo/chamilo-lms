<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCChrm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ChromaticityChannel1 extends AbstractTag
{

    protected $Id = 12;

    protected $Name = 'ChromaticityChannel1';

    protected $FullName = 'ICC_Profile::Chromaticity';

    protected $GroupName = 'ICC-chrm';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-chrm';

    protected $g2 = 'Image';

    protected $Type = 'fixed32u';

    protected $Writable = false;

    protected $Description = 'Chromaticity Channel 1';

    protected $MaxLength = 2;

}
