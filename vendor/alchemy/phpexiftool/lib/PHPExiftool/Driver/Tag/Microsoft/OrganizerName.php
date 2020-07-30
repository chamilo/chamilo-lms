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
class OrganizerName extends AbstractTag
{

    protected $Id = '{AAA660F9-9865-458E-B484-01BC7FE3973E} 100';

    protected $Name = 'OrganizerName';

    protected $FullName = 'Microsoft::Xtra';

    protected $GroupName = 'Microsoft';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Microsoft';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Organizer Name';

}
