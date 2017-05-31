MediaVorus
==========

[![Build Status](https://secure.travis-ci.org/romainneutron/MediaVorus.png?branch=master)](http://travis-ci.org/romainneutron/MediaVorus)

A lib to get every technical informations about your files

#Exemple

```php
use MediaVorus\MediaVorus;
use MediaVorus\Media\Image;

$mediavorus = MediaVorus::create();

$image = $mediavorus->guess('RawCanon.cr2');

echo sprintf("Photo as been taken with a %s Shutter Speed", $Image->getShutterSpeed());
```

#Documentation

Documentation is hosted on Read The Docs http://mediavorus.readthedocs.org/

#License

MediaVorus is released under the [MIT license](http://opensource.org/licenses/MIT)


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/romainneutron/mediavorus/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

