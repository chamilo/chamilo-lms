<?php //$Id: geometry.lib.php 16500 2008-10-11 14:56:57Z yannoo $

//more natural array index names
define(X, 0);
define(Y, 1);

//aproximation level to comparing reals
define(APROX, 0.0001);

/**
 * This method calculates and returns the area of an irregular
 * polygon. Algorithm taken from
 * http://forums.devx.com/showpost.php?p=423349&postcount=2
 *
 * @param array(int) xs the values of the x coordinates
 * @param array(int) ys the corresponding values of the y coordinates
 * @return double the area
 */
function calculate_area($xs, $ys) {
  if (!_check_polygon($xs, $ys)) {
    return FALSE;
  }

  //calculate and return the answer
  return abs((subCalculation($xs, $ys)) - (subCalculation($ys, $xs)))/2;
}

/**
 * Area Under One Line Segment
 */
function subCalculation($a, $b) {
  $answer = 0;
  for ($i=0; $i < (count($a)-1); $i++) {
    $answer += ($a[$i] * $b[$i+1]);
  }
  $answer += $a[count($a)-1] * $b[0];
  return $answer;
}

/**
 * Get intersection point of two lines, assumes no paralells
 */
function lines_intersect($line1, $line2) {
  if (!is_array($line1) || !is_array($line2)) {
    $msg = '[geometry-lib] line: Invalid - each line must be array(array(x,y), array(x,y)) - line1: '. print_r($line1,1) .', line2: '. print_r($line2,1);
    error_log($msg);
    return FALSE;
  }
  if (count($line1)!=2 || count($line2)!=2) {
    $msg = '[geometry-lib] line: Invalid - each line must have two arrays';
    error_log($msg);
    return FALSE;
  }

  // get slopes m1 and m2
  // p,q,r,s points
  list($p,$q) = $line1;
  list($r,$s) = $line2;
  $m1 = _get_slope($p, $q);
  $m2 = _get_slope($r, $s);

  //get intersect point
  // solving y-y1=m(x-x1) for each pair of points
  if ($m1 == $m2) {
    return NULL;
  }
  $x = (-$m2*$r[0] + $r[1] + $m1*$p[0] - $p[1]) / ($m1-$m2);
  $y = (-$m1*$r[1] + $m1*$m2*($r[0]-$p[0]) + $m2*$p[1]) / ($m2-$m1);

  if (_is_in_segment(array($x, $y), $p, $q) && _is_in_segment(array($x, $y), $r, $s))
    return array($x, $y);
  return NULL;
}

/**
 * Verify if point p is a point of the segment ab
 */
function _is_in_segment($p, $a, $b) {
           //if ($a[X]==8.7 && $b[X]==6.3) {echo ":: ?? en segmento ? <br />";
           //     echo "px= {$p[X]}".print_r($p,1)." >= min=". min($a[X],$b[X]) ."<br />";}
  if ( $p[X]>=min($a[X],$b[X]) && $p[X]<=max($a[X],$b[X])
    && $p[Y]>=min($a[Y],$b[Y]) && $p[Y]<=max($a[Y],$b[Y]) ) {
           //if ($a[X]==8.7 && $b[X]==6.3) echo ":: ?? si, en linea ? <br />";
      return _is_in_line($p, $a, $b);
    }
  return FALSE;
}

/**
 * Verify if point p is a point of the line ab
 */
function _is_in_line($p, $a, $b) {
  $m1 = _get_slope($a, $b);
  $b1 = $a[Y]-$m1*$a[X];
  if (abs($p[Y] - ($m1*$p[X] + $b1)) < APROX) {
    return TRUE;
  }
}

/**
 * Get the slope of the line that pass through points p and q
 */
function _get_slope($p, $q) {
  if ($q[X]-$p[X] == 0)
    return 0;
  return  ($q[Y]-$p[Y])/($q[X]-$p[X]);
}

/**
 * Check if the coordinates are correct or not
 */
function _check_polygon($xs, $ys) {
  //check that xs and ys have the same length
  if (count($xs) != count($ys)) {
    $msg = '[geometry-lib] polygon: Invalid - length of x and y coordinate arrays differs';
    error_log($msg);
    return FALSE;
  }
  //check that this is a polygon (more than 2 points!!)
  if (count($xs) < 3) {
    $msg = '[geometry-lib] polygon: Invalid '. polygon2string($xs, $ys);
    error_log($msg);
    return FALSE;
  }
  return TRUE;
}

