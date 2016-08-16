<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ManufactureCode extends AbstractTag
{

    protected $Id = 124;

    protected $Name = 'ManufactureCode';

    protected $FullName = 'Casio::QVCI';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'Manufacture Code';

    protected $flag_Permanent = true;

    protected $MaxLength = 9;

}
