<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\VCard;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Nickname extends AbstractTag
{

    protected $Id = 'Nickname';

    protected $Name = 'Nickname';

    protected $FullName = 'VCard::Main';

    protected $GroupName = 'VCard';

    protected $g0 = 'VCard';

    protected $g1 = 'VCard';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Nickname';

}
