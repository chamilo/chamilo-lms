<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nintendo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ModelID extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'ModelID';

    protected $FullName = 'Nintendo::CameraInfo';

    protected $GroupName = 'Nintendo';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nintendo';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Model ID';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

}
