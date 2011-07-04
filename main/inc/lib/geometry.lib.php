<?php

/**
 * Author: Arnaud Ligot (CBlue SPRL) <arnaud@cblue.be>
 */

DEFINE('DEBUG', false);

/**
 * poly_init -    build the array which will store the image of the polygone
 *
 * @param max[x]    X resolution
 * @param max[y]    Y resolution
 *
 * @returns an array such as: for all i in [0..max[x][ : for all j in [0..max[y][ : array[i][j] = FALSE
 */
function poly_init($max) {
    return array_fill(0, $max["x"]-1, 
            array_fill(0, $max["y"]-1, FALSE));
}


/**
 * poly_compile - return an array which holds the image of the polygone
 *            FALSE = blank pixel
 *            TRUE = black pixel
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
 * @param boolean    print or not a debug
 *
 * @returns an array such as: for all i in [0..max[x][ : for all j in [0..max[y][ : array[i][j] = in_poly(poly, i,j)
 *                in_poly(poly,i,j) = true iff (i,j) is inside the polygon defined by poly
 */
function poly_compile($poly, $max, $test = false) {
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

    //$bords = array_fill(0, $bord_lenght-1, array()); // building this array
    $bords = array_fill(0, $bord_lenght, array()); // building this array

    /* adding the first point of the polygone */
    if (is_array($bords[$poly[0]['y']])) //avoid warning 
    	array_push($bords[$poly[0]['y']], $poly[0]['x']);
    
    $i = 1; // we re-use $i and $old_pente bellow the loop
    $old_pente=0;
    for (    ;    // for each points of the polygon but the first 
        $i<sizeof($poly) && (!empty($poly[$i]['x']) && !empty($poly[$i]['y'])); $i++) {
        
        /* special cases */
        if ($poly[$i-1]['y'] == $poly[$i]['y']) {
            if ($poly[$i-1]['x'] == $poly[$i]['x']) 
                continue; // twice the same point
            else {    //  infinite elevation of the edge
            	if (is_array($bords[$poly[$i]['y']]))
                	array_push($bords[$poly[$i]['y']],$poly[$i]['x']);                
                $old_pente=0;
                continue;
            }
        }
    	
		//echo 'point:'.$poly[$i]['y']; bug here
        // adding the point as a part of an edge
        if (is_array($bords[$poly[$i]['y']])) //avoid warning
        array_push($bords[$poly[$i]['y']], $poly[$i]['x']);
        if (DEBUG) echo '('.$poly[$i]['x'].';'.$poly[$i]['y'].')   ';
        
        /* computing the elevation of the edge going */
        //        from $poly[$i-1] to $poly[$i] 
        $pente = ($poly[$i-1]['x']-$poly[$i]['x'])/
                 ($poly[$i-1]['y']-$poly[$i]['y']);

        // if the sign of the elevation change from the one of the 
        // previous edge, the point must be added a second time inside
        // $bords
        if ($i>1)
            if (($old_pente<0 && $pente>0) 
                    || ($old_pente>0 && $pente<0)) {
				if (is_array($bords[$poly[$i]['y']])) //avoid warning  
                	array_push($bords[$poly[$i]['y']],$poly[$i]['x']);
                
                if (DEBUG) 
                    echo '*('.$poly[$i]['x'].
                        ';'.$poly[$i]['y'].')   ';
        	}

        /* detect the direction of the elevation in Y */
        $dy_inc = ($poly[$i]['y']-$poly[$i-1]['y']) > 0 ? 1 : -1;
        $x = $poly[$i-1]['x'];
//        if (DEBUG) echo "init: ".$poly[$i-1]['y']."  dy_inc: ".$dy_inc.
//            "   end: ".$poly[$i]['y']."   pente:".$pente;


        /* computing points between $poly[$i-1]['y'] and $poly[$i-1]['y'] */

        // we iterate w/ $dy in ]$poly[$i-1]['y'],$poly[$i-1]['y'][
        //    w/ $dy_inc as increment
        for ($dy = $poly[$i-1]['y']+$dy_inc; 
            $dy != $poly[$i]['y']; 
            $dy += $dy_inc) {
            $x += $pente*$dy_inc;
            array_push($bords[$dy], $x);
//            if (DEBUG) echo '/('.$x.';'.$dy.')   ';
        }
        $old_pente = $pente; 
    }
    
    // closing the polygone (the edge between $poly[$i-1] and $poly[0])
    if ($poly[$i-1]['y']!=$poly[0]['y']) {// droite--> rien à faire

        // elevation between $poly[0]['x'] and $poly[1]['x'])
        $rest = $poly[0]['y']-$poly[1]['y'];
        if ($rest!=0)
        	$pente1 = ($poly[0]['x']-$poly[1]['x'])/($rest);
        else 
			$pente1 = 0;
			
        // elevation between $poly[$i-1]['x'] and $poly[0]['x'])
        $pente = ($poly[$i-1]['x']-$poly[0]['x'])/ 
            ($poly[$i-1]['y']-$poly[0]['y']);

//        if (DEBUG) echo 'start('.$poly[$i-1]['x'].','.$poly[$i-1]['y'].
//                ')-end('.$poly[0]['x'].','.$poly[0]['y'].
//                ')-pente'.$pente;

        // doubling the first point if needed (see above)
        if (($pente1<0 && $pente>0) || ($pente1>0 && $pente<0)) {  
        	if (is_array($bords[$poly[$i]['y']]))      	
            	array_push($bords[$poly[$i]['y']],  round($poly[$i]['x']));
            //if (DEBUG) echo '('.$poly[$i-1]['x'].';'.$poly[$i-1]['y'].')   ';
        }
        //  doubling the last point if neededd
        if (($old_pente<0 && $pente>0) || ($old_pente>0 && $pente<0)) {
        	if (is_array($bords[$poly[$i-1]['y']])) //avoid warning
            	array_push($bords[$poly[$i-1]['y']], round($poly[$i-1]['x']));
            //if (DEBUG) echo '*('.$poly[$i-1]['x'].';'.$poly[$i-1]['y'].')   ';
        }

        
        $dy_inc = ($poly[0]['y']-$poly[$i-1]['y']) > 0 ? 1 : -1;
        $x = $poly[$i-1]['x'];
//        if (DEBUG) echo "init: ".$poly[$i-1]['y']."  dy_inc: ".$dy_inc.
//            "   end: ".$poly[0]['y'];

        for ($dy = $poly[$i-1]['y']+$dy_inc; 
            $dy != $poly[0]['y']; 
            $dy += $dy_inc)
        {
            $x += $pente*$dy_inc;
            array_push($bords[$dy], round($x));
//            if (DEBUG) echo '/('.$x.';'.$dy.')   ';
        }
    }
    
    /* filling the polygon */
    /* basic idea: we sort a column of edges. 
        For each pair of point, we color the points in between */
    $n = count($bords);
    for ($i = 0; $i<$n; $i++) {  // Y
        //error_log(__FILE__.' - Border Num '.$i,0);
        if (is_array($bords[$i])) {
       		sort($bords[$i]);
        } 
         	
        for ($j = 0; $j<sizeof($bords[$i]);$j+=2) // bords
            for ($k = round($bords[$i][$j]); $k<=$bords[$i][$j+1];$k++) { 
                $res[$k][$i] = true; //filling the array with trues
                if ($test == 1)  {
                	/*how to draw the polygon in a human way:
                	In ubuntu : sudo apt-get install gnuplot
                	Create an empty file with all points with the result of this echos (No commas, no point, no headers)
                	In gnuplot: 
                	For 1 polygon:  plot "/home/jmontoya/test"
                	For 2 polygons:  plot "/home/jmontoya/test", "/home/jmontoya/test2"
                	A new window will appear with the plot
                	*/
                	echo $k.'  '.$i; echo '<br />';                	
                }
            }
    }
    return $res;
}

