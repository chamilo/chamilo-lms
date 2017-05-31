<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ocad;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class OcadRevision extends AbstractTag
{

    protected $Id = 'Rev';

    protected $Name = 'OcadRevision';

    protected $FullName = 'JPEG::Ocad';

    protected $GroupName = 'Ocad';

    protected $g0 = 'APP0';

    protected $g1 = 'Ocad';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Ocad Revision';

    protected $MaxLength = 6;

}
