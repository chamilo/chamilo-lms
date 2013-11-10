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
//use \SessionManager as SessionManager;

/**
 * This command "starts a turn" (in Chamilo language, makes a session available)
 * for all its users (they need to already be subscribed to the session)
 *
 * Assumptions:
 * - the session exists but has start and end date previous to the real exam date
 * - the session is made available simply by making the start and end date period so large that there's no chance it wouldn't work
 * - users need to be subscribed to the session already
 * - the context must be very limited: the turn is defined by the display_order field in the branch_rel_session table. The value of display_order must be unique in this table (you cannot have more than one branch with sessions)
 */
class ModulationImportBranchesCommand extends Command
{
    /**
     * The int ID of the course that will be used inside the session, for the exam
     */
    protected $courseId = 1;
    
    protected function configure()
    {
        $this
            ->setName('modulation:import-branches')
            ->setDescription('Import branches and create turns for all users')
            ->addArgument('file', InputArgument::REQUIRED, 'The name of the CSV file to import, which is expected to have the following format: document;region;province;district;building;address;room;turn;mode');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // This is just wrong, but was requested. Used to pass a session_id to
        // be used on the MineduAuthHttpsPostSend plugin.
        global $session_id, $app;
        Session::setSession($app['session']);
        Session::write('_user', api_get_user_info(1));

        

        $file = $input->getArgument('file');
        // Test db
        $output->writeln("Starting to read $file into database ".$app['configuration']['main_database']);
        $csv = file($file);
        $tree = array();
        $regions = $provinces = $districts = $locals = $rooms = $turns = 0;
        $i = 0;
        foreach ($csv as $line) {
            list($dni,$region,$province,$district,$local,$address,$room,$turn,$mode) = preg_split('/;/',$line);
            $match = array('ó','Ó','ñ','Ñ','í','Í','é','É');
	    $replace = array('o','O','n','N','i','I','e','E');
            $dni = Database::escape_string(trim($dni));
            $region = Database::escape_string(trim($region));
            $province = Database::escape_string(trim($province));
            $district = Database::escape_string(trim($district));
            $local = Database::escape_string(trim(str_replace($match,$replace,$local)));
            $address = Database::escape_string(trim(str_replace($match,$replace,$address)));
            $room = Database::escape_string(trim($room));
            $turn = Database::escape_string(trim($turn));
            $mode = Database::escape_string(trim($mode));

            if (!isset($tree[$region])) {
              $tree[$region] = array();
              $regions++;
            }
            if (!isset($tree[$region][$province])) {
              $tree[$region][$province] = array();
              $provinces++;
            }
            if (!isset($tree[$region][$province][$district])) {
              $tree[$region][$province][$district] = array();
              $districts++;
            }
            if (!isset($tree[$region][$province][$district][$local.$address])) {
              $tree[$region][$province][$district][$local.$address] = array();
              $locals++;
            }
            if (!isset($tree[$region][$province][$district][$local.$address][$room])) {
              $tree[$region][$province][$district][$local.$address][$room] = array('mode' => $mode, 'turns' => array());
              $rooms++;
            }
            if (!isset($tree[$region][$province][$district][$local.$address][$room]['turns'][intval($turn)])) {
              $tree[$region][$province][$district][$local.$address][$room]['turns'][intval($turn)] = array();
              $tree[$region][$province][$district][$local.$address][$room]['turns'][intval($turn)+4] = array();
              $turns++;
            }
            $tree[$region][$province][$district][$local.$address][$room]['turns'][intval($turn)][] = $dni;
	    $tree[$region][$province][$district][$local.$address][$room]['turns'][intval($turn)+4][] = $dni;
            $i++;
            //$output->writeln("This was line $i");
        }

        // This section is very dangerous!!! Uncomment it only if you know what you're doing
        // Wipe out all branches, sessions and sessions_rel_user records before inserting new ones
        $sql = "TRUNCATE session_rel_course_rel_user";
        $res = Database::query($sql);
        $sql = "TRUNCATE session_rel_course";
        $res = Database::query($sql);
        $sql = "TRUNCATE session_rel_user";
        $res = Database::query($sql);
        $sql = "TRUNCATE branch_rel_session";
        $res = Database::query($sql);
        $sql = "TRUNCATE session";
        $res = Database::query($sql);
        $sql = "TRUNCATE branch_sync";
        $res = Database::query($sql);
        $sql = "TRUNCATE c_quiz_distribution_rel_session";
        $res = Database::query($sql);
        
        $output->writeln("All related tables have been truncated");

        // Now launch the complete filling of the branch_sync, session, and all other stuff
        $this->recursiveBranchesCreator($tree, null, null, null, null, $output);
 
        //$output->writeln('The turn has been enabled.');
        return 0;
    }

