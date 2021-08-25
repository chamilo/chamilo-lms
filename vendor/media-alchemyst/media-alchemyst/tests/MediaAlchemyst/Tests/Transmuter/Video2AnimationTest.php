<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Transmuter\Video2Animation;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Specification\Animation;

class Video2AnimationTest extends AbstractAlchemystTester
{
    /**
     * @var Video2Animation
     */
    protected $object;

    /**
     *
     * @var \MediaAlchemyst\Specification\Animation
     */
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp()
    {
        $this->object = new Video2Animation(new DriversContainer(), $this->getFsManager());

        $this->specs = new Animation();
        $this->specs->setDimensions(130, 110);
        $this->specs->setResizeMode(Animation::RESIZE_MODE_OUTBOUND);

        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/Test.ogv');
        $this->dest = __DIR__ . '/../../../files/output_.gif';
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Animation::execute
     * @todo Implement testExecute().
     */
    public function testExecute()
    {
        $this->object->execute($this->specs, $this->source, $this->dest);

        $output = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(130, $output->getWidth());
        $this->assertEquals(110, $output->getHeight());
    }
}
