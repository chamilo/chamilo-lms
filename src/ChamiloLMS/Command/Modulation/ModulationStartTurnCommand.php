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
class ModulationStartTurnCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('modulation:start-turn')
            ->setDescription('Starts the given turn')
            ->addArgument('turn', InputArgument::REQUIRED, 'The id of the turn, as defined by branch_rel_session.display_order');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // This is just wrong, but was requested. Used to pass a session_id to
        // be used on the MineduAuthHttpsPostSend plugin.
        global $session_id, $app;
        Session::setSession($app['session']);
        Session::write('_user', api_get_user_info(1));

        $turn = intval($input->getArgument('turn'));
        $sql = "SELECT session_id FROM branch_rel_session WHERE display_order = $turn";
        $res = Database::query($sql);
        if (Database::num_rows($res) < 1) {
            $output->writeln("Turn $turn could not be found in database");
            return false;
        }
        if (Database::num_rows($res) > 1) {
            $output->writeln("Turn $turn was found several times. Not applying change");
            return false;
        }
        $row = Database::fetch_assoc($res);
        $sessionId = $row['session_id'];
        $params = array(
                    'id' => $sessionId,
                    'access_start_date' => api_get_utc_datetime('2010-01-01 07:00:00'),
                    'access_end_date' => api_get_utc_datetime('2020-12-31 23:00:00'),
                    'coach_access_start_date' => api_get_utc_datetime('2010-01-01 07:00:00'),
                    'coach_access_end_date' => api_get_utc_datetime('2020-12-31 23:00:00'),
        );
        $s = new \SessionManager();
        $s->update($params);

        //$output->writeln('The turn has been enabled.');
        return true;
    }
}
