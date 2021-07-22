<?php

/**
 * Class Sepe.
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
        /* Tracking log */
        $tableLog = Database::get_main_table('plugin_sepe_log');
        $paramsLog = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "crearCentro",
            'fecha' => date("Y-m-d H:i:s"),
        ];
        Database::insert($tableLog, $paramsLog);
        /* End tracking log */

        // Code
        $crearCentroInput = $crearCentroInput->DATOS_IDENTIFICATIVOS;
        $centerOrigin = $crearCentroInput->ID_CENTRO->ORIGEN_CENTRO;
        $centerCode = $crearCentroInput->ID_CENTRO->CODIGO_CENTRO;
        $centerName = $crearCentroInput->NOMBRE_CENTRO;
        $urlPlatform = $crearCentroInput->URL_PLATAFORMA;
        $urlTracking = $crearCentroInput->URL_SEGUIMIENTO;
        $phone = $crearCentroInput->TELEFONO;
        $mail = $crearCentroInput->EMAIL;

        if (empty($centerOrigin) ||
            empty($centerCode) ||
            empty($centerName) ||
            empty($urlPlatform) ||
            empty($urlTracking) ||
            empty($phone) ||
            empty($mail)
        ) {
            error_log('no data');

            return [
                "RESPUESTA_DATOS_CENTRO" => [
                    "CODIGO_RETORNO" => "2",
                    "ETIQUETA_ERROR" => "Error en parametro",
                    "DATOS_IDENTIFICATIVOS" => $crearCentroInput,
                ],
            ];
        }

        $table = Database::get_main_table('plugin_sepe_center');

        // Check if exists data in table
        if (Database::count_rows($table) > 0) {
            // Check if exists actions
            $table_actions = Database::get_main_table('plugin_sepe_actions');
            if (Database::count_rows($table_actions) > 0) {
                return [
                    "RESPUESTA_DATOS_CENTRO" => [
                        "CODIGO_RETORNO" => "1",
                        "ETIQUETA_ERROR" => "Centro con acciones",
                        "DATOS_IDENTIFICATIVOS" => $crearCentroInput,
                    ],
                ];
            } else {
                $sql = "DELETE FROM $table";
                Database::query($sql);
            }
        }

        $params = [
            'center_origin' => $centerOrigin,
            'center_code' => $centerCode,
            'center_name' => $centerName,
            'url' => $urlPlatform,
            'tracking_url' => $urlTracking,
            'phone' => $phone,
            'mail' => $mail,
        ];

        $id = Database::insert($table, $params);

        if (empty($id)) {
            return [
                "RESPUESTA_DATOS_CENTRO" => [
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema base de datos",
                    "DATOS_IDENTIFICATIVOS" => $crearCentroInput,
                ],
            ];
        } else {
            return [
                "RESPUESTA_DATOS_CENTRO" => [
                    "CODIGO_RETORNO" => "0",
                    "ETIQUETA_ERROR" => "Correcto",
                    "DATOS_IDENTIFICATIVOS" => $crearCentroInput,
                ],
            ];
        }
    }

    /**
     * @return stdClass
     */
    public function obtenerDatosCentro()
    {
        /* Tracking Log */
        $tableLog = Database::get_main_table('plugin_sepe_log');
        $paramsLog = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "obtenerDatosCentro",
            'fecha' => date("Y-m-d H:i:s"),
        ];
        Database::insert($tableLog, $paramsLog);
        /* End tracking log */

        // Code
        $table = Database::get_main_table('plugin_sepe_center');

        // Comprobamos si existen datos almacenados previamente
        if (Database::count_rows($table) > 0) {
            //Hay datos en la tabla;
            $sql = "SELECT * FROM $table;";
            $rs = Database::query($sql);
            if (!$rs) {
                return [
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema acceso base de datos",
                    "DATOS_IDENTIFICATIVOS" => '',
                ];
            } else {
                $row = Database::fetch_assoc($rs);
                $centerOrigin = $row['center_origin'];
                $centerCode = $row['center_code'];
                $centerName = $row['center_name'];
                $urlPlatform = $row['url'];
                $urlTracking = $row['tracking_url'];
                $phone = $row['phone'];
                $mail = $row['mail'];

                $data = new stdClass();
                $data->ID_CENTRO = new stdClass();
                $data->ID_CENTRO->ORIGEN_CENTRO = $centerOrigin;
                $data->ID_CENTRO->CODIGO_CENTRO = $centerCode;
                $data->NOMBRE_CENTRO = $centerName;
                $data->URL_PLATAFORMA = $urlPlatform;
                $data->URL_SEGUIMIENTO = $urlTracking;
                $data->TELEFONO = $phone;
                $data->EMAIL = $mail;

                $obj = new stdClass();
                $obj->CODIGO_RETORNO = 0;
                $obj->ETIQUETA_ERROR = 'Correcto';
                $obj->DATOS_IDENTIFICATIVOS = new SoapVar($data, SOAP_ENC_OBJECT);

                $result = new stdClass();
                $result->RESPUESTA_DATOS_CENTRO = new SoapVar($obj, SOAP_ENC_OBJECT);

                return $result;
            }
        } else {
            $data = new stdClass();
            $data->ID_CENTRO = new stdClass();
            $data->ID_CENTRO->ORIGEN_CENTRO = '';
            $data->ID_CENTRO->CODIGO_CENTRO = '';
            $data->NOMBRE_CENTRO = '';
            $data->URL_PLATAFORMA = '';
            $data->URL_SEGUIMIENTO = '';
            $data->TELEFONO = '';
            $data->EMAIL = '';

            $obj = new stdClass();
            $obj->CODIGO_RETORNO = 0;
            $obj->ETIQUETA_ERROR = 'Correcto';
            $obj->DATOS_IDENTIFICATIVOS = new SoapVar($data, SOAP_ENC_OBJECT);

            $result = new stdClass();
            $result->RESPUESTA_DATOS_CENTRO = new SoapVar($obj, SOAP_ENC_OBJECT);
            //error_log('Sin datos en la BD');
            /*
            $data = new stdClass();
            $obj = new stdClass();
            $obj->CODIGO_RETORNO = '-1';
            $obj->ETIQUETA_ERROR = 'Sin datos';
            $obj->DATOS_IDENTIFICATIVOS = new SoapVar($data, SOAP_ENC_OBJECT);

            $result = new stdClass();
            $result->RESPUESTA_DATOS_CENTRO = new SoapVar($obj, SOAP_ENC_OBJECT);
            */
            return $result;
        }
    }

    /**
     * @param $crearAccionInput
     *
     * @return array
     */
    public function crearAccion($crearAccionInput)
    {
        /* Tracking Log */
        $tableLog = Database::get_main_table('plugin_sepe_log');
        $paramsLog = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "crearAccion",
            'fecha' => date("Y-m-d H:i:s"),
        ];
        Database::insert($tableLog, $paramsLog);
        /* End tracking log */

        $array = json_decode(json_encode($crearAccionInput), true);
        $crearAccionInputArray = (array) $array;
        // Code
        $actionOrigin = $crearAccionInput->ACCION_FORMATIVA->ID_ACCION->ORIGEN_ACCION;
        $actionCode = $crearAccionInput->ACCION_FORMATIVA->ID_ACCION->CODIGO_ACCION;
        $situation = $crearAccionInput->ACCION_FORMATIVA->SITUACION;
        $specialtyOrigin = $crearAccionInput->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL->ORIGEN_ESPECIALIDAD;
        $professionalArea = $crearAccionInput->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL->AREA_PROFESIONAL;
        $specialtyCode = $crearAccionInput->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL->CODIGO_ESPECIALIDAD;
        $duration = $crearAccionInput->ACCION_FORMATIVA->DURACION;
        $startDate = $crearAccionInput->ACCION_FORMATIVA->FECHA_INICIO;
        $endDate = $crearAccionInput->ACCION_FORMATIVA->FECHA_FIN;
        $fullItineraryIndicator = $crearAccionInput->ACCION_FORMATIVA->IND_ITINERARIO_COMPLETO;
        $financingType = $crearAccionInput->ACCION_FORMATIVA->TIPO_FINANCIACION;
        $attendeesCount = $crearAccionInput->ACCION_FORMATIVA->NUMERO_ASISTENTES;
        $actionName = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->DENOMINACION_ACCION;
        $globalInfo = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->INFORMACION_GENERAL;
        $schedule = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->HORARIOS;
        $requerements = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->REQUISITOS;
        $contactAction = $crearAccionInput->ACCION_FORMATIVA->DESCRIPCION_ACCION->CONTACTO_ACCION;

        if (empty($actionOrigin) || empty($actionCode)) {
            error_log('2 - error en parametros - l244');

            return [
                "RESPUESTA_OBT_ACCION" => [
                    "CODIGO_RETORNO" => "2",
                    "ETIQUETA_ERROR" => "Error en parametro",
                    "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                ],
            ];
        }

        // Comprobamos si existen datos almacenados previamente
        $table = Database::get_main_table('plugin_sepe_actions');
        $actionOrigin = Database::escape_string($actionOrigin);
        $actionCode = Database::escape_string($actionCode);

        $sql = "SELECT action_origin FROM $table
                WHERE action_origin='".$actionOrigin."' AND action_code='".$actionCode."';";
        $rs = Database::query($sql);

        if (Database::num_rows($rs) > 0) {
            return [
                "RESPUESTA_OBT_ACCION" => [
                    "CODIGO_RETORNO" => "1",
                    "ETIQUETA_ERROR" => "Acción existente",
                    "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                ],
            ];
        }

        $startDate = self::fixDate($startDate);
        $endDate = self::fixDate($endDate);

        $params = [
            'action_origin' => $actionOrigin,
            'action_code' => $actionCode,
            'situation' => $situation,
            'specialty_origin' => $specialtyOrigin,
            'professional_area' => $professionalArea,
            'specialty_code' => $specialtyCode,
            'duration' => $duration,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'full_itinerary_indicator' => $fullItineraryIndicator,
            'financing_type' => $financingType,
            'attendees_count' => $attendeesCount,
            'action_name' => $actionName,
            'global_info' => $globalInfo,
            'schedule' => $schedule,
            'requirements' => $requerements,
            'contact_actio' => $contactAction,
        ];

        $actionId = Database::insert($table, $params);

        if (!empty($actionId)) {
            return [
                "RESPUESTA_OBT_ACCION" => [
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema base de datos - insertando acciones formativas",
                    "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                ],
            ];
        }

        // DATOS ESPECIALIDADES DE LA ACCION
        $table = Database::get_main_table('plugin_sepe_specialty');

        $specialties = $crearAccionInput->ACCION_FORMATIVA->ESPECIALIDADES_ACCION;
        foreach ($specialties as $specialtyList) {
            if (!is_array($specialtyList)) {
                $auxList = [];
                $auxList[] = $specialtyList;
                $specialtyList = $auxList;
            }
            foreach ($specialtyList as $specialty) {
                $specialtyOrigin = $specialty->ID_ESPECIALIDAD->ORIGEN_ESPECIALIDAD;
                $professionalArea = $specialty->ID_ESPECIALIDAD->AREA_PROFESIONAL;
                $specialtyCode = $specialty->ID_ESPECIALIDAD->CODIGO_ESPECIALIDAD;
                $centerOrigin = $specialty->CENTRO_IMPARTICION->ORIGEN_CENTRO;
                $centerCode = $specialty->CENTRO_IMPARTICION->CODIGO_CENTRO;
                $startDate = $specialty->FECHA_INICIO;
                $endDate = $specialty->FECHA_FIN;

                $modalityImpartition = $specialty->MODALIDAD_IMPARTICION;
                $classroomHours = $specialty->DATOS_DURACION->HORAS_PRESENCIAL;
                $distanceHours = $specialty->DATOS_DURACION->HORAS_TELEFORMACION;

                $morningParticipansNumber = null;
                $morningAccessNumber = null;
                $morningTotalDuration = null;

                if (isset($specialty->USO->HORARIO_MANANA)) {
                    $morningParticipansNumber = $specialty->USO->HORARIO_MANANA->NUM_PARTICIPANTES;
                    $morningAccessNumber = $specialty->USO->HORARIO_MANANA->NUMERO_ACCESOS;
                    $morningTotalDuration = $specialty->USO->HORARIO_MANANA->DURACION_TOTAL;
                }

                $afternoonParticipantNumber = null;
                $afternoonAccessNumber = null;
                $afternoonTotalDuration = null;

                if (isset($specialty->USO->HORARIO_TARDE)) {
                    $afternoonParticipantNumber = $specialty->USO->HORARIO_TARDE->NUM_PARTICIPANTES;
                    $afternoonAccessNumber = $specialty->USO->HORARIO_TARDE->NUMERO_ACCESOS;
                    $afternoonTotalDuration = $specialty->USO->HORARIO_TARDE->DURACION_TOTAL;
                }

                $nightParticipantsNumber = null;
                $nightAccessNumber = null;
                $nightTotalDuration = null;

                if (isset($specialty->USO->HORARIO_NOCHE)) {
                    $nightParticipantsNumber = $specialty->USO->HORARIO_NOCHE->NUM_PARTICIPANTES;
                    $nightAccessNumber = $specialty->USO->HORARIO_NOCHE->NUMERO_ACCESOS;
                    $nightTotalDuration = $specialty->USO->HORARIO_NOCHE->DURACION_TOTAL;
                }

                $attendeesCount = null;
                $learningActivityCount = null;
                $attemptCount = null;
                $evaluationActivityCount = null;

                if (isset($specialty->USO->SEGUIMIENTO_EVALUACION)) {
                    $attendeesCount = $specialty->USO->SEGUIMIENTO_EVALUACION->NUM_PARTICIPANTES;
                    $learningActivityCount = $specialty->USO->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_APRENDIZAJE;
                    $attemptCount = $specialty->USO->SEGUIMIENTO_EVALUACION->NUMERO_INTENTOS;
                    $evaluationActivityCount = $specialty->USO->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_EVALUACION;
                }

                $startDate = self::fixDate($startDate);
                $endDate = self::fixDate($endDate);

                $params = [
                    'action_id' => $actionId,
                    'specialty_origin' => $specialtyOrigin,
                    'professional_area' => $professionalArea,
                    'specialty_code' => $specialtyCode,
                    'center_origin' => $centerOrigin,
                    'center_code' => $centerCode,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'modality_impartition' => $modalityImpartition,
                    'classroom_hours' => $classroomHours,
                    'distance_hours' => $distanceHours,
                    'mornings_participants_number' => $morningParticipansNumber,
                    'mornings_access_number' => $morningAccessNumber,
                    'morning_total_duration' => $morningTotalDuration,
                    'afternoon_participants_number' => $afternoonParticipantNumber,
                    'afternoon_access_number' => $afternoonAccessNumber,
                    'afternoon_total_duration' => $afternoonTotalDuration,
                    'night_participants_number' => $nightParticipantsNumber,
                    'night_access_number' => $nightAccessNumber,
                    'night_total_duration' => $nightTotalDuration,
                    'attendees_count' => $attendeesCount,
                    'learning_activity_count' => $learningActivityCount,
                    'attempt_count' => $attemptCount,
                    'evaluation_activity_count' => $evaluationActivityCount,
                ];

                $specialtyId = Database::insert($table, $params);

                if (empty($specialtyId)) {
                    return [
                        "RESPUESTA_OBT_ACCION" => [
                            "CODIGO_RETORNO" => "-1",
                            "ETIQUETA_ERROR" => "Problema base de datos - insertando datos de especialidad de la accion",
                            "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                        ],
                    ];
                }

                if ($specialtyId) {
                    $tableSpecialtyClassroom = Database::get_main_table('plugin_sepe_specialty_classroom');
                    $tableCenters = Database::get_main_table('plugin_sepe_centers');
                    foreach ($specialty->CENTROS_SESIONES_PRESENCIALES->CENTRO_PRESENCIAL as $centroList) {
                        if (!is_array($centroList)) {
                            $auxList = [];
                            $auxList[] = $centroList;
                            $centroList = $auxList;
                        }
                        foreach ($centroList as $centro) {
                            $centerOrigin = $centro->ORIGEN_CENTRO;
                            $centerCode = $centro->CODIGO_CENTRO;
                            $centerOrigin = Database::escape_string($centerOrigin);
                            $centerCode = Database::escape_string($centerCode);
                            $sql = "SELECT id FROM $tableCenters
                                    WHERE center_origin='".$centerOrigin."' AND center_code='".$centerCode."';";
                            $res = Database::query($sql);
                            if (Database::num_rows($res) > 0) {
                                $aux_row = Database::fetch_assoc($res);
                                $centerId = $aux_row['id'];
                            } else {
                                $sql = "INSERT INTO $tableCenters (center_origin, center_code)
                                        VALUES ('".$centerOrigin."','".$centerCode."');";
                                Database::query($sql);
                                $centerId = Database::insert_id();
                            }
                            $sql = "INSERT INTO $tableSpecialtyClassroom (specialty_id, center_id)
                                    VALUES ('".$specialtyId."','".$centerId."')";
                            Database::query($sql);
                            $id = Database::insert_id();

                            if (empty($id)) {
                                return [
                                    "RESPUESTA_OBT_ACCION" => [
                                        "CODIGO_RETORNO" => "-1",
                                        "ETIQUETA_ERROR" => "Problema base de datos - insertando centro presenciales",
                                        "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                                    ],
                                ];
                            }
                        }
                    }

                    $tableTutors = Database::get_main_table('plugin_sepe_tutors');
                    $tableSpecialityTutors = Database::get_main_table('plugin_sepe_specialty_tutors');

                    if (!empty($specialty->TUTORES_FORMADORES)) {
                        foreach ($specialty->TUTORES_FORMADORES as $tutorList) {
                            if (!is_array($tutorList)) {
                                $auxList = [];
                                $auxList[] = $tutorList;
                                $tutorList = $auxList;
                            }
                            foreach ($tutorList as $tutor) {
                                $documentType = $tutor->ID_TUTOR->TIPO_DOCUMENTO;
                                $documentNumber = $tutor->ID_TUTOR->NUM_DOCUMENTO;
                                $documentLetter = $tutor->ID_TUTOR->LETRA_NIF;
                                $tutorAccreditation = $tutor->ACREDITACION_TUTOR;
                                $professionalExperience = $tutor->EXPERIENCIA_PROFESIONAL;
                                $teachingCompetence = $tutor->COMPETENCIA_DOCENTE;
                                $experienceTeleforming = $tutor->EXPERIENCIA_MODALIDAD_TELEFORMACION;
                                $trainingTeleforming = $tutor->FORMACION_MODALIDAD_TELEFORMACION;

                                $documentType = Database::escape_string($documentType);
                                $documentNumber = Database::escape_string($documentNumber);
                                $documentLetter = Database::escape_string($documentLetter);

                                /* check tutor not exists */
                                $sql = "SELECT id FROM $tableTutors
                                        WHERE
                                          document_type='".$documentType."' AND
                                          document_number='".$documentNumber."' AND
                                          document_letter='".$documentLetter."';";
                                $res = Database::query($sql);
                                if (Database::num_rows($res) > 0) {
                                    $aux_row = Database::fetch_assoc($res);
                                    $tutorId = $aux_row['id'];
                                } else {
                                    $sql = "INSERT INTO $tableTutors (document_type, document_number, document_letter)
                                            VALUES ('".$documentType."','".$documentNumber."','".$documentLetter."');";
                                    Database::query($sql);
                                    $tutorId = Database::insert_id();
                                }
                                if (empty($tutorId)) {
                                    return [
                                        "RESPUESTA_OBT_ACCION" => [
                                            "CODIGO_RETORNO" => "-1",
                                            "ETIQUETA_ERROR" => "Problema base de datos - insertando tutores",
                                            "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                                        ],
                                    ];
                                }

                                $params = [
                                    'specialty_id' => $specialtyId,
                                    'tutor_id' => $tutorId,
                                    'tutor_accreditation' => $tutorAccreditation,
                                    'professional_experience' => $professionalExperience,
                                    'teaching_competence' => $teachingCompetence,
                                    'experience_teleforming' => $experienceTeleforming,
                                    'training_teleforming' => $trainingTeleforming,
                                ];
                                Database::insert($tableSpecialityTutors, $params);
                            }
                        }
                    }
                }
            }
        }

        // DATOS PARTICIPANTES
        $tableParticipants = Database::get_main_table('plugin_sepe_participants');
        $tableTutorsCompany = Database::get_main_table('plugin_sepe_tutors_company');
        $participants = $crearAccionInput->ACCION_FORMATIVA->PARTICIPANTES;
        foreach ($participants as $participantList) {
            if (!is_array($participantList)) {
                $auxList = [];
                $auxList[] = $participantList;
                $participantList = $auxList;
            }
            foreach ($participantList as $participant) {
                $documentType = $participant->ID_PARTICIPANTE->TIPO_DOCUMENTO;
                $documentNumber = $participant->ID_PARTICIPANTE->NUM_DOCUMENTO;
                $documentLetter = $participant->ID_PARTICIPANTE->LETRA_NIF;
                $keyCompetence = $participant->INDICADOR_COMPETENCIAS_CLAVE;
                $contractId = null;
                $companyFiscalNumber = null;
                $documentTypeCompany = null;
                $documentNumberCompany = null;
                $documentLetterCompany = null;
                $documentTypeTraining = null;
                $documentNumberTraining = null;
                $documentLetterTraining = null;
                $tutorIdCompany = null;
                $tutorIdTraining = null;

                if (isset($participant->CONTRATO_FORMACION)) {
                    $contractId = isset($participant->CONTRATO_FORMACION->ID_CONTRATO_CFA) ? $participant->CONTRATO_FORMACION->ID_CONTRATO_CFA : null;
                    $companyFiscalNumber = isset($participant->CONTRATO_FORMACION->CIF_EMPRESA) ? $participant->CONTRATO_FORMACION->CIF_EMPRESA : null;
                    $documentTypeCompany = isset($participant->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->TIPO_DOCUMENTO) ? $participant->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->TIPO_DOCUMENTO : null;
                    $documentNumberCompany = isset($participant->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->NUM_DOCUMENTO) ? $participant->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->NUM_DOCUMENTO : null;
                    $documentLetterCompany = isset($participant->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->LETRA_NIF) ? $participant->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->LETRA_NIF : null;
                    if (!empty($documentTypeCompany) || !empty($documentNumberCompany) || !empty($documentLetterCompany)) {
                        $tmp_e = Database::query('SELECT id FROM '.$tableTutorsCompany.' WHERE document_type="'.$documentTypeCompany.'" AND document_number="'.$documentNumberCompany.'" AND document_letter="'.$documentLetterCompany.'";');
                        if (Database::num_rows($tmp_e) > 0) {
                            $row_tmp = Database::fetch_assoc($tmp_e);
                            $tutorIdCompany = $row_tmp['id'];
                            Database::query("UPDATE $tableTutorsCompany SET company='1' WHERE id='".$tutorIdCompany."'");
                        } else {
                            $params_tmp = [
                                'document_type' => $documentTypeCompany,
                                'document_number' => $documentNumberCompany,
                                'document_letter' => $documentLetterCompany,
                                'company' => '1',
                            ];
                            $tutorIdCompany = Database::insert($tableTutorsCompany, $params_tmp);
                        }
                    }

                    $documentTypeTraining = isset($participant->CONTRATO_FORMACION->ID_TUTOR_FORMACION->TIPO_DOCUMENTO) ? $participant->CONTRATO_FORMACION->ID_TUTOR_FORMACION->TIPO_DOCUMENTO : null;
                    $documentNumberTraining = isset($participant->CONTRATO_FORMACION->ID_TUTOR_FORMACION->NUM_DOCUMENTO) ? $participant->CONTRATO_FORMACION->ID_TUTOR_FORMACION->NUM_DOCUMENTO : null;
                    $documentLetterTraining = isset($participant->CONTRATO_FORMACION->ID_TUTOR_FORMACION->LETRA_NIF) ? $participant->CONTRATO_FORMACION->ID_TUTOR_FORMACION->LETRA_NIF : null;
                    if (!empty($documentTypeTraining) || !empty($documentNumberTraining) || !empty($documentLetterTraining)) {
                        $documentTypeTraining = Database::escape_string($documentTypeTraining);
                        $documentNumberTraining = Database::escape_string($documentNumberTraining);
                        $documentLetterTraining = Database::escape_string($documentLetterTraining);
                        $tmp_f = Database::query(
                            '
                            SELECT id FROM '.$tableTutorsCompany.'
                            WHERE
                                document_type="'.$documentTypeTraining.'" AND
                                document_number="'.$documentNumberTraining.'" AND
                                document_letter="'.$documentLetterTraining.'";'
                        );
                        if (Database::num_rows($tmp_f) > 0) {
                            $row_tmp = Database::fetch_assoc($tmp_f);
                            $tutorIdTraining = $row_tmp['id'];
                            Database::query("UPDATE $tableTutorsCompany SET training='1' WHERE id='".$tutorIdTraining."'");
                        } else {
                            $params_tmp = [
                                'document_type' => $documentTypeTraining,
                                'document_number' => $documentNumberTraining,
                                'document_letter' => $documentLetterTraining,
                                'training' => '1',
                            ];
                            $tutorIdTraining = Database::insert($tableTutorsCompany, $params_tmp);
                        }
                    }
                }

                $params = [
                    'action_id' => $actionId,
                    'document_type' => $documentType,
                    'document_number' => $documentNumber,
                    'document_letter' => $documentLetter,
                    'key_competence' => $keyCompetence,
                    'contract_id' => $contractId,
                    'company_fiscal_number' => $companyFiscalNumber,
                    'company_tutor_id' => $tutorIdCompany,
                    'training_tutor_id' => $tutorIdTraining,
                ];
                $participantId = Database::insert($tableParticipants, $params);
                if (empty($participantId)) {
                    return [
                        "RESPUESTA_OBT_ACCION" => [
                            "CODIGO_RETORNO" => "-1",
                            "ETIQUETA_ERROR" => "Problema base de datos - insertando participantes",
                            "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                        ],
                    ];
                }

                $participantId = Database::insert_id();

                foreach ($participant->ESPECIALIDADES_PARTICIPANTE as $valueList) {
                    if (!is_array($participantList)) {
                        $auxList = [];
                        $auxList[] = $valueList;
                        $valueList = $auxList;
                    }
                    foreach ($valueList as $value) {
                        $specialtyOrigin = null;
                        $professionalArea = null;
                        $specialtyCode = null;

                        if (isset($value->ID_ESPECIALIDAD)) {
                            $specialtyOrigin = $value->ID_ESPECIALIDAD->ORIGEN_ESPECIALIDAD;
                            $professionalArea = $value->ID_ESPECIALIDAD->AREA_PROFESIONAL;
                            $specialtyCode = $value->ID_ESPECIALIDAD->CODIGO_ESPECIALIDAD;
                        }

                        $registrationDate = $value->FECHA_ALTA;
                        $leavingDate = $value->FECHA_BAJA;

                        $centerOrigin = null;
                        $centerCode = null;
                        $startDate = null;
                        $endDate = null;

                        if (!empty($value->EVALUACION_FINAL)) {
                            $startDate = isset($value->EVALUACION_FINAL->FECHA_INICIO) ? $value->EVALUACION_FINAL->FECHA_INICIO : null;
                            $endDate = isset($value->EVALUACION_FINAL->FECHA_FIN) ? $value->EVALUACION_FINAL->FECHA_FIN : null;
                            if (!empty($value->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION)) {
                                $centerOrigin = $value->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->ORIGEN_CENTRO;
                                $centerCode = $value->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->CODIGO_CENTRO;
                            }
                        }

                        $finalResult = null;
                        $finalQualification = null;
                        $finalScore = null;

                        if (isset($value->RESULTADOS)) {
                            $finalResult = isset($value->RESULTADOS->RESULTADO_FINAL) ? $value->RESULTADOS->RESULTADO_FINAL : null;
                            $finalQualification = isset($value->RESULTADOS->CALIFICACION_FINAL) ? $value->RESULTADOS->CALIFICACION_FINAL : null;
                            $finalScore = isset($value->RESULTADOS->PUNTUACION_FINAL) ? $value->RESULTADOS->PUNTUACION_FINAL : null;
                        }

                        $registrationDate = self::fixDate($registrationDate);
                        $leavingDate = self::fixDate($leavingDate);

                        $startDate = self::fixDate($startDate);
                        $endDate = self::fixDate($endDate);

                        $table_aux = Database::get_main_table('plugin_sepe_participants_specialty');

                        $params = [
                            'participant_id' => $participantId,
                            'specialty_origin' => $specialtyOrigin,
                            'professional_area' => $professionalArea,
                            'specialty_code' => $specialtyCode,
                            'registration_date' => $registrationDate,
                            'leaving_date' => $leavingDate,
                            'center_origin' => $centerOrigin,
                            'center_code' => $centerCode,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'final_result' => $finalResult,
                            'final_qualification' => $finalQualification,
                            'final_score' => $finalScore,
                        ];

                        $participantSpecialtyId = Database::insert($table_aux, $params);
                        if (empty($participantSpecialtyId)) {
                            return [
                                "RESPUESTA_OBT_ACCION" => [
                                    "CODIGO_RETORNO" => "-1",
                                    "ETIQUETA_ERROR" => "Problema base de datos - insertando especialidad participante",
                                    "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                                ],
                            ];
                        }

                        foreach ($value->TUTORIAS_PRESENCIALES as $tutorialList) {
                            if (!is_array($tutorialList)) {
                                $auxList = [];
                                $auxList[] = $tutorialList;
                                $tutorialList = $auxList;
                            }
                            foreach ($tutorialList as $tutorial) {
                                $centerOrigin = $tutorial->CENTRO_PRESENCIAL_TUTORIA->ORIGEN_CENTRO;
                                $centerCode = $tutorial->CENTRO_PRESENCIAL_TUTORIA->CODIGO_CENTRO;
                                $startDate = $tutorial->FECHA_INICIO;
                                $endDate = $tutorial->FECHA_FIN;

                                $startDate = self::fixDate($startDate);
                                $endDate = self::fixDate($endDate);

                                $table_aux2 = Database::get_main_table('plugin_sepe_participants_specialty_tutorials');
                                $params = [
                                    'participant_specialty_id' => $participantSpecialtyId,
                                    'center_origin' => $centerOrigin,
                                    'center_code' => $centerCode,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                ];
                                $id = Database::insert($table_aux2, $params);

                                if (!empty($id)) {
                                    return [
                                        "RESPUESTA_OBT_ACCION" => [
                                            "CODIGO_RETORNO" => "-1",
                                            "ETIQUETA_ERROR" => "Problema base de datos - insertando tutorias presenciales participante",
                                            "ACCION_FORMATIVA" => $crearAccionInputArray['ACCION_FORMATIVA'],
                                        ],
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        $obtenerAccionInput = new stdClass();
        $obtenerAccionInput->ID_ACCION = new stdClass();
        $obtenerAccionInput->ID_ACCION->ORIGEN_ACCION = $actionOrigin;
        $obtenerAccionInput->ID_ACCION->CODIGO_ACCION = $actionCode;

        return self::obtenerAccion($obtenerAccionInput);
    }

    public function obtenerAccion($obtenerAccionInput)
    {
        /* Tracking Log */
        $tableLog = Database::get_main_table('plugin_sepe_log');
        $paramsLog = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "obtenerAccion",
            'fecha' => date("Y-m-d H:i:s"),
        ];
        Database::insert($tableLog, $paramsLog);

        /* End tracking log */

        $actionOrigin = $obtenerAccionInput->ID_ACCION->ORIGEN_ACCION;
        $actionCode = $obtenerAccionInput->ID_ACCION->CODIGO_ACCION;

        if (empty($actionOrigin) || empty($actionCode)) {
            return [
                "RESPUESTA_OBT_ACCION" => [
                    "CODIGO_RETORNO" => "2",
                    "ETIQUETA_ERROR" => "Error en parametro",
                    "ACCION_FORMATIVA" => "",
                ],
            ];
        }

        $table = Database::get_main_table('plugin_sepe_actions');
        $tableCenters = Database::get_main_table('plugin_sepe_centers');
        $classRoomTable = Database::get_main_table('plugin_sepe_specialty_classroom');
        $tutorTable = Database::get_main_table('plugin_sepe_tutors');
        $specialityTutorTable = Database::get_main_table('plugin_sepe_specialty_tutors');
        $participantsSpecialityTable = Database::get_main_table('plugin_sepe_participants_specialty');
        $participantsSpecialityTutorialsTable = Database::get_main_table('plugin_sepe_participants_specialty_tutorials');
        $tableTutorsCompany = Database::get_main_table('plugin_sepe_tutors_company');

        $actionOrigin = Database::escape_string($actionOrigin);
        $actionCode = Database::escape_string($actionCode);

        // Comprobamos si existen datos almacenados previamente
        $sql = "SELECT *
                FROM $table
                WHERE
                    action_origin='".$actionOrigin."' AND
                    action_code='".$actionCode."';";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $row = Database::fetch_assoc($rs);
            $mainSpecialty = [
                'ORIGEN_ESPECIALIDAD' => $row['specialty_origin'],
                'AREA_PROFESIONAL' => $row['professional_area'],
                'CODIGO_ESPECIALIDAD' => $row['specialty_code'],
            ];
            $actionDescription = [
                'DENOMINACION_ACCION' => $row['action_name'],
                'INFORMACION_GENERAL' => $row['global_info'],
                'HORARIOS' => $row['schedule'],
                'REQUISITOS' => $row['requirements'],
                'CONTACTO_ACCION' => $row['contact_action'],
            ];

            $tableSpeciality = Database::get_main_table('plugin_sepe_specialty');
            $tableParticipants = Database::get_main_table('plugin_sepe_participants');
            $sql = "SELECT * FROM $tableSpeciality
                    WHERE action_id ='".$row['id']."'";
            $rs = Database::query($sql);
            $specialty = [];
            if (Database::num_rows($rs) > 0) {
                while ($aux = Database::fetch_assoc($rs)) {
                    $specialtyId = $aux['id'];
                    $sql = "SELECT * FROM $classRoomTable
                            WHERE specialty_id='".$specialtyId."'";
                    $resultClassroom = Database::query($sql);
                    if (Database::num_rows($resultClassroom) === 0) {
                        return [
                            "RESPUESTA_OBT_ACCION" => [
                                "CODIGO_RETORNO" => "-1",
                                "ETIQUETA_ERROR" => "Problema base de datos - consulta de centros presenciales",
                                "ACCION_FORMATIVA" => '',
                            ],
                        ];
                    }

                    $classroomCenterList = new ArrayObject();
                    while ($tmp = Database::fetch_assoc($resultClassroom)) {
                        $sql = "SELECT * FROM $tableCenters WHERE id='".$tmp['center_id']."';";
                        $resultCenter = Database::query($sql);
                        $auxCenter = Database::fetch_assoc($resultCenter);

                        $classroomCenter = new stdClass();
                        $classroomCenter->ORIGEN_CENTRO = $auxCenter['center_origin'];
                        $classroomCenter->CODIGO_CENTRO = $auxCenter['center_code'];
                        $classroomCenter = new SoapVar(
                            $classroomCenter,
                            SOAP_ENC_OBJECT,
                            null,
                            null,
                            'CENTRO_PRESENCIAL'
                        );
                        $classroomCenterList->append($classroomCenter);
                    }
                    $sql = "SELECT * FROM $specialityTutorTable
                            WHERE specialty_id='".$specialtyId."'";
                    $resultSpecialtyTutor = Database::query($sql);

                    $trainingTutors = new ArrayObject();
                    if (Database::num_rows($resultSpecialtyTutor)) {
                        while ($tmp_aux = Database::fetch_assoc($resultSpecialtyTutor)) {
                            $sql = "SELECT * FROM $tutorTable
                                    WHERE id='".$tmp_aux['tutor_id']."'
                                    LIMIT 1";
                            $rs_tutor = Database::query($sql);
                            if (Database::num_rows($rs_tutor)) {
                                $tmp = Database::fetch_assoc($rs_tutor);

                                $obj = new stdClass();
                                $obj->ID_TUTOR = new stdClass();
                                $obj->ID_TUTOR->TIPO_DOCUMENTO = $tmp['document_type'];
                                $obj->ID_TUTOR->NUM_DOCUMENTO = $tmp['document_number'];
                                $obj->ID_TUTOR->LETRA_NIF = $tmp['document_letter'];

                                $obj->ACREDITACION_TUTOR = $tmp_aux['tutor_accreditation'];
                                $obj->EXPERIENCIA_PROFESIONAL = $tmp_aux['professional_experience'];
                                $obj->COMPETENCIA_DOCENTE = $tmp_aux['teaching_competence'];
                                $obj->EXPERIENCIA_MODALIDAD_TELEFORMACION = $tmp_aux['experience_teleforming'];
                                $obj->FORMACION_MODALIDAD_TELEFORMACION = $tmp_aux['training_teleforming'];

                                $obj = new SoapVar($obj, SOAP_ENC_OBJECT, null, null, 'TUTOR_FORMADOR');
                                $trainingTutors->append($obj);
                            }
                        }
                    }

                    $params = new ArrayObject();
                    $idEspecialidad = new stdClass();
                    $idEspecialidad->ORIGEN_ESPECIALIDAD = $aux['specialty_origin'];
                    $idEspecialidad->AREA_PROFESIONAL = $aux['professional_area'];
                    $idEspecialidad->CODIGO_ESPECIALIDAD = $aux['specialty_code'];

                    $params[0] = new SoapVar(
                        $idEspecialidad,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'ID_ESPECIALIDAD',
                        null
                    );

                    $centroImparticion = new stdClass();
                    $centroImparticion->ORIGEN_CENTRO = $aux['center_origin'];
                    $centroImparticion->CODIGO_CENTRO = $aux['center_code'];

                    $params[1] = new SoapVar(
                        $centroImparticion,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'CENTRO_IMPARTICION',
                        null
                    );
                    $params[2] = new SoapVar(
                        self::undoFixDate($aux['start_date']),
                        XSD_STRING,
                        null,
                        null,
                        'FECHA_INICIO',
                        null
                    );
                    $params[3] = new SoapVar(
                        self::undoFixDate($aux['end_date']),
                        XSD_STRING,
                        null,
                        null,
                        'FECHA_FIN',
                        null
                    );
                    $params[4] = new SoapVar(
                        $aux['modality_impartition'],
                        XSD_STRING,
                        null,
                        null,
                        'MODALIDAD_IMPARTICION',
                        null
                    );

                    $obj = new stdClass();
                    $obj->HORAS_PRESENCIAL = $aux['classroom_hours'];
                    $obj->HORAS_TELEFORMACION = $aux['distance_hours'];

                    $params[5] = new SoapVar(
                        $obj,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'DATOS_DURACION',
                        null
                    );
                    $params[6] = new SoapVar(
                        $classroomCenterList,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'CENTROS_SESIONES_PRESENCIALES',
                        null
                    );
                    $params[7] = new SoapVar(
                        $trainingTutors,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'TUTORES_FORMADORES',
                        null
                    );

                    $obj = new stdClass();

                    if (!empty($aux['mornings_participants_number']) ||
                        !empty($aux['mornings_access_number']) ||
                        !empty($aux['morning_total_duration'])
                    ) {
                        $obj->HORARIO_MANANA = new stdClass();
                        $obj->HORARIO_MANANA->NUM_PARTICIPANTES = $aux['mornings_participants_number'];
                        $obj->HORARIO_MANANA->NUMERO_ACCESOS = $aux['mornings_access_number'];
                        $obj->HORARIO_MANANA->DURACION_TOTAL = $aux['morning_total_duration'];
                    }

                    if (!empty($aux['afternoon_participants_number']) ||
                        !empty($aux['afternoon_access_number']) ||
                        !empty($aux['afternoon_total_duration'])
                    ) {
                        $obj->HORARIO_TARDE = new stdClass();
                        $obj->HORARIO_TARDE->NUM_PARTICIPANTES = $aux['afternoon_participants_number'];
                        $obj->HORARIO_TARDE->NUMERO_ACCESOS = $aux['afternoon_access_number'];
                        $obj->HORARIO_TARDE->DURACION_TOTAL = $aux['afternoon_total_duration'];
                    }

                    if (!empty($aux['night_participants_number']) ||
                        !empty($aux['night_access_number']) ||
                        !empty($aux['night_total_duration'])
                    ) {
                        $obj->HORARIO_NOCHE = new stdClass();
                        $obj->HORARIO_NOCHE->NUM_PARTICIPANTES = $aux['night_participants_number'];
                        $obj->HORARIO_NOCHE->NUMERO_ACCESOS = $aux['night_access_number'];
                        $obj->HORARIO_NOCHE->DURACION_TOTAL = $aux['night_total_duration'];
                    }

                    if (!empty($aux['attendees_count']) ||
                        !empty($aux['learning_activity_count']) ||
                        !empty($aux['attempt_count']) ||
                        !empty($aux['evaluation_activity_count'])
                    ) {
                        $obj->SEGUIMIENTO_EVALUACION = new stdClass();
                        $obj->SEGUIMIENTO_EVALUACION->NUM_PARTICIPANTES = $aux['attendees_count'];
                        $obj->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_APRENDIZAJE = $aux['learning_activity_count'];
                        $obj->SEGUIMIENTO_EVALUACION->NUMERO_INTENTOS = $aux['attempt_count'];
                        $obj->SEGUIMIENTO_EVALUACION->NUMERO_ACTIVIDADES_EVALUACION = $aux['evaluation_activity_count'];
                    }

                    $params[8] = new SoapVar(
                        $obj,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'USO',
                        null
                    );
                    $specialty[] = new SoapVar(
                        $params,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'ESPECIALIDAD'
                    );
                }
            } else {
                return [
                    "RESPUESTA_OBT_ACCION" => [
                        "CODIGO_RETORNO" => "-1",
                        "ETIQUETA_ERROR" => "Problema base de datos - consulta especialidad de accion",
                        "ACCION_FORMATIVA" => '',
                    ],
                ];
            }

            $sql = "SELECT * FROM $tableParticipants
                    WHERE action_id ='".$row['id']."'";
            $resultParticipants = Database::query($sql);
            $specialtyMainParticipant = [];
            if (Database::num_rows($resultParticipants)) {
                while ($aux = Database::fetch_assoc($resultParticipants)) {
                    $participantId = $aux['id'];

                    $sql = "SELECT * FROM $participantsSpecialityTable
                            WHERE participant_id='".$participantId."'";
                    $resultParticipantSpecialty = Database::query($sql);

                    $namespace = null;
                    $specialtyParticipant = [];
                    while ($tmp = Database::fetch_assoc($resultParticipantSpecialty)) {
                        $participantSpecialtyId = $tmp['id'];
                        $sql = "SELECT * FROM $participantsSpecialityTutorialsTable
                                WHERE participant_specialty_id='".$participantSpecialtyId."'";
                        $resultTutorials = Database::query($sql);
                        $classroomTutorials = new ArrayObject();

                        while ($tmp2 = Database::fetch_assoc($resultTutorials)) {
                            $obj = new stdClass();
                            $obj->CENTRO_PRESENCIAL_TUTORIA = new stdClass();
                            $obj->CENTRO_PRESENCIAL_TUTORIA->ORIGEN_CENTRO = $tmp2['center_origin'];
                            $obj->CENTRO_PRESENCIAL_TUTORIA->CODIGO_CENTRO = $tmp2['center_code'];
                            $startDate = self::undoFixDate($tmp2['start_date']);
                            if (!empty($startDate)) {
                                $obj->FECHA_INICIO = $startDate;
                            }
                            $endDate = self::undoFixDate($tmp2['end_date']);
                            if (!empty($endDate)) {
                                $obj->FECHA_FIN = $endDate;
                            }

                            $obj = new SoapVar(
                                $obj,
                                SOAP_ENC_OBJECT,
                                null,
                                null,
                                'TUTORIA_PRESENCIAL'
                            );
                            $classroomTutorials->append($obj);
                        }

                        $obj = new stdClass();
                        $obj->ID_ESPECIALIDAD = new stdClass();
                        $obj->ID_ESPECIALIDAD->ORIGEN_ESPECIALIDAD = $tmp['specialty_origin'];
                        $obj->ID_ESPECIALIDAD->AREA_PROFESIONAL = $tmp['professional_area'];
                        $obj->ID_ESPECIALIDAD->CODIGO_ESPECIALIDAD = $tmp['specialty_code'];

                        $registrationDate = self::undoFixDate($tmp['registration_date']);

                        // @todo check which is correct send 0000/00/00 or empty
                        if (!empty($registrationDate)) {
                            $obj->FECHA_ALTA = $registrationDate;
                        }

                        $leavingDate = self::undoFixDate($tmp['leaving_date']);
                        if (!empty($leavingDate)) {
                            $obj->FECHA_BAJA = $leavingDate;
                        }

                        $obj->TUTORIAS_PRESENCIALES = new SoapVar(
                            $classroomTutorials,
                            SOAP_ENC_OBJECT,
                            null,
                            null,
                            'TUTORIAS_PRESENCIALES',
                            null
                        );
                        $obj->EVALUACION_FINAL = new stdClass();

                        if (!empty($tmp['center_origin']) && !empty($tmp['center_code'])) {
                            $obj->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION = new stdClass();
                            $obj->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->ORIGEN_CENTRO = $tmp['center_origin'];
                            $obj->EVALUACION_FINAL->CENTRO_PRESENCIAL_EVALUACION->CODIGO_CENTRO = $tmp['center_code'];
                        }

                        $startDate = self::undoFixDate($tmp['start_date']);
                        if (!empty($startDate)) {
                            $obj->EVALUACION_FINAL->FECHA_INICIO = $startDate;
                        }

                        $endDate = self::undoFixDate($tmp['end_date']);

                        if (!empty($endDate)) {
                            $obj->EVALUACION_FINAL->FECHA_FIN = $endDate;
                        }

                        $obj->RESULTADOS = new stdClass();

                        if (isset($tmp['final_result']) && $tmp['final_result'] != '') {
                            $obj->RESULTADOS->RESULTADO_FINAL = $tmp['final_result'];
                        }
                        if (isset($tmp['final_qualification']) && $tmp['final_qualification'] != '') {
                            $obj->RESULTADOS->CALIFICACION_FINAL = $tmp['final_qualification'];
                        }

                        if (isset($tmp['final_score']) && $tmp['final_score'] != '') {
                            $obj->RESULTADOS->PUNTUACION_FINAL = $tmp['final_score'];
                        }
                        $specialtyParticipant[] = new SoapVar(
                            $obj,
                            SOAP_ENC_OBJECT,
                            null,
                            null,
                            'ESPECIALIDAD'
                        );
                    }

                    $params = new stdClass();
                    $params->ID_PARTICIPANTE = new stdClass();
                    $params->ID_PARTICIPANTE->TIPO_DOCUMENTO = $aux['document_type'];
                    $params->ID_PARTICIPANTE->NUM_DOCUMENTO = $aux['document_number'];
                    $params->ID_PARTICIPANTE->LETRA_NIF = $aux['document_letter'];
                    $params->INDICADOR_COMPETENCIAS_CLAVE = $aux['key_competence'];

                    $params->CONTRATO_FORMACION = new stdClass();

                    if (!empty($aux['contract_id'])) {
                        $params->CONTRATO_FORMACION->ID_CONTRATO_CFA = $aux['contract_id'];
                    }

                    if (!empty($aux['company_fiscal_number'])) {
                        $params->CONTRATO_FORMACION->CIF_EMPRESA = $aux['company_fiscal_number'];
                    }

                    if (!empty($aux['company_tutor_id'])) {
                        $resultCompany = Database::query("SELECT * FROM $tableTutorsCompany WHERE id='".$aux['company_tutor_id']."';");
                        $auxCompany = Database::fetch_assoc($resultCompany);
                        if (!empty($auxCompany['document_type']) ||
                         !empty($auxCompany['document_number']) ||
                         !empty($auxCompany['document_letter'])
                            ) {
                            $params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA = new stdClass();
                            $params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->TIPO_DOCUMENTO = $auxCompany['document_type'];
                            $params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->NUM_DOCUMENTO = $auxCompany['document_number'];
                            $params->CONTRATO_FORMACION->ID_TUTOR_EMPRESA->LETRA_NIF = $auxCompany['document_letter'];
                        }
                    }
                    if (!empty($aux['training_tutor_id'])) {
                        $resultTraining = Database::query("SELECT * FROM $tableTutorsCompany WHERE id='".$aux['training_tutor_id']."';");
                        $auxTraining = Database::fetch_assoc($resultTraining);
                        if (!empty($auxTraining['document_type']) ||
                            !empty($auxTraining['document_number']) ||
                            !empty($auxTraining['document_letter'])
                        ) {
                            $params->CONTRATO_FORMACION->ID_TUTOR_FORMACION = new stdClass();
                            $params->CONTRATO_FORMACION->ID_TUTOR_FORMACION->TIPO_DOCUMENTO = $auxTraining['document_type'];
                            $params->CONTRATO_FORMACION->ID_TUTOR_FORMACION->NUM_DOCUMENTO = $auxTraining['document_number'];
                            $params->CONTRATO_FORMACION->ID_TUTOR_FORMACION->LETRA_NIF = $auxTraining['document_letter'];
                        }
                    }

                    $params->ESPECIALIDADES_PARTICIPANTE = new SoapVar(
                        $specialtyParticipant,
                        SOAP_ENC_OBJECT,
                        null,
                        null,
                        'ESPECIALIDADES_PARTICIPANTE'
                    );
                    $specialtyMainParticipant[] = new SoapVar(
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
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ID_ACCION->ORIGEN_ACCION = $actionOrigin;
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ID_ACCION->CODIGO_ACCION = $actionCode;

            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->SITUACION = $row['situation'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ID_ESPECIALIDAD_PRINCIPAL = $mainSpecialty;

            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->DURACION = $row['duration'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->FECHA_INICIO = self::undoFixDate($row['start_date']);
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->FECHA_FIN = self::undoFixDate($row['end_date']);
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->IND_ITINERARIO_COMPLETO = $row['full_itinerary_indicator'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->TIPO_FINANCIACION = $row['financing_type'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->NUMERO_ASISTENTES = $row['attendees_count'];
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->DESCRIPCION_ACCION = $actionDescription;
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->ESPECIALIDADES_ACCION = $specialty;
            $result->RESPUESTA_OBT_ACCION->ACCION_FORMATIVA->PARTICIPANTES = $specialtyMainParticipant;

            return $result;
        } else {
            // Existe la accion
            return [
                "RESPUESTA_OBT_ACCION" => [
                    "CODIGO_RETORNO" => "1",
                    "ETIQUETA_ERROR" => "Acción inexistente",
                    "ACCION_FORMATIVA" => "",
                ],
            ];
        }
    }

    public function obtenerListaAcciones()
    {
        /* Tracking Log */
        $tableLog = Database::get_main_table('plugin_sepe_log');
        $paramsLog = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "obtenerListaAcciones",
            'fecha' => date("Y-m-d H:i:s"),
        ];
        Database::insert($tableLog, $paramsLog);
        /* End tracking log */

        $table = Database::get_main_table('plugin_sepe_actions');

        $sql = "SELECT action_origin, action_code FROM $table";
        $rs = Database::query($sql);

        if (!$rs) {
            error_log('Problema base de datos ');

            return [
                "RESPUESTA_OBT_LISTA_ACCIONES" => [
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema base de datos - consulta acciones disponible",
                    "LISTA_ACCIONES" => '',
                ],
            ];
        }

        $list = [];
        if (Database::num_rows($rs)) {
            while ($row = Database::fetch_assoc($rs)) {
                $params = new stdClass();
                $params->ORIGEN_ACCION = $row['action_origin'];
                $params->CODIGO_ACCION = $row['action_code'];

                $list[] = new SoapVar($params, SOAP_ENC_OBJECT);
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
        /* Tracking Log */
        $tableLog = Database::get_main_table('plugin_sepe_log');
        $paramsLog = [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'action' => "eliminarAccion",
            'fecha' => date("Y-m-d H:i:s"),
        ];
        Database::insert($tableLog, $paramsLog);
        /* End tracking log */

        $actionOrigin = $eliminarAccionInput->ID_ACCION->ORIGEN_ACCION;
        $actionCode = $eliminarAccionInput->ID_ACCION->CODIGO_ACCION;

        if (empty($actionOrigin) || empty($actionCode)) {
            return [
                "RESPUESTA_ELIMINAR_ACCION" => [
                    "CODIGO_RETORNO" => "2",
                    "ETIQUETA_ERROR" => "Error en parametro",
                ],
            ];
        }

        $table = Database::get_main_table('plugin_sepe_actions');
        $sql = "DELETE FROM $table
                WHERE action_origin='".$actionOrigin."' AND action_code='".$actionCode."';";

        $rs = Database::query($sql);
        if (!$rs) {
            return [
                "RESPUESTA_ELIMINAR_ACCION" => [
                    "CODIGO_RETORNO" => "-1",
                    "ETIQUETA_ERROR" => "Problema base de datos - consulta acciones disponible",
                ],
            ];
        }

        return [
            "RESPUESTA_ELIMINAR_ACCION" => [
                "CODIGO_RETORNO" => "0",
                "ETIQUETA_ERROR" => "Correcto",
            ],
        ];
    }

    // yyyy-mm-dd to dd/mm/yyyy
    public static function undoFixDate($date)
    {
        if ($date == '0000-00-00' || empty($date)) {
            return null;
        }

        $date = explode('-', $date);

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
        if (!$this->authenticated) {
            error_log('403');
        }
    }
}
