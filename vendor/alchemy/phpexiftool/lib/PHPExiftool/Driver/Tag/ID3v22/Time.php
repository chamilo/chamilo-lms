<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ID3v22;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Time extends AbstractTag
{

    protected $Id = 'TIM';

    protected $Name = 'Time';

    protected $FullName = 'ID3::v2_2';

    protected $GroupName = 'ID3v2_2';

    protected $g0 = 'ID3';

    protected $g1 = 'ID3v2_2';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Time';

    protected $local_g2 = 'Time';

}
