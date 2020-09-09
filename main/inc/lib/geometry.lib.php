<?php
/* For licensing terms, see /license.txt */
/**
 * @author Arnaud Ligot (CBlue SPRL) <arnaud@cblue.be>
 *
 * @package chamilo.include.geometry
 */
define('DEBUG', false);

/**
 * poly_init -    build the array which will store the image of the polygone.
 *
 * @param max[x]    X resolution
 * @param max[y]    Y resolution
 * @returns an array such as: for all i in [0..max[x][ : for all j in [0..max[y][ : array[i][j] = FALSE
 */
function poly_init($max)
{
    return array_fill(
        0,
        $max["x"] - 1,
        array_fill(0, $max["y"] - 1, false)
    );
}

/**
 * poly_compile - return an array which holds the image of the polygone
 *            FALSE = blank pixel
 *            TRUE = black pixel.
 *
 * @param poly        points from the polygone
 *            example:
 *                poly[0]['x'] = ...
 *                poly[0]['y'] = ...
 *                poly[1]['x'] = ...
 *                poly[1]['y'] = ...
 *                ...
 *                poly[n]['x'] = <empty>
 *                poly[n]['y'] = <empty>
 *                poly[n+1]['x'] = <empty>
 *                poly[n+1]['y'] = <empty>
 *                ...
 * @param max        see poly_init
 * @param bool    print or not a debug
 *
 * @returns an array such as: for all i in [0..max[x][ : for all j in [0..max[y][ : array[i][j] = in_poly(poly, i,j)
 *                in_poly(poly,i,j) = true iff (i,j) is inside the polygon defined by poly
 */
