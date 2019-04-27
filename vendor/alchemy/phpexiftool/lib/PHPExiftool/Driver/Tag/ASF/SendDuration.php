<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ASF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SendDuration extends AbstractTag
{

    protected $Id = 48;

    protected $Name = 'SendDuration';

    protected $FullName = 'ASF::FileProperties';

    protected $GroupName = 'ASF';

    protected $g0 = 'ASF';

    protected $g1 = 'ASF';

    protected $g2 = 'Video';

    protected $Type = 'int64u';

    protected $Writable = false;

    protected $Description = 'Send Duration';

}
