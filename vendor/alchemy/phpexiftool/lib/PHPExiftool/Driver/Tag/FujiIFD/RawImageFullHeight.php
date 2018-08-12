<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FujiIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawImageFullHeight extends AbstractTag
{

    protected $Id = 61442;

    protected $Name = 'RawImageFullHeight';

    protected $FullName = 'FujiFilm::IFD';

    protected $GroupName = 'FujiIFD';

    protected $g0 = 'RAF';

    protected $g1 = 'FujiIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Raw Image Full Height';

}
