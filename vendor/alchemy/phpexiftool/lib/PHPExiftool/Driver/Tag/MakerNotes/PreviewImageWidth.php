<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MakerNotes;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImageWidth extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'PreviewImageWidth';

    protected $FullName = 'QuickTime::Flip';

    protected $GroupName = 'MakerNotes';

    protected $g0 = 'QuickTime';

    protected $g1 = 'MakerNotes';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Preview Image Width';

}
