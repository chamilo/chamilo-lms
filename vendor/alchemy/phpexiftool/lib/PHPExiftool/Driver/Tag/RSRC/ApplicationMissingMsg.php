<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RSRC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ApplicationMissingMsg extends AbstractTag
{

    protected $Id = 'STR _0xbff3';

    protected $Name = 'ApplicationMissingMsg';

    protected $FullName = 'RSRC::Main';

    protected $GroupName = 'RSRC';

    protected $g0 = 'RSRC';

    protected $g1 = 'RSRC';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Application Missing Msg';

}
