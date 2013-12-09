<?php
/* For licensing terms, see /license.txt */

require_once '../../../../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fckeditor/editor/plugins/ajaxfilemanager/inc/config.php';

?>
<select class="input inputSearch" name="search_folder" id="search_folder">
    <?php
    foreach (getFolderListing(CONFIG_SYS_ROOT_PATH) as $k => $v) {
        ?>
        <option value="<?php echo $v; ?>"><?php echo shortenFileName($k, 30); ?></option>
    <?php
    }
    ?>
</select>