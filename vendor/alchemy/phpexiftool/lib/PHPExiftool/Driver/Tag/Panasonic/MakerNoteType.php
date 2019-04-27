<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MakerNoteType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'MakerNoteType';

    protected $FullName = 'Panasonic::Type2';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Maker Note Type';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

}
