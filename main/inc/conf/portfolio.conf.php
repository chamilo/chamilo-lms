<?php

/*
 * This file contains the portfolios configuration.
 * 
 * @see \Portfolio
 */

$portfolios = array();

$_mahara_portfolio = new Portfolio\Mahara('http://localhost/mahara/mahara_15/');
$portfolios[] = $_mahara_portfolio;

$_download_portfolio = new Portfolio\Download();
$_download_portfolio->set_title(get_lang('Download'));
$portfolios[] = $_download_portfolio;