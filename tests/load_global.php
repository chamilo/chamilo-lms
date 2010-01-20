<?php
	require_once(dirname(__FILE__).'/../main/inc/global.inc.php');
	require_once(api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
	
	$cidReq = 'COURSEX';
    // check if course exists 
    $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $sql = "SELECT code FROM  $table_course WHERE code = '$cidReq' ";
    $rs = Database::query($sql, __FILE__, __LINE__);
	$row = Database::fetch_row($rs);
	if (empty($row[0])) {
		// create a course
		$course_datos = array(
				'wanted_code'=> 'COURSEX',
				'title'=>'COURSEX',
				'tutor_name'=>'John Doe',
				'category_code'=>'LANG',
				'course_language'=>'spanish',
				'course_admin_id'=>'001',
				'db_prefix'=> $_configuration['db_prefix'],
				'firstExpirationDelay'=>'999'
				);
		$res = create_course($course_datos['wanted_code'], $course_datos['title'],
							 $course_datos['tutor_name'], $course_datos['category_code'],
							 $course_datos['course_language'],$course_datos['course_admin_id'],
							 $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);
	}

	$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	$course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
    $sql =    "SELECT course.*, course_category.code faCode, course_category.name faName
             FROM $course_table
             LEFT JOIN $course_cat_table
             ON course.category_code = course_category.code
             WHERE course.code = '$cidReq'";
    $result = Database::query($sql,__FILE__,__LINE__);

	
    if (Database::num_rows($result)>0) {
        $cData = Database::fetch_array($result);
        $_cid                            = $cData['code'             ];
		$_course = array();
		$_course['id'          ]         = $cData['code'             ]; //auto-assigned integer
		$_course['name'        ]         = $cData['title'         ];
        $_course['official_code']         = $cData['visual_code'        ]; // use in echo
        $_course['sysCode'     ]         = $cData['code'             ]; // use as key in db
        $_course['path'        ]         = $cData['directory'        ]; // use as key in path
        $_course['dbName'      ]         = $cData['db_name'           ]; // use as key in db list
        $_course['dbNameGlu'   ]         = $_configuration['table_prefix'] . $cData['db_name'] . $_configuration['db_glue']; // use in all queries
        $_course['titular'     ]         = $cData['tutor_name'       ];
        $_course['language'    ]         = $cData['course_language'   ];
        $_course['extLink'     ]['url' ] = $cData['department_url'    ];
        $_course['extLink'     ]['name'] = $cData['department_name'];
        $_course['categoryCode']         = $cData['faCode'           ];
        $_course['categoryName']         = $cData['faName'           ];
        $_course['visibility'  ]         = $cData['visibility'];
        $_course['subscribe_allowed']    = $cData['subscribe'];
		$_course['unubscribe_allowed']   = $cData['unsubscribe'];

        api_session_register('_cid');
        api_session_register('_course');
    }
         
    $_SESSION['_user']['user_id'] = 1;    
    $_SESSION['is_courseAdmin'] = 1;

?>
