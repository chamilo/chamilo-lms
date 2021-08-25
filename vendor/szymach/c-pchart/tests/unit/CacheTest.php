<?php

namespace CpChart\Test;

use Codeception\Test\Unit;
use CpChart\Cache;
use CpChart\Data;
use CpChart\Image;
use UnitTester;

class CacheTest extends Unit
{
    const CACHE_DB = 'cache.db';
    const INDEX_DB = 'index.db';

    /**
     * @var UnitTester
     */
    protected $tester;

    public function testWritingAndRetrievingOperations()
    {
        list($data, $image) = $this->createImageData();

        // Write to cache
        $cache = new Cache();
        $chartHash = $cache->getHash($data);
        $cache->writeToCache($chartHash, $image);
        $this->tester->seeFileFound($this->getCacheFilePath(self::CACHE_DB));
        $this->tester->seeFileFound($this->getCacheFilePath(self::INDEX_DB));
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));

        // Render and then remove the chart
        $filename = $this->tester->getOutputPathForChart('drawCachedSpline.png');
        $image->render($filename);
        $this->tester->seeFileFound($filename);
        $this->tester->deleteFile($filename);
        $this->tester->cantSeeFileFound($filename);

        // Test retrieving image from cache
        $cache->saveFromCache($chartHash, $filename);
        $this->tester->seeFileFound($filename);
        $this->tester->assertEquals(true, $cache->strokeFromCache($chartHash));
    }

    public function testRemovalOperations()
    {
        list($data, $image) = $this->createImageData();

        // Write to cache
        $cache = new Cache();
        $chartHash = $cache->getHash($data);
        $cache->writeToCache($chartHash, $image);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));

        // Remove by name
        $cache->remove($chartHash);
        $this->tester->assertEquals(false, $cache->isInCache($chartHash));

        // Remove older than x seconds
        $cache->writeToCache($chartHash, $image);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));
        $cache->removeOlderThan(4);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));
        sleep(5);
        $cache->removeOlderThan(4);
        $this->tester->assertEquals(false, $cache->isInCache($chartHash));

        // Flush the cache
        $cache->writeToCache($chartHash, $image);
        $this->tester->assertEquals(true, $cache->isInCache($chartHash));
        $cache->flush();
        $this->tester->assertEquals(false, $cache->isInCache($chartHash));
    }

    protected function _before()
    {
        $this->clearCache();
    }

    protected function _after()
    {
        $this->clearCache();
    }

    private function createImageData()
    {
        $data = new Data();
        $data->addPoints([1, 3, 4, 3, 5]);

        $image = new Image(700, 230, $data);
        $image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
        $image->setGraphArea(60, 40, 670, 190);
        $image->drawScale();
        $image->drawSplineChart();
        $image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL,
            ["StartR" => 0, "StartG" => 0, "StartB" => 0, "EndR" => 50, "EndG" => 50, "EndB" => 50, "Alpha" => 100]
        );
        $image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
        $image->drawText(10, 13, "Test of the pCache class", ["R" => 255, "G" => 255, "B" => 255]);

        return [$data, $image];
    }

    private function clearCache()
    {
        foreach ([self::CACHE_DB, self::INDEX_DB] as $cacheFile) {
            $filename = $this->getCacheFilePath($cacheFile);
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }

    private function getCacheFilePath($filename)
    {
        return sprintf('%s/%s', $this->tester->getCacheDirectory(), $filename);
    }
}
