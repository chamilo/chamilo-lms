<?php


function GetCodepage($llcc) {
	if (strlen($llcc) == 5) {
		$lang = substr(strtolower($llcc),0,2);
		$country = substr(strtoupper($llcc),3,2);
	}
	else { $lang = strtolower($llcc); $country = ''; }
	$unifonts = "";
	$dir = "ltr";
	$spacing = "";

	switch($lang){
	  CASE "en":
	  CASE "ca":
	  CASE "cy":
	  CASE "da":
	  CASE "de":
	  CASE "es":
	  CASE "eu":
	  CASE "fr":
	  CASE "ga":
	  CASE "fi": 
	  CASE "is":
	  CASE "it":
	  CASE "nl":
	  CASE "no":
	  CASE "pt":
	  CASE "sv":
		$cp = "win-1252";  break;

	  // ISO-8859-2
	  CASE "cs":
	  CASE "hr":
	  CASE "hu":
	  CASE "pl":
	  CASE "ro":
	  CASE "sk":
	  CASE "sl":
		$cp = "iso-8859-2";  break;

	  // ISO-8859-4
	  CASE "et":
	  CASE "kl":
	  CASE "lt":
	  CASE "lv":
		$cp = "iso-8859-4";  break;

	  // WIN-1251
	  CASE "bg":
	  CASE "mk":
	  CASE "ru":
	  CASE "sr":
	  CASE "uk":
		$cp = "win-1251";  break;

	  // ISO-8859-9 (Turkish)
	  CASE "tr": 
		$cp = "iso-8859-9";  break;

	  // ISO-8859-7 (Greek)
	  CASE "el":  
		$cp = "iso-8859-7";  break;

	  // UTF-8
	  CASE "id":
	  CASE "ms":
	  CASE "sh":
	  CASE "sq":
	  CASE "af":
	  CASE "be":
	  CASE "fo":
	  CASE "gl":
	  CASE "gv":
		$cp = "UTF-8";  break;

	  // RTL Languages
	  CASE "he":  $cp = "UTF-8"; $dir = "rtl";  $spacing = "W";  $unifonts = "dejavusans,dejavusansB,dejavusansI,dejavusansBI,dejavusanscondensed,dejavusanscondensedB,dejavusanscondensedI,dejavusanscondensedBI,freesans,freesansB,freesansI,freesansBI,freeserif,freeserifB,freeserifI,freeserifBI,freemono,freemonoB,freemonoI,freemonoBI";  break;

	  // Arabic
	  CASE "ar":  $cp = "UTF-8"; $dir = "rtl";  $spacing = "W";  $unifonts = "dejavusans,dejavusansB,dejavusansI,dejavusansBI,dejavusanscondensed,dejavusanscondensedB,dejavusanscondensedI,dejavusanscondensedBI,ar_1_001,ar_1_002,ar_1_003,ar_1_004,ar_1_005,ar_1_006,ar_1_007,ar_2_001,ar_2_002,ar_2_003,ar_2_004,ar_k_001,ar_k_002";  break;
	  CASE "fa":  $cp = "UTF-8"; $unifonts = "dejavusans,dejavusansB,dejavusansI,dejavusansBI,dejavusanscondensed,dejavusanscondensedB,dejavusanscondensedI,dejavusanscondensedBI,fa_1_001,fa_1_002";  $dir = "rtl";  $spacing = "W";  break;

	  CASE "ps":  $cp = "UTF-8"; $dir = "rtl"; $spacing = "W";  $unifonts = "ps_1_001"; break;
	  CASE "ur":  $cp = "UTF-8"; $dir = "rtl"; $spacing = "W";  $unifonts = "ur_1_001"; break;

	  // Sindhi (Arabic or Devanagari)
	  CASE "sd":
		if ($country == "IN") { $cp = "UTF-8"; $spacing = "W"; $unifonts = "ind_hi_1_001"; }
		else if ($country == "PK") { $cp = "UTF-8"; $dir = "rtl"; $spacing = "W"; $unifonts = "sd_1_001,sd_1_002"; }
		else { $cp = "UTF-8"; $dir = "rtl"; $spacing = "W"; $unifonts = "sd_1_001,sd_1_002"; }
		break;


	  // INDIC 
	  // Assamese
	  CASE "as":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_bn_1_001"; break;
	  // Bengali
	  CASE "bn":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_bn_1_001"; break;
	  // Gujarati
	  CASE "gu":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_gu_1_001"; break;
	  // Hindi (Devanagari)
	  CASE "hi":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_hi_1_001"; break;
	  // Kannada
	  CASE "kn":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_kn_1_001"; break;
	  // Kashmiri
	  CASE "ks":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_hi_1_001"; break;
	  // Malayalam
	  CASE "ml":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_ml_1_001"; break;
	  // Nepali (Devanagari)
	  CASE "ne":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_hi_1_001"; break;
	  // Oriya
	  CASE "or":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_or_1_001"; break;
	  // Punjabi (Gurmukhi)
	  CASE "pa":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_pa_1_001"; break;
	  // Tamil
	  CASE "ta":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_ta_1_001"; break;
	  // Telegu
	  CASE "te":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "ind_te_1_001"; break;

	  // Sinhalese
	  CASE "si":  $cp = "UTF-8"; $spacing = "W";  $unifonts = "freesans"; break;

	  // THAI
	  CASE "th":  $cp = "UTF-8"; $spacing = "C";  $unifonts = "garuda,garudaB,garudaI,garudaBI,norasi,norasiB,norasiI,norasiBI,freeserif";  break;

	  // VIETNAMESE
	  CASE "vi":  $cp = "UTF-8"; $unifonts = "dejavusans,dejavusansB,dejavusansI,dejavusansBI,dejavusanscondensed,dejavusanscondensedB,dejavusanscondensedI,dejavusanscondensedBI,freeserif"; break;

	  // CJK Langauges
	  CASE "ja":  $cp = "SHIFT_JIS"; $spacing = "C";  break;
	  CASE "ko":  $cp = "UHC"; $spacing = "C";  break;
	  CASE "zh":
		if ($country == "HK" || $country == "TW") { $cp = "BIG5"; $spacing = "C"; }
		else if ($country == "CN") { $cp = "GBK"; $spacing = "C"; }
	  	else { $cp = "GBK"; $spacing = "C"; }
		break;

/*
	  // CJK Langauges - ALTERNATIVE
	  CASE "ja": 
	  CASE "zh":  
		$cp = "UTF-8"; $spacing = "C";  $unifonts = "zn_hannom_a"; break;
	  CASE "ko":  $cp = "UTF-8"; $spacing = "C";  $unifonts = "unbatang_0613"; break;
*/


	  // Default UTF-8
	  default:  $cp = "UTF-8"; break;	// mPDF 4.2 Don't need to set unifonts - will make all available if left blank
							// Default spacing set to '' (i.e. mixed character/word)

	}


	$unifonts_arr = array();
	if ($unifonts) {
		$unifonts_arr = preg_split('/\s*,\s*/',$unifonts);
	}
	return array($cp,$unifonts_arr,$dir,$spacing);
}

?>