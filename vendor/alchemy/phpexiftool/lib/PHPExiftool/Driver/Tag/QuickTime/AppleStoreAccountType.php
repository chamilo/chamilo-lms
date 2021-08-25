<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AppleStoreAccountType extends AbstractTag
{

    protected $Id = 'akID';

    protected $Name = 'AppleStoreAccountType';

    protected $FullName = 'QuickTime::ItemList';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Apple Store Account Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'iTunes',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AOL',
        ),
    );

}