/**
 * poly_dump - dump an image on the screen
 *
 * @param array       the polygone as output by poly_compile()
 * @param array       see above (poly_init)
 * @param string      Format ('raw' text or 'html')
 * 
 * @return string     html code of the representation of the polygone image
 */
function poly_dump(&$poly, $max, $format='raw') {
    if ($format == 'html') {
        $s = "<div style='font-size: 8px; line-height:3px'><pre>\n";
    }
    for ($i=0; $i<$max['y']; $i++) {
        for($j=0; $j<$max['x']; $j++)
            if($poly[$j][$i] == TRUE)
                $s .= ($format=='html'?"<b>1</b>":'1');
            else
                $s .= "0";
        $s .= ($format=='html'?"<br />\n":"\n");
    }
    $s .= ($format=='html'?"</pre></div>\n":"\n");
    return $s;
}

/**
 * poly_result    -    compute statis for two polygones
 *
 * @param poly1        first polygone as returned by poly_compile
 * @param poly2        second ....
 * @param max        resolution as specified for poly_init
 * 
 * @returns (see below, UTSL)
 */
function poly_result(&$poly1, &$poly2, $max) {
    $onlyIn1 = 0;
    $surfaceOf1 = 0;
    $surfaceOf2 = 0;    

    for ($i=0; $i<$max['x']; $i++) 
        for($j=0; $j<$max['y']; $j++) {
            if (isset($poly1[$i][$j]) && ($poly1[$i][$j] == TRUE)) {
                $surfaceOf1++;
                if (isset($poly2[$i][$j]) && ($poly2[$i][$j] == FALSE)) 
                    $onlyIn1++;
            }
            if (isset($poly2[$i][$j]) && ($poly2[$i][$j] == TRUE)) 
                $surfaceOf2++;
        }
    
    return array (
        "s1" => $surfaceOf1,
        "s2" => $surfaceOf2,
        "both" => $surfaceOf1 - $onlyIn1,
        "s1Only" => $onlyIn1,
        "s2Only" => $surfaceOf2 - ($surfaceOf1 - $onlyIn1));
}

