<?php

namespace spec\Tacker\Normalizer;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;


class EnvfileNormalizerSpec extends ObjectBehavior
{
    /**
     * @param Symfony\Component\Config\FileLocatorInterface $locator
     */
    function let($locator)
    {
        $this->beConstructedWith($locator);
    }

    function it_only_loads_file_once($locator)
    {
        $locator->locate(Argument::any())->shouldBeCalledTimes(1);
        $locator->locate('Envfile') ->willReturn(__DIR__ . '/../Fixtures/Envfile');

        $this->normalize('#ENV_VAR#');
        $this->normalize('#ENV_VAR_2#');
    }

    function it_does_nothing_when_file_doesnt_exists($locator)
    {
        $locator->locate(Argument::any())->willReturn(null);

        $this->normalize('#ENV_VAR#')->shouldReturn('#ENV_VAR#');
        $this->normalize(true)->shouldReturn(true);
    }

    function it_falls_back_on_dist_file($locator)
    {
        $locator->locate('Envfile')->willThrow(new \InvalidArgumentException)
            ->shouldBeCalled();
        $locator->locate('Envfile.dist')->willReturn(__DIR__ . '/../Fixtures/Envfile')
            ->shouldBeCalled();

        $this->normalize('#VALUE#');
    }

    function it_loads_from_file_and_replaces($locator)
    {
        $locator->locate('Envfile') ->willReturn(__DIR__ . '/../Fixtures/Envfile');

        $this->normalize('#ENVFILE_VAR#')->shouldReturn('yeah');
    }
}