function polygon2string($xs, $ys) {
  if (count($xs) < 3) {
    error_log('[geometry-lib] polygon: Polygon must have more than two points');
    return '( not a polygon )';
  }
  $output = '( ';
  //do it the long way to allow print bad polygons
  for ($i=0; $i < count($xs); $i++) {
    $points[$i] = array(X => $xs[$i]);
  }
  for ($i=0; $i < count($ys); $i++) {
    $points[$i] = array(Y => $ys[$i]);
  }
  foreach ($points as $point) {
    $output .= '('. (isset($point[X]) ? $point[X]: '') .', '. (isset($point[Y])? $point[Y]: '') .'),';
  }
  $output .= ' )';
  return $output;
}

/**
 * Verify if the point p is inside the region delimited by the
 * coordinates of the polygon
 * note: assumes *convex* polygons
 */
function _is_inside_polygon($p, $xs, $ys) {
  if (!_check_polygon($xs, $ys)) {
    return FALSE;
  }

               //echo ":: _is_inside_polygon :: ({$p[X]}, {$p[Y]}) ? <br />";
  $conditionals = _get_inside_conditionals($xs, $ys);
  foreach ($conditionals as $condition) {
    if ($condition['sign'] == 'major') {
      if ( !($p[Y] >= $condition['m']*$p[X]+$condition['b']) ) {
        return FALSE;
      }
    }
    else { //minor
     if ( !($p[Y] <= $condition['m']*$p[X]+$condition['b']) ) {
        return FALSE;
      }
    }
  }

  return TRUE;
}

/**
 * Get the required conditionals which together makes the is_inside
 * a polygon condition, assumes convex
 */
function _get_inside_conditionals($xs, $ys) {
  $conditionals = array();
                 //echo ":: get_inside_cond :: 1st element ({$xs[0]}, {$ys[0]})<br />";
  for ($i=0; $i < (count($xs)-1); $i++) {
    //describe the line between points $i and $i+1
    $m = _get_slope(array($xs[$i], $ys[$i]), array($xs[$i+1], $ys[$i+1]));
    $b = $ys[$i] - $m*$xs[$i];
                 //echo ":: get_inside_cond :: eval {$m}x + $b<br />";

    //decide where is inside
                 //echo ":: get_inside_cond :: where is inside?<br />";
    $sign='';
    for ($j=0; $j < (count($xs)); $j++) {
                 //$tmp = $m*$xs[$j]+$b; //print
      if (abs($ys[$j] - ($m*$xs[$j]+$b)) < APROX) {
                 //echo ":: get_inside_cond :: not entering on {$ys[$j]} == {$tmp}<br />";
        continue;
      }
                 //if (abs($m+0.370370)<APROX) echo ":: get_inside_cond :: :: eval ({$ys[$j]} > {$tmp})?<br />";
      if ($ys[$j] > $m*$xs[$j]+$b) {
        $sign='major';
        break;
      }
      else {
        $sign='minor';
        break;
      }
    }
                 //echo ":: get_inside_cond :: -> sign=$sign<br />";

    $conditionals[] = array('m' => $m, 'b' => $b, 'sign' => $sign);
  }

  //the final conditional
  $m = _get_slope(array($xs[count($xs)-1], $ys[count($xs)-1]), array($xs[0], $ys[0]));
  $b = $ys[0] - $m*$xs[0];
                 //echo ":: get_inside_cond :: eval {$m}x + $b<br />";
  //decide where is inside
                 //echo ":: get_inside_cond :: where is inside?<br />";
  $sign='';
  for ($j=0; $j < (count($xs)); $j++) {
    if (abs($ys[$j] - ($m*$xs[$j]+$b)) < APROX) continue;
    if ($ys[$j] > $m*$xs[$j]+$b) {
      $sign='major';
      break;
    }
    else {
      $sign='minor';
      break;
    }
  }

  $conditionals[] = array('m' => $m, 'b' => $b, 'sign' => $sign);

  return $conditionals;
}

function get_intersection_data($rxs, $rys, $uxs, $uys) {
  $i_pol = get_intersection_polygon($rxs, $rys, $uxs, $uys);
  if (!is_array($i_pol)) {
    return array('success' => 0);
  }
  $r_area = calculate_area($rxs, $rys);
  $u_area = calculate_area($uxs, $uys);
  $ixs=array();
  $iys=array();
  foreach ($i_pol as $point) {
    $ixs[] = $point['point'][X];
    $iys[] = $point['point'][Y];
  }
  if (!_check_polygon($ixs, $iys)) {
    $success=0;
  }
  else {
    $i_area = calculate_area($ixs, $iys);
    $success = $i_area/$r_area;
  }
  return array(
    'success' => $success,
  );
}

