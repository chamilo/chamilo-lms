<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KodakBordersIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BorderID extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'BorderID';

    protected $FullName = 'Kodak::Borders';

    protected $GroupName = 'KodakBordersIFD';

    protected $g0 = 'Meta';

    protected $g1 = 'KodakBordersIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Border ID';

}
