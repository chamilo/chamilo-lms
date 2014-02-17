<?php

namespace spec\Tacker\Normalizer;

class EnvironmentNormalizerSpec extends \PhpSpec\ObjectBehavior
{
    function let()
    {
        putenv('TACKER_NORMALIZE=normalized');
        putenv('tacker_lowercase=normalized');
    }

    function letgo()
    {
        putenv('TACKER_NORMALIZE');
        putenv('tacker_lowercase');
    }

    function it_replaces_placeholders()
    {
        $this->normalize('#TACKER_NORMALIZE#')->shouldReturn('normalized');
        $this->normalize('#tacker_lowercase#')->shouldReturn('#tacker_lowercase#');
    }

    function it_does_not_replace_placeholder_if_environment_var_does_not_exists()
    {
        $this->normalize('#DOES_NOT_EXISTS#')->shouldReturn('#DOES_NOT_EXISTS#');
        $this->normalize(true)->shouldReturn(true);
    }
}
