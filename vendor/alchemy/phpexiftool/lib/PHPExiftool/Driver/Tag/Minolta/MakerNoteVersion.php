<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MakerNoteVersion extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'MakerNoteVersion';

    protected $FullName = 'Minolta::Main';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Maker Note Version';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

}
