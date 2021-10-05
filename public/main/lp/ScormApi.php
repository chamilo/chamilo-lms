<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

class ScormApi
{
    /**
     * Writes an item's new values into the database and returns the operation result.
     *
     * @param int    $lp_id                Learnpath ID
     * @param int    $user_id              User ID
     * @param int    $view_id              View ID
     * @param int    $item_id              Item ID
     * @param float  $score                Current score
     * @param float  $max                  Maximum score
     * @param float  $min                  Minimum score
     * @param string $status               Lesson status
     * @param int    $time                 Session time
     * @param string $suspend              Suspend data
     * @param string $location             Lesson location
     * @param array  $interactions         Interactions array
     * @param string $core_exit            Core exit SCORM string
     * @param int    $sessionId            Session ID
     * @param int    $courseId             Course ID
     * @param int    $lmsFinish            Whether the call was issued from SCORM's LMSFinish()
     * @param int    $userNavigatesAway    Whether the user is moving to another item
     * @param int    $statusSignalReceived Whether the SCO called SetValue(lesson_status)
     * @param int    $nextItem             Switch to next item
     * @param int    $loadNav
     *
     * @return bool|string|null The resulting JS string
     */
    public static function saveItem(
        $lp_id,
        $user_id,
        $view_id,
        $item_id,
        $score = -1.0,
        $max = -1.0,
        $min = -1.0,
        $status = '',
        $time = 0,
        $suspend = '',
        $location = '',
        $interactions = [],
        $core_exit = 'none',
        $sessionId = null,
        $courseId = null,
        $lmsFinish = 0,
        $userNavigatesAway = 0,
        $statusSignalReceived = 0,
        $nextItem = 0,
        $loadNav = 0
    ) {
        $debug = 0;
        $return = null;
        if (empty($courseId)) {
            $courseId = api_get_course_int_id();
        }

        if ($debug > 0) {
            error_log('--------------------------------------');
            error_log('SAVE ITEM - lp_ajax_save_item.php');
            error_log('--------------------------------------');
            error_log("item_id: $item_id - lp_id: $lp_id - user_id: - $user_id - view_id: $view_id - item_id: $item_id");
            error_log("SCORE: $score - max:$max - min: $min - status:$status");
            error_log("TIME: $time - suspend: $suspend - location: $location - core_exit: $core_exit");
            error_log("finish: $lmsFinish - navigatesAway: $userNavigatesAway");
            error_log("courseId: $courseId");
        }

        $myLP = learnpath::getLpFromSession($courseId, $lp_id, $user_id);

        if (!is_a($myLP, 'learnpath')) {
            if ($debug) {
                error_log('mylp variable is not an learnpath object');
            }

            return null;
        }
        $prerequisitesCheck = $myLP->prerequisites_match($item_id);

        /** @var learnpathItem $myLPI */
        if ($myLP->items && isset($myLP->items[$item_id])) {
            $myLPI = $myLP->items[$item_id];
        }

        if (empty($myLPI)) {
            if ($debug > 0) {
                error_log("item #$item_id not found in the items array: ".print_r($myLP->items, 1));
            }

            return null;
        }

        // This functions sets the $this->db_item_view_id variable needed in get_status() see BT#5069
        $myLPI->set_lp_view($view_id);

        // Launch the prerequisites check and set error if needed
        if (true !== $prerequisitesCheck) {
            // If prerequisites were not matched, don't update any item info
            if ($debug) {
                error_log("prereq_check failed: ".intval($prerequisitesCheck));
            }

            return null;
        } else {
            if ($debug > 1) {
                error_log('Prerequisites are OK');
            }

            $logInfo = [
                'tool' => TOOL_LEARNPATH,
                'tool_id' => $lp_id,
                'tool_id_detail' => $item_id,
                'action' => 'view',
                'action_details' => $myLP->getCurrentAttempt(),
            ];
            Event::registerLog($logInfo);

            if (isset($max) && -1 != $max) {
                $myLPI->max_score = $max;
                $myLPI->set_max_score($max);
                if ($debug > 1) {
                    error_log("Setting max_score: $max");
                }
            }

            if (isset($min) && -1 != $min && 'undefined' !== $min) {
                $myLPI->min_score = $min;
                if ($debug > 1) {
                    error_log("Setting min_score: $min");
                }
            }

            // set_score function used to save the status, but this is not the case anymore
            if (isset($score) && -1 != $score) {
                if ($debug > 1) {
                    error_log('Calling set_score('.$score.')');
                    error_log('set_score changes the status to failed/passed if mastery score is provided');
                }
                $myLPI->set_score($score);
                if ($debug > 1) {
                    error_log('Done calling set_score '.$myLPI->get_score());
                }
            } else {
                if ($debug > 1) {
                    error_log('Score not updated');
                }
            }

            $statusIsSet = false;
            // Default behaviour.
            if (isset($status) && '' != $status && 'undefined' !== $status) {
                if ($debug > 1) {
                    error_log('Calling set_status('.$status.')');
                }

                $myLPI->set_status($status);
                $statusIsSet = true;
                if ($debug > 1) {
                    error_log('Done calling set_status: checking from memory: '.$myLPI->get_status(false));
                }
            } else {
                if ($debug > 1) {
                    error_log('Status not updated');
                }
            }

            $my_type = $myLPI->get_type();
            // Set status to completed for hotpotatoes if score > 80%.
            if ('hotpotatoes' === $my_type) {
                if ((empty($status) || 'undefined' === $status || 'not attempted' === $status) && $max > 0) {
                    if (($score / $max) > 0.8) {
                        $myStatus = 'completed';
                        if ($debug > 1) {
                            error_log('Calling set_status('.$myStatus.') for hotpotatoes');
                        }
                        $myLPI->set_status($myStatus);
                        $statusIsSet = true;
                        if ($debug > 1) {
                            error_log('Done calling set_status for hotpotatoes - now '.$myLPI->get_status(false));
                        }
                    }
                } elseif ('completed' === $status && $max > 0 && ($score / $max) < 0.8) {
                    $myStatus = 'failed';
                    if ($debug > 1) {
                        error_log('Calling set_status('.$myStatus.') for hotpotatoes');
                    }
                    $myLPI->set_status($myStatus);
                    $statusIsSet = true;
                    if ($debug > 1) {
                        error_log('Done calling set_status for hotpotatoes - now '.$myLPI->get_status(false));
                    }
                }
            } elseif ('sco' === $my_type) {
                /*
                 * This is a specific implementation for SCORM 1.2, matching page 26 of SCORM 1.2's RTE
                 * "Normally the SCO determines its own status and passes it to the LMS.
                 * 1) If cmi.core.credit is set to "credit" and there is a mastery
                 *    score in the manifest (adlcp:masteryscore), the LMS can change
                 *    the status to either passed or failed depending on the
                 *    student's score compared to the mastery score.
                 * 2) If there is no mastery score in the manifest
                 *    (adlcp:masteryscore), the LMS cannot override SCO
                 *    determined status.
                 * 3) If the student is taking the SCO for no-credit, there is no
                 *    change to the lesson_status, with one exception.  If the
                 *    lesson_mode is "browse", the lesson_status may change to
                 *    "browsed" even if the cmi.core.credit is set to no-credit.
                 * "
                 * Additionally, the LMS behaviour should be:
                 * If a SCO sets the cmi.core.lesson_status then there is no problem.
                 * However, the SCORM does not force the SCO to set the cmi.core.lesson_status.
                 * There is some additional requirements that must be adhered to
                 * successfully handle these cases:
                 * Upon initial launch
                 *   the LMS should set the cmi.core.lesson_status to "not attempted".
                 * Upon receiving the LMSFinish() call or the user navigates away,
                 *   the LMS should set the cmi.core.lesson_status for the SCO to "completed".
                 * After setting the cmi.core.lesson_status to "completed",
                 *   the LMS should now check to see if a Mastery Score has been
                 *   specified in the cmi.student_data.mastery_score, if supported,
                 *   or the manifest that the SCO is a member of.
                 *   If a Mastery Score is provided and the SCO did set the
                 *   cmi.core.score.raw, the LMS shall compare the cmi.core.score.raw
                 *   to the Mastery Score and set the cmi.core.lesson_status to
                 *   either "passed" or "failed".  If no Mastery Score is provided,
                 *   the LMS will leave the cmi.core.lesson_status as "completed"
                 */
                $masteryScore = $myLPI->get_mastery_score();
                if (-1 == $masteryScore || empty($masteryScore)) {
                    $masteryScore = false;
                }
                $credit = $myLPI->get_credit();

                /**
                 * 1) If cmi.core.credit is set to "credit" and there is a mastery
                 *    score in the manifest (adlcp:masteryscore), the LMS can change
                 *    the status to either passed or failed depending on the
                 *    student's score compared to the mastery score.
                 */
                if ('credit' === $credit &&
                    $masteryScore &&
                    (isset($score) && -1 != $score) &&
                    !$statusIsSet && !$statusSignalReceived
                ) {
                    if ($score >= $masteryScore) {
                        $myLPI->set_status('passed');
                        if ($debug) {
                            error_log('Set status: passed');
                        }
                    } else {
                        $myLPI->set_status('failed');
                        if ($debug) {
                            error_log('Set status: failed');
                        }
                    }
                    $statusIsSet = true;
                }

                /**
                 *  2) If there is no mastery score in the manifest
                 *    (adlcp:masteryscore), the LMS cannot override SCO
                 *    determined status.
                 */
                if (!$statusIsSet && !$masteryScore && !$statusSignalReceived) {
                    if (!empty($status)) {
                        if ($debug) {
                            error_log("Set status: $status because: statusSignalReceived ");
                        }
                        $myLPI->set_status($status);
                        $statusIsSet = true;
                    }
                    //if no status was set directly, we keep the previous one
                }

                /**
                 * 3) If the student is taking the SCO for no-credit, there is no
                 *    change to the lesson_status, with one exception. If the
                 *    lesson_mode is "browse", the lesson_status may change to
                 *    "browsed" even if the cmi.core.credit is set to no-credit.
                 */
                if (!$statusIsSet && 'no-credit' === $credit && !$statusSignalReceived) {
                    $mode = $myLPI->get_lesson_mode();
                    if ('browse' === $mode && 'browsed' === $status) {
                        if ($debug) {
                            error_log("Set status: $status because mode browse");
                        }
                        $myLPI->set_status($status);
                        $statusIsSet = true;
                    }
                    //if no status was set directly, we keep the previous one
                }

                /**
                 * If a SCO sets the cmi.core.lesson_status then there is no problem.
                 * However, the SCORM does not force the SCO to set the
                 * cmi.core.lesson_status.  There is some additional requirements
                 * that must be adhered to successfully handle these cases:.
                 */
                if (!$statusIsSet && empty($status) && !$statusSignalReceived) {
                    /**
                     * Upon initial launch the LMS should set the
                     * cmi.core.lesson_status to "not attempted".
                     */
                    // this case should be handled by LMSInitialize() and xajax_switch_item()
                    /**
                     * Upon receiving the LMSFinish() call or the user navigates
                     * away, the LMS should set the cmi.core.lesson_status for the
                     * SCO to "completed".
                     */
                    if ($lmsFinish || $userNavigatesAway) {
                        $myStatus = 'completed';
                        $updateStatus = true;
                        // Do not update status if "score as progress" and $userNavigatesAway
                        // The progress will be saved by the scorm BT#16766.
                        if ($userNavigatesAway && !$lmsFinish && $myLP->getUseScoreAsProgress()) {
                            $updateStatus = false;
                        }

                        if ($updateStatus) {
                            /**
                             * After setting the cmi.core.lesson_status to "completed",
                             *   the LMS should now check to see if a Mastery Score has been
                             *   specified in the cmi.student_data.mastery_score, if supported,
                             *   or the manifest that the SCO is a member of.
                             *   If a Mastery Score is provided and the SCO did set the
                             *   cmi.core.score.raw, the LMS shall compare the cmi.core.score.raw
                             *   to the Mastery Score and set the cmi.core.lesson_status to
                             *   either "passed" or "failed".  If no Mastery Score is provided,
                             *   the LMS will leave the cmi.core.lesson_status as "completed”.
                             */
                            if ($masteryScore && (isset($score) && -1 != $score)) {
                                if ($score >= $masteryScore) {
                                    $myStatus = 'passed';
                                } else {
                                    $myStatus = 'failed';
                                }
                            }
                            if ($debug) {
                                error_log("Set status: $myStatus because lmsFinish || userNavigatesAway");
                            }
                            $myLPI->set_status($myStatus);
                            $statusIsSet = true;
                        }
                    }
                }
                // End of type=='sco'
            }

            // If no previous condition changed the SCO status, proceed with a
            // generic behaviour
            if (!$statusIsSet && !$statusSignalReceived) {
                // Default behaviour
                if (isset($status) && '' != $status && 'undefined' !== $status) {
                    if ($debug > 1) {
                        error_log('Calling set_status('.$status.')');
                    }

                    $myLPI->set_status($status);

                    if ($debug > 1) {
                        error_log('Done calling set_status: checking from memory: '.$myLPI->get_status(false));
                    }
                } else {
                    if ($debug > 1) {
                        error_log("Status not updated");
                    }
                }
            }

            if (isset($time) && '' != $time && 'undefined' !== $time) {
                // If big integer, then it's a timestamp, otherwise it's normal scorm time.
                if ($debug > 1) {
                    error_log('Calling set_time('.$time.') ');
                }
                if ($time == intval(strval($time)) && $time > 1000000) {
                    if ($debug > 1) {
                        error_log("Time is INT");
                    }
                    $real_time = time() - $time;
                    if ($debug > 1) {
                        error_log('Calling $real_time '.$real_time.' ');
                    }
                    $myLPI->set_time($real_time, 'int');
                } else {
                    if ($debug > 1) {
                        error_log("Time is in SCORM format");
                    }
                    if ($debug > 1) {
                        error_log('Calling $time '.$time.' ');
                    }
                    $myLPI->set_time($time, 'scorm');
                }
            } else {
                $myLPI->current_stop_time = time();
            }

            if (isset($suspend) && '' != $suspend && 'undefined' !== $suspend) {
                $myLPI->current_data = $suspend;
            }

            if (isset($location) && '' != $location && 'undefined' !== $location) {
                $myLPI->set_lesson_location($location);
            }

            // Deal with interactions provided in arrays in the following format:
            // id(0), type(1), time(2), weighting(3), correct_responses(4), student_response(5), result(6), latency(7)
            if (is_array($interactions) && count($interactions) > 0) {
                foreach ($interactions as $index => $interaction) {
                    //$mylpi->add_interaction($index,$interactions[$index]);
                    //fix DT#4444
                    $clean_interaction = str_replace('@.|@', ',', $interactions[$index]);
                    $myLPI->add_interaction($index, $clean_interaction);
                }
            }

            if ('undefined' !== $core_exit) {
                $myLPI->set_core_exit($core_exit);
            }
            $myLP->save_item($item_id, false);
        }

        $myStatusInDB = $myLPI->get_status(true);
        if ($debug) {
            error_log("Status in DB: $myStatusInDB");
        }

        if ('completed' !== $myStatusInDB &&
            'passed' !== $myStatusInDB &&
            'browsed' !== $myStatusInDB &&
            'failed' !== $myStatusInDB
        ) {
            $myStatusInMemory = $myLPI->get_status(false);
            if ($debug) {
                error_log("myStatusInMemory: $myStatusInMemory");
            }

            if ($myStatusInMemory != $myStatusInDB) {
                $myStatus = $myStatusInMemory;
            } else {
                $myStatus = $myStatusInDB;
            }
        } else {
            $myStatus = $myStatusInDB;
        }

        $myTotal = $myLP->getTotalItemsCountWithoutDirs();
        $myComplete = $myLP->get_complete_items_count();
        $myProgressMode = $myLP->get_progress_bar_mode();
        $myProgressMode = '' === $myProgressMode ? '%' : $myProgressMode;

        if ($debug > 1) {
            error_log("mystatus: $myStatus");
            error_log("myprogress_mode: $myProgressMode");
            error_log("progress: $myComplete / $myTotal");
        }

        if ('sco' !== $myLPI->get_type()) {
            // If this object's JS status has not been updated by the SCORM API, update now.
            $return .= "olms.lesson_status='".$myStatus."';";
        }
        $return .= "update_toc('".$myStatus."','".$item_id."');";
        $update_list = $myLP->get_update_queue();

        foreach ($update_list as $my_upd_id => $my_upd_status) {
            if ($my_upd_id != $item_id) {
                /* Only update the status from other items (i.e. parents and brothers),
                do not update current as we just did it already. */
                $return .= "update_toc('".$my_upd_status."','".$my_upd_id."');";
            }
        }
        $progressBarSpecial = false;
        $scoreAsProgressSetting = api_get_configuration_value('lp_score_as_progress_enable');
        if (true === $scoreAsProgressSetting) {
            $scoreAsProgress = $myLP->getUseScoreAsProgress();
            if ($scoreAsProgress) {
                if (isset($score) && -1 != $score) {
                    $score = $myLPI->get_score();
                    $maxScore = $myLPI->get_max();
                    $return .= "update_progress_bar('$score', '$maxScore', '$myProgressMode');";
                }
                $progressBarSpecial = true;
            }
        }
        if (!$progressBarSpecial) {
            $return .= "update_progress_bar('$myComplete', '$myTotal', '$myProgressMode');";
        }

        if (!Session::read('login_as')) {
            // If $_SESSION['login_as'] is set, then the user is an admin logged as the user.
            $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

            $sql = "SELECT login_id, login_date
                FROM $tbl_track_login
                WHERE login_user_id= ".api_get_user_id()."
                ORDER BY login_date DESC
                LIMIT 0,1";

            $q_last_connection = Database::query($sql);
            if (Database::num_rows($q_last_connection) > 0) {
                $current_time = api_get_utc_datetime();
                $row = Database::fetch_array($q_last_connection);
                $i_id_last_connection = $row['login_id'];
                $sql = "UPDATE $tbl_track_login
                    SET logout_date='".$current_time."'
                    WHERE login_id = $i_id_last_connection";
                Database::query($sql);
            }
        }

        if (2 == $myLP->get_type()) {
            $return .= 'update_stats();';
        }

        // To be sure progress is updated.
        $myLP->save_last($score);

        Session::write('lpobject', serialize($myLP));
        Session::write('oLP', $myLP);
        if ($debug > 0) {
            error_log("lp_view_session_id :".$myLP->lp_view_session_id);
            error_log('---------------- lp_ajax_save_item.php : save_item end ----- ');
        }

        $logInfo = [
            'tool' => TOOL_LEARNPATH,
            'tool_id' => $myLP->get_id(),
            'action_details' => $myLP->getCurrentAttempt(),
            'tool_id_detail' => $myLP->get_current_item_id(),
            'action' => 'save_item',
        ];
        Event::registerLog($logInfo);

        $nextItem = (int) $nextItem;
        if (!empty($nextItem)) {
            $return .= self::switchItem(
                $lp_id,
                $user_id,
                $view_id,
                $item_id,
                $nextItem
            );
        }

        $loadNav = (int) $loadNav;
        if (1 === $loadNav) {
            $mediaplayer = $myLP->get_mediaplayer($item_id, 'true');

            if ($nextItem) {
                $now = time();
                $return .= "updateTimer($now);\n";
            }

            $score = $myLP->getCalculateScore($sessionId);
            $stars = $myLP->getCalculateStars($sessionId);
            $score = sprintf(get_lang('%s points'), $score);
            $return .= "updateGamification('$stars', '$score'); \n";

            $position = $myLP->isFirstOrLastItem($item_id);
            $return .= "checkCurrentItemPosition('$position'); \n";

            if ($mediaplayer) {
                $return .= $mediaplayer;
                $return .= "<script>
                    $(function() {
                        $('video:not(.skip), audio:not(.skip)').mediaelementplayer();
                    });
                </script>";
            }
        }

        return $return;
    }

