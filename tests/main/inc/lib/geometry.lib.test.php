<?php
class TestGeometry extends UnitTestCase {

	public function TestGeometry(){
		$this->UnitTestCase('Geometry library - main/inc/lib/geometry.lib.test.php');
	}

	public function testCalculateArea(){
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$res = calculate_area($xs,$ys);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testSubCalculation(){
		$a=1;
		$b=2;
		$res = subCalculation($a,$b);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	public function testLinesIntersect(){
		$line1 = array(1,2);
		$line2 = array(3,4);
		$res = lines_intersect($line1,$line2);
		$this->assertNull($res);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testIsInSegment(){
		$p= 1;
		$a= 2;
		$b= 3;
		$res = _is_in_segment($p, $a, $b);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testIsInLine(){
		$p=1;
		$a=2;
		$b=3;
		$res = _is_in_line($p, $a, $b);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testGetSlope(){
		$p=1;
		$q=2;
		$res = _get_slope($p, $q);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res === 0);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testCheckPolygon(){
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$res = _check_polygon($xs, $ys);
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testPolygon2String(){
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$res = polygon2string($xs, $ys);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testIsInsidePolygon(){
		$p=1;
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$res =  _is_inside_polygon($p, $xs, $ys);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testGetInsideConditionals(){
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$res = _get_inside_conditionals($xs, $ys);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testGetIntersectionData(){
		$rxs=array(1,2,3);
		$rys=array(2,3,4);
		$uxs=array(5,6,7);
		$uys=array(8,9,10);
		$res = get_intersection_data($rxs, $rys, $uxs, $uys);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testGetIntersectionPolygon(){
		$rxs=array(1,2,3);
		$rys=array(2,3,4);
		$uxs=array(5,6,7);
		$uys=array(8,9,10);
		$res = get_intersection_polygon($rxs, $rys, $uxs, $uys);
		$this->assertNull($res);
		$this->assertTrue($res === null);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testGetIntersectionPolygonData(){
		$rxs=array(1,2,3);
		$rys=array(2,3,4);
		$uxs=array(5,6,7);
		$uys=array(8,9,10);
		$res = _get_intersection_polygon_data($rxs, $rys, $uxs, $uys);
		$this->assertTrue(is_array($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function testFUllyInside(){
		$axs=array(1,2,3);
		$ays=array(2,3,4);
		$bxs=array(5,6,7);
		$bys=array(8,9,10);
		$res = fully_inside($axs, $ays, $bxs, $bys);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	public function testIsNextTo(){
		$point = 1;
		$last_point = 2;
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$between_points = FALSE;
		$res = _is_next_to($point, $last_point, $xs, $ys, $between_points);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === false);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testGetRightPoint(){
		$point=1;
		$polygon= array(12,12);
		$res = _get_right_point($point, $polygon);
		$this->assertNull($res);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testGetLeftPoint(){
		$point = 1;
		$polygon= array(12,12);
		$res = _get_left_point($point, $polygon);
		$this->assertNull($res);
		$this->assertFalse($res);
		//var_dump($res);
	}

	public function testToPointsArray(){
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$res = _to_points_array($xs, $ys);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testToPointsIntersection(){
		$xs = array(1,2,3);
		$ys = array(4,5,6);
		$res = _to_points_intersection($xs, $ys);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	public function testInInternArray(){
		$point = 2;
		$intern_points = array(1,2);
		$res = in_intern_array($point,$intern_points);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}
}
?>
