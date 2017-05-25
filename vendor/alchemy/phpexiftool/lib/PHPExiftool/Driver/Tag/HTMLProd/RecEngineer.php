<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\HTMLProd;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RecEngineer extends AbstractTag
{

    protected $Id = 'recengineer';

    protected $Name = 'RecEngineer';

    protected $FullName = 'HTML::prod';

    protected $GroupName = 'HTML-prod';

    protected $g0 = 'HTML';

    protected $g1 = 'HTML-prod';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Rec Engineer';

}
