<?php
/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__ . '/../inc/global.inc.php';

api_block_anonymous_users();
if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

$extraFields = MySpace::duGetUserExtraFields();
$defaultVar  = array_key_exists('dni', $extraFields) ? 'dni'
    : (array_key_exists('document', $extraFields) ? 'document' : (array_key_first($extraFields) ?: ''));

$selectedVar = isset($_REQUEST['field_var']) ? Security::remove_XSS($_REQUEST['field_var']) : $defaultVar;
$actionMode  = (isset($_REQUEST['unify_mode']) && $_REQUEST['unify_mode'] === 'delete') ? 'delete' : 'deactivate';
$doSearch    = isset($_REQUEST['do_search']) ? 1 : 0;
$doUnify     = isset($_POST['do_unify']) ? 1 : 0;

$self = api_get_self();

if ($doUnify) {
    // CSRF
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Security::check_token()) {
        $q = http_build_query([
            'field_var'  => $_POST['field_var'] ?? $selectedVar,
            'unify_mode' => $_POST['unify_mode'] ?? $actionMode,
            'do_search'  => 1,
            'flash'      => 'csrf',
        ]);
        header("Location: {$self}?{$q}");
        exit;
    }

    $fieldVar     = Security::remove_XSS($_POST['field_var'] ?? '');
    $fieldInfo    = MySpace::duGetUserExtraFieldByVariable($fieldVar);
    $fieldId      = (int) ($fieldInfo['id'] ?? 0);
    $fieldValue   = Security::remove_XSS($_POST['field_value'] ?? '');
    $finalUserId  = (int) ($_POST['final_user_id'] ?? 0);
    $unifyMode    = ($_POST['unify_mode'] ?? 'deactivate') === 'delete' ? 'delete' : 'deactivate';
    $urlId        = (int) api_get_current_access_url_id();

    if ($fieldId && $finalUserId && $fieldValue !== '') {
        $finalUserIsInGroup = false;
        $usersInGroup = MySpace::duGetUsersByFieldValue($fieldId, $urlId, $fieldValue);
        foreach ($usersInGroup as $uu) {
            if ((int)$uu['user_id'] === $finalUserId) { $finalUserIsInGroup = true; break; }
        }

        if (!$finalUserIsInGroup) {
            $q = http_build_query([
                'field_var'  => $fieldVar,
                'unify_mode' => $unifyMode,
                'do_search'  => 1,
                'flash'      => 'na',
            ]);
            header("Location: {$self}?{$q}");
            exit;
        }

        Database::query('START TRANSACTION');
        $ok = true;

        foreach ($usersInGroup as $u) {
            $uid = (int)$u['user_id'];
            if ($uid === $finalUserId) { continue; }

            MySpace::duUpdateAllUserRefsList($uid, $finalUserId);

            $ok = $ok && MySpace::duDisableOrDeleteUser($uid, $unifyMode);
        }

        if ($ok) {
            Database::query('COMMIT');
            Security::clear_token();
            $q = http_build_query([
                'field_var'  => $fieldVar,
                'unify_mode' => $unifyMode,
                'do_search'  => 1,
                'flash'      => 'ok',
                'fv'         => $fieldVar.'='.$fieldValue,
            ]);
            header("Location: {$self}?{$q}");
            exit;
        } else {
            Database::query('ROLLBACK');
            $q = http_build_query([
                'field_var'  => $fieldVar,
                'unify_mode' => $unifyMode,
                'do_search'  => 1,
                'flash'      => 'err',
                'em'         => get_lang('OperationFailedRollback'),
            ]);
            header("Location: {$self}?{$q}");
            exit;
        }
    } else {
        $q = http_build_query([
            'field_var'  => $selectedVar,
            'unify_mode' => $actionMode,
            'do_search'  => 1,
            'flash'      => 'na',
        ]);
        header("Location: {$self}?{$q}");
        exit;
    }
}

$nameTools = get_lang('DuplicatedUsers');
Display::display_header($nameTools);

echo '<div class="actions">'.MySpace::getTopMenu().'</div>';
echo MySpace::getAdminActions();

