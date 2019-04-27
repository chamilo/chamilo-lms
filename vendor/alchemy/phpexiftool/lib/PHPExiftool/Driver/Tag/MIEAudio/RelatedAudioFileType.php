<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEAudio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RelatedAudioFileType extends AbstractTag
{

    protected $Id = '0Type';

    protected $Name = 'RelatedAudioFileType';

    protected $FullName = 'MIE::Audio';

    protected $GroupName = 'MIE-Audio';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Audio';

    protected $g2 = 'Audio';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Related Audio File Type';

}
