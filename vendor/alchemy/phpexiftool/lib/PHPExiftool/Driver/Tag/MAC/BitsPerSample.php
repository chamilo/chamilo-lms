<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MAC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BitsPerSample extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'BitsPerSample';

    protected $FullName = 'APE::NewHeader';

    protected $GroupName = 'MAC';

    protected $g0 = 'APE';

    protected $g1 = 'MAC';

    protected $g2 = 'Audio';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Bits Per Sample';

}
