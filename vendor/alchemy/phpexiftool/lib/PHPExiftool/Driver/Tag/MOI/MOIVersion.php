<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MOI;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MOIVersion extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'MOIVersion';

    protected $FullName = 'MOI::Main';

    protected $GroupName = 'MOI';

    protected $g0 = 'MOI';

    protected $g1 = 'MOI';

    protected $g2 = 'Video';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'MOI Version';

    protected $MaxLength = 2;

}
