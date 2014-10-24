<?php
/* For licensing terms, see /license.txt */
/**
 * Definition of the SessionHandlerMemcache class
 * @package chamilo.library
 */
/**
 * Class SessionHandlerMemcache deals with volatile Memcache storage
 * and a more persistent but less frequent database backup storage
 * @todo This class might require a review to MySQL calls, depending on
 * when the session variables start to be saved
 */
class SessionHandlerMemcache
{
    public $connection;
    public $connection_handler;
    public $lifetime;
    public $session_name;
    public $memcache;
    public $initSessionData;

    public function __construct()
    {
        global $_configuration;

        $this->memcache = new Memcache;
        if (!empty($_configuration['memcache_server'])) {
            foreach ($_configuration['memcache_server'] as $serverData) {
                $isServerAvailable = @fsockopen($serverData['host'], $serverData['port']);
                if (!$isServerAvailable){
                    continue;
                }
                $this->memcache->addServer($serverData['host'], $serverData['port']);
            }
        }
        $this->lifetime = 3600; // 60 minutes

        $this->connection = array (
            'server' => $_configuration['db_host'],
            'login' => $_configuration['db_user'],
            'password' => $_configuration['db_password'],
            'base' => $_configuration['main_database']
        );

        $this->connection_handler = false;
    }

    public function sqlConnect()
    {
        if (!$this->connection_handler) {
            $this->connection_handler = @mysql_connect($this->connection['server'], $this->connection['login'], $this->connection['password'], true);

            // The system has not been designed to use special SQL modes that were introduced since MySQL 5
            @mysql_query("set session sql_mode='';", $this->connection_handler);

            @mysql_select_db($this->connection['base'], $this->connection_handler);

            // Initialization of the database connection encoding to be used.
            // The internationalization library should be already initialized.
            @mysql_query("SET SESSION character_set_server='utf8';", $this->connection_handler);
            @mysql_query("SET SESSION collation_server='utf8_general_ci';", $this->connection_handler);
            $system_encoding = api_get_system_encoding();
            if (api_is_utf8($system_encoding)) {
                // See Bug #1802: For UTF-8 systems we prefer to use "SET NAMES 'utf8'" statement in order to avoid a bizarre problem with Chinese language.
                @mysql_query("SET NAMES 'utf8';", $this->connection_handler);
            } else {
                @mysql_query("SET CHARACTER SET '" . Database::to_db_encoding($system_encoding) . "';", $this->connection_handler);
            }
        }

        return ($this->connection_handler) ? true : false;
    }

    public function sqlClose()
    {

        if ($this->connection_handler) {
            mysql_close($this->connection_handler);
            $this->connection_handler = false;
            return true;
        }

        return false;
    }

    public function sqlQuery($query, $dieOnError = true)
    {
        $result = mysql_query($query, $this->connection_handler);
        if ($dieOnError && !$result) {
            $this->sqlClose();
            return;
        }

        return $result;
    }

    public function open($savePath, $sessionName)
    {
        $sessionID = session_id();
        if ($sessionID !== "") {
            $this->initSessionData = $this->read($sessionID);
            $this->session_name = $sessionName;
        }
        return true;
    }

    public function close()
    {
        $this->lifeTime = null;
        $this->memcache = null;
        $this->initSessionData = null;

        return $this->gc(0) ? true : false;
    }

    public function read($sessionID)
    {
        $data = $this->memcache->get($sessionID);
        if (($data === false || empty($data)) && $this->sqlConnect()) {
            $result = $this->sqlQuery("SELECT session_value FROM ".$this->connection['base'].".php_session WHERE session_id='$sessionID'");
            if (!empty($result) && $result !== false && $row = Database::fetch_row($result)) {
                $data = stripslashes($row[0]);
                $this->memcache->set($sessionID, $data);
            } else {
                $data = false;
            }
        } else {
            $data = stripslashes($data);
        }

        return $data;
    }

    public function write($sessionID, $data)
    {
        global $_configuration;

        $this->memcache->set($sessionID, $data);
        if ($this->memcache->get('interactions-' . $sessionID) !== false) {
            $interactions = $this->memcache->get('interactions-' . $sessionID);
            ++$interactions;
            if ($_configuration['session_stored_after_n_times'] < $interactions) {
                $interactions = 1;
            }
            $this->memcache->set('interactions-' . $sessionID, $interactions);
        } else {
            $this->memcache->set('interactions-' . $sessionID, 1);
        }

        $interactions = $this->memcache->get('interactions-' . $sessionID);
        //$this->initSessionData !== $data #avoid this validation for performance improvements

        if ($_configuration['session_stored_after_n_times'] === $interactions) {
            $sessionID = mysql_real_escape_string($sessionID);
            $sessionExpirationTS = ($this->lifetime + time());
            $sessionData = mysql_real_escape_string($data);

             if ($this->sqlConnect()) {
                $result = $this->sqlQuery("INSERT INTO ".$this->connection['base'].".php_session(
                    session_id,session_name,session_time,session_start,session_value)
                    VALUES('$sessionID','".$this->session_name."','$sessionExpirationTS','$sessionExpirationTS','".addslashes($sessionData)."')", false);

                if (!$result) {
                    $this->sqlQuery("UPDATE ".$this->connection['base'].".php_session
                        SET session_name='".$this->session_name."',session_time='$sessionExpirationTS',session_value='".addslashes($sessionData)."'
                        WHERE session_id='$sessionID'");
                }
                return true;
            }
        }

        return false;
    }

    public function destroy($sessionID)
    {
        $this->memcache->delete($sessionID);
        if ($this->sqlConnect()) {
            $this->sqlQuery("DELETE FROM ".$this->connection['base'].".php_session WHERE session_id='$sessionID'");
            return true;
        }

        return false;
    }

    public function gc($maxlifetime)
    {
        if ($this->sqlConnect()) {
            $result = $this->sqlQuery("SELECT COUNT(session_id) FROM ".$this->connection['base'].".php_session");
            list($nbr_results) = Database::fetch_row($result);

            if ($nbr_results > 5000) {
                $this->sqlQuery("DELETE FROM ".$this->connection['base'].".php_session WHERE session_time<'".strtotime('-'.$this->lifetime.' minutes')."'");
            }

            $this->sqlClose();

            return true;
        }

        return false;
    }
}
