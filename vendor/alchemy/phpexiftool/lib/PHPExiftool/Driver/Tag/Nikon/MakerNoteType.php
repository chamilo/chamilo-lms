<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MakerNoteType extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'MakerNoteType';

    protected $FullName = 'Nikon::AVIVers';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Video';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Maker Note Type';

    protected $flag_Permanent = true;

}
