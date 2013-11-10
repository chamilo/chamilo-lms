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
 * This command "starts a turn" (in Chamilo language, makes a session available)
 * for all its users (they need to already be subscribed to the session)
 *
 * Assumptions:
 * - the session exists but has start and end date previous to the real exam date
 * - the session is made available simply by making the start and end date period so large that there's no chance it wouldn't work
 * - users need to be subscribed to the session already
 * - the context must be very limited: the turn is defined by the display_order field in the branch_rel_session table. The value of display_order must be unique in this table (you cannot have more than one branch with sessions)
 */
class ModulationUsersInExamCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('modulation:users-in-exam')
            ->setDescription('Gets the number of users still busy taking an exam');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // This is just wrong, but was requested. Used to pass a session_id to
        // be used on the MineduAuthHttpsPostSend plugin.
        global $session_id, $app;
        Session::setSession($app['session']);
        Session::write('_user', api_get_user_info(1));

        $sql = "SELECT NOW()";
        $res = Database::query($sql);
        $row = Database::fetch_row($res);
        $current_date = $row[0];
        $query = "SELECT count(distinct(user_id)) ".
              " FROM track_e_attempt ".
              " WHERE DATE_ADD(tms, ".
              " INTERVAL 2 MINUTE) >= '".$current_date."'  ";
        $res = Database::query($query);
        $row = Database::fetch_row($res);
        $output->writeln($row[0]);
        return 0;
    }
}
