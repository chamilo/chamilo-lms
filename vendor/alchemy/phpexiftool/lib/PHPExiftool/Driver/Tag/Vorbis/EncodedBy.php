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
class EncodedBy extends AbstractTag
{

    protected $Id = 'ENCODED_BY';

    protected $Name = 'EncodedBy';

    protected $FullName = 'Vorbis::Comments';

    protected $GroupName = 'Vorbis';

    protected $g0 = 'Vorbis';

    protected $g1 = 'Vorbis';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Encoded By';

}
