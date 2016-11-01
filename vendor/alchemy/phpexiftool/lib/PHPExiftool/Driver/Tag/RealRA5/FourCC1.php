<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RealRA5;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FourCC1 extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'FourCC1';

    protected $FullName = 'Real::AudioV5';

    protected $GroupName = 'Real-RA5';

    protected $g0 = 'Real';

    protected $g1 = 'Real-RA5';

    protected $g2 = 'Audio';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Four CC1';

    protected $MaxLength = 4;

}
