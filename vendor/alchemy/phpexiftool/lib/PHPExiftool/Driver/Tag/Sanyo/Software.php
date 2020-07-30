<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sanyo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Software extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Software';

    protected $FullName = 'Sanyo::MP4';

    protected $GroupName = 'Sanyo';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sanyo';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Software';

    protected $flag_Permanent = true;

    protected $MaxLength = 32;

}
