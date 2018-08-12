<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEUTM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class UTMMapDatum extends AbstractTag
{

    protected $Id = 'Datum';

    protected $Name = 'UTMMapDatum';

    protected $FullName = 'MIE::UTM';

    protected $GroupName = 'MIE-UTM';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-UTM';

    protected $g2 = 'Location';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'UTM Map Datum';

}
