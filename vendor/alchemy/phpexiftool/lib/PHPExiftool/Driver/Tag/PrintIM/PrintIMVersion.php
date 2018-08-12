<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PrintIM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PrintIMVersion extends AbstractTag
{

    protected $Id = 'PrintIMVersion';

    protected $Name = 'PrintIMVersion';

    protected $FullName = 'PrintIM::Main';

    protected $GroupName = 'PrintIM';

    protected $g0 = 'PrintIM';

    protected $g1 = 'PrintIM';

    protected $g2 = 'Printing';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'PrintIM Version';

}
