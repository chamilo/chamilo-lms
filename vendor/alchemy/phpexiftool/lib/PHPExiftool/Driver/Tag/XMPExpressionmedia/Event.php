<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPExpressionmedia;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Event extends AbstractTag
{

    protected $Id = 'Event';

    protected $Name = 'Event';

    protected $FullName = 'XMP::ExpressionMedia';

    protected $GroupName = 'XMP-expressionmedia';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-expressionmedia';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Event';

    protected $flag_Avoid = true;

}
