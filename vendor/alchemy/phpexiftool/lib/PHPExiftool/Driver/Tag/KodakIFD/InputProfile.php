<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KodakIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InputProfile extends AbstractTag
{

    protected $Id = 5001;

    protected $Name = 'InputProfile';

    protected $FullName = 'Kodak::IFD';

    protected $GroupName = 'KodakIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KodakIFD';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Input Profile';

    protected $flag_Binary = true;

    protected $flag_Permanent = true;

}
