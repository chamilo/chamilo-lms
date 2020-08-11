<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IPTC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class IPTC extends AbstractTag
{

    protected $Id = 'IPTC';

    protected $Name = 'IPTC';

    protected $FullName = 'Extra';

    protected $GroupName = 'IPTC';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'IPTC';

    protected $local_g0 = 'IPTC';

    protected $local_g1 = 'IPTC';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

}
