<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BackgroundColor extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'BackgroundColor';

    protected $FullName = 'RIFF::ANIM';

    protected $GroupName = 'RIFF';

    protected $g0 = 'RIFF';

    protected $g1 = 'RIFF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Background Color';

    protected $MaxLength = 4;

}
