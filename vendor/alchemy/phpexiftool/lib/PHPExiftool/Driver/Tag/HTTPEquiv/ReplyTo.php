<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\HTTPEquiv;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ReplyTo extends AbstractTag
{

    protected $Id = 'reply-to';

    protected $Name = 'ReplyTo';

    protected $FullName = 'HTML::equiv';

    protected $GroupName = 'HTTP-equiv';

    protected $g0 = 'HTML';

    protected $g1 = 'HTTP-equiv';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Reply To';

}
