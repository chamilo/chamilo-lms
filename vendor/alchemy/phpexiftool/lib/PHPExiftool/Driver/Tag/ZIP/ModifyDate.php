<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ZIP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ModifyDate extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ModifyDate';

    protected $FullName = 'mixed';

    protected $GroupName = 'ZIP';

    protected $g0 = 'ZIP';

    protected $g1 = 'ZIP';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Modify Date';

    protected $local_g2 = 'Time';

}
