<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\AdobeCM;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AdobeCMType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'AdobeCMType';

    protected $FullName = 'JPEG::AdobeCM';

    protected $GroupName = 'AdobeCM';

    protected $g0 = 'APP13';

    protected $g1 = 'AdobeCM';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Adobe CM Type';

}
