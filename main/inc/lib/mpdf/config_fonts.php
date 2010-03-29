<?php


// List of ALL available fonts (incl. styles) in non-Unicode directory
// Always put main font (without styles) before font+style; put preferred default first in order
// Do NOT include Arial Helvetica Times Courier Symbol or ZapfDingbats
$this->available_fonts = array(
		'dejavusanscondensed','dejavusanscondensedB','dejavusanscondensedI','dejavusanscondensedBI',
		'dejavuserifcondensed','dejavuserifcondensedB','dejavuserifcondensedI','dejavuserifcondensedBI',
		'dejavusans','dejavusansB','dejavusansI','dejavusansBI',
		'dejavuserif','dejavuserifB','dejavuserifI','dejavuserifBI',
		'dejavusansmono','dejavusansmonoB','dejavusansmonoI','dejavusansmonoBI',

		'freesans','freesansB','freesansI','freesansBI',
		'freeserif','freeserifB','freeserifI','freeserifBI',
		'freemono','freemonoB','freemonoI','freemonoBI',
		'ocrb',

/* Add any extra codepaged fonts here */

		);

// List of ALL available fonts (incl. styles) in Unicode directory
// Always put main font (without styles) before font+style; put preferred default first in order
// Do NOT include Arial Helvetica Times Courier Symbol or ZapfDingbats
$this->available_unifonts = array(
		'dejavusanscondensed','dejavusanscondensedB','dejavusanscondensedI','dejavusanscondensedBI',
		'dejavuserifcondensed','dejavuserifcondensedB','dejavuserifcondensedI','dejavuserifcondensedBI',
		'dejavusans','dejavusansB','dejavusansI','dejavusansBI',
		'dejavuserif','dejavuserifB','dejavuserifI','dejavuserifBI',
		'dejavusansmono','dejavusansmonoB','dejavusansmonoI','dejavusansmonoBI',

		'freesans','freesansB','freesansI','freesansBI',
		'freeserif','freeserifB','freeserifI','freeserifBI',
		'freemono','freemonoB','freemonoI','freemonoBI',
		'garuda','garudaB','garudaI','garudaBI',
		'norasi','norasiB','norasiI','norasiBI',
		'ocrb',

/* Indic scripts - Uncomment the lines below if you install the Indic font pack */
/*
		'ind_hi_1_001', 'ind_bn_1_001', 'ind_ml_1_001', 'ind_kn_1_001', 'ind_gu_1_001', 
		'ind_or_1_001', 'ind_ta_1_001', 'ind_te_1_001', 'ind_pa_1_001', 
*/

/* Arabic scripts - Uncomment the lines below if you install the Arabic/RTL font pack */
/*
		'ar_1_001', 'ar_1_002', 'ar_1_003', 'ar_1_004', 'ar_1_005', 'ar_1_006', 'ar_1_007', 
		'ar_2_001', 'ar_2_002', 'ar_2_003', 'ar_2_004', 
		'ar_k_001', 'ar_k_002', 'fa_1_001', 'fa_1_002', 
		'ur_1_001', 'sd_1_001', 'sd_1_002', 'ps_1_001',
*/

/* CJK scripts - Uncomment the lines below if you install the CJK ont pack */
/*
		'zn_hannom_a', 'unbatang_0613',
*/

		);




// List of all font families in directories (either) 
// + any others that may be read from a stylesheet - to determine 'type'
// should include sans-serif, serif and monospace, arial, helvetica, times and courier
// The order will determine preference when substituting fonts in certain circumstances
$this->sans_fonts = array('dejavusanscondensed','dejavusans','freesans','franklin','tahoma','garuda','calibri','trebuchet',
				'verdana','geneva','lucida','arial','helvetica','arialnarrow','arialblack','sans','sans-serif','cursive','fantasy',
);

$this->serif_fonts = array('dejavuserifcondensed','dejavuserif','freeserif','constantia','georgia','albertus','times',
				'norasi','serif','charis','palatino', 
);

$this->mono_fonts = array('dejavusansmono','freemono','courier', 'mono','monospace','ocrb','ocr-b','lucidaconsole');

?>