/**
 * Get an array which describe the polygon formed by the intersection of
 * the two polygons given
 */
function get_intersection_polygon($rxs, $rys, $uxs, $uys) {
  list($intern_points, $intersection_points) = _get_intersection_polygon_data($rxs, $rys, $uxs, $uys);
  //print '<br>=========<br>intersection points: '. print_r($intersection_points,1);
  //print '<br>=========<br>intern points: '. print_r($intern_points,1);
  //print '<br>=========<br>';

  /**
   * Algoritmo para encontrar el orden correcto de los puntos del
   * poligono resultante al intersectar los dos pasados como parametros
   *
   * pol[0] = primer x
   * mientras { pol[0]!=pol(tam(pol)-1) o tam(pol)==1 }
   *   umo=pol[tam(pol)-1]
   *   salir=false
   *   para cada ptoInterno y !salir
   *     si { cotiguo(ptoInterno, umo) y no es el anterior }
   *       pol[] = ptoInterno
   *       salir = true
   *     fin si
   *   fin para
   *   para cada xfaltante y !salir
   *     si { contiguo(x, umo) y no es el anterior }
   *       pol[] = x
   *       salir=true
   *     fin si
   *   fin para
   *   TODO: vertices internos no contiguos a interseccion ·
   * fin mientras
   *
   */

  if (!count($intersection_points)) {
    if (fully_inside($uxs, $uys, $rxs, $rys)) {
      return _to_points_intersection($uxs, $uys);
    }
    return NULL;
  }

  // intersection polygon points ordered
  $pol = array();
  $pol[0] = array_shift($intersection_points);
  //$pol[0]['point'] = $intersection_point['point'];
  $pol[0]['type'] = 'intersection';
  $next_index = 1;
  // TODO: work out condition, now $max_v vertices is the major # supported
  //       on intersection and there are unuseful extra work done
  $max_v=20;
  $cc=0;
  while (( ($pol[0]!=$pol[count($pol)-1] || count($pol)==1) ) && $cc<$max_v) {
    $last_point = $pol[count($pol)-1];
    //print 'last point = '. print_r($last_point,1);
    $salir = FALSE;

               //echo ":: bucle de internos !!<br />";
    for ($i=0; ($i<count($intern_points)) && !$salir; $i++) {
      //verify if the point is next to last_point
      $p=$intern_points[$i];
      $p['type'] = 'intern';
      //echo 'p='.print_r($p,1).' last_point='.print_r($last_point,1).' pol='.print_r($rr=_to_points_array($rxs,$rys),1);
      //TODO: consider all cases(not intern nor intersection points followed in a polygon)

      switch ($last_point['type']) {
      case 'intersection':
              //echo ":: entro a interseccion !!<br />";
              //echo ":: :: con el punto p = " . print_r($p,1) ."<br />";
        if ( _is_next_to($p['point'], $last_point, $rxs, $rys, TRUE)
          || _is_next_to($p['point'], $last_point, $uxs, $uys, TRUE) ) {
               //echo "-> asigno el punto !!<br />";
            if ( isset($pol[$next_index-2]) ) {
              if ($pol[$next_index-2] != $p) {
                $pol[] = $p;
                $salir=TRUE;
                $last_point = $pol[count($pol)-1];
                $next_index++;
              }
            }
            else {
              //echo $i.'shols ';
              //print "{$p[X]} ...... {$p[Y]}<br>";
              $pol[] = $p;
              $salir=TRUE;
              $last_point = $pol[count($pol)-1];
              $next_index++;
            }
        }
        break;
      case 'intern':
              //echo ":: entro a interno !!<br />";
        if ( _is_next_to($p, $last_point, $rxs, $rys)
          || _is_next_to($p, $last_point, $uxs, $uys) ) {
               //echo "-> asigno el punto !!<br />";
            if ( isset($pol[$next_index-2]) ) {
              if ($pol[$next_index-2] != $p) {
                $pol[] = $p;
                $salir=TRUE;
                $last_point = $pol[count($pol)-1];
                $next_index++;
              }
            }
            else {
              //echo $i.'shols ';
              //print "{$p[X]} ...... {$p[Y]}<br>";
              $pol[] = $p;
              $salir=TRUE;
              $last_point = $pol[count($pol)-1];
              $next_index++;
            }
          }
        break;
      }
    }
               //echo ":: FIN bucle de internos !!<br />";

    //print 'last point = '. print_r($last_point,1);
               //echo ":: bucle de intersecciones !!<br />";
    for ($i=0; ($i<count($intersection_points)) && !$salir; $i++) {
      //verify if the point is next to last_point
      $p=$intersection_points[$i];
      $p['type'] = 'intersection';
              //echo ":: :: con el punto p = " . print_r($p,1) ."<br />";
      if ( _is_next_to($p, $last_point, $rxs, $rys, TRUE)
        || _is_next_to($p, $last_point, $uxs, $uys, TRUE) ) {
               //echo "-> asigno el punto !!<br />";
          if ( isset($pol[$next_index-2]) ) {
            if ($pol[$next_index-2] != $p) {
              $pol[] = $p;
              $salir=TRUE;
              $last_point = $pol[count($pol)-1];
              $next_index++;
            }
          }
          else {
            //echo $i.'shols ';
            //print "{$p[X]} ...... {$p[Y]}<br>";
            $pol[] = $p;
            $salir=TRUE;
            $last_point = $pol[count($pol)-1];
            $next_index++;
          }
      }
    }
               //echo ":: FIN bucle de intersecciones !!<br />";

    //print 'last point = '. print_r($last_point,1);
               //echo ":: procesar puntos internos no limitrofes!!<br />";
               //echo ":: proc ptos intern :: eval ({$last_point['point'][X]}, {$last_point['point'][Y]})<br />";
    if ($last_point['type'] != 'intern' && $last_point['type'] != 'intern-no-limit') {
      continue;
    }
    else if (!$salir) { // review next_to polygon points
               //echo ":: proc ptos intern :: review next_to polygon points<br />";
      // get next_to points depending of the polygon
      if ( array_search($last_point['point'], $r_points=_to_points_array($rxs, $rys)) !== FALSE ) {
               //echo ":: proc ptos intern :: pto en el poligono resultado<br />";
        $right_point = _get_right_point($last_point['point'], $r_points);
        $left_point = _get_left_point($last_point['point'], $r_points);
        // is inside the other polygon?
        $p = array('type'=>'intern-no-limit');
        if (_is_inside_polygon($right_point, $uxs, $uys)) {
               //echo ":: proc ptos intern :: pto derecho del pto poligono dento de la region de upol<br />";
               //echo "-> asigno el punto !!<br />";
          $p['point'] = $right_point;
          if ( isset($pol[$next_index-2]) ) {
            if ($pol[$next_index-2]['point'] != $p['point']) {
              $pol[] = $p;
              $salir=TRUE;
              $last_point = $pol[count($pol)-1];
              $next_index++;
            }
          }
          else {
            $pol[] = $p;
            $salir=TRUE;
            $last_point = $pol[count($pol)-1];
            $next_index++;
          }
        }
        else if (_is_inside_polygon($left_point, $uxs, $uys)) {
               //echo ":: proc ptos intern :: pto izquierdo del pto poligono dento de la region de upol<br />";
               //echo "-> asigno el punto !!<br />";
          $p['point'] = $left_point;
          if ( isset($pol[$next_index-2]) ) {
            if ($pol[$next_index-2]['point'] != $p['point']) {
              $pol[] = $p;
              $salir=TRUE;
              $last_point = $pol[count($pol)-1];
              $next_index++;
            }
          }
          else {
            $pol[] = $p;
            $salir=TRUE;
            $last_point = $pol[count($pol)-1];
            $next_index++;
          }
        }
      }
      else if ( array_search($last_point['point'], $u_points=_to_points_array($uxs, $uys)) !== FALSE ) {
               //echo ":: proc ptos ern :: pto en el poligono usuario<br />";
        $right_point = _get_right_point($last_point['point'], $u_points);
        $left_point = _get_left_point($last_point['point'], $u_points);
        // is inside the other polygon?
        $p = array('type'=>'intern-no-limit');
        if (_is_inside_polygon($right_point, $rxs, $rys) && !$salir) {
               //echo ":: proc ptos intern :: pto derecho del pto poligono dentro de la region de rpol<br />";
          $p['point'] = $right_point;
          if ( isset($pol[$next_index-2]) ) {
            if ($pol[$next_index-2]['point'] != $p['point']) {
               //echo "-> asigno el punto !!<br />";
              $pol[] = $p;
              $salir=TRUE;
              $last_point = $pol[count($pol)-1];
              $next_index++;
            }
          }
          else {
               //echo "-> asigno el punto !!<br />";
            $pol[] = $p;
            $salir=TRUE;
            $last_point = $pol[count($pol)-1];
            $next_index++;
          }
        }
        if (_is_inside_polygon($left_point, $rxs, $rys) && !$salir) {
               //echo ":: proc ptos intern :: pto izquierdo del pto poligono dentro de la region de rpol<br />";
               //echo "-> asigno el punto !!<br />";
          $p['point'] = $left_point;
               ////echo "pol: ". print_r($pol[$next_index-2],1) ." p=".print_r($p,1)."doo !!<br />";
          if ( isset($pol[$next_index-2]) ) {
            if ($pol[$next_index-2]['point'] != $p['point']) {
              $pol[] = $p;
              $salir=TRUE;
              $last_point = $pol[count($pol)-1];
              $next_index++;
            }
          }
          else {
            $pol[] = $p;
            $salir=TRUE;
            $last_point = $pol[count($pol)-1];
            $next_index++;
          }
        }
      }
    }
               //echo ":: FIN procesar puntos internos no limitrofes!!<br />";

    $cc++;
    //print_r($pol);
  }
              //echo ":: termino con $cc iteraciones hechas<br />";

  return $pol;
}

