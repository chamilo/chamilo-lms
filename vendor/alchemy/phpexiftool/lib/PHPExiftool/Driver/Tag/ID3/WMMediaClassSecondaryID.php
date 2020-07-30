<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ID3;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WMMediaClassSecondaryID extends AbstractTag
{

    protected $Id = 'WM_MediaClassSecondaryID';

    protected $Name = 'WM_MediaClassSecondaryID';

    protected $FullName = 'ID3::Private';

    protected $GroupName = 'ID3';

    protected $g0 = 'ID3';

    protected $g1 = 'ID3';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'WM Media Class Secondary ID';

}
