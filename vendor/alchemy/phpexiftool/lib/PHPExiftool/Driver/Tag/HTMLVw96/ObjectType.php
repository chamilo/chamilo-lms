<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\HTMLVw96;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ObjectType extends AbstractTag
{

    protected $Id = 'objecttype';

    protected $Name = 'ObjectType';

    protected $FullName = 'HTML::vw96';

    protected $GroupName = 'HTML-vw96';

    protected $g0 = 'HTML';

    protected $g1 = 'HTML-vw96';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Object Type';

}
