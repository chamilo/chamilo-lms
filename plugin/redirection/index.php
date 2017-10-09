<?php
/* For licensing terms, see /license.txt */
/**
 * Config the plugin
 * @author Enrique Alcaraz Lopez 
 * @package chamilo.plugin.redirection
 */

require_once __DIR__.'/config.php';
$redirecciones = PluginRedirection::get();

if (isset($_REQUEST["id"])) {
    PluginRedirection::delete($_REQUEST["id"]);
    header ("Location: index.php");
    exit();
} elseif (isset($_POST["submit_button"])) {    
    PluginRedirection::insert($_POST["user_id"], $_POST["url"]);
    header ("Location: index.php");
    exit();
}


?>

<form action="./index.php" method="post">
<div class="table-responsive well"> 
    <table class="table table-condensed">            
        <thead>
            <td><input type="text" class="form-control" placeholder="User_id" name="user_id" /></td>
            <td><input type="text" class="form-control" placeholder="url" name="url" /></td>
            <td><input type='submit' value='Agregar' name="submit_button" class='btn btn-primary' /></td>
        </thead>        
    </table>
    
</div>
</form>


<div class="table-responsive"> 
    <table class="table table-bordered table-condensed">            
        <tr>
            <th>Usuario</th>
            <th>Url</th>
            <th></th>
        </tr>            
        <?php
        foreach ($redirecciones as $redi) {
            echo '<tr>';
            echo '<td>' . $redi["user_id"] . '</td>';
            echo '<td>' . $redi["url"] . '</td>';
            echo '<td><a href="index.php?id=' . $redi["id"] . '">Borrar</a></td>';
            echo '</tr>';
        }
        ?>
    </table>
</div>
