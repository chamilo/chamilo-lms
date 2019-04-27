<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SonyDateTime2 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SonyDateTime2';

    protected $FullName = 'Sony::Tag9050';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Sony Date Time 2';

    protected $local_g2 = 'Time';

    protected $flag_Permanent = true;

    protected $MaxLength = 6;

}
