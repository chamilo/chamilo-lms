<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Theora;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Quality extends AbstractTag
{

    protected $Id = 33;

    protected $Name = 'Quality';

    protected $FullName = 'Theora::Identification';

    protected $GroupName = 'Theora';

    protected $g0 = 'Theora';

    protected $g1 = 'Theora';

    protected $g2 = 'Video';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Quality';

}
