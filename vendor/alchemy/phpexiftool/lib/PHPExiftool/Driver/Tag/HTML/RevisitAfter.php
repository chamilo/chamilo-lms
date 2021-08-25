<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\HTML;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RevisitAfter extends AbstractTag
{

    protected $Id = 'revisit-after';

    protected $Name = 'RevisitAfter';

    protected $FullName = 'HTML::Main';

    protected $GroupName = 'HTML';

    protected $g0 = 'HTML';

    protected $g1 = 'HTML';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Revisit After';

}
