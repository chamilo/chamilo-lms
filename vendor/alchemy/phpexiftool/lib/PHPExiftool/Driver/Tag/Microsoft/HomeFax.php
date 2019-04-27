<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Microsoft;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HomeFax extends AbstractTag
{

    protected $Id = '{660E04D6-81AB-4977-A09F-82313113AB26} 100';

    protected $Name = 'HomeFax';

    protected $FullName = 'Microsoft::Xtra';

    protected $GroupName = 'Microsoft';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Microsoft';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Home Fax';

}
