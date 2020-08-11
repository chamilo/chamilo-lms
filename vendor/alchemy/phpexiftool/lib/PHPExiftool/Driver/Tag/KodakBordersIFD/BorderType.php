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
class BorderType extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'BorderType';

    protected $FullName = 'Kodak::Borders';

    protected $GroupName = 'KodakBordersIFD';

    protected $g0 = 'Meta';

    protected $g1 = 'KodakBordersIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Border Type';

}
