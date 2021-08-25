<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ASF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CreationDate extends AbstractTag
{

    protected $Id = 24;

    protected $Name = 'CreationDate';

    protected $FullName = 'ASF::FileProperties';

    protected $GroupName = 'ASF';

    protected $g0 = 'ASF';

    protected $g1 = 'ASF';

    protected $g2 = 'Video';

    protected $Type = 'int64u';

    protected $Writable = false;

    protected $Description = 'Creation Date';

    protected $local_g2 = 'Time';

}