function poly_compile($poly, $max, $test = false)
{
    $res = poly_init($max);

    // looking for EDGES
    // may be optimized by a dynamic choice
    // between Y and X based on max[y]<max[x]
    /*
     * bords    cointains the edges of the polygone
     *        it is an array of array,
     *            there are an array for each collon of the image
     *
     *        for all j in [O..max[y][ : for all i in bords[$j] :
     *            (i,j) is a point inside an edge of the polygone
     */
    $bord_lenght = $max['x'];
    if ($max['y'] > $bord_lenght) {
        $bord_lenght = $max['y'];
    }

    $bords = array_fill(0, $bord_lenght, []); // building this array

    /* adding the first point of the polygone */
    if (isset($bords[$poly[0]['y']]) && is_array($bords[$poly[0]['y']])) {
        // avoid warning
        array_push($bords[$poly[0]['y']], $poly[0]['x']);
    }

    $i = 1; // we re-use $i and $old_pente bellow the loop
    $old_pente = 0;
    // for each points of the polygon but the first
    for (; $i < count($poly) && (!empty($poly[$i]['x']) && !empty($poly[$i]['y'])); $i++) {
        /* special cases */
        if ($poly[$i - 1]['y'] == $poly[$i]['y']) {
            if ($poly[$i - 1]['x'] == $poly[$i]['x']) {
                continue;
            } // twice the same point
            else {    //  infinite elevation of the edge
                if (is_array($bords[$poly[$i]['y']])) {
                    array_push($bords[$poly[$i]['y']], $poly[$i]['x']);
                }
                $old_pente = 0;
                continue;
            }
        }

        //echo 'point:'.$poly[$i]['y']; bug here
        // adding the point as a part of an edge
        if (isset($poly[$i]) &&
            isset($poly[$i]['y']) &&
            isset($bords[$poly[$i]['y']]) &&
            is_array($bords[$poly[$i]['y']])
        ) {
            // Avoid warning
            array_push($bords[$poly[$i]['y']], $poly[$i]['x']);
        }

        if (DEBUG) {
            echo '('.$poly[$i]['x'].';'.$poly[$i]['y'].')   ';
        }

        /* computing the elevation of the edge going */
        //        from $poly[$i-1] to $poly[$i]
        $pente = ($poly[$i - 1]['x'] - $poly[$i]['x']) /
                 ($poly[$i - 1]['y'] - $poly[$i]['y']);

        // if the sign of the elevation change from the one of the
        // previous edge, the point must be added a second time inside
        // $bords
        if ($i > 1) {
            if (($old_pente < 0 && $pente > 0)
                || ($old_pente > 0 && $pente < 0)) {
                if (isset($poly[$i]) && isset($poly[$i]['y']) &&
                    isset($bords[$poly[$i]['y']]) &&
                    is_array($bords[$poly[$i]['y']])
                ) {
                    array_push($bords[$poly[$i]['y']], $poly[$i]['x']);
                }

                if (DEBUG) {
                    echo '*('.$poly[$i]['x'].
                        ';'.$poly[$i]['y'].')   ';
                }
            }
        }

        /* detect the direction of the elevation in Y */
        $dy_inc = ($poly[$i]['y'] - $poly[$i - 1]['y']) > 0 ? 1 : -1;
        $x = $poly[$i - 1]['x'];
        /* computing points between $poly[$i-1]['y'] and $poly[$i-1]['y'] */

        // we iterate w/ $dy in ]$poly[$i-1]['y'],$poly[$i-1]['y'][
        //    w/ $dy_inc as increment
        for ($dy = $poly[$i - 1]['y'] + $dy_inc;
            $dy != $poly[$i]['y'];
            $dy += $dy_inc) {
            $x += $pente * $dy_inc;
            array_push($bords[$dy], $x);
        }
        $old_pente = $pente;
    }

    // closing the polygone (the edge between $poly[$i-1] and $poly[0])
    if ($poly[$i - 1]['y'] != $poly[0]['y']) {
        // droite--> rien à faire
        // elevation between $poly[0]['x'] and $poly[1]['x'])
        $rest = $poly[0]['y'] - $poly[1]['y'];
        if ($rest != 0) {
            $pente1 = ($poly[0]['x'] - $poly[1]['x']) / ($rest);
        } else {
            $pente1 = 0;
        }

        // elevation between $poly[$i-1]['x'] and $poly[0]['x'])
        $pente = ($poly[$i - 1]['x'] - $poly[0]['x']) /
            ($poly[$i - 1]['y'] - $poly[0]['y']);

//        if (DEBUG) echo 'start('.$poly[$i-1]['x'].','.$poly[$i-1]['y'].
//                ')-end('.$poly[0]['x'].','.$poly[0]['y'].
//                ')-pente'.$pente;

        // doubling the first point if needed (see above)
        if (($pente1 < 0 && $pente > 0) || ($pente1 > 0 && $pente < 0)) {
            if (is_array($bords[$poly[$i - 1]['y']])) {
                array_push($bords[$poly[$i - 1]['y']], round($poly[$i - 1]['x']));
            }
            //if (DEBUG) echo '('.$poly[$i-1]['x'].';'.$poly[$i-1]['y'].')   ';
        }
        //  doubling the last point if neededd
        if (($old_pente < 0 && $pente > 0) || ($old_pente > 0 && $pente < 0)) {
            if (is_array($bords[$poly[$i - 1]['y']])) { //avoid warning
                array_push($bords[$poly[$i - 1]['y']], round($poly[$i - 1]['x']));
            }
            //if (DEBUG) echo '*('.$poly[$i-1]['x'].';'.$poly[$i-1]['y'].')   ';
        }

        $dy_inc = ($poly[0]['y'] - $poly[$i - 1]['y']) > 0 ? 1 : -1;
        $x = $poly[$i - 1]['x'];
//        if (DEBUG) echo "init: ".$poly[$i-1]['y']."  dy_inc: ".$dy_inc.
//            "   end: ".$poly[0]['y'];

        for ($dy = $poly[$i - 1]['y'] + $dy_inc; $dy != $poly[0]['y']; $dy += $dy_inc) {
            $x += $pente * $dy_inc;
            array_push($bords[$dy], round($x));
        }
    }

    /* filling the polygon */
    /* basic idea: we sort a column of edges.
        For each pair of point, we color the points in between */
    $n = count($bords);
    for ($i = 0; $i < $n; $i++) {  // Y
        if (is_array($bords[$i])) {
            sort($bords[$i]);
        }

        for ($j = 0; $j < count($bords[$i]); $j += 2) { // bords
            if (!isset($bords[$i][$j + 1])) {
                continue;
            }

            for ($k = round($bords[$i][$j]); $k <= $bords[$i][$j + 1]; $k++) {
                $res[$k][$i] = true; //filling the array with trues
                if ($test == 1) {
                    /*how to draw the polygon in a human way:
                    In ubuntu : sudo apt-get install gnuplot
                    Create an empty file with all points with the result of this echos (No commas, no point, no headers)
                    In gnuplot:
                    For 1 polygon:  plot "/home/jmontoya/test"
                    For 2 polygons:  plot "/home/jmontoya/test", "/home/jmontoya/test2"
                    A new window will appear with the plot
                    */
                    echo $k.'  '.$i;
                    echo '<br />';
                }
            }
        }
    }

    return $res;
}

