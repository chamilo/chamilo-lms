<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ICCMeta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MediaWeight extends AbstractTag
{

    protected $Id = 'MediaWeight';

    protected $Name = 'MediaWeight';

    protected $FullName = 'ICC_Profile::Metadata';

    protected $GroupName = 'ICC-meta';

    protected $g0 = 'ICC_Profile';

    protected $g1 = 'ICC-meta';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Media Weight';

}
