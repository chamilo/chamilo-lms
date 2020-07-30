<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Real;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class URL extends AbstractTag
{

    protected $Id = 'url';

    protected $Name = 'URL';

    protected $FullName = 'Real::Metafile';

    protected $GroupName = 'Real';

    protected $g0 = 'Real';

    protected $g1 = 'Real';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'URL';

}
