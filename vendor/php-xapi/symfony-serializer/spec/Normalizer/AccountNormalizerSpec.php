<?php

namespace spec\Xabbuh\XApi\Serializer\Symfony\Normalizer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\DataFixtures\AccountFixtures;
use Xabbuh\XApi\Model\IRL;
use XApi\Fixtures\Json\AccountJsonFixtures;

class AccountNormalizerSpec extends ObjectBehavior
{
    function it_is_a_normalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\NormalizerInterface');
    }

    function it_is_a_denormalizer()
    {
        $this->shouldHaveType('Symfony\Component\Serializer\Normalizer\DenormalizerInterface');
    }

    function it_supports_normalizing_accounts()
    {
        $this->supportsNormalization(AccountFixtures::getTypicalAccount())->shouldBe(true);
    }

    function it_denormalizes_accounts()
    {
        $account = $this->denormalize(array('homePage' => 'https://tincanapi.com', 'name' => 'test'), 'Xabbuh\XApi\Model\Account');

        $account->shouldBeAnInstanceOf('Xabbuh\XApi\Model\Account');
        $account->getHomePage()->equals(IRL::fromString('https://tincanapi.com'))->shouldReturn(true);
        $account->getName()->shouldReturn('test');
    }

    function it_supports_denormalizing_accounts()
    {
        $this->supportsDenormalization(AccountJsonFixtures::getTypicalAccount(), 'Xabbuh\XApi\Model\Account')->shouldBe(true);
    }
}
