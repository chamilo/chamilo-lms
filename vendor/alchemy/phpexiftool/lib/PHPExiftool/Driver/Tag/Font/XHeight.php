<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Font;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class XHeight extends AbstractTag
{

    protected $Id = 'XHeight';

    protected $Name = 'XHeight';

    protected $FullName = 'Font::AFM';

    protected $GroupName = 'Font';

    protected $g0 = 'Font';

    protected $g1 = 'Font';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'X Height';

}
