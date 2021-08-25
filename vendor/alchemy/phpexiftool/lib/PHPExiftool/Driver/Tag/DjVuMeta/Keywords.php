<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\DjVuMeta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Keywords extends AbstractTag
{

    protected $Id = 'Keywords';

    protected $Name = 'Keywords';

    protected $FullName = 'DjVu::Meta';

    protected $GroupName = 'DjVu-Meta';

    protected $g0 = 'DjVu';

    protected $g1 = 'DjVu-Meta';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Keywords';

}
