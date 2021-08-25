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
class ChromaticityChannels extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'ChromaticityChannels';

    protected $FullName = 'ICC_Profile::Chromaticity';

    protected $GroupName = 'ICC-chrm';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-chrm';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Chromaticity Channels';

}
