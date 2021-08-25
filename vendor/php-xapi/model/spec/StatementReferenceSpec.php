<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Xabbuh\XApi\Model;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementObject;
use Xabbuh\XApi\Model\StatementReference;

class StatementReferenceSpec extends ObjectBehavior
{
    function it_is_an_xapi_object()
    {
        $this->beConstructedWith(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'));
        $this->shouldHaveType(StatementObject::class);
    }

    function it_is_equal_to_another_reference_with_the_same_statement_id()
    {
        $this->beConstructedWith(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'));

        $statementReference = new StatementReference(StatementId::fromString('16fd2706-8baf-433b-82eb-8c7fada847da'));

        $this->equals($statementReference)->shouldReturn(true);
    }
}
