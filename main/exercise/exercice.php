<?php
// This file serves the only purpose of maintaining backwards compatibility
// with previous content of Chamilo that might have pointed directly to
// exercise.php as it was called before.
// The *previous* exercise.php was renamed to exercise.php, which is the file
// included here. All new links to the main exercises page should point
// directly to exercise.php. This redirection is enabled for 1.10.x (2015-04-21)
// The final goal of this file is to be removed in a few years time, if
// considered realistically not harmful
require __DIR__.'/exercise.php';
