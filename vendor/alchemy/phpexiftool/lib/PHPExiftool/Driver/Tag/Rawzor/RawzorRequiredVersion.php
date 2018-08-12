<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Rawzor;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawzorRequiredVersion extends AbstractTag
{

    protected $Id = 'RawzorRequiredVersion';

    protected $Name = 'RawzorRequiredVersion';

    protected $FullName = 'Rawzor::Main';

    protected $GroupName = 'Rawzor';

    protected $g0 = 'Rawzor';

    protected $g1 = 'Rawzor';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Rawzor Required Version';

}
