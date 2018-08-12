<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEPreview;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PreviewImageType extends AbstractTag
{

    protected $Id = '0Type';

    protected $Name = 'PreviewImageType';

    protected $FullName = 'MIE::Preview';

    protected $GroupName = 'MIE-Preview';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Preview';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Preview Image Type';

}
