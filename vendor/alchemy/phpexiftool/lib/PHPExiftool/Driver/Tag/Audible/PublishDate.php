<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Audible;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PublishDate extends AbstractTag
{

    protected $Id = 'pubdate';

    protected $Name = 'PublishDate';

    protected $FullName = 'Audible::Main';

    protected $GroupName = 'Audible';

    protected $g0 = 'Audible';

    protected $g1 = 'Audible';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Publish Date';

    protected $local_g2 = 'Time';

}
