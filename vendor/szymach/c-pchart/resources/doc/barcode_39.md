# Drawing a barcode 39

[Reference](http://wiki.pchart.net/doc.barcode39.pBarcode39.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Barcode\Barcode39;
use CpChart\Image;

/* Create the Image object */
$image = new Image(700, 230);

/* Draw the background */
$image->drawFilledRectangle(0, 0, 700, 230, [
    "R" => 170,
    "G" => 183,
    "B" => 87,
    "Dash" => 1,
    "DashR" => 190,
    "DashG" => 203,
    "DashB" => 107
]);

/* Overlay with a gradient */
$image->drawGradientArea(0, 0, 700, 230, DIRECTION_VERTICAL, [
    "StartR" => 219,
    "StartG" => 231,
    "StartB" => 139,
    "EndR" => 1,
    "EndG" => 138,
    "EndB" => 68,
    "Alpha" => 50
]);
$image->drawGradientArea(0, 0, 700, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 80
]);

/* Draw the picture border */
$image->drawRectangle(0, 0, 699, 229, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the title */
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "Barcode 39 - Add barcode to your pictures", ["R" => 255, "G" => 255, "B" => 255]);

/* Create the barcode 39 object */
$barcodeChart = new Barcode39();

/* Draw a simple barcode */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
$barcodeChart->draw($image, "pChart Rocks!", 50, 50, ["ShowLegend" => true, "DrawArea" => true]);

/* Draw a rotated barcode */
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 12]);
$barcodeChart->draw($image, "Turn me on", 650, 50, ["ShowLegend" => true, "DrawArea" => true, "Angle" => 90]);

/* Draw a rotated barcode */
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 12]);
$barcodeChart->draw($image, "Do what you want !", 290, 140, [
    "R" => 255,
    "G" => 255,
    "B" => 255,
    "AreaR" => 150,
    "AreaG" => 30,
    "AreaB" => 27,
    "ShowLegend" => true,
    "DrawArea" => true,
    "Angle" => 350,
    "AreaBorderR" => 70,
    "AreaBorderG" => 20,
    "AreaBorderB" => 20
]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.barcode39.png");
```
