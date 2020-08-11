# Drawing a spring chart

[Reference](http://wiki.pchart.net/doc.spring.drawspring.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Spring;
use CpChart\Image;

/* Create the Image object */
$image = new Image(300, 300);

/* Background customization */
$image->drawGradientArea(0, 0, 300, 300, DIRECTION_HORIZONTAL, [
    "StartR" => 217,
    "StartG" => 250,
    "StartB" => 116,
    "EndR" => 181,
    "EndG" => 209,
    "EndB" => 27,
    "Alpha" => 100
]);
$image->drawGradientArea(0, 0, 300, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 100
]);
$image->drawRectangle(0, 0, 299, 299, ["R" => 0, "G" => 0, "B" => 0]);
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "pSpring - Draw spring charts", ["R" => 255, "G" => 255, "B" => 255]);

/* Prepare the graph area */
$image->setGraphArea(20, 20, 280, 280);
$image->setFontProperties(["FontName" => "Forgotte.ttf", "FontSize" => 9, "R" => 80, "G" => 80, "B" => 80]);
$image->setShadow(true, ["X" => 2, "Y" => 2, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10]);

/* Create the pSpring object */
$springChart = new Spring();

/* Set the nodes default settings */
$springChart->setNodeDefaults(["FreeZone" => 50]);

/* Build random nodes & connections */
for ($i = 0; $i <= 10; $i++) {
    $connections = [];
    for ($j = 0; $j <= rand(0, 1); $j++) {
        $connections[] = rand(0, 10);
    }
    $springChart->addNode($i, ["Name" => "Node " . $i, "Connections" => $connections]);
}

/* Compute and draw the Spring Graph */
$springChart->drawSpring($image, ["DrawQuietZone" => true]);

/* Render the picture */
$image->render("drawSpring3.png");
```
