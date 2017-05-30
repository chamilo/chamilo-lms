<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InternalFlashAE2 extends AbstractTag
{

    protected $Id = 4130;

    protected $Name = 'InternalFlashAE2';

    protected $FullName = 'Olympus::Main';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Internal Flash AE2';

    protected $flag_Permanent = true;

}
