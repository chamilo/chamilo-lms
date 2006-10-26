<?php // $Id: install.inc.php 1846 2004-06-22 14:36:28Z olivierb78 $
/*
            +----------------------------------------------------------------------+
      | DOKEOS 1.5 $Revision: 1846 $                                      |
      +----------------------------------------------------------------------+
      | Copyright (c) 2004 The Dokeos Company                                |        |
      +----------------------------------------------------------------------+
      |   This program is free software; you can redistribute it and/or      |
      |   modify it under the terms of the GNU General Public License        |
      |   as published by the Free Software Foundation; either version 2     |
      |   of the License, or (at your option) any later version.             |
      |                                                                      |
      |   This program is distributed in the hope that it will be useful,    |
      |   but WITHOUT ANY WARRANTY; without even the implied warranty of     |
      |   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the      |
      |   GNU General Public License for more details.                       |
      |                                                                      |
      |   You should have received a copy of the GNU General Public License  |
      |   along with this program; if not, write to the Free Software        |
      |   Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA          |
      |   02111-1307, USA. The GNU GPL license is also available through     |
      |   the world-wide-web at http://www.gnu.org/copyleft/gpl.html         |
      +----------------------------------------------------------------------+
      | Authors: Thomas Depraetere <thomas.depraetere@dokeos.com>            |
      |          Hugues Peeters    <peeters@ipm.ucl.ac.be>                   |
      |          Christophe Gesché <gesche@ipm.ucl.ac.be>                    |
      +----------------------------------------------------------------------+
 */
$langEG 			= "par ex.";
$langDBHost			= "Database Hôte";
$langDBLogin		= "Database User";
$langDBPassword 	= "Database Mot de passe";
$langMainDB			= "Base principale de Dokeos";
$langStatDB             = "Base pour le tracking.  Utile uniquement si vous séparez les bases centrale et tracking";
$langEnableTracking     = "Activer le Tracking";
$langAllFieldsRequired	= "Toutes ces données sont requises";
$langPrintVers			= "Version imprimable";
$langLocalPath			= "Corresponding local path";
$langAdminEmail			= "Email de l'administrateur";
$langAdminName			= "Nom de l'administrateur";
$langAdminSurname		= "Prénom de l'administrateur";
$langAdminLogin			= "Identifiant de l'administrator";
$langAdminPass			= "Mot de passe de l'administrator";
$langEducationManager	= "Responsable du contenu";
$langHelpDeskPhone		= "N° de téléphone de l'assisance technique";
$langCampusName			= "Nom du campus";
$langInstituteShortName = "Nom abrégé de l'institution";
$langInstituteName		= "URL de l'institution";


$langDBSettingIntro		= "
				Install script will create claroline main DB. Please note that Dokeos
				will need to create many DBs. If you are allowed only one
				DB for your website by your Hosting Services, Dokeos will not work.";
$langStep1 			= "Étape 1 sur 6 ";
$langStep2 			= "Étape 2 sur 6 ";
$langStep3 			= "Étape 3 sur 6 ";
$langStep4 			= "Étape 4 sur 6 ";
$langStep5 			= "Étape 5 sur 6 ";
$langStep6 			= "Étape 6 sur 6 ";
$langCfgSetting		= "Config settings";
$langDBSetting 		= "MySQL database settings";
$langMainLang 		= "Langue principale";
$langLicence		= "Licence";
$langLastCheck		= "Last check before install";
$langRequirements	= "Requirements";

$langDbPrefixForm	= "Prefix pour le nom de base MySQL";
$langDbPrefixCom	= "Laissez vide si non requis";
$langEncryptUserPass	= "Crypter les mots de passes des utilisateur dans la base de données";
$langSingleDb	= "Use one or several DB for Dokeos";

$langWarningResponsible = "Utilisez ce script après avoir fait un backup. Nous ne pourrons être tenu responsable pour tout problème qui vous ferai perdre des données.";
$langAllowSelfReg	=	"Auto-inscription autorisée";
$langRecommended	=	"(recomamndé)";


?>
