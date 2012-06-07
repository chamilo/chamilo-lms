<?php

/*
 * This file contains the portfolios configuration.
 * 
 * Portfolios are external applicatoins used to display and share files. The
 * portfolio configuration set up where files can be sent.
 * 
 * @see \Portfolio
 */

$portfolios = array();

//$_mahara_portfolio = new Portfolio\Mahara('http(s)://...');
//$portfolios[] = $_mahara_portfolio;

$download_portfolio = new Portfolio\Download();
$download_portfolio->set_title(get_lang('download'));
$portfolios[] = $download_portfolio;