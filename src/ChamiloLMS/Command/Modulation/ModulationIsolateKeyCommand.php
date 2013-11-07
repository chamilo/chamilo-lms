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
 * using an external table. This command isolates the data for just one USB key
 * by finding the resources proper to a specific key (identified by its
 * branch_id) and removing the rest
 *
 * Assumptions:
 * - you should launch this command from a copy of your main database, otherwise it will wipe out your data
 */
class ModulationIsolateKeyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('modulation:isolate-key')
            ->setDescription('Isolates the data specific to a branck/key (deletes what is not necessary)')
            ->addArgument('key', InputArgument::REQUIRED, 'The ID of the key to be isolated.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // This is just wrong, but was requested. Used to pass a session_id to
        // be used on the MineduAuthHttpsPostSend plugin.
        global $session_id, $app;
        Session::setSession($app['session']);
        Session::write('_user', api_get_user_info(1));

        $destDir = '/var/opt/keys';
        $certServ = 'http://debian4.beeznest.org/keys/';

        $branchId = intval($input->getArgument('key'));
        $sql = "SELECT session_id FROM branch_rel_session WHERE branch_id = $branchId ORDER BY display_order";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            $output->writeln('No key was found with this branch ID.');
            return 0;
        }
        $sessions = array();
        $sessionUsers = array();
        while ($row = Database::fetch_assoc($res)) {
            $sessions[] = $row['session_id'];
            $sql2 = "SELECT id_user FROM session_rel_user WHERE id_session = ".$row['session_id'];
            $res2 = Database::query($sql2);
            while ($row2 = Database::fetch_assoc($res2)) {
                $sessionUsers[] = $row2['id_user'];
            }
        }
        echo "Found ".count($sessions)." sessions with ".count($sessionUsers)." users. Proceeding to isolation\n";

        /**
         * Remove the data from all relevant sessions
         */
        // optional clean-up. Delete if necessary
        $sql2 = "DELETE FROM branch_sync where branch_id != $branchId";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM session WHERE id NOT IN (".implode(', ',$sessions).")";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM session_rel_course WHERE id_session NOT IN (".implode(', ',$sessions).")";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM session_rel_course_rel_user WHERE id_session NOT IN (".implode(', ',$sessions).")";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM session_rel_user WHERE id_session NOT IN (".implode(', ',$sessions).")";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM c_quiz_distribution_rel_session WHERE session_id NOT IN (".implode(', ',$sessions).")";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM access_url_rel_session WHERE session_id NOT IN (".implode(', ',$sessions).")";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM branch_rel_session WHERE session_id NOT IN (".implode(', ',$sessions).")";
        echo $sql2."\n";
        Database::query($sql2);
        $sql2 = "DELETE FROM user WHERE user_id NOT IN (".implode(', ',$sessionUsers).") AND user_id > 1";
        echo $sql2."\n";
        Database::query($sql2);
        $sql3 = "SELECT count(*) FROM user";
        $res3 = Database::query($sql3);
        $count = Database::fetch_row($res3);
        // Set the local branch accordindly.
        $update_local_branch_sql = sprintf("UPDATE settings_current SET selected_value = %d WHERE variable = 'local_branch_id'", $branchId);
        Database::query($update_local_branch_sql);

        //$output->writeln("$count users remain");
        //$output->writeln('The database should now be isolated.');

        // Make dir if not present. Assume the command succeeds as everything
        // executes as root anyway
        if (!is_dir($destDir)) {
            @mkdir($destDir);
        }
        // Get the SSL certificate from the server, at
        //  http://server/keys/24.p12
        $cert = file_get_contents($certServ.$branchId.'.p12');
        @file_put_contents($destDir.'/'.$branchId.'.p12',$cert);
        $caPub = file_get_contents($certServ.'ca-cert.pem');
        @file_put_contents($destDir.'/ca-cert.pem',$caPub);
    }
}