/**
 * poly_dump - dump an image on the screen.
 *
 * @param array       the polygone as output by poly_compile()
 * @param array       see above (poly_init)
 * @param string      Format ('raw' text or 'html')
 *
 * @return string html code of the representation of the polygone image
 */
function poly_dump(&$poly, $max, $format = 'raw')
{
    if ($format == 'html') {
        $s = "<div style='font-size: 8px; line-height:3px'><pre>\n";
    }
    for ($i = 0; $i < $max['y']; $i++) {
        for ($j = 0; $j < $max['x']; $j++) {
            if ($poly[$j][$i] == true) {
                $s .= ($format == 'html' ? "<b>1</b>" : '1');
            } else {
                $s .= "0";
            }
        }
        $s .= ($format == 'html' ? "<br />\n" : "\n");
    }
    $s .= ($format == 'html' ? "</pre></div>\n" : "\n");

    return $s;
}

/**
 * poly_result    -    compute statis for two polygones.
 *
 * @param poly1        first polygone as returned by poly_compile
 * @param poly2        second ....
 * @param max        resolution as specified for poly_init
 *
 * @returns (see below, UTSL)
 */
function poly_result(&$poly1, &$poly2, $max)
{
    $onlyIn1 = 0;
    $surfaceOf1 = 0;
    $surfaceOf2 = 0;

    for ($i = 0; $i < $max['x']; $i++) {
        for ($j = 0; $j < $max['y']; $j++) {
            if (isset($poly1[$i][$j]) && ($poly1[$i][$j] == true)) {
                $surfaceOf1++;
                if (isset($poly2[$i][$j]) && ($poly2[$i][$j] == false)) {
                    $onlyIn1++;
                }
            }
            if (isset($poly2[$i][$j]) && ($poly2[$i][$j] == true)) {
                $surfaceOf2++;
            }
        }
    }

    return [
        "s1" => $surfaceOf1,
        "s2" => $surfaceOf2,
        "both" => $surfaceOf1 - $onlyIn1,
        "s1Only" => $onlyIn1,
        "s2Only" => $surfaceOf2 - ($surfaceOf1 - $onlyIn1), ];
}

/**
 * poly_touch    -    compute statis for two polygones.
 *
 * @param poly1        first polygone as returned by poly_compile
 * @param poly2        second ....
 * @param max        resolution as specified for poly_init
 *
 * @returns (see below, UTSL)
 */
