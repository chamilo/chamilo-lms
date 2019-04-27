<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Kodak;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImage extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PreviewImage';

    protected $FullName = 'mixed';

    protected $GroupName = 'Kodak';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Kodak';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Preview Image';

    protected $local_g2 = 'Preview';

    protected $flag_Permanent = true;

    protected $flag_Binary = false;

}