    /**
     * Get one item's details.
     *
     * @param   int LP ID
     * @param   int user ID
     * @param   int View ID
     * @param   int Current item ID
     * @param   int New item ID
     */
    public static function switchItem($lpId, $user_id, $view_id, $current_item, $next_item)
    {
        $debug = 0;
        $return = '';
        if ($debug > 0) {
            error_log('--------------------------------------');
            error_log('SWITCH');
            error_log('Params('.$lpId.','.$user_id.','.$view_id.','.$current_item.','.$next_item.')');
        }
        //$objResponse = new xajaxResponse();
        /*$item_id may be one of:
         * -'next'
         * -'previous'
         * -'first'
         * -'last'
         * - a real item ID
         */
        $mylp = learnpath::getLpFromSession(api_get_course_int_id(), $lpId, $user_id);
        $new_item_id = 0;
        switch ($next_item) {
            case 'next':
                $mylp->set_current_item($current_item);
                $mylp->next();
                $new_item_id = $mylp->get_current_item_id();
                if ($debug > 1) {
                    error_log('In {next} - next item is '.$new_item_id.'(current: '.$current_item.')');
                }
                break;
            case 'previous':
                $mylp->set_current_item($current_item);
                $mylp->previous();
                $new_item_id = $mylp->get_current_item_id();
                if ($debug > 1) {
                    error_log('In {previous} - next item is '.$new_item_id.'(current: '.$current_item.')');
                }
                break;
            case 'first':
                $mylp->set_current_item($current_item);
                $mylp->first();
                $new_item_id = $mylp->get_current_item_id();
                if ($debug > 1) {
                    error_log('In {first} - next item is '.$new_item_id.'(current: '.$current_item.')');
                }
                break;
            case 'last':
                break;
            default:
                // Should be filtered to check it's not hacked.
                if ($next_item == $current_item) {
                    // If we're opening the same item again.
                    $mylp->items[$current_item]->restart();
                }
                $new_item_id = $next_item;
                $mylp->set_current_item($new_item_id);
                if ($debug > 1) {
                    error_log('In {default} - next item is '.$new_item_id.'(current: '.$current_item.')');
                }
                break;
        }

        if (WhispeakAuthPlugin::isLpItemMarked($new_item_id)) {
            ChamiloSession::write(
                WhispeakAuthPlugin::SESSION_LP_ITEM,
                ['lp' => $lpId, 'lp_item' => $new_item_id, 'src' => '']
            );
        }

        $mylp->start_current_item(true);
        if ($mylp->force_commit) {
            $mylp->save_current();
        }

        if (is_object($mylp->items[$new_item_id])) {
            $mylpi = $mylp->items[$new_item_id];
        } else {
            if ($debug > 1) {
                error_log('In switch_item_details - generating new item object', 0);
            }
            $mylpi = new learnpathItem($new_item_id);
            $mylpi->set_lp_view($view_id);
        }
        /*
         * now get what's needed by the SCORM API:
         * -score
         * -max
         * -min
         * -lesson_status
         * -session_time
         * -suspend_data
         */
        $myscore = $mylpi->get_score();
        $mymax = $mylpi->get_max();
        if ('' === $mymax) {
            $mymax = "''";
        }
        $mymin = $mylpi->get_min();
        $mylesson_status = $mylpi->get_status();
        $mylesson_location = $mylpi->get_lesson_location();
        $mytotal_time = $mylpi->get_scorm_time('js');
        $mymastery_score = $mylpi->get_mastery_score();
        $mymax_time_allowed = $mylpi->get_max_time_allowed();
        $mylaunch_data = $mylpi->get_launch_data();
        /*
        if ($mylpi->get_type() == 'asset') {
            // Temporary measure to save completion of an asset. Later on,
            // Chamilo should trigger something on unload, maybe...
            // (even though that would mean the last item cannot be completed)
            $mylesson_status = 'completed';
            $mylpi->set_status('completed');
            $mylpi->save();
        }
        */
        $mysession_time = $mylpi->get_total_time();
        $mysuspend_data = $mylpi->get_suspend_data();
        $mylesson_location = $mylpi->get_lesson_location();
        $myic = $mylpi->get_interactions_count();
        $myistring = '';
        for ($i = 0; $i < $myic; $i++) {
            $myistring .= ",[".$i.",'','','','','','','']";
        }
        if (!empty($myistring)) {
            $myistring = substr($myistring, 1);
        }
        /*
         * The following lines should reinitialize the values for the SCO
         * However, due to many complications, we are now relying more on the
         * LMSInitialize() call and its underlying lp_ajax_initialize.php call
         * so this code is technically deprecated (but the change of item_id should
         * remain). However, due to numerous technical issues with SCORM, we prefer
         * leaving it as a double-lock security. If removing, please test carefully
         * with both SCORM and proper learning path tracking.
         */
        $return .=
            "olms.score=".$myscore.";".
            "olms.max=".$mymax.";".
            "olms.min=".$mymin.";".
            "olms.lesson_status='".$mylesson_status."';".
            "olms.lesson_location='".$mylesson_location."';".
            "olms.session_time='".$mysession_time."';".
            "olms.suspend_data='".$mysuspend_data."';".
            "olms.total_time = '".$mytotal_time."';".
            "olms.mastery_score = '".$mymastery_score."';".
            "olms.max_time_allowed = '".$mymax_time_allowed."';".
            "olms.launch_data = '".$mylaunch_data."';".
            "olms.interactions = new Array(".$myistring.");".
            "olms.item_objectives = new Array();".
            "olms.G_lastError = 0;".
            "olms.G_LastErrorMessage = 'No error';".
            "olms.finishSignalReceived = 0;";
        /*
         * and re-initialise the rest
         * -lms_lp_id
         * -lms_item_id
         * -lms_old_item_id
         * -lms_new_item_id
         * -lms_initialized
         * -lms_progress_bar_mode
         * -lms_view_id
         * -lms_user_id
         */
        $mytotal = $mylp->getTotalItemsCountWithoutDirs();
        $mycomplete = $mylp->get_complete_items_count();
        $myprogress_mode = $mylp->get_progress_bar_mode();
        $myprogress_mode = ('' == $myprogress_mode ? '%' : $myprogress_mode);
        $mynext = $mylp->get_next_item_id();
        $myprevious = $mylp->get_previous_item_id();
        $myitemtype = $mylpi->get_type();
        $mylesson_mode = $mylpi->get_lesson_mode();
        $mycredit = $mylpi->get_credit();
        $mylaunch_data = $mylpi->get_launch_data();
        $myinteractions_count = $mylpi->get_interactions_count();
        //$myobjectives_count = $mylpi->get_objectives_count();
        $mycore_exit = $mylpi->get_core_exit();

        $return .=
            //"saved_lesson_status='not attempted';" .
            "olms.lms_lp_id=".$lpId.";".
            "olms.lms_item_id=".$new_item_id.";".
            "olms.lms_old_item_id=0;".
            //"lms_been_synchronized=0;" .
            "olms.lms_initialized=0;".
            //"lms_total_lessons=".$mytotal.";" .
            //"lms_complete_lessons=".$mycomplete.";" .
            //"lms_progress_bar_mode='".$myprogress_mode."';" .
            "olms.lms_view_id=".$view_id.";".
            "olms.lms_user_id=".$user_id.";".
            "olms.next_item=".$new_item_id.";".// This one is very important to replace possible literal strings.
            "olms.lms_next_item=".$mynext.";".
            "olms.lms_previous_item=".$myprevious.";".
            "olms.lms_item_type = '".$myitemtype."';".
            "olms.lms_item_credit = '".$mycredit."';".
            "olms.lms_item_lesson_mode = '".$mylesson_mode."';".
            "olms.lms_item_launch_data = '".$mylaunch_data."';".
            "olms.lms_item_interactions_count = '".$myinteractions_count."';".
            "olms.lms_item_objectives_count = '".$myinteractions_count."';".
            "olms.lms_item_core_exit = '".$mycore_exit."';\n".
            "olms.asset_timer = 0;";

        $sessionId = api_get_session_id();
        $updateMinTime = '';
        if (Tracking::minimumTimeAvailable($sessionId, api_get_course_int_id())) {
            $timeLp = $mylp->getAccumulateWorkTime();
            $timeTotalCourse = $mylp->getAccumulateWorkTimeTotalCourse();
            // Minimum connection percentage
            $perc = 100;
            // Time from the course
            $tc = $timeTotalCourse;
            // Percentage of the learning paths
            $pl = 0;
            if (!empty($timeTotalCourse)) {
                $pl = $timeLp / $timeTotalCourse;
            }

            // Minimum time for each learning path
            $time_total = intval($pl * $tc * $perc / 100) * 60;
            $lpTimeList = Tracking::getCalculateTime($user_id, api_get_course_int_id(), $sessionId);
            $lpTime = isset($lpTimeList[TOOL_LEARNPATH][$lpId]) ? $lpTimeList[TOOL_LEARNPATH][$lpId] : 0;

            if ($lpTime >= $time_total) {
                $time_spent = $time_total;
            } else {
                $time_spent = $lpTime;
            }

            $hour = (intval($lpTime / 3600)) < 10 ? '0'.intval($lpTime / 3600) : intval($lpTime / 3600);
            $minute = date('i', $lpTime);
            $second = date('s', $lpTime);
            $updateMinTime = "
                update_time_bar('$time_spent','$time_total','%');\n
                update_chronometer('$hour','$minute','$second');\n ";
        }

        $return .=
            "update_toc('unhighlight','".$current_item."');
            update_toc('highlight','".$new_item_id."');
            update_toc('$mylesson_status','".$new_item_id."');
            update_progress_bar('$mycomplete','$mytotal','$myprogress_mode');
            $updateMinTime"
        ;

        //$return .= 'updateGamificationValues(); ';
        $mylp->set_error_msg('');
        $mylp->prerequisites_match(); // Check the prerequisites are all complete.
        if ($debug > 1) {
            error_log($return);
            error_log('Prereq_match() returned '.htmlentities($mylp->error), 0);
        }
        // Save the new item ID for the exercise tool to use.
        Session::write('scorm_item_id', $new_item_id);
        Session::write('lpobject', serialize($mylp));
        Session::write('oLP', $mylp);

        return $return;
    }
}