function poly_touch(&$poly1, &$poly2, $max)
{
    for ($i = 0; $i < $max['x']; $i++) {
        for ($j = 0; $j < $max['y']; $j++) {
            if (isset($poly1[$i][$j]) && ($poly1[$i][$j] == true)
                && isset($poly2[$i][$j]) && ($poly2[$i][$j] == true)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Convert a list of points in x1;y1|x2;y2|x3;y3 or x1;y1/x2;y2 format to
 * the format in which the functions in this library are expecting their data.
 *
 * @param   string  List of points in x1;y1|... format (or /)
 * @param   string  The points separator for the list (| or /)
 *
 * @return array An array of points in the right format to use with the
 *               local functions
 */
function convert_coordinates($coords, $sep = '|')
{
    $points = [];
    $pairs = explode($sep, $coords);
    if (!empty($pairs)) {
        foreach ($pairs as $idx => $pcoord) {
            if (empty($pcoord)) {
                continue;
            }
            $parts = explode(';', $pcoord);
            if (!empty($parts)) {
                $points[] = ['x' => $parts[0], 'y' => $parts[1]];
            }
        }
    }

    return $points;
}

/**
 * Returns the maximum coordinates in x,y (from 0,0) that the geometrical form
 * can reach.
 *
 * @param   array   Coordinates of one polygon
 *
 * @return array ('x'=>val,'y'=>val)
 */
function poly_get_max(&$coords1, &$coords2)
{
    $mx = 0;
    $my = 0;
    foreach ($coords1 as $coord) {
        if ($coord['x'] > $mx) {
            $mx = $coord['x'];
        }
        if ($coord['y'] > $my) {
            $my = $coord['y'];
        }
    }
    foreach ($coords2 as $coord) {
        if ($coord['x'] > $mx) {
            $mx = $coord['x'];
        }
        if ($coord['y'] > $my) {
            $my = $coord['y'];
        }
    }

    return ['x' => $mx, 'y' => $my];
}

/**
 * Class Geometry
 * Utils for decode hotspots and check if the user choices are correct.
 */
class Geometry
{
    /**
     * Decode a user choice as a point.
     *
     * @param string $coordinates
     *
     * @return array The x and y properties for a point
     */
    public static function decodePoint($coordinates)
    {
        $coordinates = explode(';', $coordinates);

        return [
            'x' => intval($coordinates[0]),
            'y' => intval($coordinates[1]),
        ];
    }

    /**
     * Decode a square info as properties.
     *
     * @param string $coordinates
     *
     * @return array The x, y, width, and height properties for a square
     */
    public static function decodeSquare($coordinates)
    {
        $coordinates = explode('|', $coordinates);
        $originString = explode(';', $coordinates[0]);

        return [
            'x' => intval($originString[0]),
            'y' => intval($originString[1]),
            'width' => intval($coordinates[1]),
            'height' => intval($coordinates[2]),
        ];
    }

    /**
     * Decode an ellipse info as properties.
     *
     * @param string $coordinates
     *
     * @return array The center_x, center_y, radius_x, radius_x properties for an ellipse
     */
    public static function decodeEllipse($coordinates)
    {
        $coordinates = explode('|', $coordinates);
        $originString = explode(';', $coordinates[0]);

        return [
            'center_x' => intval($originString[0]),
            'center_y' => intval($originString[1]),
            'radius_x' => intval($coordinates[1]),
            'radius_y' => intval($coordinates[2]),
        ];
    }

    /**
     * Decode a polygon info as properties.
     *
     * @param string $coordinates
     *
     * @return array The array of points for a polygon
     */
    public static function decodePolygon($coordinates)
    {
        $coordinates = explode('|', $coordinates);

        $points = [];

        foreach ($coordinates as $coordinate) {
            $point = explode(';', $coordinate);

            $points[] = [
                intval($point[0]),
                intval($point[1]),
            ];
        }

        return $points;
    }

    /**
     * Check if the point is inside of a square.
     *
     * @param array $properties The hotspot properties
     * @param array $point      The point properties
     *
     * @return bool
     */
    public static function pointIsInSquare($properties, $point)
    {
        $left = $properties['x'];
        $right = $properties['x'] + $properties['width'];
        $top = $properties['y'];
        $bottom = $properties['y'] + $properties['height'];

        $xIsValid = $point['x'] >= $left && $point['x'] <= $right;
        $yIsValid = $point['y'] >= $top && $point['y'] <= $bottom;

        return $xIsValid && $yIsValid;
    }

    /**
     * Check if the point is inside of an ellipse.
     *
     * @param array $properties The hotspot properties
     * @param array $point      The point properties
     *
     * @return bool
     */
    public static function pointIsInEllipse($properties, $point)
    {
        $dX = $point['x'] - $properties['center_x'];
        $dY = $point['y'] - $properties['center_y'];

        $dividend = pow($dX, 2) / pow($properties['radius_x'], 2);
        $divider = pow($dY, 2) / pow($properties['radius_y'], 2);

        return $dividend + $divider <= 1;
    }

    /**
     * Check if the point is inside of a polygon.
     *
     * @param array $properties The hotspot properties
     * @param array $point      The point properties
     *
     * @return bool
     */
    public static function pointIsInPolygon($properties, $point)
    {
        $points = $properties;
        $isInside = false;

        for ($i = 0, $j = count($points) - 1; $i < count($points); $j = $i++) {
            $xi = $points[$i][0];
            $yi = $points[$i][1];
            $xj = $points[$j][0];
            $yj = $points[$j][1];

            $intersect = (($yi > $point['y']) !== ($yj > $point['y'])) &&
                ($point['x'] < ($xj - $xi) * ($point['y'] - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $isInside = !$isInside;
            }
        }

        return $isInside;
    }
}
