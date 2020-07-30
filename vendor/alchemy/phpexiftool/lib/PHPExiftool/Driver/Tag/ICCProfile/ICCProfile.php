<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCProfile;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ICCProfile extends AbstractTag
{

    protected $Id = 'ICC_Profile';

    protected $Name = 'ICC_Profile';

    protected $FullName = 'Extra';

    protected $GroupName = 'ICC_Profile';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'ICC Profile';

    protected $local_g0 = 'ICC_Profile';

    protected $local_g1 = 'ICC_Profile';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

}
