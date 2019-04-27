<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImage extends AbstractTag
{

    protected $Id = 129;

    protected $Name = 'PreviewImage';

    protected $FullName = 'Minolta::Main';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Preview Image';

    protected $local_g2 = 'Preview';

    protected $flag_Permanent = true;

}
