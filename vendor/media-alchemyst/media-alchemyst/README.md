#MediAlchemyst

A PHP 5.3+ lib to transmute media files.

[![Build Status](https://travis-ci.org/alchemy-fr/Media-Alchemyst.png?branch=master)](http://travis-ci.org/alchemy-fr/Media-Alchemyst)

* Want to extract audio from a video file ?
* Want to convert an office document to an image?
* Want to resize images ?
* Want to generate a Gif animation from a video ?

Media-Alchemyst is a tool to transmute your medias from media-type to
media-type.

## Usage example

```php

use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Animation;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Video;

$alchemyst = Alchemyst::create();

$video = new Video();
$video->setDimensions(320, 240)
    ->setFramerate(15)
    ->setGOPSize(200);

// AMAZING
$alchemyst
    ->turnInto('movie.mp4', 'animation.gif', new Animation())
    ->turnInto('movie.mp4', 'screenshot.jpg', new Image())
    ->turnInto('movie.mp4', 'preview.ogv', $video);

```

## What is currently supported ?

* Working install of FFmpeg (for Audio / Video processing)
* GPAC (for X264 Video processing)
* Perl (for metadata analysis)
* GraphicsMagick and its Gmagick PHP Extension (recommended) or ImageMagick (Image processing)
* Universal Office Converter (unoconv) which supports about 100 different document formats
* SWFTools (for Flash files processing)

## Customize drivers

Drivers preferences can be specified through the `DriversContainer` :

```php
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\DriversContainer;

$drivers = new DriversContainer();
$drivers['configuration'] = array(
    'ffmpeg.threads'               => 4,
    'ffmpeg.ffmpeg.timeout'        => 3600,
    'ffmpeg.ffprobe.timeout'       => 60,
    'ffmpeg.ffmpeg.binaries'       => '/path/to/custom/ffmpeg',
    'ffmpeg.ffprobe.binaries'      => '/path/to/custom/ffprobe',
    'imagine.driver'               => 'imagick',
    'gs.timeout'                   => 60,
    'gs.binaries'                  => '/path/to/custom/gs',
    'mp4box.timeout'               => 60,
    'mp4box.binaries'              => '/path/to/custom/MP4Box',
    'swftools.timeout'             => 60,
    'swftools.pdf2swf.binaries'    => '/path/to/custom/pdf2swf',
    'swftools.swfrender.binaries'  => '/path/to/custom/swfrender',
    'swftools.swfextract.binaries' => '/path/to/custom/swfextract',
    'unoconv.binaries'             => '/path/to/custom/unoconv',
    'unoconv.timeout'              => 60,
);

$alchemyst = new Alchemyst($drivers);
$alchemyst
    ->turnInto('movie.mp4', 'animation.gif', new Animation())
```

## Silex service provider ?

Need a [Silex](silex.sensiolabs.org) service provider ? Of course it's provided !

Please note that Media-Alchemyst service provider requires MediaVorus service
provider.

```php
use Silex\Application;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;

$app = new Application();

$app->register(new PHPExiftoolServiceProvider());
$app->register(new MediaAlchemystServiceProvider());

// Have fun OH YEAH
$app['media-alchemyst']->turnInto('movie.mp4', 'animation.gif', new Animation());
```

You can customize the service provider with any of the following options :

```
$app->register(new MediaVorusServiceProvider(), array(
    'media-alchemyst.configuration' => array(
        'ffmpeg.threads'               => 4,
        'ffmpeg.ffmpeg.timeout'        => 3600,
        'ffmpeg.ffprobe.timeout'       => 60,
        'ffmpeg.ffmpeg.binaries'       => '/path/to/custom/ffmpeg',
        'ffmpeg.ffprobe.binaries'      => '/path/to/custom/ffprobe',
        'imagine.driver'               => 'imagick',
        'gs.timeout'                   => 60,
        'gs.binaries'                  => '/path/to/custom/gs',
        'mp4box.timeout'               => 60,
        'mp4box.binaries'              => '/path/to/custom/MP4Box',
        'swftools.timeout'             => 60,
        'swftools.pdf2swf.binaries'    => '/path/to/custom/pdf2swf',
        'swftools.swfrender.binaries'  => '/path/to/custom/swfrender',
        'swftools.swfextract.binaries' => '/path/to/custom/swfextract',
        'unoconv.binaries'             => '/path/to/custom/unoconv',
        'unoconv.timeout'              => 60,
    ),
    'media-alchemyst.logger' => $logger,  // A PSR Logger
));
```

## License

This is MIT licensed, enjoy :)
