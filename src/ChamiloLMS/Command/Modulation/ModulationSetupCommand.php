<?php

namespace ChamiloLMS\Command\Modulation;

use Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use \ChamiloSession as Session;
use \UserManager as UserManager;
use Entity;

/**
 * A specific implementation for the modulation of users through the database
 * using an external table
 *
 * Assumptions:
 * - a table called chamilokeys.temp_modulation contains the distribution of users within branches and turns
 * - these branches can be inserted into the branch_sync table hierarchically
 * - 8 turns have to be built as sessions, for each last level of branch_sync (level 5)
 * - branch_rel_session table only contains the local branch entries.
 * - branch_rel_session.display_order represents a "turn".
 */
class ModulationSetupCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('modulation:setup')
            ->setDescription('Builds the whole modulation of users into branches+turns');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // This is just wrong, but was requested. Used to pass a session_id to
        // be used on the MineduAuthHttpsPostSend plugin.
        global $session_id, $app;
        Session::setSession($app['session']);
        Session::write('_user', api_get_user_info(1));

        // this code requires the modulation table to have been set as temp_modulation in the same database

        /**
         * Get the different structure levels
         */
        $sql = "SELECT id, nro_documento as nro, DEPARTAMENTO_modulado as dep, PROVINCIA_modulado as pro, DISTRITO_modulado as dis, LOCAL_modulado as loc, NRO_LABORATORIO_modulado as lab, TURNO_modulado as tur".
            " FROM temp_modulation ORDER BY dep, pro, dis, loc, lab, tur, nro";
        $res = Database::query($sql);
        $table = array();
        while ($row = Database::fetch_assoc($res)) {
            if (!isset($table[$row['dep']])) {
                $table[$row['dep']] = array();
            }
            if (!isset($table[$row['dep']][$row['pro']])) {
                $table[$row['dep']][$row['pro']] = array();
            }
            if (!isset($table[$row['dep']][$row['pro']][$row['dis']])) {
                $table[$row['dep']][$row['pro']][$row['dis']] = array();
            }
            if (!isset($table[$row['dep']][$row['pro']][$row['dis']][$row['loc']])) {
                $table[$row['dep']][$row['pro']][$row['dis']][$row['loc']] = array();
            }
            if (!isset($table[$row['dep']][$row['pro']][$row['dis']][$row['loc']][$row['lab']])) {
                $table[$row['dep']][$row['pro']][$row['dis']][$row['loc']][$row['lab']] = array();
            }
            if (!isset($table[$row['dep']][$row['pro']][$row['dis']][$row['loc']][$row['lab']][$row['tur']])) {
                $table[$row['dep']][$row['pro']][$row['dis']][$row['loc']][$row['lab']][$row['tur']] = array();
            }
            if (!in_array($row['nro'],$table[$row['dep']][$row['pro']][$row['dis']][$row['loc']][$row['lab']][$row['tur']])) {
                $table[$row['dep']][$row['pro']][$row['dis']][$row['loc']][$row['lab']][$row['tur']][] = $row['nro'];
            }
        }

        /**
         * Browse through branches and create their structure in the database (branch_sync table)
         * Reset table branch_sync by deleting everything > 1
         */
        // optional clean-up. Delete if necessary
        $sql2 = "DELETE FROM branch_sync where id > 1";
        Database::query($sql2);
        $sql2 = "TRUNCATE session";
        Database::query($sql2);
        $sql2 = "TRUNCATE session_rel_course";
        Database::query($sql2);
        $sql2 = "TRUNCATE session_rel_course_rel_user";
        Database::query($sql2);
        $sql2 = "TRUNCATE session_rel_user";
        Database::query($sql2);
        $sql2 = "TRUNCATE c_quiz_distribution_rel_session";
        Database::query($sql2);
        $sql2 = "TRUNCATE access_url_rel_session";
        Database::query($sql2);
        $sql2 = "TRUNCATE branch_rel_session";
        Database::query($sql2);


        // set some stable values
        $courseId = 1;
        // Get the quiz ID (we assume the first one in the list for this course
        $sql20 = "SELECT iid FROM c_quiz WHERE c_id = $courseId order by iid";
        $res20 = Database::query($sql20);
        if (Database::num_rows($res20) > 0) {
            $row20 = Database::fetch_assoc($res20);
            $exerciseId = $row20['iid'];
        } else {
            die("Could not find quiz in course_id $courseId\n");
        }

        // get the possible distributions for the exercise (normally, we should get 12)
        $distribs = array();
        $sql21 = "SELECT id FROM c_quiz_distribution WHERE exercise_id = $exerciseId";
        $res21 = Database::query($sql21);
        if (Database::num_rows($res21) < 1) {
            die("Could not find a suitable distribution for exercise $exerciseId\n");
        } else {
            while ($row21 = Database::fetch_assoc($res21)) {
                $distribs[] = $row21['id'];
            }
        }


        // Use the $table built above to find the hierarchy of branches. Some branches are
        // not really physical branches, they are just meant to structure things
        $level1Names = $level2Names = $level3Names = $level4Names = $level5Names = array();
        $level5DNIs = array();
        foreach ($table as $dep => $lev1) {
            $level = 1;
            $sql3 = "SELECT id FROM branch_sync WHERE lvl = $level AND branch_name = '$dep'";
            $res3 = Database::query($sql3);
            if (Database::num_rows($res3) > 0) {
                echo "Branch $dep at level $level already exists\n";
                $rowa1 = Database::fetch_row($res3);
                $id1 = $rowa1[0];
            } else {
                $sql4 = "INSERT INTO branch_sync (branch_name, lvl, root, parent_id) VALUES ('$dep', $level, 1, 1)";
                $res4 = Database::query($sql4);
                $id1 = Database::insert_id();
            }
            $level1Names[$dep] = $id1;
            $level = 2;
            foreach ($lev1 as $pro => $lev2) {
                $bname2 = substr($dep,0,20).'-'.substr($pro,0,20);
                $sql4b = "SELECT id FROM branch_sync WHERE lvl = $level AND branch_name = '$bname2'";
                $res4b = Database::query($sql4b);
                if (Database::num_rows($res4b) > 0) {
                    echo "Branch $bname2 at level $level already exists\n";
                    $rowa2 = Database::fetch_row($res4b);
                    $id2 = $rowa2[0];
                } else {
                    $sql5 = "INSERT INTO branch_sync (branch_name, lvl, root, parent_id) VALUES ('$bname2', $level, 1, $id1)";
                    $res5 = Database::query($sql5);
                    $id2 = Database::insert_id();
                }
                $level2Names[$bname2] = $id2;
                $level = 3;
                foreach ($lev2 as $dis => $lev3) {
                    $bname3 = $bname2.'-'.substr($dis,0,20);
                    $sql6 = "SELECT id FROM branch_sync WHERE lvl = $level AND branch_name = '$bname3'";
                    $res6 = Database::query($sql6);
                    if (Database::num_rows($res6) > 0) {
                        echo "Branch $bname3 at level $level already exists\n";
                        $rowa3 = Database::fetch_row($res6);
                        $id3 = $rowa3[0];
                    } else {
                        $sql7 = "INSERT INTO branch_sync (branch_name, lvl, root, parent_id) VALUES ('$bname3', $level, 1, $id2)";
                        $res7 = Database::query($sql7);
                        $id3 = Database::insert_id();
                    }
                    $level3Names[$bname3] = $id3;
                    $level = 4;
                    foreach ($lev3 as $loc => $lev4) {
                        $bname4 = $bname3.'-'.substr($loc,0,50);
                        $sql8 = "SELECT id FROM branch_sync WHERE lvl = $level AND branch_name = '$bname4'";
                        $res8 = Database::query($sql8);
                        if (Database::num_rows($res8) > 0) {
                            echo "Branch $bname4 at level $level already exists\n";
                            $rowa4 = Database::fetch_row($res8);
                            $id4 = $rowa4[0];
                        } else {
                            $sql9 = "INSERT INTO branch_sync (branch_name, lvl, root, parent_id) VALUES ('$bname4', $level, 1, $id3)";
                            $res9 = Database::query($sql9);
                            $id4 = Database::insert_id();
                        }
                        $level4Names[$bname4] = $id4;
                        $level = 5;
                        foreach ($lev4 as $lab => $lev5) {
                            $bname5 = $bname4.'-'.substr($lab,0,20);
                            $sql10 = "SELECT id FROM branch_sync WHERE lvl = $level AND branch_name = '$bname5'";
                            $res10 = Database::query($sql10);
                            if (Database::num_rows($res10) > 0) {
                                echo "Branch $bname5 at level $level already exists\n";
                                $rowa5 = Database::fetch_row($res10);
                                $id5 = $rowa5[0];
                            } else {
                                $sql11 = "INSERT INTO branch_sync (branch_name, lvl, root, parent_id) VALUES ('$bname5', $level, 1, $id4)";
                                $res11 = Database::query($sql11);
                                $id5 = Database::insert_id();
                            }
                            $level5Names[$bname5] = $id5;
                            $level5DNIs[$bname5][1] = $lev5[1];
                            $level5DNIs[$bname5][2] = $lev5[2];
                            if (isset($lev5[3])) {
                                $level5DNIs[$bname5][3] = $lev5[3];
                            }
                            if (isset($lev5[4])) {
                                $level5DNIs[$bname5][4] = $lev5[4];
                            }
                            $level = 6;
                        }
                    }
                }
            }
        }
        echo "Created all labs. Now proceeding to sessions creation...\n";
        // Now create sessions for all these
        $sessionCount = 0;
        foreach ($level5Names as $name => $id) {
            // Create 8 sessions for each room, the first 4 of which are assigned two questions distributions
            for ($i = 1; $i <= 8; $i++) {
                $j = $i;
                // create session with default tutor (1) and PNC course (2?), with the name of the institution+room+turn with date in the future
                $s = new \SessionManager();
                $params = array(
                    'id_coach' => 1,
                    'name' => $name.' - Turno '.($i>4?'contingencia '.($i-4):$i),
                    'session_admin_id' => 1,
                    'visibility' => 1,
                    'display_start_date' => api_get_utc_datetime('2013-11-01 07:00:00'),
                    'display_end_date' => api_get_utc_datetime('2013-11-01 23:00:00'),
                    'access_start_date' => api_get_utc_datetime('2013-11-01 07:00:00'),
                    'access_end_date' => api_get_utc_datetime('2013-11-01 23:00:00'),
                    'coach_access_start_date' => api_get_utc_datetime('2013-11-01 07:00:00'),
                    'coach_access_end_date' => api_get_utc_datetime('2013-11-01 23:00:00')
                );
                $sessionId = $s->add($params, null);
                $sessionCount++;
                $s->add_courses_to_session($sessionId, array($courseId));
                $sql23 = "INSERT INTO branch_rel_session (branch_id, session_id, display_order) VALUES ($id, $sessionId, $i)";
                $res23 = Database::query($sql23);
                // assign specific forms to single exercise in DB
                if ($i <= 4) {
                    $local_distribs = array($distribs[($i*2)-2],$distribs[($i*2)-1]); //if turn 1, use distribs 0 and 1, if turn 2, use 2 and 3
                    $sql22 = "INSERT INTO c_quiz_distribution_rel_session (session_id, c_id, exercise_id, quiz_distribution_id) VALUES ($sessionId, $courseId, $exerciseId, ".$local_distribs[0].")";
                    $res22 = Database::query($sql22);
                    $sql22 = "INSERT INTO c_quiz_distribution_rel_session (session_id, c_id, exercise_id, quiz_distribution_id) VALUES ($sessionId, $courseId, $exerciseId, ".$local_distribs[1].")";
                    $res22 = Database::query($sql22);
                } else {
                    //contingency turns
                    //$local_distribs = array($distribs[($i+3)]); //if turn 5, use distrib 8, if turn 6, use 9, etc (only one distrib per contingency)
                    $sql22 = "INSERT INTO c_quiz_distribution_rel_session (session_id, c_id, exercise_id, quiz_distribution_id) VALUES ($sessionId, $courseId, $exerciseId, ".$distribs[($i+3)].")";
                    $res22 = Database::query($sql22);
                }
                // assign users to the session (get DNI from temp_modulation and select from user before inserting)
                $uids = array();
                if ($i > 4) {
                    $j = $i - 4;
                }
                if (is_array($level5DNIs[$name][$j])) {
                    foreach ($level5DNIs[$name][$j] as $dni) {
                        $u = UserManager::get_user_info($dni);
                        if ($u === false) {
                            echo "Could not find user $dni\n";
                        } else {
                            $uids[] = $u['user_id'];
                        }
                    }
                }
                //print_r($uids);
                $s->suscribe_users_to_session($sessionId, $uids);
            }
        }
        echo count($level5Names)." locals inserted and configured in total ($sessionCount sessions)\n";
        $command = $this->getApplication()->find('modulation:setup');

        //$return_code = $command->run($input, $output);
        /*
        if ($return_code !== 0) {
            $output->writeln('Failed trying to send the turn information.');
            return $return_code;
        }
        */
    }
}
