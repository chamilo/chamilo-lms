<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPF0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BaselineLength extends AbstractTag
{

    protected $Id = 45574;

    protected $Name = 'BaselineLength';

    protected $FullName = 'MPF::Main';

    protected $GroupName = 'MPF0';

    protected $g0 = 'MPF';

    protected $g1 = 'MPF0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Baseline Length';

}
