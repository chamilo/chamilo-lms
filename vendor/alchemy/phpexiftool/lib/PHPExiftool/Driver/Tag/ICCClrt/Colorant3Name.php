<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCClrt;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Colorant3Name extends AbstractTag
{

    protected $Id = 88;

    protected $Name = 'Colorant3Name';

    protected $FullName = 'ICC_Profile::ColorantTable';

    protected $GroupName = 'ICC-clrt';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-clrt';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Colorant 3 Name';

    protected $MaxLength = 32;

}
