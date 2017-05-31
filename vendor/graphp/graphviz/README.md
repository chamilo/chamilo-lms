# graphp/graphviz [![Build Status](https://travis-ci.org/graphp/graphviz.svg?branch=master)](https://travis-ci.org/graphp/graphviz)

GraphViz graph drawing for mathematical graph/network

The library supports visualizing graph images, including them into webpages,
opening up images from within CLI applications and exporting them
as PNG, JPEG or SVG file formats (among many others).
Because [graph drawing](http://en.wikipedia.org/wiki/Graph_drawing) is a complex area on its own,
the actual layouting of the graph is left up to the excelent [GraphViz](http://www.graphviz.org/)
"Graph Visualization Software" and we merely provide some convenient APIs to interface with GraphViz.

> Note: This project is in beta stage! Feel free to report any issues you encounter.

## Quickstart examples

Once [installed](#install), let's build and display a sample graph:

````php
$graph = new Fhaculty\Graph\Graph();

$blue = $graph->createVertex('blue');
$blue->setAttribute('graphviz.color', 'blue');

$red = $graph->createVertex('red');
$red->setAttribtue('graphviz.color', 'red');

$edge = $blue->createEdgeTo($red);
$edge->setAttribute('graphviz.color', 'grey');

$graphviz = new Graphp\GraphViz\GraphViz();
$graphviz->display($graph);
````

## Install

The recommended way to install this library is [through composer](http://getcomposer.org). [New to composer?](http://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "graphp/graphviz": "~0.2.0"
    }
}
```

In order to be able to use the [graph drawing feature](#graph-drawing) you'll have to
install GraphViz (`dot` executable). Users of Debian/Ubuntu-based distributions may simply
invoke `sudo apt-get install graphviz`, Windows users have to
[download GraphViZ for Windows](http://www.graphviz.org/Download_windows.php) and remaining
users should install from [GraphViz homepage](http://www.graphviz.org/Download.php).

## License

Released under the terms of the permissive [MIT license](http://opensource.org/licenses/MIT).
