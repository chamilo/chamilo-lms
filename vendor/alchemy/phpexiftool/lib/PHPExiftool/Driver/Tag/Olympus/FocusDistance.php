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
class FocusDistance extends AbstractTag
{

    protected $Id = 773;

    protected $Name = 'FocusDistance';

    protected $FullName = 'Olympus::FocusInfo';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'rational64u';

    protected $Writable = true;

    protected $Description = 'Focus Distance';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

}
