<?php

declare(strict_types=1);

if ('' == session_id()) {
    session_start();
}
echo '<small><small>'.date('Y-m-d G:i:s').'</small></small>';