/**
 * Get needed data to build the intesection polygon
 */
function _get_intersection_polygon_data($rxs, $rys, $uxs, $uys) {
  $ixs = array();
  $iys = array();
  $intern_points = array();
  $intersection_points = array();

  // iterate through the result polygon sides
  for ($i=0; $i < (count($rxs)-1); $i++) {
    $rline = array( array($rxs[$i],$rys[$i]),  array($rxs[$i+1],$rys[$i+1]) );

    // iterate through the user polygon sides
    for ($j=0; $j < (count($uxs)-1); $j++) {
      $uline = array( array($uxs[$j],$uys[$j]),  array($uxs[$j+1],$uys[$j+1]) );
      if ( ($ipoint=lines_intersect($rline,$uline)) != NULL ) {
        $rpoint1 = array($rxs[$i], $rys[$i]);
        $rpoint2 = array($rxs[$i+1], $rys[$i+1]);
        $upoint1 = array($uxs[$j], $uys[$j]);
        $upoint2 = array($uxs[$j+1], $uys[$j+1]);

        //save intesection points
        $intersection_points[] = array(
          'rsegment' => array( $rpoint1, $rpoint2 ),
          'usegment' => array( $upoint1, $upoint2 ),
          'point' => $ipoint,
        );

        //get intern points
               //echo ":: get_int_poly :: eval ({$rpoint1[X]}, {$rpoint1[Y]}) is inside upolygon?<br />";
        if (_is_inside_polygon($rpoint1, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already on intern points?<br />";
          if (!in_intern_array($rpoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already: no, -> asigning<br />";
            $rp = _get_right_point($rpoint1, _to_points_array($rxs, $rys));
            $lp = _get_left_point($rpoint1, _to_points_array($rxs, $rys));
            $intern_points[] = array(
              'point' => $rpoint1,
              'segment1' => array($lp, $rpoint1),
              'segment2' => array($rpoint1, $rp),
            );
          }
        }
               //echo ":: get_int_poly :: eval ({$rpoint2[X]}, {$rpoint2[Y]}) is inside upolygon?<br />";
        if (_is_inside_polygon($rpoint2, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already on intern points?<br />";
          if (!in_intern_array($rpoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already: no, -> asigning<br />";
            $rp = _get_right_point($rpoint2, _to_points_array($rxs, $rys));
            $lp = _get_left_point($rpoint2, _to_points_array($rxs, $rys));
            $intern_points[] = array(
              'point' => $rpoint2,
              'segment1' => array($lp, $rpoint2),
              'segment2' => array($rpoint2, $rp),
            );
          }
        }
               //echo ":: get_int_poly :: eval ({$upoint1[X]}, {$upoint1[Y]}) is inside rpolygon?<br />";
        if (_is_inside_polygon($upoint1, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already on intern points?<br />";
          if (!in_intern_array($upoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already: no, -> asigning<br />";
            $rp = _get_right_point($upoint1, _to_points_array($uxs, $uys));
            $lp = _get_left_point($upoint1, _to_points_array($uxs, $uys));
            $intern_points[] = array(
              'point' => $upoint1,
              'segment1' => array($lp, $upoint1),
              'segment2' => array($upoint1, $rp),
            );
          }
        }
               //echo ":: get_int_poly :: eval ({$upoint2[X]}, {$upoint2[Y]}) is inside rpolygon?<br />";
        if (_is_inside_polygon($upoint2, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already on intern points?<br />";
          if (!in_intern_array($upoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already: no, -> asigning<br />";
            $rp = _get_right_point($upoint2, _to_points_array($uxs, $uys));
            $lp = _get_left_point($upoint2, _to_points_array($uxs, $uys));
            $intern_points[] = array(
              'point' => $upoint2,
              'segment1' => array($lp, $upoint2),
              'segment2' => array($upoint2, $rp),
            );
          }
        }

      }
    }
    // process the final user line
    $uline = array( array($uxs[count($uxs)-1],$uys[count($uxs)-1]),  array($uxs[0],$uys[0]) );
    if ( ($ipoint=lines_intersect($rline,$uline)) != NULL ) {
      $rpoint1 = array($rxs[$i], $rys[$i]);
      $rpoint2 = array($rxs[$i+1], $rys[$i+1]);
      $upoint1 = array($uxs[$j], $uys[$j]);
      $upoint2 = array($uxs[$j+1], $uys[$j+1]);

      //save intesection points
      $intersection_points[] = array(
        'rsegment' => array( $rpoint1, $rpoint2 ),
        'usegment' => array( $upoint1, $upoint2 ),
        'point' => $ipoint,
      );
      //get intern points
               //echo ":: get_int_poly :: eval ({$rpoint1[X]}, {$rpoint1[Y]}) is inside upolygon?<br />";
      if (_is_inside_polygon($rpoint1, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already on intern points?<br />";
        if (!in_intern_array($rpoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($rpoint1, _to_points_array($rxs, $rys));
          $lp = _get_left_point($rpoint1, _to_points_array($rxs, $rys));
          $intern_points[] = array(
            'point' => $rpoint1,
            'segment1' => array($lp, $rpoint1),
            'segment2' => array($rpoint1, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$rpoint2[X]}, {$rpoint2[Y]}) is inside upolygon?<br />";
      if (_is_inside_polygon($rpoint2, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already on intern points?<br />";
        if (!in_intern_array($rpoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($rpoint2, _to_points_array($rxs, $rys));
          $lp = _get_left_point($rpoint2, _to_points_array($rxs, $rys));
          $intern_points[] = array(
            'point' => $rpoint2,
            'segment1' => array($lp, $rpoint2),
            'segment2' => array($rpoint2, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$upoint1[X]}, {$upoint1[Y]}) is inside rpolygon?<br />";
      if (_is_inside_polygon($upoint1, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already on intern points?<br />";
        if (!in_intern_array($upoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($upoint1, _to_points_array($uxs, $uys));
          $lp = _get_left_point($upoint1, _to_points_array($uxs, $uys));
          $intern_points[] = array(
            'point' => $upoint1,
            'segment1' => array($lp, $upoint1),
            'segment2' => array($upoint1, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$upoint2[X]}, {$upoint2[Y]}) is inside rpolygon?<br />";
      if (_is_inside_polygon($upoint2, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already on intern points?<br />";
        if (!in_intern_array($upoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($upoint2, _to_points_array($uxs, $uys));
          $lp = _get_left_point($upoint2, _to_points_array($uxs, $uys));
          $intern_points[] = array(
            'point' => $upoint2,
            'segment1' => array($lp, $upoint2),
            'segment2' => array($upoint2, $rp),
          );
        }
      }
    }
    // end of iterate through the user polygon sides
  }
  // process the final result line
  $rline = array( array($rxs[$i=count($rxs)-1],$rys[$i=count($rxs)-1]),  array($rxs[0],$rys[0]) );
  // iterate through the user polygon sides
  for ($j=0; $j < (count($uxs)-1); $j++) {
    $uline = array( array($uxs[$j],$uys[$j]),  array($uxs[$j+1],$uys[$j+1]) );
    if ( ($ipoint=lines_intersect($rline,$uline)) != NULL ) {
      $rpoint1 = array($rxs[$i], $rys[$i]);
      $rpoint2 = array($rxs[0], $rys[0]);
      $upoint1 = array($uxs[$j], $uys[$j]);
      $upoint2 = array($uxs[$j+1], $uys[$j+1]);

      //save intesection points
      $intersection_points[] = array(
        'rsegment' => array( $rpoint1, $rpoint2 ),
        'usegment' => array( $upoint1, $upoint2 ),
        'point' => $ipoint,
      );

      //get intern points
               //echo ":: get_int_poly :: eval ({$rpoint1[X]}, {$rpoint1[Y]}) is inside upolygon?<br />";
      if (_is_inside_polygon($rpoint1, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already on intern points?<br />";
        if (!in_intern_array($rpoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($rpoint1, _to_points_array($rxs, $rys));
          $lp = _get_left_point($rpoint1, _to_points_array($rxs, $rys));
          $intern_points[] = array(
            'point' => $rpoint1,
            'segment1' => array($lp, $rpoint1),
            'segment2' => array($rpoint1, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$rpoint2[X]}, {$rpoint2[Y]}) is inside upolygon?<br />";
      if (_is_inside_polygon($rpoint2, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already on intern points?<br />";
        if (!in_intern_array($rpoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($rpoint2, _to_points_array($rxs, $rys));
          $lp = _get_left_point($rpoint2, _to_points_array($rxs, $rys));
          $intern_points[] = array(
            'point' => $rpoint2,
            'segment1' => array($lp, $rpoint2),
            'segment2' => array($rpoint2, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$upoint1[X]}, {$upoint1[Y]}) is inside rpolygon?<br />";
      if (_is_inside_polygon($upoint1, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already on intern points?<br />";
        if (!in_intern_array($upoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($upoint1, _to_points_array($uxs, $uys));
          $lp = _get_left_point($upoint1, _to_points_array($uxs, $uys));
          $intern_points[] = array(
            'point' => $upoint1,
            'segment1' => array($lp, $upoint1),
            'segment2' => array($upoint1, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$upoint2[X]}, {$upoint2[Y]}) is inside rpolygon?<br />";
      if (_is_inside_polygon($upoint2, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already on intern points?<br />";
        if (!in_intern_array($upoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($upoint2, _to_points_array($uxs, $uys));
          $lp = _get_left_point($upoint2, _to_points_array($uxs, $uys));
          $intern_points[] = array(
            'point' => $upoint2,
            'segment1' => array($lp, $upoint2),
            'segment2' => array($upoint2, $rp),
          );
        }
      }

      }
    }
    // process the final user line
    $uline = array( array($uxs[count($uxs)-1],$uys[count($uxs)-1]),  array($uxs[0],$uys[0]) );
    if ( ($ipoint=lines_intersect($rline,$uline)) != NULL ) {
      $rpoint1 = array($rxs[$i], $rys[$i]);
      $rpoint2 = array($rxs[0], $rys[0]);
      $upoint1 = array($uxs[$j], $uys[$j]);
      $upoint2 = array($uxs[$j+1], $uys[$j+1]);

      //save intesection points
      $intersection_points[] = array(
        'rsegment' => array( $rpoint1, $rpoint2 ),
        'usegment' => array( $upoint1, $upoint2 ),
        'point' => $ipoint,
      );
      //get intern points
               //echo ":: get_int_poly :: eval ({$rpoint1[X]}, {$rpoint1[Y]}) is inside upolygon?<br />";
      if (_is_inside_polygon($rpoint1, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already on intern points?<br />";
        if (!in_intern_array($rpoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint1[X]}, {$rpoint1[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($rpoint1, _to_points_array($rxs, $rys));
          $lp = _get_left_point($rpoint1, _to_points_array($rxs, $rys));
          $intern_points[] = array(
            'point' => $rpoint1,
            'segment1' => array($lp, $rpoint1),
            'segment2' => array($rpoint1, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$rpoint2[X]}, {$rpoint2[Y]}) is inside upolygon?<br />";
      if (_is_inside_polygon($rpoint2, $uxs, $uys)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already on intern points?<br />";
        if (!in_intern_array($rpoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$rpoint2[X]}, {$rpoint2[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($rpoint2, _to_points_array($rxs, $rys));
          $lp = _get_left_point($rpoint2, _to_points_array($rxs, $rys));
          $intern_points[] = array(
            'point' => $rpoint2,
            'segment1' => array($lp, $rpoint2),
            'segment2' => array($rpoint2, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$upoint1[X]}, {$upoint1[Y]}) is inside rpolygon?<br />";
      if (_is_inside_polygon($upoint1, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already on intern points?<br />";
        if (!in_intern_array($upoint1, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint1[X]}, {$upoint1[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($upoint1, _to_points_array($uxs, $uys));
          $lp = _get_left_point($upoint1, _to_points_array($uxs, $uys));
          $intern_points[] = array(
            'point' => $upoint1,
            'segment1' => array($lp, $upoint1),
            'segment2' => array($upoint1, $rp),
          );
        }
      }
               //echo ":: get_int_poly :: eval ({$upoint2[X]}, {$upoint2[Y]}) is inside rpolygon?<br />";
      if (_is_inside_polygon($upoint2, $rxs, $rys)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) inside: yes<br />";
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already on intern points?<br />";
        if (!in_intern_array($upoint2, $intern_points)) {
               //echo ":: get_int_poly :: ({$upoint2[X]}, {$upoint2[Y]}) already: no, -> asigning<br />";
          $rp = _get_right_point($upoint2, _to_points_array($uxs, $uys));
          $lp = _get_left_point($upoint2, _to_points_array($uxs, $uys));
          $intern_points[] = array(
            'point' => $upoint2,
            'segment1' => array($lp, $upoint2),
            'segment2' => array($upoint2, $rp),
          );
        }
      }
    }
    // end of iterate through the user polygon sides
  // end of iterate through the result polygon sides
  return array($intern_points, $intersection_points);
}

/**
 * Verify if a list of points a is inside or not a polygon b
 */
function fully_inside($axs, $ays, $bxs, $bys) {
  // iterate through the points
  for ($i=0; $i < count($axs); $i++) {
    if (!_is_inside_polygon(array($axs[$i],$ays[$i]), $bxs, $bys)) {
      return FALSE;
    }
  }
  return TRUE;
}

function _is_next_to($point, $last_point, $xs, $ys, $between_points=FALSE) {
  $polygon = _to_points_array($xs, $ys);

  if ( ($pos=array_search($point, $polygon)) !== NULL  && !empty($pos)) {
               //echo ":: :: is_next_to :: pos found !!<br />";
    if ( $rp=_get_right_point($last_point['point'], $polygon) == $point
      || $lp=_get_left_point($last_point['point'], $polygon) == $point ) {
               //echo ":: :: is_next_to :: return simple next to !!<br />";
        return TRUE;
      }
  }
  else if ($between_points) {
               //echo ":: :: is_next_to :: between points !!<br />";
    switch ($last_point['type']) {
    case 'intern':
               //echo ":: :: is_next_to :: between points :: intern !!<br />";
      $right_point = _get_right_point($last_point['point'], $polygon);
      $left_point = _get_left_point($last_point['point'], $polygon);
               /*echo 'left = '. print_r($left_point,1)
                   .'right = '. print_r($right_point,1)
                   .'point = '. print_r($point,1)
                   .'last_point = '. print_r($last_point,1);*/
      //if (_is_in_segment($point, $last_point['segment'][0], $last_point['segment'][1])) {
      if ( _is_in_segment($point['point'], $last_point['segment1'][0], $last_point['segment1'][1])
        || _is_in_segment($point['point'], $last_point['segment2'][0], $last_point['segment2'][1])
        ) {
               //echo ":: :: is_next_to :: between points :: intern :: return in line !!<br />";
        return TRUE;
      }
      break;
    case 'intersection':
               //echo ":: :: is_next_to :: between points :: intersection !!<br />";
   //         echo "entro a next_to_interseccion !!<br />";
  //echo "point=". print_r($point,1) .', last_point='. print_r($last_point) .'<br />';
      if ( _is_in_segment($point, $last_point['rsegment'][0], $last_point['rsegment'][1])
        || _is_in_segment($point, $last_point['usegment'][0], $last_point['usegment'][1])
        ) {
               //echo ":: :: is_next_to :: between points :: intersection :: return in line !!<br />";
        return TRUE;
      }
      break;
    }
  }
  return FALSE;
}

function _get_right_point($point, $polygon) {
  if ( ($pos=array_search($point, $polygon)) !== FALSE ) {
    if ($pos == count($polygon)-1) {
      return $polygon[0];
    }
    else {
      return $polygon[$pos+1];
    }
  }
}

function _get_left_point($point, $polygon) {
  //echo '==========?<br>'; print_r($polygon) . print_r($polygon_point) .'========<br>';
  if ( ($pos=array_search($point, $polygon)) !== FALSE ) {
    if ($pos == 0) {
      return $polygon[count($polygon)-1];
    }
    else {
      return $polygon[$pos-1];
    }
  }
}

function _to_points_array($xs, $ys) {
  if (!_check_polygon($xs, $ys)) {
    return FALSE;
  }

  $points = array();
  for ($i=0; $i < count($xs); $i++) {
    $points[] = array($xs[$i], $ys[$i]);
  }
  return $points;
}

function _to_points_intersection($xs, $ys) {
  if (!_check_polygon($xs, $ys)) {
    return FALSE;
  }

  $points = array();
  for ($i=0; $i < count($xs); $i++) {
    $points[] = array('point' => array($xs[$i], $ys[$i]));
  }
  return $points;
}

function in_intern_array($point, $intern_points) {
  foreach ($intern_points as $ipoint) {
    if (abs($ipoint['point'][X]-$point[X]) < APROX && abs($ipoint['point'][Y]-$point[Y]) < APROX)
      return TRUE;
  }
  return FALSE;
}

//ver si cada punto esta dentro o fuera de la figura par saber con cual comenzar