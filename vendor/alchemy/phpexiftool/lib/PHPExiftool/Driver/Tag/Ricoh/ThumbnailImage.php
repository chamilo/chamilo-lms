<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ThumbnailImage extends AbstractTag
{

    protected $Id = 'thum';

    protected $Name = 'ThumbnailImage';

    protected $FullName = 'Ricoh::AVI';

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Thumbnail Image';

    protected $local_g2 = 'Preview';

    protected $flag_Binary = true;

    protected $flag_Permanent = true;

}
