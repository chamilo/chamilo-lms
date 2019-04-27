<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Make extends AbstractTag
{

    protected $Id = 'Make';

    protected $Name = 'Make';

    protected $FullName = 'PNG::TextualData';

    protected $GroupName = 'PNG';

    protected $g0 = 'PNG';

    protected $g1 = 'PNG';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Make';

    protected $local_g2 = 'Camera';

}
