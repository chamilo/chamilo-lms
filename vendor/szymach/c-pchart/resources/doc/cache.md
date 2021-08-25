# Cache operations

[Reference](http://wiki.pchart.net/doc.pcache.pcache.html)

To speed up the process of creating charts, you can store them in the cache files
using the `CpChart\Cache` class. It will create two files - `cache.db` and
`index.db` in a dedicated directory (`app\cache` by default, relative to the library's
root directory), but you can change these using the `$settings` array passed
to the object's constructor.

Should you decide to use the cache component, the following sections describe
how you can do that.

## Using cache to store and retrieve chart data

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Cache;
use CpChart\Data;
use CpChart\Image;

// Standard chart creation
$data = new Data();
$data->addPoints([1, 3, 4, 3, 5]);

$image = new Image(700, 230, $data);
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 11]);
$image->setGraphArea(60, 40, 670, 190);
$image->drawScale();
$image->drawSplineChart();
$image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 100
]);
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "Test of the pCache class", ["R" => 255, "G" => 255, "B" => 255]);

// Create a cache object and store the chart in it
$cache = new Cache([
    // Optionally change the default directory and file names
    'CacheFolder' => 'path/to/your/cache/directory',
    'CacheIndex' => 'name_of_the_index_file.db',
    'CacheDB' => 'name_of_the_database_file.db'
]);
$chartHash = $cache->getHash($data); // Chart dentifier in the cache
$cache->writeToCache($chartHash, $image);

// Create an image file from cache
$cache->saveFromCache($chartHash, "example.drawCachedSpline.png");

// Directly stroke the saved data to the browser
$cache->strokeFromCache($chartHash)

// Automatically choose a way to output stored data
$cache->autoOutput($chartHash)
```

## Removal operations

```php
// Assuming we have $chartHash and $cache variables from the previous example

// This will remove the chart by it's hash
$cache->remove($chartHash);

// This will remove every chart in cache older than the amount of seconds passed
// into the argument's parameter
$cache->removeOlderThan(60 * 60 * 24); // Remove data older than 24 hours

// This flushes the cache completely and regenerates the .db files
$cache->flush();
```

There is also the function called `CpChart\Cache::dbRemoval(array $settings)`,
but it only covers two use cases - removing by chart hash and age. Since there
are dedicated methods for each of them (`remove` and `removeOlderThan`, respectively),
there is no reason to cover it any further.
