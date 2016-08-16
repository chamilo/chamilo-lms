<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\AFCP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Text extends AbstractTag
{

    protected $Id = 'TEXT';

    protected $Name = 'Text';

    protected $FullName = 'AFCP::Main';

    protected $GroupName = 'AFCP';

    protected $g0 = 'AFCP';

    protected $g1 = 'AFCP';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Text';

}
