<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SR2;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SR2SubIFDLength extends AbstractTag
{

    protected $Id = 29185;

    protected $Name = 'SR2SubIFDLength';

    protected $FullName = 'Sony::SR2Private';

    protected $GroupName = 'SR2';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'SR2';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'SR2 Sub IFD Length';

    protected $flag_Permanent = true;

}
