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
use Xabbuh\XApi\Model\Score;

class ScoreSpec extends ObjectBehavior
{
    function its_properties_can_be_read()
    {
        $this->beConstructedWith(1, 100, 0, 100);

        $this->getScaled()->shouldReturn(1);
        $this->getRaw()->shouldReturn(100);
        $this->getMin()->shouldReturn(0);
        $this->getMax()->shouldReturn(100);
    }

    function it_can_be_constructed_with_a_scaled_value_only()
    {
        $this->beConstructedWith(1);

        $this->getScaled()->shouldReturn(1);
        $this->getRaw()->shouldReturn(null);
        $this->getMin()->shouldReturn(null);
        $this->getMax()->shouldReturn(null);
    }

    function it_can_be_constructed_with_a_raw_value_only()
    {
        $this->beConstructedWith(null, 100);

        $this->getScaled()->shouldReturn(null);
        $this->getRaw()->shouldReturn(100);
        $this->getMin()->shouldReturn(null);
        $this->getMax()->shouldReturn(null);
    }

    function it_can_be_constructed_with_a_min_value_only()
    {
        $this->beConstructedWith(null, null, 0);

        $this->getScaled()->shouldReturn(null);
        $this->getRaw()->shouldReturn(null);
        $this->getMin()->shouldReturn(0);
        $this->getMax()->shouldReturn(null);
    }

    function it_can_be_constructed_with_a_max_value_only()
    {
        $this->beConstructedWith(null, null, null, 100);

        $this->getScaled()->shouldReturn(null);
        $this->getRaw()->shouldReturn(null);
        $this->getMin()->shouldReturn(null);
        $this->getMax()->shouldReturn(100);
    }

    public function it_returns_a_new_instance_with_scaled()
    {
        $score = $this->withScaled(1);

        $this->getScaled()->shouldBeNull();

        $score->shouldNotBe($this);
        $score->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Score');
        $score->getScaled()->shouldReturn(1);
    }

    public function it_returns_a_new_instance_with_raw()
    {
        $score = $this->withRaw(100);

        $this->getRaw()->shouldBeNull();

        $score->shouldNotBe($this);
        $score->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Score');
        $score->getRaw()->shouldReturn(100);
    }

    public function it_returns_a_new_instance_with_min()
    {
        $score = $this->withMin(0);

        $this->getMin()->shouldBeNull();

        $score->shouldNotBe($this);
        $score->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Score');
        $score->getMin()->shouldReturn(0);
    }

    public function it_returns_a_new_instance_with_max()
    {
        $score = $this->withMax(100);

        $this->getMax()->shouldBeNull();

        $score->shouldNotBe($this);
        $score->shouldBeAnInstanceOf('\Xabbuh\XApi\Model\Score');
        $score->getMax()->shouldReturn(100);
    }

    function it_treats_integers_as_floats_when_comparing()
    {
        $this->beConstructedWith(1, 100, 0, 100);

        $score = new Score(1.0, 100.0, 0.0, 100.0);

        $this->equals($score)->shouldReturn(true);
    }
}
