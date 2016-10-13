<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEGeo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Country extends AbstractTag
{

    protected $Id = 'Country';

    protected $Name = 'Country';

    protected $FullName = 'MIE::Geo';

    protected $GroupName = 'MIE-Geo';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Geo';

    protected $g2 = 'Location';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Country';

}
