<?php

	/* For licensing terms, see /license.txt */
	// http://localhost/chamilo-1.11.10/plugin/h5p
	require_once __DIR__.'/../../main/inc/global.inc.php';

	if(api_is_anonymous()){
		header('Location: '.api_get_path(WEB_PATH));
	}

	$version = isset($_GET['version']) ? Security::remove_XSS($_GET['version']) : '';

	$out = '<p>Chamilo H5P is Update : ';

	if($version=='1-5'){

		$inSql = "ALTER TABLE `plugin_chamilo_h5p` ADD `terms_d` VARCHAR(512) NOT NULL AFTER `terms_c`;";
		Database::query($inSql);

		$inSql = "ALTER TABLE `plugin_chamilo_h5p` ADD `terms_e` VARCHAR(512) NOT NULL AFTER `terms_d`;";
		Database::query($inSql);

		$inSql = "ALTER TABLE `plugin_chamilo_h5p` ADD `terms_f` VARCHAR(512) NOT NULL AFTER `terms_e`;";
		Database::query($inSql);

		$out .= ' version 1-5';

	}

	$out .= '</p>';

	$out .= '<p><a class="btn btn-success" href="node_list.php" >View list of node</a></p>';

	$tpl = new Template('Update H5P');

	$tpl->assign('form', $out);

	$content = $tpl->fetch('/chamilo_h5p/view/update-h5p.tpl');

	$tpl->assign('content',$content);

	$tpl->display_one_col_template();
