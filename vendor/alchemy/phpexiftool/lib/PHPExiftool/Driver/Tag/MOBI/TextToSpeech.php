<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MOBI;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class TextToSpeech extends AbstractTag
{

    protected $Id = 404;

    protected $Name = 'TextToSpeech';

    protected $FullName = 'Palm::EXTH';

    protected $GroupName = 'MOBI';

    protected $g0 = 'Palm';

    protected $g1 = 'MOBI';

    protected $g2 = 'Document';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Text To Speech';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Enabled',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Disabled',
        ),
    );

}
