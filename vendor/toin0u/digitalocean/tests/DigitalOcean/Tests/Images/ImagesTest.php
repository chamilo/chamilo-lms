<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\Images;

use DigitalOcean\Tests\TestCase;
use DigitalOcean\Images\Images;
use DigitalOcean\Images\ImagesActions;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class ImagesTest extends TestCase
{
    protected $imageId;
    protected $image;
    protected $imageBuildQueryMethod;

    protected function setUp()
    {
        $this->imageId  = 123;

        $this->images = new Images($this->getMockCredentials(), $this->getMockAdapter($this->never()));
        $this->imageBuildQueryMethod = new \ReflectionMethod(
            $this->images, 'buildQuery'
        );
        $this->imageBuildQueryMethod->setAccessible(true);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMEssage Impossible to process this query: https://api.digitalocean.com/droplets/?client_id=foo&api_key=bar
     */
    public function testProcessQuery()
    {
        $images = new Images($this->getMockCredentials(), $this->getMockAdapterReturns(null));
        $images = $images->getAll();
    }

    public function testGetAllUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/images/?client_id=foo&api_key=bar',
            $this->imageBuildQueryMethod->invoke($this->images)
        );
    }

    public function testGetAll()
    {
        $response = <<<JSON
{"status":"OK","images":[{"id":429,"name":"Real Backup 10242011","distribution":"Ubuntu"},{"id":430,"name":"test233","distribution":"Ubuntu"},{"id":431,"name":"test888","distribution":"Ubuntu"},{"id":442,"name":"tesah22","distribution":"Ubuntu"},{"id":443,"name":"testah33","distribution":"Ubuntu"},{"id":444,"name":"testah44","distribution":"Ubuntu"},{"id":447,"name":"ahtest55","distribution":"Ubuntu"},{"id":448,"name":"ahtest66","distribution":"Ubuntu"},{"id":449,"name":"ahtest77","distribution":"Ubuntu"},{"id":458,"name":"Rails3-1Ruby1-9-2","distribution":"Ubuntu"},{"id":466,"name":"NYTD Backup 1-18-2012","distribution":"Ubuntu"},{"id":478,"name":"NLP Final","distribution":"Ubuntu"},{"id":540,"name":"API - Final","distribution":"Ubuntu"},{"id":577,"name":"test1-1","distribution":"Ubuntu"},{"id":578,"name":"alec snapshot1","distribution":"Ubuntu"}]}
JSON
        ;

        $images = new Images($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $images = $images->getAll();

        $this->assertTrue(is_object($images));
        $this->assertEquals('OK', $images->status);
        $this->assertCount(15, $images->images);

        $image1 = $images->images[0];
        $this->assertSame(429, $image1->id);
        $this->assertSame('Real Backup 10242011', $image1->name);
        $this->assertSame('Ubuntu', $image1->distribution);

        $image2 = $images->images[1];
        $this->assertSame(430, $image2->id);
        $this->assertSame('test233', $image2->name);
        $this->assertSame('Ubuntu', $image2->distribution);

        $image15 = $images->images[14];
        $this->assertSame(578, $image15->id);
        $this->assertSame('alec snapshot1', $image15->name);
        $this->assertSame('Ubuntu', $image15->distribution);
    }

    public function testGetMyImagesUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/images/?filter=my_images&client_id=foo&api_key=bar',
            $this->imageBuildQueryMethod->invoke(
                $this->images, null, null, array('filter' => ImagesActions::ACTION_FILTER_MY_IMAGES)
            )
        );
    }

    public function testGetMyImages()
    {
        $response = <<<JSON
{"status":"OK","images":[{"id":429,"name":"Real Backup 10242011","distribution":"Ubuntu"},{"id":430,"name":"test233","distribution":"Ubuntu"},{"id":431,"name":"test888","distribution":"Ubuntu"},{"id":442,"name":"tesah22","distribution":"Ubuntu"},{"id":443,"name":"testah33","distribution":"Ubuntu"},{"id":444,"name":"testah44","distribution":"Ubuntu"},{"id":447,"name":"ahtest55","distribution":"Ubuntu"},{"id":448,"name":"ahtest66","distribution":"Ubuntu"},{"id":449,"name":"ahtest77","distribution":"Ubuntu"},{"id":458,"name":"Rails3-1Ruby1-9-2","distribution":"Ubuntu"},{"id":466,"name":"NYTD Backup 1-18-2012","distribution":"Ubuntu"},{"id":478,"name":"NLP Final","distribution":"Ubuntu"},{"id":540,"name":"API - Final","distribution":"Ubuntu"},{"id":577,"name":"test1-1","distribution":"Ubuntu"},{"id":578,"name":"alec snapshot1","distribution":"Ubuntu"}]}
JSON
        ;

        $images   = new Images($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $myImages = $images->getMyImages();

        $this->assertTrue(is_object($myImages));
        $this->assertEquals('OK', $myImages->status);
        $this->assertCount(15, $myImages->images);
    }

    public function testGetGlobalUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/images/?filter=global&client_id=foo&api_key=bar',
            $this->imageBuildQueryMethod->invoke(
                $this->images, null, null, array('filter' => ImagesActions::ACTION_FILTER_GLOBAL)
            )
        );
    }

    public function testGetGlobal()
    {
        $response = <<<JSON
{"status":"OK","images":[{"id":429,"name":"Real Backup 10242011","distribution":"Ubuntu"},{"id":430,"name":"test233","distribution":"Ubuntu"},{"id":431,"name":"test888","distribution":"Ubuntu"},{"id":442,"name":"tesah22","distribution":"Ubuntu"},{"id":443,"name":"testah33","distribution":"Ubuntu"},{"id":444,"name":"testah44","distribution":"Ubuntu"},{"id":447,"name":"ahtest55","distribution":"Ubuntu"},{"id":448,"name":"ahtest66","distribution":"Ubuntu"},{"id":449,"name":"ahtest77","distribution":"Ubuntu"},{"id":458,"name":"Rails3-1Ruby1-9-2","distribution":"Ubuntu"},{"id":466,"name":"NYTD Backup 1-18-2012","distribution":"Ubuntu"},{"id":478,"name":"NLP Final","distribution":"Ubuntu"},{"id":540,"name":"API - Final","distribution":"Ubuntu"},{"id":577,"name":"test1-1","distribution":"Ubuntu"},{"id":578,"name":"alec snapshot1","distribution":"Ubuntu"}]}
JSON
        ;

        $images       = new Images($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $globalImages = $images->getGlobal();

        $this->assertTrue(is_object($globalImages));
        $this->assertEquals('OK', $globalImages->status);
        $this->assertCount(15, $globalImages->images);
    }

    public function testShowUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/images/123/?client_id=foo&api_key=bar',
            $this->imageBuildQueryMethod->invoke($this->images, $this->imageId)
        );
    }

    public function testShow()
    {
        $response = <<<JSON
{"status":"OK","image":{"id":429,"name":"Real Backup 10242011","distribution":"Ubuntu"}}
JSON
        ;

        $images = new Images($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $show   = $images->show($this->imageId);

        $this->assertTrue(is_object($show));
        $this->assertEquals('OK', $show->status);
        $this->assertSame(429, $show->image->id);
        $this->assertSame('Real Backup 10242011', $show->image->name);
        $this->assertSame('Ubuntu', $show->image->distribution);
    }

    public function testDestroyUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/images/123/destroy/?client_id=foo&api_key=bar',
            $this->imageBuildQueryMethod->invoke($this->images, $this->imageId, ImagesActions::ACTION_DESTROY_IMAGE)
        );
    }

    public function testDestroy()
    {
        $response = <<<JSON
{"status":"OK"}
JSON
        ;

        $images  = new Images($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $destroy = $images->destroy($this->imageId);

        $this->assertTrue(is_object($destroy));
        $this->assertEquals('OK', $destroy->status);
    }

    public function testTransfertUrl()
    {
        $newRegion = array(
            'region_id' => 123,
        );

        $this->assertEquals(
            'https://api.digitalocean.com/images/123/transfert/?region_id=123&client_id=foo&api_key=bar',
            $this->imageBuildQueryMethod->invoke(
                $this->images, $this->imageId, ImagesActions::ACTION_TRANSFERT, $newRegion
            )
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide an integer "region_id".
     */
    public function testTransfertThrowsRegionIdInvalidArgumentException()
    {
        $this->images->transfert($this->imageId, array());
    }

    public function testTransfert()
    {
        $response = <<<JSON
{"status":"OK","event_id":7501}
JSON
        ;

        $images = new Images($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $image  = $images->transfert($this->imageId, array('region_id' => 123));

        $this->assertTrue(is_object($image));
        $this->assertEquals('OK', $image->status);
        $this->assertSame(7501, $image->event_id);
    }
}
