<?php
	/**
	 * Description:
	 * build the comunication with the SOAP server Compilatio.net
	 * call severals methods for the file management in Compilatio.net
	 *
	 * Date: 26/05/16
	 * @version:1.0
	 */

	class compilatio
	{
		/*Identification key for the Compilatio account*/
		var $key  = null;
		/*Webservice connection*/
		var $soapcli;

        /**
         * compilatio constructor.
         * @param $key
         * @param $urlsoap
         * @param $proxyHost
         * @param $proxyPort
         */
		function compilatio($key, $urlsoap, $proxyHost, $proxyPort)
		{
			try
			{
				if(!empty($key)){
					$this->key = $key;
					if(!empty($urlsoap)){
						if(!empty($proxyHost)) {
							$param = array('trace' => false,
								'soap_version' => SOAP_1_2,
								'exceptions' => true,
								'proxy_host' => '"' . $proxyHost . '"',
								'proxy_port' => $proxyPort);
						} else {
							$param = array('trace' => false,
								'soap_version' => SOAP_1_2,
								'exceptions' => true);
						}
						$this->soapcli = new SoapClient($urlsoap,$param);
					}else{
						$this->soapcli = 'WS urlsoap not available' ;
					}
				}else{
					$this->soapcli ='API key not available';
				}
			}
			catch (SoapFault $fault)
			{
				$this->soapcli = "Error constructor compilatio " . $fault->faultcode ." " .$fault->faultstring ;
			}
			catch (Exception $e) {
				$this->soapcli = "Error constructor compilatio with urlsoap" . $urlsoap;
			}
		}


		/**
		 * Method for the file load
		 *
		 * @param $title
		 * @param $description
		 * @param $filename
         * @param $mimeType
		 * @param $content
		 * @return string
		 */
        function sendDoc($title, $description, $filename, $mimeType, $content)
		{
			try
			{
				if (!is_object($this->soapcli))
					return("Error in constructor compilatio() " . $this->soapcli);
				$idDocument = $this->soapcli->__call('addDocumentBase64',
					array(
						$this->key,
						utf8_encode(urlencode($title)),
						utf8_encode(urlencode($description)),
						utf8_encode(urlencode($filename)),
                        utf8_encode($mimeType),
						base64_encode($content)
					)
				);
				return $idDocument;
			}
			catch (SoapFault $fault)
			{
                return("Erreur sendDoc()" . $fault->faultcode ." " .$fault->faultstring);
			}
		}


		/**
		 * Method for recover a document's information
		 * @param $compiHash
		 * @return string
		 */
        function getDoc($compiHash)
		{
			try
			{
				if (!is_object($this->soapcli)){
					return("Error in constructor compilatio() " . $this->soapcli);
				}
				$param = array($this->key, $compiHash);
				$idDocument = $this->soapcli->__call('getDocument', $param);
				return $idDocument;
			}
			catch (SoapFault $fault)
			{
                return("Erreur getDoc()" . $fault->faultcode . " " .$fault->faultstring);
			}
		}

		/**
		 * method for recover an url document's report
		 * @param $compiHash
		 * @return string
		 */
        function getReportUrl($compiHash)
		{
			try
			{
				if (!is_object($this->soapcli)){
					return("Error in constructor compilatio() " . $this->soapcli);
				}
				$param = array($this->key,$compiHash);
				$idDocument = $this->soapcli->__call('getDocumentReportUrl', $param);
				return $idDocument;
			}
			catch (SoapFault $fault)
			{
                return("Erreur  getReportUrl()" . $fault->faultcode ." " .$fault->faultstring);
			}
		}

		/**
		 *  Method for deleting a Compialtio's account document
		 * @param $compiHash
		 * @return string
		 */
        function deldoc($compiHash)
		{
			try
			{
				if (!is_object($this->soapcli)){
					return("Error in constructor compilatio() " . $this->soapcli);
				}
				$param = array($this->key,$compiHash);
				$this->soapcli->__call('deleteDocument', $param);
			}
			catch (SoapFault $fault)
			{
                return("Erreur  deldoc()" . $fault->faultcode . " " .$fault->faultstring);
			}
		}

		/**
		 * Method for start the analysis for a document
		 * @param $compiHash
		 * @return string
		 */
        function startAnalyse($compiHash)
		{
			try
			{
				if (!is_object($this->soapcli)){
					return("Error in constructor compilatio() " . $this->soapcli);
				}
				$param = array($this->key,$compiHash);
                $this->soapcli->__call('startDocumentAnalyse', $param);
			}
			catch (SoapFault $fault)
			{
                return("Erreur  startAnalyse()" . $fault->faultcode ." " .$fault->faultstring);
			}
		}

		/**
		 * Method for recover the account's quota
		 * @return string
		 */
        function getQuotas()
		{
			try
			{
				if (!is_object($this->soapcli)){
					return("Error in constructor compilatio() " . $this->soapcli);
				}
				$param=array($this->key);
                $resultat=$this->soapcli->__call('getAccountQuotas', $param);
				return $resultat;
			}
			catch (SoapFault $fault)
			{
                return("Erreur  getQuotas()" . $fault->faultcode ." " .$fault->faultstring);
			}
		}

	}

	/**
	 * Method for identify a file extension and the possibility that the document can be managed by Compilatio
	 * @param $filename
	 * @return bool
	 */
    function verifiFileType($filename)
	{
		$types = array("doc","docx", "rtf", "xls", "xlsx", "ppt", "pptx", "odt", "pdf", "txt", "htm", "html");
		$extension = substr($filename, strrpos($filename, ".")+1);
		$extension = strtolower($extension);
		if (in_array($extension, $types)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Fonction  affichage de la barre de progression d'analyse version 3.1
	 *
	 * @param $statut From the document
	 * @param $pour
	 * @param $imagesPath
	 * @param $text  Array includes the extract from the text
	 * @return unknown_type
	 */
	function getProgressionAnalyseDocv31($status, $pour = 0, $imagesPath = '', $text = '')
	{

		$refreshReturn = "<a href='javascript:window.location.reload(false);'><img src='"
			.$imagesPath
			."ajax-loader_green.gif' title='"
			. $text['refresh']
			. "' alt='"
			. $text['refresh']
			. "'/></a> ";
        $startReturn = "<table cellpadding='0' cellspacing='0' style='border:0;margin:0;padding:0;'><tr>";
        $startReturn .= "<td width='25' style='border:0;margin:0;padding:0;'>&nbsp;</td>";
        $startReturn .= "<td valign='middle' align='right' style='border:0;margin:0;padding-right:10px;'>"
			.$refreshReturn
			."</td>";
        $startReturn .= "<td style='border:0;margin:0;padding:0;'>";
        $startReturnLittleWidth = "<table cellpadding='0' cellspacing='0' style='border:0;margin:0;padding:0;'><tr>";
        $startReturnLittleWidth .= "<td width='25' valign='middle' align='right' style='border:0;margin:0;padding:0;'>"
			.$refreshReturn
			."</td>";
		$finretour = "</td></tr></table>";
		if($status == "ANALYSE_IN_QUEUE" ){
			return $startReturn . "<span style='font-size:11px'>" . $text['analysisinqueue'] . "</span>" . $finretour;
		}
		if($status == "ANALYSE_PROCESSING" ){
			if($pour == 100){
				return $startReturn
				. "<span style='font-size:11px'>"
				. $text['analysisinfinalization']
				. "</span>"
				. $finretour;
			} else {
				return $startReturnLittleWidth
                    . "<td align=\"right\" style=\"border:0;margin:0;padding-right:10px;\">"
				. $pour
                    . "%</td><td style=\"border:0;margin:0;padding:0;\"><div style=\"background"
				. ":transparent url("
				. $imagesPath
				. "mini-jauge_fond.png) no-repeat scroll 0;height:12px;padding:0 0 0 2px;width:55px;\"><div style=\""
				. "background:transparent url("
				. $imagesPath
				. "mini-jauge_gris.png) no-repeat scroll 0;height:12px;width:"
				. $pour/2
				. "px;\"></div></div>"
				. $finretour;
			}
		}
	}

	/**
	 * Method for display the PomprseuilmankBar (% de plagiat)
	 *
	 * @param $percentagePumping
	 * @param $weakThreshold
	 * @param $highThreshold
	 * @param $imagesPath
	 * @param $text : array  includes the extract from the text
	 * @return unknown_type
	 */
	function getPomprankBarv31($pourcentagePompage, $weakThreshold, $highThreshold, $chemin_images='',$texte='') {

		$pourcentagePompage = round($pourcentagePompage);
		$pour = round((50*$pourcentagePompage)/100);
		$return = "";
		$couleur = "";
		if($pourcentagePompage < $weakThreshold) {
			$couleur = "vert";
		} else if($pourcentagePompage >= $weakThreshold && $pourcentagePompage < $highThreshold) {
			$couleur = "orange";
		} else {
			$couleur = "rouge";
		}
		$return .= "<div style='float:left;margin-right:2px;'><img src='"
			. $chemin_images."mini-drapeau_$couleur.png' title='"
			. $texte['result']
			. "' alt='faible' width='15' height='15' /></div>";
		$return .= "<div style='float:left; margin-right:5px;width:45px;text-align:right;'>"
			. $pourcentagePompage
			. " %</div>";
		$return .= "<div style='float:left;background:transparent url("
			. $chemin_images
			. "mini-jauge_fond.png) no-repeat scroll 0;height:12px;margin-top:5px;padding:0 0 0 2px;width:55px;'>";
		$return .= "<div style='float:left;background:transparent url("
			. $chemin_images
			. "mini-jauge_"
			. $couleur
			. ".png) no-repeat scroll 0;height:12px;width:"
			. $pour
			. "px'></div></div>";

		return $return;
	}
	/*
     * Method for validation of hash
     * @param $hash
     * @return bool
     *
     */
	function isMd5($hash)
	{
		if(preg_match('`^[a-f0-9]{32}$`', $hash)) {
			return true;
		} else {
			return false;
		}
	}
	/*
     * Method for identify Internet media type
     * @param $filename
     */
	function typeMime($filename)
	{
		if (preg_match("@Opera(/| )([0-9].[0-9]{1,2})@", $_SERVER['HTTP_USER_AGENT'], $resultats)){
			$navigateur = "Opera";
		} elseif (preg_match("@MSIE ([0-9].[0-9]{1,2})@", $_SERVER['HTTP_USER_AGENT'], $resultats)){
			$navigateur = "Internet Explorer";
		} else {
			$navigateur = "Mozilla";
			$mime = parse_ini_file("mime.ini");
			$extension = substr($filename, strrpos($filename, ".")+1);
		}
		if(array_key_exists($extension, $mime)) {
			$type = $mime[$extension];
		} else {
			$type = ($navigateur!="Mozilla") ? 'application/octetstream' : 'application/octet-stream';
		}
		return $type;
	}
?>