<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Fix track_e_hotspot table and migrate hotspot/values
 * In the wake of an issue with the hotspot code not being possible to update without moving from AS2 to AS3 (Flash), we
 * have decided to rewrite the hotspot tool in JS.
 * Little did we know that we would find out that the coordinates system in use by the Flash version of hotspot was in
 * fact a projection into a square form of 360x360, not depending on the original size of the image, and that a square
 * was defined by its center's coordinates and its width, with an initial not-null margin on the left and top borders.
 * The migration below fixes those parallax issues.
 */
class Version20151214170800 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE track_e_hotspot ADD c_id INT NULL");
        $this->addSql("ALTER TABLE track_e_hotspot MODIFY COLUMN hotspot_coordinate LONGTEXT NOT NULL");
        $this->addSql("UPDATE track_e_hotspot SET c_id = (SELECT id FROM course WHERE code = hotspot_course_code)");

        $answers = $this->connection->fetchAll("
            SELECT a.iid, a.c_id, a.question_id, a.hotspot_coordinates, a.hotspot_type, q.picture, c.directory
            FROM c_quiz_answer a
            INNER JOIN c_quiz_question q
            ON (a.question_id = q.id AND a.c_id = q.c_id)
            INNER JOIN course c
            ON (a.c_id = c.id AND q.c_id = c.id)
            WHERE a.hotspot_type IN ('square', 'circle', 'poly', 'delineation', 'oar')
        ");

        foreach ($answers as $answer) {
            // Recover the real image size to recalculate coordinates
            $imagePath = __DIR__ . "/../../../../courses/{$answer['directory']}/document/images/{$answer['picture']}";
            if (!file_exists($imagePath)) {
                error_log("Migration: Image does not exists: $imagePath");
                $imagePath = realpath($imagePath);
                error_log("Hotspot realpath: $imagePath");
                error_log("api_get_path: SYS_PATH: ".api_get_path(SYS_PATH));
                continue;
            }
            $imageSize = getimagesize($imagePath);
            $widthRatio = $imageSize[0] / 360;
            $heightRatio = $imageSize[1] / 360;
            $oldCoords = $answer['hotspot_coordinates'];
            $oldPairedString = explode('|', $oldCoords);
            $newPairedString = [];

            switch ($answer['hotspot_type']) {
                case 'square':
                    $oldCenter = explode(';', $oldPairedString[0]);
                    $oldCenterX = intval($oldCenter[0]);
                    $oldCenterY = intval($oldCenter[1]);
                    $oldWidth = intval($oldPairedString[1]);
                    $oldHeight = intval($oldPairedString[2]);

                    $newX = floor(($oldCenterX - $oldWidth / 2) * $widthRatio) + ceil($widthRatio);
                    $newY = floor(($oldCenterY - $oldHeight / 2) * $heightRatio) + ceil($heightRatio);
                    $newWidth = ceil($oldWidth * $widthRatio) + ceil(1 * $heightRatio);
                    $newHeight = ceil($oldHeight * $heightRatio) + floor(1 * $heightRatio);

                    $newPairedString[] = implode(';', [$newX, $newY]);
                    $newPairedString[] = $newWidth;
                    $newPairedString[] = $newHeight;
                    break;
                case 'circle':
                    $oldCenter = explode(';', $oldPairedString[0]);
                    $oldCenterX = intval($oldCenter[0]);
                    $oldCenterY = intval($oldCenter[1]);
                    $oldRadiusX = intval($oldPairedString[1]) / 2;
                    $oldRadiusY = intval($oldPairedString[2]) / 2;

                    $newCenterX = floor($oldCenterX * $widthRatio) + ceil($widthRatio);
                    $newCenterY = floor($oldCenterY * $heightRatio) + ceil($heightRatio);
                    $newRadiusX = floor($oldRadiusX * $widthRatio);
                    $newRadiusY = floor($oldRadiusY * $heightRatio);

                    $newPairedString[] = implode(';', [$newCenterX, $newCenterY]);
                    $newPairedString[] = $newRadiusX;
                    $newPairedString[] = $newRadiusY;
                    break;
                case 'poly':
                    //no break;
                case 'delineation':
                    //no break
                case 'oar':
                    $paired = [];
                    foreach ($oldPairedString as $pairString) {
                        $pair = explode(';', $pairString);
                        $x = isset($pair[0]) ? intval($pair[0]) : 0;
                        $y = isset($pair[1]) ? intval($pair[1]) : 0;

                        $paired[] = [$x, $y];
                    }

                    foreach ($paired as $pair) {
                        $x = floor($pair[0] * $widthRatio) + ceil($widthRatio);
                        $y = ceil($pair[1] * $heightRatio);

                        $newPairedString[] = implode(';', [$x, $y]);
                    }
                    break;
            }

            $stmt = $this->connection->prepare("
                UPDATE c_quiz_answer
                SET hotspot_coordinates = :coordinates
                WHERE iid = :iid AND c_id = :cid
            ");
            $stmt->bindValue('coordinates', implode('|', $newPairedString), Type::TEXT);
            $stmt->bindValue('iid', $answer['iid'], Type::INTEGER);
            $stmt->bindValue('cid', $answer['c_id'], Type::INTEGER);
            $stmt->execute();
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
