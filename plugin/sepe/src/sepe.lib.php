<?php
/**
 * Functions
 * @package chamilo.plugin.sepe
 */
//require_once __DIR__ . '../../../main/inc/global.inc.php'; 
//require_once '../config.php';
//require_once api_get_path(LIBRARY_PATH).'plugin.class.php';
require_once 'sepe_plugin.class.php';

$tableSepeCenter = Database::get_main_table(SepePlugin::TABLE_SEPE_CENTER);
$tableSepeActions = Database::get_main_table(SepePlugin::TABLE_SEPE_ACTIONS);
$tableSepeSpecialty = Database::get_main_table(SepePlugin::TABLE_SEPE_SPECIALTY);
$tableSepeSpecialtyClassroom = Database::get_main_table(SepePlugin::TABLE_SEPE_SPECIALTY_CLASSROOM);
$tableSepeSpecialtyTutors = Database::get_main_table(SepePlugin::TABLE_SEPE_SPECIALTY_TUTORS);
$tableSepeTutors = Database::get_main_table(SepePlugin::TABLE_SEPE_TUTORS);
$tableSepeParticipants = Database::get_main_table(SepePlugin::TABLE_SEPE_PARTICIPANTS);
$tableSepeParticipantsSpecialty = Database::get_main_table(SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY);
$tableSepeParticipantsSpecialtyTutorials = Database::get_main_table(SepePlugin::TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS);
$tableSepeCourseActions = Database::get_main_table(SepePlugin::TABLE_SEPE_COURSE_ACTIONS);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tableUser = Database::get_main_table(TABLE_MAIN_USER);
$tableCentros = Database::get_main_table(SepePlugin::TABLE_SEPE_CENTROS);
$tableTutorE = Database::get_main_table(SepePlugin::TABLE_SEPE_TUTORS_EMPRESA);
$tableSepeCourseActions = Database::get_main_table(SepePlugin::TABLE_SEPE_COURSE_ACTIONS);

