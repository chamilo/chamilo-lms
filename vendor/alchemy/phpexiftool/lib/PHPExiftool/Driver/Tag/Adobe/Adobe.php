<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Adobe;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Adobe extends AbstractTag
{

    protected $Id = 'Adobe';

    protected $Name = 'Adobe';

    protected $FullName = 'Extra';

    protected $GroupName = 'Adobe';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Adobe';

    protected $local_g0 = 'APP14';

    protected $local_g1 = 'Adobe';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

}
