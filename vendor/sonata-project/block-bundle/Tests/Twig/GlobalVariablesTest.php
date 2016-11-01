<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Twig\Extension;

use Sonata\BlockBundle\Twig\GlobalVariables;

class GlobalVariablesTest extends \PHPUnit_Framework_TestCase
{
    public function testGlobalVariables()
    {
        $variables = new GlobalVariables(array());

        $this->assertEmpty($variables->getTemplates());
    }
}
