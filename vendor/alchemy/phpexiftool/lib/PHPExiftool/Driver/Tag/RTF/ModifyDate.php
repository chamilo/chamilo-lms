<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RTF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ModifyDate extends AbstractTag
{

    protected $Id = 'revtim';

    protected $Name = 'ModifyDate';

    protected $FullName = 'RTF::Main';

    protected $GroupName = 'RTF';

    protected $g0 = 'RTF';

    protected $g1 = 'RTF';

    protected $g2 = 'Document';

    protected $Type = 'date';

    protected $Writable = false;

    protected $Description = 'Modify Date';

    protected $local_g2 = 'Time';

}