/**
 * poly_touch    -    compute statis for two polygones
 *
 * @param poly1        first polygone as returned by poly_compile
 * @param poly2        second ....
 * @param max        resolution as specified for poly_init
 * 
 * @returns (see below, UTSL)
 */
function poly_touch(&$poly1, &$poly2, $max) {

    for ($i=0; $i<$max['x']; $i++) {
        for($j=0; $j<$max['y']; $j++) {
            if (isset($poly1[$i][$j]) && ($poly1[$i][$j] == true) 
                && isset($poly2[$i][$j]) && ($poly2[$i][$j] == true)) {
                    return true;
            }
        }
    }
    return FALSE;
}

/**
 * Convert a list of points in x1;y1|x2;y2|x3;y3 or x1;y1/x2;y2 format to 
 * the format in which the functions in this library are expecting their data
 * @param   string  List of points in x1;y1|... format (or /)
 * @param   string  The points separator for the list (| or /)
 * @return  array   An array of points in the right format to use with the
 *                  local functions
 */
function convert_coordinates($coords,$sep='|') {
    $points = array();
    $pairs = explode($sep,$coords);
    foreach ($pairs as $idx => $pcoord) {
        list($x,$y) = explode(';',$pcoord);
        $points[] = array('x'=>$x,'y'=>$y); 
    }
	return $points;
}

/**
 * Returns the maximum coordinates in x,y (from 0,0) that the geometrical form
 * can reach
 * @param   array   Coordinates of one polygon
 * @return  array   ('x'=>val,'y'=>val)   
 */
function poly_get_max(&$coords1, &$coords2) {
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
    return array('x'=>$mx,'y'=>$my);
}