<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Samsung;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Quality extends AbstractTag
{

    protected $Id = 'QLTY';

    protected $Name = 'Quality';

    protected $FullName = 'Samsung::INFO';

    protected $GroupName = 'Samsung';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Samsung';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Quality';

    protected $flag_Permanent = true;

}
