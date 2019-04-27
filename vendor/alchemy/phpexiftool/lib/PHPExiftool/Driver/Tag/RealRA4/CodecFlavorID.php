<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RealRA4;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CodecFlavorID extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'CodecFlavorID';

    protected $FullName = 'Real::AudioV4';

    protected $GroupName = 'Real-RA4';

    protected $g0 = 'Real';

    protected $g1 = 'Real-RA4';

    protected $g2 = 'Audio';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Codec Flavor ID';

}
