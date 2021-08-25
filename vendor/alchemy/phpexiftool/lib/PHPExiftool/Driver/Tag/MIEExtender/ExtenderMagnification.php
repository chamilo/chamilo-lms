<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEExtender;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExtenderMagnification extends AbstractTag
{

    protected $Id = 'Magnification';

    protected $Name = 'ExtenderMagnification';

    protected $FullName = 'MIE::Extender';

    protected $GroupName = 'MIE-Extender';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Extender';

    protected $g2 = 'Camera';

    protected $Type = 'rational64s';

    protected $Writable = true;

    protected $Description = 'Extender Magnification';

}
