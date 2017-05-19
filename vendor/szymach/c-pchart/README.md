Table of contents:
==================
* [About](#about)
* [License](#license)
* [Contributing](#contributing)
* [Installation](#installation-via-composer)
* [Usage](#usage)
    - [Draw a chart through Image class only](#draw-a-chart-through-image-class-only)
    - [Draw a chart with a dedicated class](#draw-a-chart-with-a-dedicated-class)
    - [Notes](#notes)
* [Changelog](#changelog)
* [References](#references)
* [Links](#links)

About:
=====

This project is no longer actively developed, since I do not use it personally and decided
that I have already put enough work into modernizing it. Should any issues or errors arise,
I will still try to resolve them as soon as I can, so the base support will still be maintained.

A project bringing Composer support and some basic PHP 5 standards to pChart 2.0 library.
The aim is to allow pChart integration into modern frameworks like Symfony2.

This is the 2.0 version, which aims to further update the code, but without changing 
the functionality if possible. It will introduce some minor backwards compatibility breaks,
so if that's a concern, use the 1.* version.

What was done:

- Updated the supported PHP version to 5.5.

- Made a full port of the library's functionality.

- Defined and added namespaces to all classes.

- Replaced all `exit()` / `die()` commands with `throw` statements to allow a degree of error control.

- Refactored the code to meet PSR-2 standard and added annotations (as best as I could figure them out).
to methods Also, typehinting was added to methods where possible, so some backwards compatibility breaks
may occur.

- Added a factory service for loading the classes.

- Moved all constants to a single file `src/Resources/data/constants.php`. This file is *required*
for the library to function and is now loaded via Composer.

License:
========

It was previously stated that this package is on [MIT](https://opensource.org/licenses/MIT) license, which did not meet the requirements
set by the original author. It is now under the [GNU GPL v3](http://www.gnu.org/licenses/gpl-3.0.html)
license, so if you wish to use it in a commercial project, you need to pay an [appropriate fee](http://www.pchart.net/license).

Contributing:
=============

If you wish to contribute to the `1.*` version, there is a branch called `legacy` to which you
may submit pull requests. Otherwise feel free to use the `master` branch.

Installation (via Composer):
============================

For composer installation, add:

```json
"require": {
    "szymach/c-pchart": "~2.0@dev"
},
```

to your composer.json file and update your dependencies. Or you can run:

```sh
$ composer require szymach/c-pchart
```

in your project directory, where the composer.json file is.

After that, all classes are available under `CpChart\Chart` namespace or
`CpChart\Factory` for the factory.

Usage:
======

Now you can autoload or use the classes via their namespaces. If you want to, you
may utilize the provided factory class. Below are examples of how to use the library,
the charts themselves are borrowed from the official documentation.

Draw a chart through Image class only
---------------------------------------

Not all charts need to be created through a seperate class (ex. bar or spline charts),
some are created via the Image class (check the official documentation before drawing).
An example for a spline chart below:

```php
require __DIR__.'/../vendor/autoload.php';

use CpChart\Factory\Factory;
use Exception;

try {
    // Create a factory class - it will load necessary files automatically,
    // otherwise you will need to add them on your own
    $factory = new Factory();
    $myData = $factory->newData(array(), "Serie1");

    // Create the image and set the data
    $myPicture = $factory->newImage(700, 230, $myData);
    $myPicture->setShadow(
        true,
        array("X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 20)
    );

    // 1st spline drawn in white with control points visible
    $firstCoordinates = array(array(40, 80), array(280, 60), array(340, 166), array(590, 120));
    $fistSplineSettings = array("R" => 255, "G" => 255, "B" => 255, "ShowControl" => true);
    $myPicture->drawSpline($firstCoordinates, $fistSplineSettings);

    // 2nd spline dashed drawn in white with control points visible
    $secondCoordinates = array(array(250, 50), array(250, 180), array(350, 180), array(350, 50));
    $secondSplineSettings = array(
        "R" => 255,
        "G" => 255,
        "B" => 255,
        "ShowControl" => true,
        "Ticks" => 4
    );
    $myPicture->drawSpline($secondCoordinates, $secondSplineSettings);

    // Output the chart to the browser
    $myPicture->Render("example.drawSpline.png");
    $myPicture->Stroke();
} catch (Exception $ex) {
    echo sprintf('There was an error: %s', $ex->getMessage());
}
```

Draw a chart with a dedicated class:
------------------------------------

Some charts require using a dedicated class, which you can create via the factory.
Notice that you specify the type of chart, not the class name. An example for a pie
chart below:

```php
require __DIR__.'/../vendor/autoload.php';

use CpChart\Chart\Pie;
use CpChart\Factory\Factory;
use Exception;

try {
    $factory = new Factory();

    // Create and populate data
    $myData = $factory->newData(array(40, 60, 15, 10, 6, 4), "ScoreA");
    $myData->setSerieDescription("ScoreA", "Application A");

    // Define the absissa serie
    $myData->addPoints(array("<10", "10<>20", "20<>40", "40<>60", "60<>80", ">80"), "Labels");
    $myData->setAbscissa("Labels");

    // Create the image
    $myPicture = $factory->newImage(700, 230, $myData);

    // Draw a solid background
    $backgroundSettings = array(
        "R" => 173,
        "G" => 152,
        "B" => 217,
        "Dash" => 1,
        "DashR" => 193,
        "DashG" => 172,
        "DashB" => 237
    );
    $myPicture->drawFilledRectangle(0, 0, 700, 230, $backgroundSettings);

    //Draw a gradient overlay
    $gradientSettings = array(
        "StartR" => 209,
        "StartG" => 150,
        "StartB" => 231,
        "EndR" => 111,
        "EndG" => 3,
        "EndB" => 138,
        "Alpha" => 50
    );
    $myPicture->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, $gradientSettings);
    $myPicture->drawGradientArea(
        0,
        0,
        700,
        20,
        DIRECTION_VERTICAL,
        array(
            "StartR" => 0,
            "StartG" => 0,
            "StartB" => 0,
            "EndR" => 50,
            "EndG" => 50,
            "EndB" => 50,
            "Alpha" => 100
        )
    );

    // Add a border to the picture
    $myPicture->drawRectangle(0, 0, 699, 229, array("R" => 0, "G" => 0, "B" => 0));

    // Write the picture title
    $myPicture->setFontProperties(array("FontName" => "Silkscreen.ttf", "FontSize" => 6));
    $myPicture->drawText(10, 13, "pPie - Draw 2D pie charts", array("R" => 255, "G" => 255, "B" => 255));

    // Set the default font properties
    $myPicture->setFontProperties(
        array("FontName" => "Forgotte.ttf", "FontSize" => 10, "R" => 80, "G" => 80, "B" => 80)
    );

    // Enable shadow computing
    $myPicture->setShadow(
        true,
        array("X" => 2, "Y" => 2, "R" => 150, "G" => 150, "B" => 150, "Alpha" => 100)
    );
    $myPicture->drawText(
        140,
        200,
        "Single AA pass",
        array("R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE)
    );

    // Create and draw the chart
    /* @var $pieChart CpPie */
    $pieChart = $factory->newChart("pie", $myPicture, $myData);
    $pieChart->draw2DPie(140, 125, array("SecondPass" => false));
    $pieChart->draw2DPie(340, 125, array("DrawLabels" => true, "Border" => true));
    $pieChart->draw2DPie(
        540,
        125,
        array(
            "DataGapAngle" => 10,
            "DataGapRadius" => 6,
            "Border" => true,
            "BorderR" => 255,
            "BorderG" => 255,
            "BorderB" => 255
        )
    );
    $myPicture->drawText(
        540,
        200,
        "Extended AA pass / Splitted",
        array("R" => 0, "G" => 0, "B" => 0, "Align" => TEXT_ALIGN_TOPMIDDLE)
    );

    // Save the chart to a test directory and output it to a browser
    $pieChart->pChartObject->Render("charts/example.draw2DPie.png");
    $pieChart->pChartObject->stroke();
} catch (Exception $ex) {
    echo sprintf('There was an error: %s', $ex->getMessage());
}
```

Notes:
------

Basically, all should work as defined in the pChart 2.0 documentation with added
support for try/catch functionality. The factory class has methods to load all types of
classes present in the pChart library.

**IMPORTANT!** If you want to use any of the fonts or palletes files, provide only
the name of the actual file, do not add the 'fonts' or 'palettes' folder to the
string given into the function. If you want to load them from a different directory
than the default, you need to add the full path to the file (ex. `__DIR__.'/folder/to/my/palletes`).

Changelog
=========
1.0 Stable version with basic functionality.

1.1 Added factory service.

1.1.1 Changed chart loading via factory a bit (see class annotations).

1.1.2 Updated service class with Exception handling regarding missing / wrong class name.

1.1.3 The file with classes' constants is now loaded via Composer (thanks to ThaDafinser).

1.1.4 Fixed code-breaking typ (thanks to subtronic).

1.1.5 Added an option to hide the X axis or only it's values (thanks to julien-gm).

1.1.6 Added support for closures in formatting scale (thanks to funkjedi)

2.0 Updated all classes to PSR-2 standard, added typehinting where possible, updated
    annotations in methods to be as accurate as possible. Added Behat testing and
    restructed the namespaces into more sensible structure.

References
==========
[The original pChart website](http://www.pchart.net/)

[Composer](https://getcomposer.org/)

PHP Framework Interoperability Group at GitHub on PHP coding standards:

[PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)

[PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)

[PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

[PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md)

Links
=====

[GitHub](https://github.com/szymach/c-pchart)

[Packagist](https://packagist.org/packages/szymach/c-pchart)
