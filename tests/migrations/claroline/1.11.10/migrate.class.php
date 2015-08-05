<?php
/**
 * Functions used by migrate.php to migrate from Claroline to Chamilo
 */
class Migrate {
    public $db;
    public $userIdChanges = array(); // Previous ID => New ID array

    public function __construct() {
        require __DIR__.'/config.php';
        $this->db = new PDO(
            "mysql:host=$sourceHost;dbname=$sourceDB",
            $sourceUser,
            $sourcePass
        );
        $this->dbName = $sourceDB;
        $this->dbSingle = $sourceSingle;
        $this->dbTablePrefix = $sourcePref;
    }
    /**
     * Migrate users
     * @return int Number of users migrated
     */
    public function migrateUsers() {
        $count = 0;
        // Claroline => Chamilo
        $match = array(
            'user_id' => 'id',
            'nom' => 'lastname',
            'prenom' => 'firstname',
            'username' => 'username',
            'password' => 'password',
            'language' => 'language',
            'authSource' => 'auth_source', //in Claroline: claroline. In Chamilo: platform
            'email' => 'email',
            'officialCode' => 'official_code',
            'phoneNumber' => 'phone',
            'pictureUri' => 'picture_uri',
            'creatorId' => 'creator_id',
            'isPlatformAdmin' => 'is_admin', //existence in admin table (see below)
            'isCourseCreator' => 'status', //Claro: 1=teacher, 0=student. Chamilo: 1=teacher, 5=student
            'lastLogin' => 'last_login',
        );

        $sql = "SELECT * FROM ".$this->dbName.".".$this->dbTablePrefix."user ORDER BY user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            //print_r($row);
            $user = array();
            foreach ($match as $source => $dest) {
                $user[$dest] = $row[$source];
            }
            if ($row['user_id'] == 1) {
                // skip first user to try and keep the same user IDs for all users
                continue;
            }
            if ($row['isCourseCreator'] == 0) {
                $user['status'] = 5;
            }
            if ($row['authSource'] == 'claroline') {
                $user['auth_source'] = 'platform';
            }
            $newUserId = UserManager::create_user(
                $user['firstname'],
                $user['lastname'],
                $user['status'],
                $user['email'],
                $user['username'],
                $user['password'],
                $user['official_code'],
                $user['language'],
                $user['phone'],
                $user['picture_uri'],
                $user['auth_source'],
                null,
                null,
                null,
                null,
                null,
                null,
                $user['is_admin']
            );
            // Now we have created the user, but we'll try to give it the 
            // same ID as in the original database, or otherwise store the
            // new ID in an array for later re-use
            $sql = "SELECT username FROM user WHERE id = " . $user['id'] . " AND username != '".$user['username']."'";
            $res = Database::query($sql);
            $num = Database::num_rows($res);
            if ($num > 0) {
                //The ID is already used by someone else
                $this->userIdChanges[$user['id']] = $newUserId;
            }
            $count++;
        }
        return $count;
    }
}