if (isset($_GET['flash'])) {
    $flash = $_GET['flash'];
    if ($flash === 'ok') {
        $msg = isset($_GET['fv']) ? get_lang('OperationCompleted').' ('.htmlspecialchars($_GET['fv']).')' : get_lang('OperationCompleted');
        echo Display::return_message($msg, 'confirm');
    } elseif ($flash === 'err') {
        $msg = isset($_GET['em']) ? htmlspecialchars($_GET['em']) : get_lang('OperationFailedRollback');
        echo Display::return_message($msg, 'error');
    } elseif ($flash === 'na') {
        echo Display::return_message(get_lang('NotAllowed'), 'error');
    } elseif ($flash === 'csrf') {
        echo Display::return_message(get_lang('NotAllowed').' (CSRF)', 'error');
    }
}

echo '<div class="panel panel-default">';
echo '  <div class="panel-heading"><strong>'.get_lang('DuplicatedUsers').'</strong></div>';
echo '  <div class="panel-body">';
echo '    <form method="get" class="form-horizontal" action="">';

echo '      <div class="form-group">';
echo '        <label for="field_var" class="col-sm-3 control-label">'.get_lang('SelectExtraField').'</label>';
echo '        <div class="col-sm-6">';
echo '          <select name="field_var" id="field_var" class="form-control">';
if (!empty($extraFields)) {
    foreach ($extraFields as $var => $label) {
        $sel = $var === $selectedVar ? 'selected' : '';
        echo '        <option value="'.htmlspecialchars($var).'" '.$sel.'>'.htmlspecialchars($label).'</option>';
    }
}
echo '          </select>';
echo '        </div>';
echo '      </div>';

echo '      <div class="form-group">';
echo '        <label class="col-sm-3 control-label">'.get_lang('WhatToDoWithUnifiedUsers').'</label>';
echo '        <div class="col-sm-6">';
echo '          <div class="radio"><label>';
echo '            <input type="radio" name="unify_mode" value="deactivate" '.($actionMode==='deactivate'?'checked':'').'> '.get_lang('Deactivate');
echo '          </label></div>';
echo '          <div class="radio"><label>';
echo '            <input type="radio" name="unify_mode" value="delete" '.($actionMode==='delete'?'checked':'').'> '.get_lang('Delete');
echo '          </label></div>';
echo '        </div>';
echo '      </div>';

echo '      <div class="form-group">';
echo '        <div class="col-sm-offset-3 col-sm-6">';
echo '          <button type="submit" name="do_search" value="1" class="btn btn-primary">';
echo            Display::return_icon('search.gif', get_lang('Search'), '').' '.get_lang('Search');
echo '          </button>';
echo '        </div>';
echo '      </div>';

echo '    </form>';
echo '  </div>';
echo '</div>';

