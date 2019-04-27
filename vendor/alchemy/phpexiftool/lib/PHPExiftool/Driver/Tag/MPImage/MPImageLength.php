<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPImage;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MPImageLength extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'MPImageLength';

    protected $FullName = 'MPF::MPImage';

    protected $GroupName = 'MPImage';

    protected $g0 = 'MPF';

    protected $g1 = 'MPImage';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'MP Image Length';

}
