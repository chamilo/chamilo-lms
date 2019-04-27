<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KodakEffectsIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DigitalEffectsVersion extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'DigitalEffectsVersion';

    protected $FullName = 'Kodak::SpecialEffects';

    protected $GroupName = 'KodakEffectsIFD';

    protected $g0 = 'Meta';

    protected $g1 = 'KodakEffectsIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Digital Effects Version';

}
