<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PSP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BitsPerSample extends AbstractTag
{

    protected $Id = 19;

    protected $Name = 'BitsPerSample';

    protected $FullName = 'PSP::Image';

    protected $GroupName = 'PSP';

    protected $g0 = 'PSP';

    protected $g1 = 'PSP';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Bits Per Sample';

}
