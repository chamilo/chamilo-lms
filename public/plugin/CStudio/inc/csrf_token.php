<?php

declare(strict_types=1);

function savedCSRFToken($iduser)
{
    $cotk = '';
    $iduser = (int) $iduser;

    if ($iduser > 0) {
        $sql = "SELECT token FROM plugin_oel_tools_token WHERE id_user = $iduser ";
        $VDB = new VirtualDatabase();
        $cotk = $VDB->get_value_by_query($sql, 'token');
    }

    if ('-1' == $cotk || '' == $cotk || null === $cotk) {
        if (isset($_SESSION['csrf_token']) && '' != $_SESSION['csrf_token']) {
            $cotk = $_SESSION['csrf_token'];
        } else {
            $cotk = '-2';
        }
    }

    return $cotk;
}

function generateCSRFToken($iduser)
{
    $VDB = new VirtualDatabase();
    $iduser = (int) $iduser;

    $cotk = '-1';

    $date = date('Ymd');
    $prefix = $date.'xx';

    $sqlCleanTokens = "DELETE FROM plugin_oel_tools_token WHERE token NOT LIKE '$prefix%' AND id_user = $iduser";
    $VDB->query($sqlCleanTokens);

    if (isset($_SESSION['csrf_token']) && '' != $_SESSION['csrf_token']) {
        $cotk = $_SESSION['csrf_token'];
        if (!str_starts_with($cotk, $prefix)) {
            unset($_SESSION['csrf_token']);
        }
    }

    if ($iduser > 0) {
        $sql = "SELECT token FROM plugin_oel_tools_token WHERE id_user = $iduser ";

        $cotk = $VDB->get_value_by_query($sql, 'token');

        if ('-1' == $cotk || '' == $cotk || null === $cotk) {
            $newToken = uuidToken(28);

            $newToken = $date.'xx'.$newToken;

            $sqlToken = "REPLACE INTO plugin_oel_tools_token (id_user, token) VALUES ($iduser, '$newToken')";

            $result = $VDB->query($sqlToken);

            if ($result) {
                $cotk = $newToken;
            } else {
                error_log("CSRF Token: Failed to insert token for user $iduser");
                $cotk = '-1';
            }
        }

        if ('-1' != $cotk && '' != $cotk && null != $cotk) {
            $_SESSION['csrf_token'] = $cotk;
        }
    }

    return $cotk;
}

function validateCSRFToken($oel_token, $iduser)
{
    $ctr_token = savedCSRFToken($iduser);
    if ($ctr_token == $oel_token) {
        return true;
    }

    return false;
}

function uuidToken($length)
{
    return bin2hex(random_bytes((int) ceil($length / 2)));
}
