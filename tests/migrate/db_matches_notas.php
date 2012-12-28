<?php
/**
 * This script defines as closely as possible the matches between
 * original tables and destination (chamilo) tables and fields.
 * See db_matches.dist.php for formatting documentation
 */
//intIdTipoEmpleado = 2  = Profesor
// This is an array that matches objects (for Chamilo)
$matches = array(
    //Asistencia - put in comment for now
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_AlumnoAsistencia a ON (a.codigoPrograma = p.uidIdPrograma AND p.bitVigencia = 1 )
                    INNER JOIN Alumno al ON (al.uidIdAlumno = a.codigoAlumno AND al.bitVigencia = 1)
                    WHERE 1=1 ',
        'dest_func' => 'MigrationCustom::create_attendance',
        'dest_table' => 'session',
        'fields_match' => array(        
            array(
                'orig' => 'al.uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'user_id',
                'func' => 'get_user_id_by_persona_id',
            ),
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'a.fecha',
                'sql_alter' => 'clean_date_time',
                'dest' => 'fecha',
                'func' => '',
            ),
            array(
                'orig' => 'a.estado',
                'sql_alter' => '',
                'dest' => 'status',
                'func' => 'convert_attendance_status',
            ),
        )
    ),
    //Course advance
/*
    array(
        'orig_table' => 'ProgramaAcademico',
        //ORDER BY p.uidIdPrograma, a.Unidad, un.Descripcion
        'query' => 'SELECT DISTINCT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_AvanceCursoPrograma a ON (a.Programa = p.uidIdPrograma AND p.bitVigencia = 1)
                    INNER JOIN le_Unidad un ON (un.CodigoUnidad = a.Unidad)                    
                    ',
        'dest_func' => 'MigrationCustom::create_thematic',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'un.Descripcion',
                'sql_alter' => '',
                'dest' => 'thematic_plan',
                'func' => '',
            ),
            array(
                'orig' => 'a.Unidad',
                'sql_alter' => '',
                'dest' => 'thematic',
                'func' => '',
            ),
        )
    ),    
*/

    //Gradebook - create gradebook evaluations types
    array(
        'orig_table' => 'gradebook_evaluation_type',
        'query' => 'SELECT %s '.
                   ' FROM le_ConceptoCalificatorioTipo '.
                   ' WHERE 1 = 1 ',
        'dest_func' => 'MigrationCustom::add_evaluation_type',
        'dest_table' => 'session',
        'fields_match' => array(
            array(
                'orig' => 'Descripcion',
                'sql_alter' => '',
                'dest' => 'name',
                'func' => '',
            ),
            array(
                'orig' => 'CodigoTipo',
                'sql_alter' => '',
                'dest' => 'external_id',
                'func' => '',
            )            
        )
    ),    
    //Gradebook - create gradebook evaluations
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT DISTINCT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_Nota n ON (n.codigoPrograma = p.uidIdPrograma AND p.bitVigencia = 1 )
                    INNER JOIN le_ConceptoCalificatorio cc ON (cc.CodigoConcepto = n.CodigoConcepto)           
                    WHERE 1 = 1 ',
        'dest_func' => 'MigrationCustom::create_gradebook_evaluation',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'cc.CodigoTipo',
                'sql_alter' => '',
                'dest' => 'gradebook_evaluation_type_id',
                'func' => 'get_evaluation_type',
            ),
            array(
                'orig' => 'cc.Descripcion',
                'sql_alter' => '',
                'dest' => 'gradebook_description',
                'func' => '',
            ),
        )
    ),
    //Nota <= 2009 (ahora también para > 2009)
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT DISTINCT %s '.
                   'FROM ProgramaAcademico as p '.
                   'INNER JOIN Nota n ON (n.uidIdPrograma = p.uidIdPrograma AND p.bitVigencia = 1 ) '.
                   'INNER JOIN Alumno a ON (a.uidIdAlumno = n.uidIdAlumno) '.
                   '',
        'dest_func' => 'MigrationCustom::add_gradebook_result_with_evaluation',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'user_id',
                'func' => 'get_user_id_by_persona_id',
            ),
            array(
                'orig' => 'tinNota',
                'sql_alter' => '',
                'dest' => 'nota',
                'func' => '',
            ),            
            array(
                'orig' => 'n.sdtFechaModificacion',
                'sql_alter' => '',
                'dest' => 'fecha',
                'func' => '',
            ),
        )
    ),
/*
    'web_service_calls' =>  array(       
       'url' => "http://www2.icpna.edu.pe/wsprueba/service.asmx?wsdl",
       'filename' => 'migration.custom.class.php',
       'class' => 'MigrationCustom',       
    ),
    //le_Nota
    //>= 2010',
    /*
    array(
        'orig_table' => 'ProgramaAcademico',
        'query' => 'SELECT DISTINCT %s
                    FROM ProgramaAcademico as p 
                    INNER JOIN le_Nota n ON (n.codigoPrograma = p.uidIdPrograma AND p.bitVigencia = 1 )
                    INNER JOIN Alumno a ON (n.CodigoAlumno = a.uidIdAlumno)
                    WHERE YEAR(UltimaFechaModif) >= 2010',
        'dest_func' => 'MigrationCustom::add_gradebook_result',
        'dest_table' => 'session',
        'fields_match' => array(         
            array(
                'orig' => 'p.uidIdPrograma',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'session_id',
                'func' => 'get_session_id_by_programa_id',
            ),
            array(
                'orig' => 'uidIdPersona',
                'sql_alter' => 'sql_alter_unhash_50',
                'dest' => 'user_id',
                'func' => 'get_user_id_by_persona_id',
            ),
            array(
                'orig' => 'Nota',
                'sql_alter' => '',
                'dest' => 'nota',
                'func' => '',
            ),            
            array(
                'orig' => 'UltimaFechaModif',
                'sql_alter' => '',
                'dest' => 'fecha',
                'func' => 'clean_date_time',
            ),
        )
    ),  

     */
);
