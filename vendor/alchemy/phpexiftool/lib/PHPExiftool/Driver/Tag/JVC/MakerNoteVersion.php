<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\JVC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MakerNoteVersion extends AbstractTag
{

    protected $Id = 'VER';

    protected $Name = 'MakerNoteVersion';

    protected $FullName = 'JVC::Text';

    protected $GroupName = 'JVC';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'JVC';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Maker Note Version';

    protected $flag_Permanent = true;

}
