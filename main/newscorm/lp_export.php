<?php //$id: $
/**
 * Script to export the current path as a SCORM zip package.
 * This script cannot use the common controller lp_controller.php because we need to keep 
 * the headers clean of any session prior to output-ing the resulting file.
 * As we still need to check the user's credentials (because he might not have access to this file),
 * we need to get some ID proof.
 * Once the ID is checked with the database info, generate the file, send the corresponding headers
 * to force the download and let the user do the rest. This script should not change the screen
 * at all, so the user will still be able to continue what he was doing.
 * @todo get some ID proof
 * @package dokeos.learnpath 
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * The script takes three get parameters that enable the export.
 */

?>
