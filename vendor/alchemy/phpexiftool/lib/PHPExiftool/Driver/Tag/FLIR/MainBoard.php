<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLIR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MainBoard extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'MainBoard';

    protected $FullName = 'FLIR::Parts';

    protected $GroupName = 'FLIR';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FLIR';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Main Board';

    protected $flag_Permanent = true;

    protected $Index = 12;

}