if ($doSearch) {
    $fieldInfo = MySpace::duGetUserExtraFieldByVariable($selectedVar);
    if (empty($fieldInfo)) {
        echo Display::return_message(get_lang('ExtraFieldNotFound').': '.htmlspecialchars($selectedVar), 'error');
        Display::display_footer();
        exit;
    }

    $fieldId = (int)$fieldInfo['id'];
    $urlId   = (int) api_get_current_access_url_id();
    $dups    = MySpace::duGetDuplicateValues($fieldId, $urlId);

    echo "<div class='panel panel-default'>";
    echo "  <div class='panel-heading'><strong>".get_lang('SearchResultsFor').": </strong><code>".htmlspecialchars($selectedVar)."</code></div>";
    echo "  <div class='panel-body'>";

    if (empty($dups)) {
        echo Display::return_message(get_lang('NoDuplicatesFound'));
    } else {
        foreach ($dups as $g) {
            $value = $g['the_value'];
            $users = MySpace::duGetUsersByFieldValue($fieldId, $urlId, $value);

            echo "<div class='panel panel-info mb-3'>";
            echo "  <div class='panel-heading'>";
            echo "    <strong>".htmlspecialchars($selectedVar)."</strong>: <code>".htmlspecialchars($value)."</code>";
            echo "    <span class='badge' style='margin-left:8px'>".count($users).' '.get_lang('Users')."</span>";
            echo "  </div>";
            echo "  <div class='panel-body'>";

            echo "<div class='table-responsive'>";
            echo "<table class='table table-striped table-hover table-condensed'>";
            echo "<thead><tr>";
            echo "  <th>".htmlspecialchars($selectedVar)."</th>";
            echo "  <th>".get_lang('Username')."</th>";
            echo "  <th>".get_lang('FirstName')."</th>";
            echo "  <th>".get_lang('LastName')."</th>";
            echo "  <th>".get_lang('Email')."</th>";
            echo "  <th>".get_lang('UserId')."</th>";
            echo "  <th>".get_lang('RegistrationDate')."</th>";
            echo "  <th class='text-center'>".get_lang('UnifyToThisUser')."</th>";
            echo "</tr></thead><tbody>";

            foreach ($users as $u) {
                $uid = (int)$u['user_id'];
                echo "<tr>";
                echo "  <td>".htmlspecialchars($value)."</td>";
                echo "  <td>".htmlspecialchars($u['username'])."</td>";
                echo "  <td>".htmlspecialchars($u['firstname'])."</td>";
                echo "  <td>".htmlspecialchars($u['lastname'])."</td>";
                echo "  <td>".htmlspecialchars($u['email'])."</td>";
                echo "  <td>".$uid."</td>";
                echo "  <td>".htmlspecialchars($u['registration_date'])."</td>";
                echo "  <td class='text-center'>";
                echo "    <button type='button' class='btn btn-xs btn-danger'".
                    " data-toggle='modal' data-target='#confirmUnify'".
                    " data-finalid='".$uid."'".
                    " data-fieldvar='".htmlspecialchars($selectedVar, ENT_QUOTES)."'".
                    " data-fieldvalue='".htmlspecialchars($value, ENT_QUOTES)."'".
                    " data-count='".(count($users)-1)."'".
                    " data-actionmode='".htmlspecialchars($actionMode, ENT_QUOTES)."'".
                    ">".
                    Display::return_icon('save.png', get_lang('Unify'), '').' '.get_lang('Unify').
                    "</button>";
                echo "  </td>";
                echo "</tr>";
            }

            echo "</tbody></table>";
            echo "</div>";

            echo "  </div>";
            echo "</div>";
        }
    }

    echo "  </div>";
    echo "</div>";
}

?>
    <div id="confirmUnify" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="confirmUnifyLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="<?php echo get_lang('Close'); ?>"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="confirmUnifyLabel"><?php echo get_lang('Unify'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <p><?php echo get_lang('AreYouSureToUnify'); ?></p>
                        <p><strong><?php echo get_lang('FinalUser'); ?>:</strong> <span id="mFinalUser">#</span></p>
                        <p id="mCount"></p>

                        <input type="hidden" name="field_var" id="mFieldVar">
                        <input type="hidden" name="field_value" id="mFieldValue">
                        <input type="hidden" name="final_user_id" id="mFinalId">
                        <input type="hidden" name="unify_mode" id="mUnifyMode" value="deactivate">
                        <input type="hidden" name="do_unify" value="1">
                        <?php echo Security::get_HTML_token(); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo get_lang('Cancel'); ?></button>
                        <button type="submit" class="btn btn-danger"><?php echo get_lang('Unify'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        (function() {
            var $modal = $('#confirmUnify');
            $modal.on('show.bs.modal', function (e) {
                var btn = $(e.relatedTarget);
                var finalId    = btn.data('finalid');
                var fieldVar   = btn.data('fieldvar');
                var fieldValue = btn.data('fieldvalue');
                var count      = btn.data('count');
                var mode       = btn.data('actionmode');

                $('#mFinalId').val(finalId);
                $('#mFieldVar').val(fieldVar);
                $('#mFieldValue').val(fieldValue);
                $('#mUnifyMode').val(mode);

                $('#mFinalUser').text('#' + finalId);
                var txt = "<?php echo get_lang('WillMergeNUsers'); ?>";
                $('#mCount').text(txt.replace('{n}', count));
            });
        })();
    </script>
<?php
Display::display_footer();
