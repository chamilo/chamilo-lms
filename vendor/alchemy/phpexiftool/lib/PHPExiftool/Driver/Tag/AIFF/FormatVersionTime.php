<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\AIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FormatVersionTime extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'FormatVersionTime';

    protected $FullName = 'AIFF::FormatVers';

    protected $GroupName = 'AIFF';

    protected $g0 = 'AIFF';

    protected $g1 = 'AIFF';

    protected $g2 = 'Other';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Format Version Time';

    protected $local_g2 = 'Time';

}
