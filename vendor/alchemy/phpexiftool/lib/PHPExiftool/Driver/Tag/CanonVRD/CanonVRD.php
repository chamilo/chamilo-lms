<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CanonVRD extends AbstractTag
{

    protected $Id = 'CanonVRD';

    protected $Name = 'CanonVRD';

    protected $FullName = 'Extra';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Canon VRD';

    protected $local_g0 = 'CanonVRD';

    protected $local_g1 = 'CanonVRD';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

}
