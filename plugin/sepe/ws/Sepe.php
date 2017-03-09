<?php

/**
 * Class Sepe
 */
class Sepe
{
    
    /**
     * @param crearCentroInput[] $crearCentroInput
     *
     * @return array
     */
    public function crearCentro($crearCentroInput)
    {
		error_log('function crearCentro()');
        /* ***************************** Log de seguimiento ****************************** */
		
		$table_log = Database::get_main_table('plugin_sepe_log');
		$params_log = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "crearCentro",
			'fecha' => date("Y-m-d H:i:s")
        );
		Database::insert($table_log, $params_log);
		/* ******************************************************************************* */
		
		// Code
        $crearCentroInput = $crearCentroInput->DATOS_IDENTIFICATIVOS;
        $origen_centro = $crearCentroInput->ID_CENTRO->ORIGEN_CENTRO;
        $codigo_centro = $crearCentroInput->ID_CENTRO->CODIGO_CENTRO;
        $nombre_centro = $crearCentroInput->NOMBRE_CENTRO;
        $url_plataforma = $crearCentroInput->URL_PLATAFORMA;
        $url_seguimiento = $crearCentroInput->URL_SEGUIMIENTO;
        $telefono = $crearCentroInput->TELEFONO;
        $email = $crearCentroInput->EMAIL;

        if (empty($origen_centro) ||
            empty($codigo_centro) ||
            empty($nombre_centro) ||
            empty($url_plataforma) ||
            empty($url_seguimiento) ||
            empty($telefono) ||
            empty($email)
        ) {
            error_log('no data');
            return array(
                "RESPUESTA_DATOS_CENTRO" => array(
                    "CODIGO_RETORNO" => "2",
                    "ETIQUETA_ERROR" => "Error en parametro",
                    "DATOS_IDENTIFICATIVOS" => $crearCentroInput
                )
            );
        }

        $table = Database::get_main_table('plugin_sepe_center');

        // Comprobamos si existen datos almacenados previamente
        if (Database::count_rows($table) > 0) {
            // Hay datos en la tabla; comprobamos si tiene acciones
            $table_acciones = Database::get_main_table('plugin_sepe_actions');
            if (Database::count_rows($table_acciones) > 0) {
                error_log('Centro con acciones');
                return array(
                    "RESPUESTA_DATOS_CENTRO" => array(
                        "CODIGO_RETORNO" => "1",
                        "ETIQUETA_ERROR" => "Centro con acciones",
                        "DATOS_IDENTIFICATIVOS" => $crearCentroInput
                    )
                );
            } else {
                $sql = "DELETE FROM $table";
                Database::query($sql);
            }
        }

        $params = array(
            'origen_centro' => $origen_centro,
            'codigo_centro' => $codigo_centro,
            'nombre_centro' => $nombre_centro,
            'url' => $url_plataforma,
            'url_seguimiento' => $url_seguimiento,
            'telefono' => $telefono,
            'email' => $email,
        );

        $id = Database::insert($table, $params);