    /**
     * Recursively creates branches up to the 5th level
     * @param array Branches is the (multi-level) array containing the details of each branch
     * @param int Level of depth at which we are now (shouldn't get higher than 6)
     * @param int The ID of the parent branch
     * @param int The ID of the root (mother of all) branch
     * @param string The name of the parent branch (because we use it to prefix the current name)
     * @return bool true on success, false on failure
     */
    public function recursiveBranchesCreator($branches, $lvl = 1, $parentId = 0, $rootId = 0, $parentName = '')
    {
        // Just in case there is no root element so far, make one. The root 
        // element is NOT part of the array, so process it *then* move on
        if ($lvl == 0) {
            //$output->writeln("Inserting root branch");
            //get branch 0 from the default entry (assume national level)
            $sql = "SELECT id FROM branch_sync WHERE parent_id = 0";
            $res = Database::query($sql);
            if (Database::num_rows($res) < 1) {
                // Create root branch
                $sqli = "INSERT INTO branch_sync (branch_name,lvl,root,parent_id) VALUES (".
                        "'Minedu central',0,0,0)";
                $resi = Database::query($sqli);
                $parentId = Database::insert_id($resi);
                $rootId = $parentId;
            } else {
                $row = Database::fetch_row($res);
                $parentId = $row[0];
                $rootId = $parentId;
            }
            $lvl = 1;
        }
        if ($lvl == 6) {
            // We reached the maximum depth level. Now treat what's coming as sessions, 
            // using parent_id as branch_id in branch_rel_session
            //$output->writeln("Inserting sessions");
            $sql = "SELECT id FROM c_quiz_distribution order by id";
            $res = Database::query($sql);
            $distribs = array();
            // fetch 12 rows of c_quiz_distribution and put them inside the c_quiz_distribution_rel_session table accordingly
            while ($row = Database::fetch_row($res)) {
                $distribs[] = $row[0];
            }
            foreach ($branches['turns'] as $turn => $users) {
                //$completeName = substr($parentName,0,146).' - '.$turn;
                $completeName = '['.$parentId.'] '.$turn;
                $session = new \SessionManager();
                $params = array(
                            'id_coach' => 1,
                            'name' => $completeName,
                            'access_start_date' => api_get_utc_datetime('2010-01-01 07:00:00'),
                            'access_end_date' => api_get_utc_datetime('2010-01-01 23:00:00'),
                            'coach_access_start_date' => api_get_utc_datetime('2010-01-01 07:00:00'),
                            'coach_access_end_date' => api_get_utc_datetime('2010-01-01 23:00:00'),
                );
                $s = new \SessionManager();
                $sessionId = $s->add($params);
                //if ($sessionId !== false) {
                    //$output->writeln("Session $sessionId added");
                //}

                // Insert relation with users and course
                //$output->writeln("Adding course ".$this->courseId." to session ".$sessionId);
                \SessionManager::add_courses_to_session($sessionId,array($this->courseId));

                // Insert relation with branch
                $sql = "INSERT INTO branch_rel_session (branch_id, session_id, display_order) VALUES ".
                       " ($parentId,$sessionId,$turn)"; 
                $res = Database::query($sql);

                // Insert quiz distributions
                if ($turn > 4) {
                    $d = $distribs[$turn+3];
                    echo "Turn $turn = distribution $d\n";
                    $sql = "INSERT INTO c_quiz_distribution_rel_session (session_id, c_id, exercise_id, quiz_distribution_id) VALUES ($sessionId,".$this->courseId.",1,$d)";
                } else {
                    $d = $distribs[($turn*2)-2];
                    echo "Turn $turn = distribution $d\n";
                    $sql = "INSERT INTO c_quiz_distribution_rel_session (session_id, c_id, exercise_id, quiz_distribution_id) VALUES ($sessionId,".$this->courseId.",1,$d)";
                    $d = $distribs[($turn*2)-1];
                    echo "Turn $turn = distribution $d\n";
                    $sql = "INSERT INTO c_quiz_distribution_rel_session (session_id, c_id, exercise_id, quiz_distribution_id) VALUES ($sessionId,".$this->courseId.",1,$d)";
                }

                // Insert users into session
                $usersList = array();
                foreach ($users as $username) {
                    $sql = "SELECT user_id FROM user WHERE username = '".Database::escape_string($username)."'";
                    $res = Database::query($sql);
                    if ($res === false or Database::num_rows($res)<1) {
                        //$output->writeln("Could not find user $username in users table"); 
                    } else {
                        $row = Database::fetch_row($res);
                        $usersList[] = $row[0];
                    }
                }
                // Inserting users into session will automatically insert them into the course
                \SessionManager::suscribe_users_to_session($sessionId, $usersList, null, true);
            }
        } else {
            foreach ($branches as $name => $branch) {
                //$completeName = $parentName.'/'.$name;
                $completeName = $name;
                $mode = '';
                if ($lvl == 5 and isset($branch['mode'])) {
                    $mode = $branch['mode'];
                    $completeName = substr($parentName,0,40).'[...]'.substr($parentName,-20).' - Aula '.$name;
                }
                //$output->writeln("Inserting branch ".$completeName);
                $sqli = "INSERT INTO branch_sync(branch_name,lvl,root,parent_id,branch_ip) VALUES (".
                        "'$completeName',$lvl,$rootId,$parentId,'$mode')";
                $resi = Database::query($sqli);
                $parentId = Database::insert_id($resi);
                self::recursiveBranchesCreator($branch, $lvl+1, $parentId, $rootId, $completeName);
            }
        }
    }
}