function datos_identificativos()
{
    global $tableSepeCenter;
    $sql = "SELECT * FROM $tableSepeCenter;";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function obtener_cod_action($cod) 
{
    global $tableSepeCourseActions;
    $sql = "SELECT cod_action FROM $tableSepeCourseActions WHERE id_course='".$cod."';";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['cod_action'];
}

function obtener_course($cod) 
{
    global $tableSepeCourseActions;
    $sql = "SELECT id_course FROM $tableSepeCourseActions WHERE cod_action='".$cod."';";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['id_course'];
}
function obtener_course_code($cod) 
{
    global $tableCourse;
    $course_id = obtener_course($cod);
    $sql = "SELECT code FROM $tableCourse WHERE id='".$course_id."'";    
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['code'];
}

function obtener_participant($cod) 
{
    global $tableSepeParticipantsSpecialty;
    $sql = "SELECT cod_participant FROM  $tableSepeParticipantsSpecialty WHERE cod='".$cod."';";
    $rs = Database::query($sql);
    $aux = Database::fetch_assoc($rs);
    return $aux['cod_participant'];
}

function accion_formativa($cod) 
{
    global $tableSepeActions;
    $sql = "SELECT * FROM $tableSepeActions WHERE cod='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function especialidad_accion($cod) 
{
    global $tableSepeSpecialty;
    $sql = "SELECT * FROM $tableSepeSpecialty WHERE cod='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function especialidad_classroom($cod) 
{
    global $tableSepeSpecialtyClassroom;
    global $tableCentros;
    $sql = "SELECT a.*,ORIGEN_CENTRO,CODIGO_CENTRO FROM $tableSepeSpecialtyClassroom a LEFT JOIN $tableCentros b
            ON a.cod_centro=b.cod WHERE a.cod='".$cod."';";
    //echo $sql; exit;
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function especialidad_tutorial($cod) 
{
    global $tableSepeParticipantsSpecialtyTutorials;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialtyTutorials WHERE cod='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function list_tutor($cod_specialty)
{
    global $tableSepeSpecialtyTutors;
    $sql = "SELECT * FROM $tableSepeSpecialtyTutors WHERE cod_specialty='".$cod_specialty."';";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function listado_centros() 
{
    global $tableCentros;
       $sql = "SELECT * FROM $tableCentros;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listadoTutorE($cond="empresa='SI'") 
{
    global $tableTutorE;
       $sql = "SELECT * FROM $tableTutorE WHERE ".$cond." ORDER BY alias ASC, NUM_DOCUMENTO ASC;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $tmp = array();
        $tmp['cod'] = $row['cod'];
        if (trim($row['alias'])!='') {
            $tmp['alias'] = $row['alias'].' - '.$row['TIPO_DOCUMENTO'].' '.$row['NUM_DOCUMENTO'].' '.$row['LETRA_NIF'];    
        } else {
            $tmp['alias'] = $row['TIPO_DOCUMENTO'].' '.$row['NUM_DOCUMENTO'].' '.$row['LETRA_NIF'];    
        }
        $aux[] = $tmp;
    }
    return $aux;
}

function listadoTutorF() 
{
    global $tableTutorE;
       $sql = "SELECT * FROM $tableTutorE WHERE formacion='SI' ORDER BY alias ASC, NUM_DOCUMENTO ASC;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $tmp = array();
        $tmp['cod'] = $row['cod'];
        if (trim($row['alias'])!='') {
            $tmp['alias'] = $row['alias'].' - '.$row['TIPO_DOCUMENTO'].' '.$row['NUM_DOCUMENTO'].' '.$row['LETRA_NIF'];    
        } else {
            $tmp['alias'] = $row['TIPO_DOCUMENTO'].' '.$row['NUM_DOCUMENTO'].' '.$row['LETRA_NIF'];    
        }
        $aux[] = $tmp;
    }
    return $aux;
}

function listado_tutores() 
{
    global $tableSepeTutors;
    global $tableUser;
       $sql = "SELECT a.*, b.firstname AS firstname, b.lastname AS lastname 
            FROM $tableSepeTutors AS a, $tableUser AS b 
            WHERE a.cod_user_chamilo=b.user_id;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listado_tutores_specialty($cod_specialty) 
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    global $tableUser;
    $sql = "SELECT cod_tutor FROM $tableSepeSpecialtyTutors;";
    $rs = Database::query($sql);
    $lista_tutores = array();
    while ($tmp = Database::fetch_assoc($rs)) {
        $lista_tutores[] = $tmp['cod_tutor'];
    }
       $sql = "SELECT a.*, b.firstname AS firstname, b.lastname AS lastname 
            FROM $tableSepeTutors AS a LEFT JOIN $tableUser AS b ON a.cod_user_chamilo=b.user_id;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        if (!in_array($row['cod'],$lista_tutores)) {
            $tutor = array();
            $tutor['cod'] = $row['cod'];
            if (trim($row['firstname'])!='' || trim($row['lastname'])!='') {
                $tutor['datos'] = $row['firstname'].' '.$row['lastname'].' ('.$row['TIPO_DOCUMENTO'].' '.$row['NUM_DOCUMENTO'].' '.$row['LETRA_NIF'].' )';    
            } else {
                $tutor['datos'] = $row['TIPO_DOCUMENTO'].' '.$row['NUM_DOCUMENTO'].' '.$row['LETRA_NIF'];    
            }
            $aux[] = $tutor;
        }
    }
    return $aux;
}

function especialidad_tutor($cod) 
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    $sql = "SELECT a.*,cod_user_chamilo,TIPO_DOCUMENTO,NUM_DOCUMENTO,LETRA_NIF FROM $tableSepeSpecialtyTutors a
            INNER JOIN $tableSepeTutors b ON a.cod_tutor=b.cod 
            WHERE a.cod='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function limpiarAsignadosProfesores($listProfesor,$cod_specialty,$cod_profesor_chamilo) 
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    $sql = "SELECT cod_tutor FROM $tableSepeSpecialtyTutors WHERE cod_specialty='".$cod_specialty."';";
    $rs = Database::query($sql);
    if (Database::num_rows($rs)>0) {
        while ($aux = Database::fetch_assoc($rs)) {
            $sql = "SELECT cod_user_chamilo FROM $tableSepeTutors WHERE cod='".$aux['cod_tutor']."';";
            $res = Database::query($sql);
            if (Database::num_rows($res)>0) {
                $tmp = Database::fetch_assoc($res);
                if ($tmp['cod_user_chamilo']!=$cod_profesor_chamilo) {
                    unset($listProfesor[$tmp['cod_user_chamilo']]);
                }
            }
        }
    }
    return $listProfesor;
}

function participante_accion($cod) 
{
    global $tableSepeParticipants;
    $sql = "SELECT * FROM $tableSepeParticipants WHERE cod='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function especialidad_participante($cod) 
{
    global $tableSepeParticipantsSpecialty;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialty WHERE cod='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function tutorias_especialidad_participante($cod) 
{
    global $tableSepeParticipantsSpecialtyTutorials;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialtyTutorials WHERE cod='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function existeDatosIdentificativos() 
{
    global $tableSepeCenter;
    $sql = "SELECT 1 FROM $tableSepeCenter;";
    $result = Database::query($sql);
    if ( Database::affected_rows($result) > 0 ) {
        return true;
    } else {
        return false;
    }
}

/**
 * List specialties
 * @return array Results (list of specialty details)
 */
function listSpecialty($cod)
{
    global $tableSepeSpecialty;
    $sql = "SELECT cod, ORIGEN_ESPECIALIDAD, AREA_PROFESIONAL, CODIGO_ESPECIALIDAD
            FROM $tableSepeSpecialty
            WHERE cod_action='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * List participants
 * @return array Results (list of participants details)
 */
function listParticipant($cod)
{
    global $tableSepeParticipants;
    global $tableUser;
    $sql = "SELECT cod, TIPO_DOCUMENTO, NUM_DOCUMENTO, LETRA_NIF, firstname, lastname
            FROM $tableSepeParticipants LEFT JOIN $tableUser ON $tableSepeParticipants.cod_user_chamilo=$tableUser.user_id
            WHERE cod_action='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * List classroom
 * @return array Results (list of classroom details)
 */
function listClassroom($cod)
{
    global $tableSepeSpecialtyClassroom;
    global $tableCentros;
    $sql = "SELECT a.*,ORIGEN_CENTRO,CODIGO_CENTRO FROM $tableSepeSpecialtyClassroom a LEFT JOIN $tableCentros b 
            ON a.cod_centro=b.cod WHERE cod_specialty='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * List tutors
 * @return array Results (list of tutors details)
 */
function listTutors($cod)
{
    global $tableSepeSpecialtyTutors;
    global $tableSepeTutors;
    global $tableUser;
    $aux = array();
    $sql = "SELECT a.*,TIPO_DOCUMENTO,NUM_DOCUMENTO,LETRA_NIF, firstname, lastname FROM $tableSepeSpecialtyTutors a 
            INNER JOIN $tableSepeTutors b ON a.cod_tutor=b.cod 
            LEFT JOIN $tableUser c ON b.cod_user_chamilo=c.user_id 
            WHERE a.cod_specialty='".$cod."';";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * List participants specialty
 * @return array Results (list of participants specialty details)
 */
function listParticipantSpecialty($cod)
{
    global $tableSepeParticipantsSpecialty;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialty WHERE cod_participant='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * List participants specialty
 * @return array Results (list of participants specialty details)
 */
function listSpecialtyTutorial($cod)
{
    global $tableSepeParticipantsSpecialtyTutorials;
    $sql = "SELECT * FROM $tableSepeParticipantsSpecialtyTutorials WHERE cod_participant_specialty='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

/**
 * List of courses associated with formative actions
 * @return array Results (list of courses id)
 */
function listCourseAction()
{
    global $tableSepeActions;
    global $tableSepeCourseActions;
    //global ;
    //$table = Database::get_main_table(TABLE_SEPE_COURSE_ACTIONS);
    $sql = "SELECT $tableSepeCourseActions.*, course.title AS title, $tableSepeActions.ORIGEN_ACCION AS ORIGEN_ACCION, $tableSepeActions.CODIGO_ACCION AS CODIGO_ACCION 
            FROM $tableSepeCourseActions, course, $tableSepeActions 
            WHERE $tableSepeCourseActions.id_course=course.id AND $tableSepeActions.cod=$tableSepeCourseActions.cod_action";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}

function listCourseFree()
{
    global $tableCourse;
    global $tableSepeCourseActions;
    $sql = "SELECT id, title FROM $tableCourse
            WHERE NOT EXISTS (
                SELECT * FROM $tableSepeCourseActions WHERE $tableCourse.id = $tableSepeCourseActions.id_course)
            ;";
    $res = Database::query($sql);
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}


function listActionFree()
{
    global $tableSepeActions;
    global $tableSepeCourseActions;
   $sql = "SELECT cod, ORIGEN_ACCION,CODIGO_ACCION    FROM $tableSepeActions
            WHERE NOT EXISTS (
                SELECT * FROM $tableSepeCourseActions WHERE $tableSepeActions.cod = $tableSepeCourseActions.cod_action)
            ;";
    $res = Database::query($sql);
    $aux = array();
    while ($row = Database::fetch_assoc($res)) {
        $aux[] = $row;
    }
    return $aux;
}


/**
* function texto_aleatorio (integer $long = 5, boolean $lestras_min = true, boolean $letras_max = true, boolean $num = true))
*
* Permite generar contrasenhas de manera aleatoria.
*
* @$long: Especifica la longitud de la contrasenha
* @$letras_min: Podra usar letas en minusculas
* @$letras_max: Podra usar letas en mayusculas
* @$num: Podra usar numeros
*
* return string
*/

function texto_aleatorio ($long = 6, $letras_min = true, $letras_max = true, $num = true) 
{
    $salt = $letras_min?'abchefghknpqrstuvwxyz':'';
    $salt .= $letras_max?'ACDEFHKNPRSTUVWXYZ':'';
    $salt .= $num?(strlen($salt)?'2345679':'0123456789'):'';

    if (strlen($salt) == 0) {
        return '';
    }

    $i = 0;
    $str = '';

    srand((double)microtime()*1000000);

    while ($i < $long) {
        $num = rand(0, strlen($salt)-1);
        $str .= substr($salt, $num, 1);
        $i++;
    }

    return $str;
}

function info_tutor_rel_profesor($cod) 
{
    global $tableSepeTutors;
    $sql = "SELECT * FROM $tableSepeTutors WHERE cod_user_chamilo='".$cod."';";
    $res = Database::query($sql);
    $aux = array();
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);
    } else {
        $row = false;
    }
    return $row;
}

function info_compentencia_docente($code) 
{
    $sql = "SELECT * FROM plugin_sepe_competencia_docente WHERE code='".$code."';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    return $row['valor'];
}

function obtener_usuario_chamilo($cod_participant) 
{
    global $tableSepeParticipants;
    $sql = "SELECT * FROM $tableSepeParticipants WHERE cod='".$cod_participant."';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    if ($row['cod_user_chamilo']=='0' || $row['cod_user_chamilo']=='') {
        return false;
    } else {
        return $row['cod_user_chamilo'];
    }
}
/*
function obtener_modulos_alumno_accion($user_id, $cod_action) 
{
    global $tableSepeParticipants;
    global $tableSepeParticipantsSpecialty;
    $sql = "SELECT cod FROM $tableSepeParticipants WHERE cod_action='".$cod_action."' AND cod_user_chamilo='".$user_id."';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    if ($row['cod']=='' || $row['cod']==0) {
        return 'No sincronizado con acción formativa';
    } else {
        $sql = "SELECT COUNT(*) AS num FROM $tableSepeParticipantsSpecialty WHERE cod_participant='".$row['cod']."';";
        $res = Database::query($sql);
        $tmp = Database::fetch_assoc($res);
        $resultado = $tmp['num'];
        return $resultado;
    }
}
*/
