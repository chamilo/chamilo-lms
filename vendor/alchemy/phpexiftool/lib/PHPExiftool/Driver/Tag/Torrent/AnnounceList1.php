<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Torrent;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AnnounceList1 extends AbstractTag
{

    protected $Id = 'announce-list';

    protected $Name = 'AnnounceList1';

    protected $FullName = 'Torrent::Main';

    protected $GroupName = 'Torrent';

    protected $g0 = 'Torrent';

    protected $g1 = 'Torrent';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Announce List 1';

}
