<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Audible;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CoverArt extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CoverArt';

    protected $FullName = 'mixed';

    protected $GroupName = 'Audible';

    protected $g0 = 'mixed';

    protected $g1 = 'Audible';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Cover Art';

    protected $local_g2 = 'Preview';

    protected $flag_Binary = true;

}