        if (empty($id)) {
            error_log('error');

            return array(
                "RESPUESTA_DATOS_CENTRO" => array(
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema base de datos",
                    "DATOS_IDENTIFICATIVOS" => $crearCentroInput
                )
            );
        } else {
            return array(
                "RESPUESTA_DATOS_CENTRO" => array(
                    "CODIGO_RETORNO" => "0",
                    "ETIQUETA_ERROR" => "Correcto",
                    "DATOS_IDENTIFICATIVOS" => $crearCentroInput
                )
            );
        }
    }

    /**
     *
     * @return array
     */
    public function obtenerDatosCentro()
    {
		error_log("function obtenerDatosCentro()");
		/* ***************************** Log de seguimiento ****************************** */
		$table_log = Database::get_main_table('plugin_sepe_log');
		$params_log = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "obtenerDatosCentro",
			'fecha' => date("Y-m-d H:i:s")
        );
		Database::insert($table_log, $params_log);
		/* ******************************************************************************* */
		
        // Code
        $table = Database::get_main_table('plugin_sepe_center');

        // Comprobamos si existen datos almacenados previamente
        if (Database::count_rows($table) > 0) {
            //Hay datos en la tabla;
            $sql = "SELECT * FROM $table;";
            $rs = Database::query($sql);
            if (!$rs) {
                return array(
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema acceso base de datos",
                    "DATOS_IDENTIFICATIVOS" => ''
                );
            } else {
				$row = Database::fetch_assoc($rs);
                $origen_centro = $row['origen_centro'];
                $codigo_centro = $row['codigo_centro'];
                $nombre_centro = $row['nombre_centro'];
                $url_plataforma = $row['url'];
                $url_seguimiento = $row['url_seguimiento'];
                $telefono = $row['telefono'];
                $email = $row['email'];

                $datos = new stdClass();
                $datos->ID_CENTRO = new stdClass();
                $datos->ID_CENTRO->ORIGEN_CENTRO = $origen_centro;
                $datos->ID_CENTRO->CODIGO_CENTRO = $codigo_centro;
                $datos->NOMBRE_CENTRO = $nombre_centro;
                $datos->URL_PLATAFORMA = $url_plataforma;
                $datos->URL_SEGUIMIENTO = $url_seguimiento;
                $datos->TELEFONO = $telefono;
                $datos->EMAIL = $email;

                $obj = new stdClass();
                $obj->CODIGO_RETORNO = 0;
                $obj->ETIQUETA_ERROR = 'Correcto';
                $obj->DATOS_IDENTIFICATIVOS = new SoapVar($datos, SOAP_ENC_OBJECT);

                $result = new stdClass();
                $result->RESPUESTA_DATOS_CENTRO = new SoapVar($obj, SOAP_ENC_OBJECT);
		
                //error_log(print_r($result, 1));

                return $result;
            }
        } else {
			$datos = new stdClass();
			$datos->ID_CENTRO = new stdClass();
			$datos->ID_CENTRO->ORIGEN_CENTRO = '';
			$datos->ID_CENTRO->CODIGO_CENTRO = '';
			$datos->NOMBRE_CENTRO = '';
			$datos->URL_PLATAFORMA = '';
			$datos->URL_SEGUIMIENTO = '';
			$datos->TELEFONO = '';
			$datos->EMAIL = '';

			$obj = new stdClass();
			$obj->CODIGO_RETORNO = 0;
			$obj->ETIQUETA_ERROR = 'Correcto';
			$obj->DATOS_IDENTIFICATIVOS = new SoapVar($datos, SOAP_ENC_OBJECT);

			$result = new stdClass();
			$result->RESPUESTA_DATOS_CENTRO = new SoapVar($obj, SOAP_ENC_OBJECT);
            //error_log('Sin datos en la BD');
			/*
			$datos = new stdClass();
			$obj = new stdClass();
			$obj->CODIGO_RETORNO = '-1';
			$obj->ETIQUETA_ERROR = 'Sin datos';
			$obj->DATOS_IDENTIFICATIVOS = new SoapVar($datos, SOAP_ENC_OBJECT);

			$result = new stdClass();
			$result->RESPUESTA_DATOS_CENTRO = new SoapVar($obj, SOAP_ENC_OBJECT);
			*/
			return $result; 
        }
    }

    /**
     * @param $crearAccionInput
     * @return array
     */
    public function crearAccion($crearAccionInput)
    {
		error_log('crearAccion()');
		/* ***************************** Log de seguimiento ****************************** */
		$table_log = Database::get_main_table('plugin_sepe_log');
		$params_log = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "crearAccion",
			'fecha' => date("Y-m-d H:i:s")
        );
		Database::insert($table_log, $params_log);
		/* ******************************************************************************* */
		
        $array = json_decode(json_encode($crearAccionInput), true);
        $crearAccionInputArray = (array) $array;
        // Code
        $origen_accion = $crearAccionInput->ACCION_FORMATIVA->ID_ACCION->ORIGEN_ACCION;
        $codigo_accion = $crearAccionInput->ACCION_FORMATIVA->ID_ACCION->CODIGO_ACCION;
        $situacion = $crearAccionInput->ACCION_FORMATIVA->SITUACION;
        $origen_especialidad = $crearAccionInput->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL->ORIGEN_ESPECIALIDAD;
        $area_profesional = $crearAccionInput->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL->AREA_PROFESIONAL;
        $codigo_especialidad = $crearAccionInput->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL->CODIGO_ESPECIALIDAD;
        $duracion = $crearAccionInput->ACCION_FORMATIVA->DURACION;
        $fecha_inicio = $crearAccionInput->ACCION_FORMATIVA->FECHA_INICIO;
        $fecha_fin = $crearAccionInput->ACCION_FORMATIVA->FECHA_FIN;
        $ind_itinerario_completo = $crearAccionInput->ACCION_FORMATIVA->IND_ITINERARIO_COMPLETO;
        $tipo_financiacion = $crearAccionInput->ACCION_FORMATIVA->TIPO_FINANCIACION;
        $numero_asistentes = $crearAccionInput->ACCION_FORMATIVA->NUMERO_ASISTENTES;
        $denominacion_accion = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->DENOMINACION_ACCION;
        $informacion_general = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->INFORMACION_GENERAL;
        $horarios = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->HORARIOS;
        $requisitos = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->REQUISITOS;
        $contacto_accion = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->CONTACTO_ACCION;

        if (empty($origen_accion) || empty($codigo_accion)) {
            return array(
                "RESPUESTA_OBT_ACCION" => array(
                    "CODIGO_RETORNO"=>"2",
                    "ETIQUETA_ERROR"=>"Error en parametro",
                    "ACCION_FORMATIVA"=> $crearAccionInputArray['ACCION_FORMATIVA']
                )
            );
        }

		// Comprobamos si existen datos almacenados previamente
        $table = Database::get_main_table('plugin_sepe_actions');
        $sql = "SELECT ORIGEN_ACCION FROM $table
                WHERE ORIGEN_ACCION='".$origen_accion."' AND CODIGO_ACCION='".$codigo_accion."';";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            // Existe la accion
			return array(
                "RESPUESTA_OBT_ACCION" => array(
                    "CODIGO_RETORNO"=>"1",
                    "ETIQUETA_ERROR"=>"Acción existente",
                    "ACCION_FORMATIVA"=>$crearAccionInputArray['ACCION_FORMATIVA']
                )
            );
			
        }

        $fecha_inicio = self::fixDate($fecha_inicio);
        $fecha_fin = self::fixDate($fecha_fin);

        $sql = "INSERT INTO $table (ORIGEN_ACCION, CODIGO_ACCION, SITUACION, ORIGEN_ESPECIALIDAD, AREA_PROFESIONAL, CODIGO_ESPECIALIDAD, DURACION, FECHA_INICIO, FECHA_FIN, IND_ITINERARIO_COMPLETO, TIPO_FINANCIACION, NUMERO_ASISTENTES, DENOMINACION_ACCION, INFORMACION_GENERAL, HORARIOS, REQUISITOS, CONTACTO_ACCION)
                VALUES ('".$origen_accion."','".$codigo_accion."','".$situacion."','".$origen_especialidad."','".$area_profesional."','".$codigo_especialidad."','".$duracion."','".$fecha_inicio."','".$fecha_fin."','".$ind_itinerario_completo."','".$tipo_financiacion."','".$numero_asistentes."','".$denominacion_accion."','".$informacion_general."','".$horarios."','".$requisitos."','".$contacto_accion."')";

        $rs = Database::query($sql);
        if (!$rs) {
            return array(
                "RESPUESTA_OBT_ACCION" => array(
                    "CODIGO_RETORNO"=>"-1",
                    "ETIQUETA_ERROR"=>"Problema base de datos - insertando acciones formativas",
                    "ACCION_FORMATIVA"=>$crearAccionInputArray['ACCION_FORMATIVA']
                )
            );
        }
        $cod_actions = Database::insert_id();

        // DATOS ESPECIALIDADES DE LA ACCION
        $table = Database::get_main_table('plugin_sepe_specialty');

        $especialidades = $crearAccionInput->ACCION_FORMATIVA->ESPECIALIDADES_ACCION;
        //error_log(print_r($especialidades, 1));
        foreach ($especialidades as $especialidadList) {
			if(!is_array($especialidadList)){
				$lista_aux = array();
				$lista_aux[] = $especialidadList;
				$especialidadList = $lista_aux;	
			}
            //error_log(print_r($especialidadList, 1));
            foreach ($especialidadList as $especialidad) {
                //error_log(print_r($especialidad, 1));
                $origen_especialidad = $especialidad->ID_ESPECIALIDAD->ORIGEN_ESPECIALIDAD;
                $area_profesional = $especialidad->ID_ESPECIALIDAD->AREA_PROFESIONAL;
                $codigo_especialidad = $especialidad->ID_ESPECIALIDAD->CODIGO_ESPECIALIDAD;
                $origen_centro = $especialidad->CENTRO_IMPARTICION->ORIGEN_CENTRO;
                $codigo_centro = $especialidad->CENTRO_IMPARTICION->CODIGO_CENTRO;
                $fecha_inicio = $especialidad->FECHA_INICIO;
                $fecha_fin = $especialidad->FECHA_FIN;

                $modalidad_imparticion = $especialidad->MODALIDAD_IMPARTICION;
                $horas_presencial = $especialidad->DATOS_DURACION->HORAS_PRESENCIAL;
                $horas_teleformacion = $especialidad->DATOS_DURACION->HORAS_TELEFORMACION;

                $hm_num_participantes = null;
                $hm_numero_accesos = null;
                $hm_duracion_total = null;

                if (isset($especialidad->USO->HORARIO_MANANA)) {
                    $hm_num_participantes = $especialidad->USO->HORARIO_MANANA->NUM_PARTICIPANTES;
                    $hm_numero_accesos = $especialidad->USO->HORARIO_MANANA->NUMERO_ACCESOS;
                    $hm_duracion_total = $especialidad->USO->HORARIO_MANANA->DURACION_TOTAL;
                }

                $ht_num_participantes = null;
                $ht_numero_accesos = null;
                $ht_duracion_total = null;

                if (isset($especialidad->USO->HORARIO_TARDE)) {
                    $ht_num_participantes = $especialidad->USO->HORARIO_TARDE->NUM_PARTICIPANTES;
                    $ht_numero_accesos = $especialidad->USO->HORARIO_TARDE->NUMERO_ACCESOS;
                    $ht_duracion_total = $especialidad->USO->HORARIO_TARDE->DURACION_TOTAL;
                }

                $hn_num_participantes = null;
                $hn_numero_accesos = null;
                $hn_duracion_total = null;

                if (isset($especialidad->USO->HORARIO_NOCHE)) {
                    $hn_num_participantes = $especialidad->USO->HORARIO_NOCHE->NUM_PARTICIPANTES;
                    $hn_numero_accesos = $especialidad->USO->HORARIO_NOCHE->NUMERO_ACCESOS;
                    $hn_duracion_total = $especialidad->USO->HORARIO_NOCHE->DURACION_TOTAL;
                }

                $num_participantes = null;
                $numero_actividades_aprendizaje = null;
                $numero_intentos = null;
                $numero_actividades_evaluacion = null;

                if (isset($especialidad->USO->SEGUIMIENTO_EVALUACION)) {
                    $num_participantes = $especialidad->USO->SEGUIMIENTO_EVALUACION->NUM_PARTICIPANTES;
                    $numero_actividades_aprendizaje = $especialidad->USO->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_APRENDIZAJE;
                    $numero_intentos = $especialidad->USO->SEGUIMIENTO_EVALUACION->NUMERO_INTENTOS;
                    $numero_actividades_evaluacion = $especialidad->USO->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_EVALUACION;
                }

                $fecha_inicio = self::fixDate($fecha_inicio);
                $fecha_fin = self::fixDate($fecha_fin);

                $params = array(
                    'cod_action' => $cod_actions,
                    'ORIGEN_ESPECIALIDAD' => $origen_especialidad,
                    'AREA_PROFESIONAL' => $area_profesional,
                    'CODIGO_ESPECIALIDAD' =>$codigo_especialidad ,
                    'ORIGEN_CENTRO' => $origen_centro,
                    'CODIGO_CENTRO' => $codigo_centro,
                    'FECHA_INICIO' => $fecha_inicio ,
                    'FECHA_FIN' => $fecha_fin,
                    'MODALIDAD_IMPARTICION' => $modalidad_imparticion,
                    'HORAS_PRESENCIAL' => $horas_presencial,
                    'HORAS_TELEFORMACION' => $horas_teleformacion,
                    'HM_NUM_PARTICIPANTES' => $hm_num_participantes,
                    'HM_NUMERO_ACCESOS' => $hm_numero_accesos,
                    'HM_DURACION_TOTAL' => $hm_duracion_total,
                    'HT_NUM_PARTICIPANTES' => $ht_num_participantes,
                    'HT_NUMERO_ACCESOS' => $ht_numero_accesos,
                    'HT_DURACION_TOTAL' => $ht_duracion_total,
                    'HN_NUM_PARTICIPANTES' => $hn_num_participantes,
                    'HN_NUMERO_ACCESOS' => $hn_numero_accesos,
                    'HN_DURACION_TOTAL' => $hn_duracion_total,
                    'NUM_PARTICIPANTES' => $num_participantes,
                    'NUMERO_ACTIVIDADES_APRENDIZAJE' => $numero_actividades_aprendizaje ,
                    'NUMERO_INTENTOS' => $numero_intentos,
                    'NUMERO_ACTIVIDADES_EVALUACION' => $numero_actividades_evaluacion
                );

                $cod_specialty = Database::insert($table, $params);

                if (empty($cod_specialty)) {
                    return array(
                        "RESPUESTA_OBT_ACCION" => array(
                            "CODIGO_RETORNO" => "-1",
                            "ETIQUETA_ERROR" => "Problema base de datos - insertando datos de especialidad de la accion",
                            "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA']
                        )
                    );
                }

                
                if ($cod_specialty) {
					$tableSpecialtyClassroom = Database::get_main_table('plugin_sepe_specialty_classroom');
					$tableCentros = Database::get_main_table('plugin_sepe_centros');
                    foreach ($especialidad->CENTROS_SESIONES_PRESENCIALES->CENTRO_PRESENCIAL as $centroList) {
                        //error_log(print_r($especialidad->CENTROS_SESIONES_PRESENCIALES->CENTRO_PRESENCIAL, 1));
                        if(!is_array($centroList)){
							$lista_aux = array();
							$lista_aux[] = $centroList;
							$centroList = $lista_aux;	
						}
						//error_log(print_r($especialidadList, 1));
						foreach ($centroList as $centro) {
							$origen_centro = $centro->ORIGEN_CENTRO;
							$codigo_centro = $centro->CODIGO_CENTRO;
							$sql = "SELECT cod FROM $tableCentros WHERE ORIGEN_CENTRO='".$origen_centro."' AND CODIGO_CENTRO='".$codigo_centro."';";
							$res = Database::query($sql);
							if(Database::num_rows($res)>0){
								$aux_row = Database::fetch_assoc($res);
								$cod_centro = $aux_row['cod'];
							}else{
								$sql = "INSERT INTO $tableCentros (ORIGEN_CENTRO, CODIGO_CENTRO)
                                        VALUES ('" . $origen_centro . "','" . $codigo_centro . "');";
                                Database::query($sql);
                                $cod_centro = Database::insert_id();
							}
							$sql = "INSERT INTO $tableSpecialtyClassroom (cod_specialty, cod_centro)
									VALUES ('" . $cod_specialty . "','" . $cod_centro . "')";
							Database::query($sql);
							$id = Database::insert_id();
								
							if (empty($id)) {
								return array(
									"RESPUESTA_OBT_ACCION" => array(
										"CODIGO_RETORNO" => "-1",
										"ETIQUETA_ERROR" => "Problema base de datos - insertando centro presenciales",
										"ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA']
									)
								);
							}
						}
                    }

                    $tableTutors = Database::get_main_table('plugin_sepe_tutors');
                    $tableSpecialityTutors = Database::get_main_table('plugin_sepe_specialty_tutors');

                    if (!empty($especialidad->TUTORES_FORMADORES)) {
                        //error_log(print_r($especialidad->TUTORES_FORMADORES, 1));
                        foreach ($especialidad->TUTORES_FORMADORES as $tutorList) {
                            if(!is_array($tutorList)){
								$lista_aux = array();
								$lista_aux[] = $tutorList;
								$tutorList = $lista_aux;	
							}
							foreach ($tutorList as $tutor) {
                                //error_log(print_r($tutor, 1));
                                $tipo_documento = $tutor->ID_TUTOR->TIPO_DOCUMENTO;
                                $num_documento = $tutor->ID_TUTOR->NUM_DOCUMENTO;
                                $letra_nif = $tutor->ID_TUTOR->LETRA_NIF;
                                $acreditacion = $tutor->ACREDITACION_TUTOR;
                                $experiencia_profesional = $tutor->EXPERIENCIA_PROFESIONAL;
                                $competencia_docente = $tutor->COMPETENCIA_DOCENTE;
                                $experiencia_modalidad_teleformacion = $tutor->EXPERIENCIA_MODALIDAD_TELEFORMACION;
                                $formacion_modalidad_teleformacion = $tutor->FORMACION_MODALIDAD_TELEFORMACION;
                                
								/*Comprobamos que no insertemos a un tutor que ya existe */
								$sql = "SELECT cod FROM $tableTutors WHERE 
								TIPO_DOCUMENTO='".$tipo_documento."' AND NUM_DOCUMENTO='".$num_documento."' AND LETRA_NIF='".$letra_nif."';";
								$res = Database::query($sql);
								if(Database::num_rows($res)>0){
									$aux_row = Database::fetch_assoc($res);
									$cod_tutor = $aux_row['cod'];
								}else{
									$sql = "INSERT INTO $tableTutors (TIPO_DOCUMENTO, NUM_DOCUMENTO,LETRA_NIF)
                                        VALUES ('" . $tipo_documento . "','" . $num_documento . "','" . $letra_nif . "');";
                                	Database::query($sql);
                                    $cod_tutor = Database::insert_id();
								}
                                if (empty($cod_tutor)) {
                                    return array(
                                        "RESPUESTA_OBT_ACCION" => array(
                                            "CODIGO_RETORNO" => "-1",
                                            "ETIQUETA_ERROR" => "Problema base de datos - insertando tutores",
                                            "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA']
                                        )
                                    );
                                }
								$sql = "INSERT INTO $tableSpecialityTutors (cod_specialty, cod_tutor, ACREDITACION_TUTOR, EXPERIENCIA_PROFESIONAL, COMPETENCIA_DOCENTE, EXPERIENCIA_MODALIDAD_TELEFORMACION, FORMACION_MODALIDAD_TELEFORMACION)
                                        VALUES ('" . $cod_specialty . "','" . $cod_tutor . "','" . $acreditacion . "','" . $experiencia_profesional . "','" . $competencia_docente . "','" . $experiencia_modalidad_teleformacion . "','" . $formacion_modalidad_teleformacion . "');";
                                Database::query($sql);
                            }
                        }
                    }
                }
            }
        }

        // DATOS PARTICIPANTES
		$table_participants = Database::get_main_table('plugin_sepe_participants');
		$table_tutors_empresa = Database::get_main_table('plugin_sepe_tutors_empresa');
		$participantes = $crearAccionInput->ACCION_FORMATIVA->PARTICIPANTES;
        //error_log(print_r($participantes, 1));
		$counter = 0;
        foreach ($participantes as $participanteList) {
			//error_log(print_r($participanteList,1));
			if(!is_array($participanteList)){
				$lista_aux = array();
				$lista_aux[] = $participanteList;
				$participanteList = $lista_aux;	
			}
			//error_log(print_r($participanteList,1));
            foreach ($participanteList as $participante) {
                //error_log("first loop: $counter");
				$counter++;

                $tipo_documento = $participante->ID_PARTICIPANTE->TIPO_DOCUMENTO;
                $num_documento = $participante->ID_PARTICIPANTE->NUM_DOCUMENTO;
                $letra_nif = $participante->ID_PARTICIPANTE->LETRA_NIF;
                $indicador_compentencias_clave = $participante->INDICADOR_COMPETENCIAS_CLAVE;

                $id_contrato_cfa = null;
                $cif_empresa = null;
                $te_tipo_documento = null;
                $te_num_documento = null;
                $te_letra_nif = null;
                $tf_tipo_documento = null;
                $tf_num_documento = null;
                $tf_letra_nif = null;
				$cod_tutor_empresa = 1;
				$cod_tutor_formacion = 1;

                if (isset($participante->CONTRATO_FORMACION)) {
                    $id_contrato_cfa = isset($participante->CONTRATO_FORMACION->ID_CONTRATO_CFA) ? $participante->CONTRATO_FORMACION->ID_CONTRATO_CFA : null;
                    $cif_empresa = isset($participante->CONTRATO_FORMACION->CIF_EMPRESA) ? $participante->CONTRATO_FORMACION->CIF_EMPRESA : null;
                    $te_tipo_documento = isset($participante->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->TIPO_DOCUMENTO) ? $participante->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->TIPO_DOCUMENTO : null;
                    $te_num_documento = isset($participante->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->NUM_DOCUMENTO) ? $participante->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->NUM_DOCUMENTO : null;
                    $te_letra_nif = isset($participante->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->LETRA_NIF) ? $participante->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->LETRA_NIF : null;
					if(!empty($te_tipo_documento) || !empty($te_num_documento) || !empty($te_letra_nif)){
						$tmp_e = Database::query('SELECT cod FROM '.$table_tutors_empresa.' WHERE TIPO_DOCUMENTO="'.$te_tipo_documento.'" AND NUM_DOCUMENTO="'.$te_num_documento.'" AND LETRA_NIF="'.$te_letra_nif.'";');
						//error_log('SELECT cod FROM $table_tutors_empresa WHERE TIPO_DOCUMENTO="'.$te_tipo_documento.'" AND NUM_DOCUMENTO="'.$te_num_documento.'" AND LETRA_NIF="'.$te_letra_nif.'";');
						if(Database::num_rows($tmp_e)>0){
							$row_tmp = Database::fetch_assoc($tmp_e);
							$cod_tutor_empresa = $row_tmp['cod'];
							Database::query("UPDATE $table_tutors_empresa SET empresa='SI' WHERE cod='".$cod_tutor_empresa."'");
						}else{
							$params_tmp = array(
								'TIPO_DOCUMENTO' => $te_tipo_documento,
								'NUM_DOCUMENTO' => $te_num_documento,
								'LETRA_NIF' => $te_letra_nif,
								'empresa' => 'SI'
							);
							$cod_tutor_empresa = Database::insert($table_tutors_empresa, $params_tmp);
						}
					}
					
					$tf_tipo_documento = isset($participante->CONTRATO_FORMACION->ID_TUTOR_FORMACION->TIPO_DOCUMENTO) ? $participante->CONTRATO_FORMACION->ID_TUTOR_FORMACION->TIPO_DOCUMENTO : null;
					$tf_num_documento = isset($participante->CONTRATO_FORMACION->ID_TUTOR_FORMACION->NUM_DOCUMENTO) ? $participante->CONTRATO_FORMACION->ID_TUTOR_FORMACION->NUM_DOCUMENTO : null;
					$tf_letra_nif = isset($participante->CONTRATO_FORMACION->ID_TUTOR_FORMACION->LETRA_NIF) ? $participante->CONTRATO_FORMACION->ID_TUTOR_FORMACION->LETRA_NIF : null;
					if(!empty($tf_tipo_documento) || !empty($tf_num_documento) || !empty($tf_letra_nif)){
						$tmp_f = Database::query('SELECT cod FROM '.$table_tutors_empresa.' WHERE TIPO_DOCUMENTO="'.$tf_tipo_documento.'" AND NUM_DOCUMENTO="'.$tf_num_documento.'" AND LETRA_NIF="'.$tf_letra_nif.'";');
						if(Database::num_rows($tmp_f)>0){
							$row_tmp = Database::fetch_assoc($tmp_f);
							$cod_tutor_formacion = $row_tmp['cod'];
							Database::query("UPDATE $table_tutors_empresa SET formacion='SI' WHERE cod='".$cod_tutor_formacion."'");
						}else{
							$params_tmp = array(
								'TIPO_DOCUMENTO' => $tf_tipo_documento,
								'NUM_DOCUMENTO' => $tf_num_documento,
								'LETRA_NIF' => $tf_letra_nif,
								'formacion' => 'SI'
							);
							$cod_tutor_formacion = Database::insert($table_tutors_empresa, $params_tmp);
						}
					}
                }

                $params = array(
                    'cod_action' => $cod_actions,
                    'TIPO_DOCUMENTO' => $tipo_documento,
                    'NUM_DOCUMENTO' => $num_documento,
                    'LETRA_NIF' => $letra_nif,
                    'INDICADOR_COMPETENCIAS_CLAVE' => $indicador_compentencias_clave,
                    'ID_CONTRATO_CFA' => $id_contrato_cfa,
                    'CIF_EMPRESA' => $cif_empresa,
					'cod_tutor_empresa' => $cod_tutor_empresa,
					'cod_tutor_formacion' => $cod_tutor_formacion
                );
                $cod_participant = Database::insert($table_participants, $params);
                if (empty($cod_participant)) {
                    return array(
                        "RESPUESTA_OBT_ACCION" => array(
                            "CODIGO_RETORNO" => "-1",
                            "ETIQUETA_ERROR" => "Problema base de datos - insertando participantes",
                            "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA']
                        )
                    );
                }

                $cod_participant = Database::insert_id();
				
                foreach ($participante->ESPECIALIDADES_PARTICIPANTE as $valueList) {
					if(!is_array($participanteList)){
						$lista_aux = array();
						$lista_aux[] = $valueList;
						$valueList = $lista_aux;	
					}
                    foreach ($valueList as $value) {
						$origen_especialidad = null;
                        $area_profesional = null;
                        $codigo_especialidad = null;

                        if (isset($value->ID_ESPECIALIDAD)) {
                            $origen_especialidad = $value->ID_ESPECIALIDAD->ORIGEN_ESPECIALIDAD;
                            $area_profesional = $value->ID_ESPECIALIDAD->AREA_PROFESIONAL;
                            $codigo_especialidad = $value->ID_ESPECIALIDAD->CODIGO_ESPECIALIDAD;
                        }

                        $fecha_alta = $value->FECHA_ALTA;
                        $fecha_baja = $value->FECHA_BAJA;

                        $origen_centro = null;
                        $codigo_centro = null;
                        $fecha_inicio = null;
                        $fecha_fin = null;

                        if (!empty($value->EVALUACION_FINAL)) {
                            $fecha_inicio = isset($value->EVALUACION_FINAL->FECHA_INICIO) ? $value->EVALUACION_FINAL->FECHA_INICIO : null;
                            $fecha_fin = isset($value->EVALUACION_FINAL->FECHA_FIN) ? $value->EVALUACION_FINAL->FECHA_FIN : null;
                            if (!empty($value->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION)) {
                                $origen_centro = $value->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->ORIGEN_CENTRO;
                                $codigo_centro = $value->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->CODIGO_CENTRO;
                            }
                        }

                        $resultado_final = null;
                        $calificacion_final = null;
                        $puntuacion_final = null;

                        if (isset($value->RESULTADOS)) {
                            $resultado_final = isset($value->RESULTADOS->RESULTADO_FINAL) ? $value->RESULTADOS->RESULTADO_FINAL : null;
                            $calificacion_final = isset($value->RESULTADOS->CALIFICACION_FINAL) ? $value->RESULTADOS->CALIFICACION_FINAL : null;
                            $puntuacion_final = isset($value->RESULTADOS->PUNTUACION_FINAL) ? $value->RESULTADOS->PUNTUACION_FINAL : null;
                        }

                        $fecha_alta = self::fixDate($fecha_alta);
                        $fecha_baja = self::fixDate($fecha_baja);

                        $fecha_inicio = self::fixDate($fecha_inicio);
                        $fecha_fin = self::fixDate($fecha_fin);
						
						$table_aux = Database::get_main_table('plugin_sepe_participants_specialty');
                        $sql = "INSERT INTO $table_aux (cod_participant,ORIGEN_ESPECIALIDAD,AREA_PROFESIONAL,CODIGO_ESPECIALIDAD,FECHA_ALTA,FECHA_BAJA,ORIGEN_CENTRO,CODIGO_CENTRO,FECHA_INICIO,FECHA_FIN,RESULTADO_FINAL,CALIFICACION_FINAL,PUNTUACION_FINAL)
                                VALUES ('" . $cod_participant . "','" . $origen_especialidad . "','" . $area_profesional . "','" . $codigo_especialidad . "','" . $fecha_alta . "','" . $fecha_baja . "','" . $origen_centro . "','" . $codigo_centro . "','" . $fecha_inicio . "','" . $fecha_fin . "','" . $resultado_final . "','" . $calificacion_final . "','" . $puntuacion_final . "');";
                        Database::query($sql);
                        $cod_participant_specialty = Database::insert_id();
                        if (empty($cod_participant_specialty)) {
                            return array(
                                "RESPUESTA_OBT_ACCION" => array(
                                    "CODIGO_RETORNO" => "-1",
                                    "ETIQUETA_ERROR" => "Problema base de datos - insertando especialidad participante",
                                    "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA']
                                )
                            );
                        }

                        //error_log(print_r($value->TUTORIAS_PRESENCIALES, 1));
                        foreach ($value->TUTORIAS_PRESENCIALES as $tutorialList) {
							if(!is_array($tutorialList)){
								$lista_aux = array();
								$lista_aux[] = $tutorialList;
								$tutorialList = $lista_aux;	
							}
							foreach ($tutorialList as $tutorial) {
								$origen_centro = $tutorial->CENTRO_PRESENCIAL_TUTORIA->ORIGEN_CENTRO;
								$codigo_centro = $tutorial->CENTRO_PRESENCIAL_TUTORIA->CODIGO_CENTRO;
								$fecha_inicio = $tutorial->FECHA_INICIO;
								$fecha_fin = $tutorial->FECHA_FIN;
	
								$fecha_inicio = self::fixDate($fecha_inicio);
								$fecha_fin = self::fixDate($fecha_fin);
								
								$table_aux2 = Database::get_main_table('plugin_sepe_participants_specialty_tutorials');
								$sql = "INSERT INTO $table_aux2 (cod_participant_specialty,ORIGEN_CENTRO,CODIGO_CENTRO,FECHA_INICIO,FECHA_FIN)
										VALUES ('" . $cod_participant_specialty . "','" . $origen_centro . "','" . $codigo_centro . "','" . $fecha_inicio . "','" . $fecha_fin . "');";
								$rs = Database::query($sql);
								if (!$rs) {
									return array(
										"RESPUESTA_OBT_ACCION" => array(
											"CODIGO_RETORNO" => "-1",
											"ETIQUETA_ERROR" => "Problema base de datos - insertando tutorias presenciales participante",
											"ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA']
										)
									);
								}
							}//foreach ($tutorialList as $tutorial) 
						}// foreach ($value->TUTORIAS_PRESENCIALES as $tutorialList)
                    }
                }
            }
        }

        $obtenerAccionInput = new stdClass();
        $obtenerAccionInput->ID_ACCION = new stdClass();
        $obtenerAccionInput->ID_ACCION->ORIGEN_ACCION = $origen_accion;
        $obtenerAccionInput->ID_ACCION->CODIGO_ACCION = $codigo_accion;

        $result = self::obtenerAccion($obtenerAccionInput);
        return $result;
    }

    public function obtenerAccion($obtenerAccionInput)
    {
		error_log('function obtenerAccion()');
		/* ***************************** Log de seguimiento ****************************** */
		$table_log = Database::get_main_table('plugin_sepe_log');
		$params_log = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "obtenerAccion",
			'fecha' => date("Y-m-d H:i:s")
        );
		Database::insert($table_log, $params_log);

		/* ******************************************************************************* */
		
        $origen_accion = $obtenerAccionInput->ID_ACCION->ORIGEN_ACCION;
        $codigo_accion = $obtenerAccionInput->ID_ACCION->CODIGO_ACCION;

        if (empty($origen_accion) || empty($codigo_accion)) {
            return array(
                "RESPUESTA_OBT_ACCION" => array(
                    "CODIGO_RETORNO" => "2",
                    "ETIQUETA_ERROR" => "Error en parametro",
                    "ACCION_FORMATIVA" => ""
                )
            );
        }

        $table = Database::get_main_table('plugin_sepe_actions');
		$tableCentros = Database::get_main_table('plugin_sepe_centros');
        $classRoomTable = Database::get_main_table('plugin_sepe_specialty_classroom');
        $tutorTable = Database::get_main_table('plugin_sepe_tutors');
        $specialityTutorTable = Database::get_main_table('plugin_sepe_specialty_tutors');
        $participantsSpecialityTable = Database::get_main_table('plugin_sepe_participants_specialty');
        $participantsSpecialityTutorialsTable = Database::get_main_table('plugin_sepe_participants_specialty_tutorials');
		$table_tutors_empresa = Database::get_main_table('plugin_sepe_tutors_empresa');

        // Comprobamos si existen datos almacenados previamente
        $sql = "SELECT *
                FROM $table
                WHERE
                    ORIGEN_ACCION='".$origen_accion."' AND
                    CODIGO_ACCION='".$codigo_accion."';";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_assoc($rs);
            $id_especialidad_principal = array(
                'ORIGEN_ESPECIALIDAD' => $row['ORIGEN_ESPECIALIDAD'],
                'AREA_PROFESIONAL' => $row['AREA_PROFESIONAL'],
                'CODIGO_ESPECIALIDAD' => $row['CODIGO_ESPECIALIDAD']
            );
            $descripcion_accion = array(
                'DENOMINACION_ACCION' => $row['DENOMINACION_ACCION'],
                'INFORMACION_GENERAL' => $row['INFORMACION_GENERAL'],
                'HORARIOS' => $row['HORARIOS'],
                'REQUISITOS' => $row['REQUISITOS'],
                'CONTACTO_ACCION' => $row['CONTACTO_ACCION']
            );

            // Creamos la variable de especialidad
            $tableSpeciality = Database::get_main_table('plugin_sepe_specialty');
            $tableParticipants = Database::get_main_table('plugin_sepe_participants');
            $sql = "SELECT * FROM $tableSpeciality
                    WHERE cod_action ='".$row['cod']."'";
            $rs = Database::query($sql);
            $especialidad = array();
            if (Database::num_rows($rs) > 0) {
                $counter = 0;
                while ($aux = Database::fetch_assoc($rs)) {
                    $cod_specialty = $aux['cod'];
                    // Listamos en un array los centros presenciales que luego seran insertado en la especialidad
                    $sql = "SELECT * FROM $classRoomTable
                            WHERE cod_specialty='" . $cod_specialty . "'";
                    $rs_tmp = Database::query($sql);
                    if (Database::num_rows($rs_tmp) == false) {
                        return array(
                            "RESPUESTA_OBT_ACCION" => array(
                                "CODIGO_RETORNO" => "-1",
                                "ETIQUETA_ERROR" => "Problema base de datos - consulta de centros presenciales",
                                "ACCION_FORMATIVA" => ''
                            )
                        );
                    }

                    $centro_sessiones_presenciales = new ArrayObject();
                    while ($tmp = Database::fetch_assoc($rs_tmp)) {
						$sql = "SELECT * FROM $tableCentros WHERE cod='".$tmp['cod_centro']."';";
						$rs_centro = Database::query($sql);
						$aux_centro = Database::fetch_assoc($rs_centro);
						
                        $centro_presencial = new stdClass();
                        $centro_presencial->ORIGEN_CENTRO = $aux_centro['ORIGEN_CENTRO'];
                        $centro_presencial->CODIGO_CENTRO = $aux_centro['CODIGO_CENTRO'];
                        $centro_presencial = new SoapVar($centro_presencial, SOAP_ENC_OBJECT, null, null, 'CENTRO_PRESENCIAL');
                        $centro_sessiones_presenciales->append($centro_presencial);
                    }
                    // Listamos en un array los tutores que luego seran insertado en la especialidad
                    $sql = "SELECT * FROM $specialityTutorTable
                            WHERE cod_specialty='" . $cod_specialty . "'";
                    $rs_tmp = Database::query($sql);

                    $tutores_formadores = new ArrayObject();
                    if (Database::num_rows($rs_tmp)) {
                        while ($tmp_aux = Database::fetch_assoc($rs_tmp)) {
                            $sql = "SELECT * FROM $tutorTable
                                    WHERE cod='" . $tmp_aux['cod_tutor'] . "'
                                    LIMIT 1";
                              $rs_tutor = Database::query($sql);
                            if (Database::num_rows($rs_tutor)) {
                                $tmp = Database::fetch_assoc($rs_tutor);

                                $obj = new stdClass();
                                $obj->ID_TUTOR = new stdClass();
                                $obj->ID_TUTOR->TIPO_DOCUMENTO = $tmp['TIPO_DOCUMENTO'];
                                $obj->ID_TUTOR->NUM_DOCUMENTO = $tmp['NUM_DOCUMENTO'];
                                $obj->ID_TUTOR->LETRA_NIF = $tmp['LETRA_NIF'];

                                $obj->ACREDITACION_TUTOR = $tmp_aux['ACREDITACION_TUTOR'];
                                $obj->EXPERIENCIA_PROFESIONAL = $tmp_aux['EXPERIENCIA_PROFESIONAL'];
                                $obj->COMPETENCIA_DOCENTE = $tmp_aux['COMPETENCIA_DOCENTE'];
                                $obj->EXPERIENCIA_MODALIDAD_TELEFORMACION = $tmp_aux['EXPERIENCIA_MODALIDAD_TELEFORMACION'];
                                $obj->FORMACION_MODALIDAD_TELEFORMACION = $tmp_aux['FORMACION_MODALIDAD_TELEFORMACION'];

                                $obj = new SoapVar($obj, SOAP_ENC_OBJECT, null, null, 'TUTOR_FORMADOR');
                                $tutores_formadores->append($obj);
                            }
                        }
                    }

                    $params = new ArrayObject();
                    $idEspecialidad = new stdClass();
                    $idEspecialidad->ORIGEN_ESPECIALIDAD = $aux['ORIGEN_ESPECIALIDAD'];
                    $idEspecialidad->AREA_PROFESIONAL = $aux['AREA_PROFESIONAL'];
                    $idEspecialidad->CODIGO_ESPECIALIDAD = $aux['CODIGO_ESPECIALIDAD'];

                    $params[0] = new SoapVar($idEspecialidad, SOAP_ENC_OBJECT, NULL, null, 'ID_ESPECIALIDAD', null);

                    $centroImparticion = new stdClass();
                    $centroImparticion->ORIGEN_CENTRO = $aux['ORIGEN_CENTRO'];
                    $centroImparticion->CODIGO_CENTRO = $aux['CODIGO_CENTRO'];

                    $params[1] = new SoapVar($centroImparticion, SOAP_ENC_OBJECT, NULL, null, 'CENTRO_IMPARTICION', null);
                    $params[2] = new SoapVar(self::undoFixDate($aux['FECHA_INICIO']), XSD_STRING, NULL, null, 'FECHA_INICIO', null);
                    $params[3] = new SoapVar(self::undoFixDate($aux['FECHA_FIN']), XSD_STRING, NULL, null, 'FECHA_FIN', null);
                    $params[4] = new SoapVar($aux['MODALIDAD_IMPARTICION'], XSD_STRING, NULL, null, 'MODALIDAD_IMPARTICION', null);

                    $obj = new stdClass();
                    $obj->HORAS_PRESENCIAL = $aux['HORAS_PRESENCIAL'];
                    $obj->HORAS_TELEFORMACION = $aux['HORAS_TELEFORMACION'];

                    $params[5] = new SoapVar($obj, SOAP_ENC_OBJECT, NULL, null, 'DATOS_DURACION', null);
                    $params[6] = new SoapVar($centro_sessiones_presenciales, SOAP_ENC_OBJECT, null, null, 'CENTROS_SESIONES_PRESENCIALES', null);
                    $params[7] = new SoapVar($tutores_formadores, SOAP_ENC_OBJECT, null, null, 'TUTORES_FORMADORES', null);

                    $obj = new stdClass();

                    if (!empty($aux['HM_NUM_PARTICIPANTES']) ||
                        !empty($aux['HM_NUMERO_ACCESOS']) ||
                        !empty($aux['HM_DURACION_TOTAL'])
                    ) {
                        $obj->HORARIO_MANANA = new stdClass();
                        $obj->HORARIO_MANANA->NUM_PARTICIPANTES = $aux['HM_NUM_PARTICIPANTES'];
                        $obj->HORARIO_MANANA->NUMERO_ACCESOS = $aux['HM_NUMERO_ACCESOS'];
                        $obj->HORARIO_MANANA->DURACION_TOTAL = $aux['HM_DURACION_TOTAL'];
                    }

                    if (!empty($aux['HT_NUM_PARTICIPANTES']) ||
                        !empty($aux['HT_NUMERO_ACCESOS']) ||
                        !empty($aux['HT_DURACION_TOTAL'])
                    ) {
                        $obj->HORARIO_TARDE = new stdClass();
                        $obj->HORARIO_TARDE->NUM_PARTICIPANTES = $aux['HT_NUM_PARTICIPANTES'];
                        $obj->HORARIO_TARDE->NUMERO_ACCESOS = $aux['HT_NUMERO_ACCESOS'];
                        $obj->HORARIO_TARDE->DURACION_TOTAL = $aux['HT_DURACION_TOTAL'];
                    }

                    if (!empty($aux['HN_NUM_PARTICIPANTES'])  ||
                        !empty($aux['HN_NUMERO_ACCESOS'])  ||
                        !empty($aux['HN_DURACION_TOTAL'])
                    ) {
                        $obj->HORARIO_NOCHE = new stdClass();
                        $obj->HORARIO_NOCHE->NUM_PARTICIPANTES = $aux['HN_NUM_PARTICIPANTES'];
                        $obj->HORARIO_NOCHE->NUMERO_ACCESOS = $aux['HN_NUMERO_ACCESOS'];
                        $obj->HORARIO_NOCHE->DURACION_TOTAL = $aux['HN_DURACION_TOTAL'];
                    }

                    if (!empty($aux['NUM_PARTICIPANTES'])  ||
                        !empty($aux['NUMERO_ACTIVIDADES_APRENDIZAJE'])  ||
                        !empty($aux['NUMERO_ACTIVIDADES_APRENDIZAJE'])  ||
                        !empty($aux['NUMERO_INTENTOS'])  ||
                        !empty($aux['NUMERO_ACTIVIDADES_EVALUACION'])
                    ) {
                        $obj->SEGUIMIENTO_EVALUACION = new stdClass();
                        $obj->SEGUIMIENTO_EVALUACION->NUM_PARTICIPANTES = $aux['NUM_PARTICIPANTES'];
                        $obj->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_APRENDIZAJE = $aux['NUMERO_ACTIVIDADES_APRENDIZAJE'];
                        $obj->SEGUIMIENTO_EVALUACION->NUMERO_INTENTOS = $aux['NUMERO_INTENTOS'];
                        $obj->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_EVALUACION = $aux['NUMERO_ACTIVIDADES_EVALUACION'];
                    }

                    $params[8] = new SoapVar($obj, SOAP_ENC_OBJECT, null, null, 'USO', null);
                    $especialidad[] = new SoapVar($params, SOAP_ENC_OBJECT, null, null, 'ESPECIALIDAD');
                    $counter++;
                }
            } else {
                return array(
                    "RESPUESTA_OBT_ACCION" => array(
                        "CODIGO_RETORNO"=>"-1",
                        "ETIQUETA_ERROR"=>"Problema base de datos - consulta especialidad de accion",
                        "ACCION_FORMATIVA"=>''
                    )
                );
            }

            // Creamos la variable de participantes

            $sql = "SELECT * FROM $tableParticipants
                    WHERE cod_action ='".$row['cod']."'";
            $rs_tmp = Database::query($sql);
            /*if (Database::num_rows($rs_tmp) == false) {
                error_log('error');
                return array(
                    "RESPUESTA_OBT_ACCION" => array(
                        "CODIGO_RETORNO"=>"-1",
                        "ETIQUETA_ERROR"=>"Problema base de datos - consulta de participantes",
                        "ACCION_FORMATIVA"=>''
                    )
                );
            }*/
            $especialidadParticipanteMain = array();
            if (Database::num_rows($rs_tmp)) {
                while ($aux = Database::fetch_assoc($rs_tmp)) {
                    $cod_participant = $aux['cod'];

                    //Listamos en un array la especialidad del participante que luego seran insertado en el participante
                    $sql = "SELECT * FROM $participantsSpecialityTable
                            WHERE cod_participant='" . $cod_participant . "'";
                    $rs_tmp2 = Database::query($sql);
                    
                    $namespace = null;
                    $especialidadParticipante = array();
                    while ($tmp = Database::fetch_assoc($rs_tmp2)) {
                        $cod_participant_specialty = $tmp['cod'];
                        $sql = "SELECT * FROM $participantsSpecialityTutorialsTable
                                WHERE cod_participant_specialty='" . $cod_participant_specialty . "'";
                        //error_log($sql);
                        $rs_tmp3 = Database::query($sql);
                        $tutorias_presenciales = new ArrayObject();

                        while ($tmp2 = Database::fetch_assoc($rs_tmp3)) {
                            $obj = new stdClass();
                            $obj->CENTRO_PRESENCIAL_TUTORIA = new stdClass();
                            $obj->CENTRO_PRESENCIAL_TUTORIA->ORIGEN_CENTRO = $tmp2['ORIGEN_CENTRO'];
                            $obj->CENTRO_PRESENCIAL_TUTORIA->CODIGO_CENTRO = $tmp2['CODIGO_CENTRO'];
                            $fechaInicio = self::undoFixDate($tmp2['FECHA_INICIO']);
                            if (!empty($fechaInicio)) {
                                $obj->FECHA_INICIO = $fechaInicio;
                            }
                            $fechaFin = self::undoFixDate($tmp2['FECHA_FIN']);
                            if (!empty($fechaFin)) {
                                $obj->FECHA_FIN = $fechaFin;
                            }

                            $obj = new SoapVar($obj, SOAP_ENC_OBJECT, null, null, 'TUTORIA_PRESENCIAL');
                            $tutorias_presenciales->append($obj);
                        }

                        $obj = new stdClass();
                        $obj->ID_ESPECIALIDAD = new stdClass();
                        $obj->ID_ESPECIALIDAD->ORIGEN_ESPECIALIDAD = $tmp['ORIGEN_ESPECIALIDAD'];
                        $obj->ID_ESPECIALIDAD->AREA_PROFESIONAL = $tmp['AREA_PROFESIONAL'];
                        $obj->ID_ESPECIALIDAD->CODIGO_ESPECIALIDAD = $tmp['CODIGO_ESPECIALIDAD'];

                        $fechaAlta = self::undoFixDate($tmp['FECHA_ALTA']);

                        // @todo check which is correct send 0000/00/00 or empty
                        if (!empty($fechaAlta)) {
                            $obj->FECHA_ALTA = $fechaAlta;
                        }

                        $fechaBaja = self::undoFixDate($tmp['FECHA_BAJA']);
                        if (!empty($fechaBaja)) {
                            $obj->FECHA_BAJA = $fechaBaja;
                        }

                        $obj->TUTORIAS_PRESENCIALES = new SoapVar($tutorias_presenciales, SOAP_ENC_OBJECT, null, null, 'TUTORIAS_PRESENCIALES', null);
                        $obj->EVALUACION_FINAL = new stdClass();

                        if (!empty($tmp['ORIGEN_CENTRO']) && !empty($tmp['CODIGO_CENTRO'])) {
                            $obj->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION = new stdClass();
                            $obj->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->ORIGEN_CENTRO = $tmp['ORIGEN_CENTRO'];
                            $obj->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->CODIGO_CENTRO = $tmp['CODIGO_CENTRO'];
                        }

                        $fechaInicio = self::undoFixDate($tmp['FECHA_INICIO']);
                        if (!empty($fechaInicio)) {
                            $obj->EVALUACION_FINAL->FECHA_INICIO = $fechaInicio;
                        }

                        $fechaFin = self::undoFixDate($tmp['FECHA_FIN']);

                        if (!empty($fechaFin)) {
                            $obj->EVALUACION_FINAL->FECHA_FIN = $fechaFin;
                        }

                        $obj->RESULTADOS = new stdClass();

                        if (isset($tmp['RESULTADO_FINAL']) && $tmp['RESULTADO_FINAL'] != '') {
                            $obj->RESULTADOS->RESULTADO_FINAL = $tmp['RESULTADO_FINAL'];
                        }
                        if (isset($tmp['CALIFICACION_FINAL']) && $tmp['CALIFICACION_FINAL'] != '') {
                            $obj->RESULTADOS->CALIFICACION_FINAL = $tmp['CALIFICACION_FINAL'];
                        }

                        if (isset($tmp['PUNTUACION_FINAL']) && $tmp['PUNTUACION_FINAL'] != '') {
                            $obj->RESULTADOS->PUNTUACION_FINAL = $tmp['PUNTUACION_FINAL'];
                        }
                        $especialidadParticipante[] = new SoapVar($obj, SOAP_ENC_OBJECT, null, null, 'ESPECIALIDAD');
                    }

                    $params = new stdClass();
                    $params->ID_PARTICIPANTE = new stdClass();
                    $params->ID_PARTICIPANTE->TIPO_DOCUMENTO = $aux['TIPO_DOCUMENTO'];
                    $params->ID_PARTICIPANTE->NUM_DOCUMENTO = $aux['NUM_DOCUMENTO'];
                    $params->ID_PARTICIPANTE->LETRA_NIF = $aux['LETRA_NIF'];
                    $params->INDICADOR_COMPETENCIAS_CLAVE = $aux['INDICADOR_COMPETENCIAS_CLAVE'];

                    $params->CONTRATO_FORMACION = new stdClass();

                    if (!empty($aux['ID_CONTRATO_CFA'])) {
                        $params->CONTRATO_FORMACION->ID_CONTRATO_CFA = $aux['ID_CONTRATO_CFA'];
                    }

                    if (!empty($aux['CIF_EMPRESA'])) {
                        $params->CONTRATO_FORMACION->CIF_EMPRESA = $aux['CIF_EMPRESA'];
                    }

                    if (!empty($aux['cod_tutor_empresa'])
					//if (!empty($aux['TE_TIPO_DOCUMENTO']) ||
                    //    !empty($aux['TE_NUM_DOCUMENTO']) ||
                    //    !empty($aux['TE_LETRA_NIF'])
                    ) {
						//error_log("obtener_empresa");
						$rs_tmp_e = Database::query("SELECT * FROM $table_tutors_empresa WHERE cod='".$aux['cod_tutor_empresa']."';");
						$aux_e = Database::fetch_assoc($rs_tmp_e);
						if(!empty($aux_e['TIPO_DOCUMENTO']) ||
                         !empty($aux_e['NUM_DOCUMENTO']) ||
                         !empty($aux_e['LETRA_NIF'])
                   	 	) {
							//error_log(print_r($aux_e['TIPO_DOCUMENTO'],1));
                        	$params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA = new stdClass();
                        	$params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->TIPO_DOCUMENTO = $aux_e['TIPO_DOCUMENTO'];
                        	$params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->NUM_DOCUMENTO = $aux_e['NUM_DOCUMENTO'];
							$params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->LETRA_NIF = $aux_e['LETRA_NIF'];
						}
                    }
					if (!empty($aux['cod_tutor_formacion'])
                    //if (!empty($aux['TF_TIPO_DOCUMENTO']) ||
                     //   !empty($aux['TF_NUM_DOCUMENTO']) ||
                     //   !empty($aux['TF_LETRA_NIF'])
                    ) {
						//error_log("obtener_formacion");
						$rs_tmp_f = Database::query("SELECT * FROM $table_tutors_empresa WHERE cod='".$aux['cod_tutor_formacion']."';");
						$aux_f = Database::fetch_assoc($rs_tmp_f);
						if (!empty($aux_f['TIPO_DOCUMENTO']) ||
						    !empty($aux_f['NUM_DOCUMENTO']) ||
						    !empty($aux_f['LETRA_NIF'])
						) {
							//error_log(print_r($aux_f['TIPO_DOCUMENTO'],1));
							$params->CONTRATO_FORMACION->ID_TUTOR_FORMACION = new stdClass();
							$params->CONTRATO_FORMACION->ID_TUTOR_FORMACION->TIPO_DOCUMENTO = $aux_f['TIPO_DOCUMENTO'];
							$params->CONTRATO_FORMACION->ID_TUTOR_FORMACION->NUM_DOCUMENTO = $aux_f['NUM_DOCUMENTO'];
							$params->CONTRATO_FORMACION->ID_TUTOR_FORMACION->LETRA_NIF = $aux_f['LETRA_NIF'];
						}
                    }


                    $params->ESPECIALIDADES_PARTICIPANTE = new SoapVar(
                        $especialidadParticipante,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'ESPECIALIDADES_PARTICIPANTE'
                    );
                    $especialidadParticipanteMain[] = new SoapVar(
                        $params,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'PARTICIPANTE'
                    );
                }
            }

            $result = new stdClass();

            $result->RESPUESTA_OBT_ACCION = new stdClass();
            $result->RESPUESTA_OBT_ACCION->CODIGO_RETORNO = 0;
            $result->RESPUESTA_OBT_ACCION->ETIQUETA_ERROR = 'Correcto';

            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA = new stdClass();
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ID_ACCION = new stdClass();
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ID_ACCION->ORIGEN_ACCION = $origen_accion;
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ID_ACCION->CODIGO_ACCION = $codigo_accion;

            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->SITUACION = $row['SITUACION'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL = $id_especialidad_principal;

            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->DURACION = $row['DURACION'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->FECHA_INICIO = self::undoFixDate($row['FECHA_INICIO']);
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->FECHA_FIN = self::undoFixDate($row['FECHA_FIN']);
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->IND_ITINERARIO_COMPLETO = $row['IND_ITINERARIO_COMPLETO'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->TIPO_FINANCIACION = $row['TIPO_FINANCIACION'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->NUMERO_ASISTENTES = $row['NUMERO_ASISTENTES'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->DESCRIPCION_ACCION = $descripcion_accion;
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ESPECIALIDADES_ACCION = $especialidad;
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->PARTICIPANTES = $especialidadParticipanteMain;

            return $result;
        } else {
            // Existe la accion
            return array(
                "RESPUESTA_OBT_ACCION" => array(
                    "CODIGO_RETORNO" => "1",
                    "ETIQUETA_ERROR" => "Acción inexistente",
                    "ACCION_FORMATIVA" => ""
                )
            );
        }
    }

    public function obtenerListaAcciones()
    {
		error_log('function obtenerListaAcciones()');
		/* ***************************** Log de seguimiento ****************************** */
		$table_log = Database::get_main_table('plugin_sepe_log');
		$params_log = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "obtenerListaAcciones",
			'fecha' => date("Y-m-d H:i:s")
        );
		Database::insert($table_log, $params_log);
		/* ******************************************************************************* */
		
        $table = Database::get_main_table('plugin_sepe_actions');

        $sql = "SELECT ORIGEN_ACCION, CODIGO_ACCION FROM $table";
        $rs = Database::query($sql);

        if (!$rs) {
            error_log('Problema base de datos ');
            return array(
                "RESPUESTA_OBT_LISTA_ACCIONES" => array(
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema base de datos - consulta acciones disponible",
                    "LISTA_ACCIONES" => ''
                )
            );
        }

        $list = array();
        if (Database::num_rows($rs)) {
            $counter = 0;
            while ($row = Database::fetch_assoc($rs)) {
                //error_log(print_r($row, 1));
                $params = new stdClass();
                $params->ORIGEN_ACCION = $row['ORIGEN_ACCION'];
                $params->CODIGO_ACCION = $row['CODIGO_ACCION'];

                $list[] = new SoapVar($params, SOAP_ENC_OBJECT);
                $counter++;
            }
        }

        $result = new stdClass();
        $result->RESPUESTA_OBT_LISTA_ACCIONES = new stdClass();
        $result->RESPUESTA_OBT_LISTA_ACCIONES->CODIGO_RETORNO = 0;
        $result->RESPUESTA_OBT_LISTA_ACCIONES->ETIQUETA_ERROR = 'Correcto';

        if (!empty($list)) {
            $result->RESPUESTA_OBT_LISTA_ACCIONES->ID_ACCION = $list;
        }

        return $result;
    }

    public function eliminarAccion($eliminarAccionInput)
    {
		error_log('function eliminarAccion()');
		/* ***************************** Log de seguimiento ****************************** */
		$table_log = Database::get_main_table('plugin_sepe_log');
		$params_log = array(
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "eliminarAccion",
			'fecha' => date("Y-m-d H:i:s")
        );
		Database::insert($table_log, $params_log);
		/* ******************************************************************************* */
        
		$origen_accion = $eliminarAccionInput->ID_ACCION->ORIGEN_ACCION;
        $codigo_accion = $eliminarAccionInput->ID_ACCION->CODIGO_ACCION;

        if (empty($origen_accion) || empty($codigo_accion)) {
            return array(
                "RESPUESTA_ELIMINAR_ACCION" => array(
                    "CODIGO_RETORNO" => "2",
                    "ETIQUETA_ERROR" => "Error en parametro"
                )
            );
        }
		
		$table = Database::get_main_table('plugin_sepe_actions');
        $sql = "DELETE FROM $table
                WHERE ORIGEN_ACCION='".$origen_accion."' AND CODIGO_ACCION='".$codigo_accion."';";
        
		$rs = Database::query($sql);
        if(!$rs){
            return array(
                "RESPUESTA_ELIMINAR_ACCION" => array(
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema base de datos - consulta acciones disponible"
                )
            );
        }
		
        return array(
            "RESPUESTA_ELIMINAR_ACCION" => array(
                "CODIGO_RETORNO" => "0",
                "ETIQUETA_ERROR" => "Correcto"
            )
        );
    }

    // yyyy-mm-dd to dd/mm/yyyy
    public static function undoFixDate($date)
    {
        if ($date == '0000-00-00' || empty($date)) {
            return null;
        }

        $date = explode('-', $date);
        //
        $date = $date[2].'/'.$date[1].'/'.$date[0];
        return $date;
    }

    // dd/mm/yyyy to yyyy-mm-dd
    public static function fixDate($date)
    {
        if ($date == '00/00/0000' || empty($date)) {
            return null;
        }

        $date = explode('/', $date);
        // Year-month-day
        $date = $date[2].'-'.$date[1].'-'.$date[0];
        return $date;
    }

    protected function checkAuth()
    {
        error_log('checkAuth');
        if (!$this->authenticated) {
//            HTML_Output::error(403);
            error_log('403');
        }
    }
}
