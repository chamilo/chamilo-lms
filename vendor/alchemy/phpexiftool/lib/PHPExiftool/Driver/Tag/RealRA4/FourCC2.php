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
class FourCC2 extends AbstractTag
{

    protected $Id = 18;

    protected $Name = 'FourCC2';

    protected $FullName = 'Real::AudioV4';

    protected $GroupName = 'Real-RA4';

    protected $g0 = 'Real';

    protected $g1 = 'Real-RA4';

    protected $g2 = 'Audio';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Four CC2';

    protected $MaxLength = 4;

}
