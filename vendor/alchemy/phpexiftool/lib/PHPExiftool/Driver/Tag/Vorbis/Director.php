<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Vorbis;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Director extends AbstractTag
{

    protected $Id = 'DIRECTOR';

    protected $Name = 'Director';

    protected $FullName = 'Vorbis::Comments';

    protected $GroupName = 'Vorbis';

    protected $g0 = 'Vorbis';

    protected $g1 = 'Vorbis';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Director';

}
