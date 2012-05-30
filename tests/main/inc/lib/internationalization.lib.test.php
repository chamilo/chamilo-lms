<?php
/* For licensing terms, see /license.txt */

/**
 * This is a test of internationalization.lib.php which is
 * a common purpose library for supporting internationalization
 * related functions. Only the public API is tested here.
 * @author Ricardo Rodriguez Salazar, 2009.
 * @author Ivan Tcholakov, 2009-2010.
 *
 * Notes:
 * 1. While saving this file, please, preserve its UTF-8 encoding.
 * Othewise this test would be broken.
 * 2. While running this test, send a header declaring UTF-8 encoding.
 * Then you would see variable dumps correctly.
 * 3. Tests about string comparison and sorting might give false results
 * if the intl extension has not been installed.
 */


class TestInternationalization extends UnitTestCase {

	private $language_strings = array( // All these strings are UTF-8 encoded.
		'afrikaans' => "Hy laat my in groen weivelde rus. Hy bring my by waters waar daar vrede is.",
		'albanian' => "Çdokush prej jush mund të kontribuojë vullnetarisht me dijen e tij për zgjerimin e mëtejshëm të kësaj enciklopedie të lirë.",
		'alemannic' => "Das bedütet, dass a dem Projekt alli chöi teilnä, wo en alemannischi Dialektspilart beherrsche, wo da gredt wird.",
		'amharic' => "ዳግማዊ ፡ ምኒልክ ፡ ንጉሠ ፡ ነገሥት ፡ ዘኢትዮጵያ ።",
		'arabic' => "ما اسمك؟",
		'armenian' => "Ընդհանուր տեղեկություններ, կառավարման համակարգ, ժողովրդագրություն և աշխարհագրական տվյալներ:",
		'asturian' => "La ortografía nun ye fonolóxica sinon histórica, tando considerada como una de les llingües más abegoses d'aprender de les qu'usen esi alfabetu.",
		'belarusian' => "Умоўным часам пачатку гісторыі сучаснай беларускай літаратурнай мовы лічыцца пачатак 19 стагоддзя.",
		'bosnian' => "Engleski jezik je nastao iz jezika germanskih plemena koja su se u kasnom starom vijeku naselila na jugoistoku otoka Velike Britanije.",
		'brazilian' => "O governo federal irá zerar o número de municípios sem bibliotecas este ano. De acordo com o Sistema Nacional de Bibliotecas Públicas, 661 municípios ainda não têm esses equipamentos. Se sua cidade não estiver nesta relação e não possuir biblioteca pública municipal, informe aqui. A cultura é um direito de todo o cidadão!",
		'breton' => "Hon Tad, c'hwi hag a zo en Neñv, ra vo santelaet hoc'h ano. Ra zeuio ho Rouantelezh.",
		'bulgarian' => "Глобалното затопляне ще освободи Северния ледовит океан от ледовете през лятото през следващите 20 години.",
		'catalan' => "Els drets juridicolingüístics dels catalanoparlants són ben diferents segons l'indret geogràfic, podem parlar de catalanoparlants de primera i de segona.",
		'croatian' => "Oče naš, koji jesi na nebesima, sveti se ime Tvoje. Dođi kraljevstvo Tvoje, budi volja Tvoja, kako na Nebu, tako i na Zemlji.",
		'czech' => "V převážné většině mezinárodních škol je vyučovacím jazykem angličtina.",
		'danish' => "Et ganske særligt kendetegn ved dansk er stød.",
		'dari' => "جمعیت افغانستان حدود ۳۰ میلیون نفر برآورد می‌شود. براساس سرشماری مقدماتی کمیته ملی احصائیه کشور، جمعیت افغانستان در سال ۱۳۸۵، ۲۴ میلیون",
		'dutch' => "De officiële taal, zoals die wordt onderwezen op scholen en gebruikt wordt door de autoriteiten, wordt Standaardnederlands genoemd.",
		'english' => "Approximately 375 million people speak English as their first language.",
		'esperanto' => "La vortprovizo de Esperanto devenas plejparte el la okcidenteŭropaj lingvoj, dum ĝia sintakso kaj morfologio montras ankaŭ slavlingvan influon.",
		'estonian' => "Ta eelistab lubjarikast pinnast, kuid kasvab ka settelistel muldadel ja väheviljakal pinnasel, kus teisi puittaimi ei leidu.",
		'basque' => "Euskaren gramatika zailtasun handikoa da, horrez gain, hizkuntza indoeuroparra ez izanik, eratze edo joskera bereziak ditu, beste hizkuntzetan aurkitu ez ditzakegunak.",
		'finnish' => "Nominit taipuvat sijoissa eli sijamuodoissa yleensä sekä yksikössä että monikossa.",
		'french' => "La majorité du fonds lexical français provient du latin (en tant que langue-mère) ou bien est construit à partir des racines gréco-latines.",
		'frisian' => "Us Heit, dy't yn de himelen is jins namme wurde hillige. Jins keninkryk komme.",
		'friulian' => "Mandi, jo mi clami Jacum! Vuê al è propite cjalt! O scugni propite lâ cumò, ariviodisi.",
		'galician' => "Se borrarán todos los comentarios que, con criterio subjetivo como en toda web, se consideren inadecuados.",
		'georgian' => "საკუთარ პერიოდში ბუგერო მსოფლიოს ერთ-ერთ უდიდეს მხატვრად იყო აღიარებული, თუმცა მე-20 საუკუნის დასაწყისში მას უკვე არაფრად აგდებდნენ, შესაძლოა მისი იმპრესიონისტთა მიმართ აგრესიულობის გამო. მიუხედავად ამისა, დღეს მას თაყვანისმცემლების ახალი დიდი ტალღა შეემატა - მისი ნამუშევრები მსოფლიოს ასზე მეტ უდიდეს მუზეუმშია გამოფენილი.",
		'german' => "Durch ihre zentrale Lage in Europa wurde die deutsche Sprache über die Jahrhunderte durch andere Sprachen beeinflusst.",
		'greek' => "Η ναυτική βιομηχανία αποτέλεσε ένα σημαντικό στοιχείο της Ελληνικής οικονομικής δραστηριότητας από τα αρχαία χρόνια.",
		'hawaiian' => "A ma mua o ka hō'ea 'ana i Tahiti, ua ho'okele maila lākou mai Sāmoa a Tonga paha.",
		'hebrew' => "שימו לב: אם עדיין לא נרשמתם, ייתכן כי חלק מהתכונות אינן זמינות לכם. במקרה זה רצוי ליצור חשבון חדש, פעולה האורכת מספר דקות בלבד. ראו איך ליצור חשבון חדש.",
		'hindi' => "साइट का लिंक लगाने के लिए निम्नांकित फार्मेट में उपयुक्त कक्ष में/या नया कक्ष बना कर लिखें :",
		'hungarian' => "A magyar nyelv az uráli nyelvcsalád tagja, a finnugor nyelvek közé tartozó ugor nyelvek egyike.",
		'icelandic' => "Margir Íslendingar telja íslenskuna vera „upprunalegra“ mál en flest önnur og að hún hafi breyst minna.",
		'indonesian' => "Selain itu, Baristand senantiasa meningkatkan kualitas personil dan berbagai fasilitas pendukung industri, serta fasilitas lainnya yaitu perpustakaan dengan buku-buku ilmiah, laporan hasil penelitian, dan majalah ilmiah.",
		'irish' => "háinig críoch dheifnideach lena chuid scríbhneoireachta nuair a cuireadh i dteach na ngealt é agus síocóis dhúlagrach ag luí ar a intinn.",
		'italian' => "Tuttavia l'assetto attuale della lingua è in sostanza quello del fiorentino trecentesco, ripulito dei tratti più marcatamente locali.",
		'japanese' => "日本語（にほんご、にっぽんご）は、主として、日本で使用されてきた言語である。日本国は法令上、公用語を明記していないが、事実上の公用語となっており、学校教育の「国語」で教えられる。",
		'korean' => "이 문서는 삭제되었습니다. 이 문서의 삭제/이동 기록은 다음과 같습니다.",
		'latin' => "Architecti est scientia pluribus disciplinis et variis eruditionibus ornata, cuius iudicio probantur omnia quae ab ceteris artibus perficiuntur opera.",
		'latvian' => "Tas visvairāk ir vērojams jaunu terminu darināšanā, kas bieži izsauc arī negatīvu reakciju.",
		'lithuanian' => "Dabartinės literatūrinės kalbos pagrindas remiasi vakarų aukštaičių pietiečių (suvalkiečių) tarme, išlaikiusia senesnes fonetikos ir morfologijos lytis.",
		'macedonian' => "Залагањето за создавање на македонски литературен јазик датира уште од почетокот на XIX век, со појавата на Просветителите.",
		'malay' => "Suntingan yang tidak sesuai akan dikeluarkan segera, dan pesalah yang berulang boleh disekat daripada menyunting. Harap maklum.",
		'manx' => "Haink ram cooney da aavioghey ny Gaelgey liorish yn obbyr recortyssagh jeant liorish aahirreyderyn 'sy 20oo eash.",
		'marathi' => "समस्त विकिपीडिया वाचक आणि संपादकांना दीपावलीच्या हार्दीक शुभेच्छा !",
		'middle_frisian' => "30 beest van en wief dat er gen schrift van is dy plæge hem Kom krod my ney de Verman ta.",
		'mingo' => "Kakwékö nêkê ne'hu niyawë'ö, ne n-u'kaiwayeí ne' thusnye'ö N-awëníyu', ne' húkwa huwënitkëhtahkö haya'tatek, n- utukëstaniak, háwê,",
		'nepali' => "तपाईंको/तिम्रो नाम के हो?",
		'norwegian' => "Språksamfunn lånar ord frå meir prestisjefylte språksamfunn, via tospråklege talarar. Studium av lånordslag i norsk speglar dermed samfunnsmessige tilhøve i Europa opp gjennom hundreåra.",
		'occitan' => "Ara s'estima que sus una populacion de 14 o 15 milions d'occitans, son entre 500 000 e 2 000 000 los que son capables de parlar l'occitan correntament, mas las ocasions de lo parlar dins la societat son raras.",
		'pashto' => "د راجيت سيتارام پنډت په قول ميلنده پڼهو اصلي نسخه په زړه پښتو ليکل شويده - موړ کتاب چې د",
		'persian' => "زبان فارسی (پارسی، دری، یا تاجیکی) زبانی است که در کشورهای ایران، افغانستان[۲]، تاجیکستان[۳] و",
		'polish' => "Język polski wywodzi się z języka praindoeuropejskiego za pośrednictwem języka prasłowiańskiego.",
		'portuguese' => "Assim como os outros idiomas, o português sofreu uma evolução histórica, sendo influenciado por vários idiomas e dialetos, até chegar ao estágio conhecido atualmente. Deve-se considerar, porém, que o português de hoje compreende vários dialetos e subdialetos, falares e subfalares, muitas vezes bastante distintos, além de dois padrões reconhecidos internacionalmente (português brasileiro e português europeu).",
		'quechua_cusco' => "Simi yachaqkunaqa rimanakun, qhichwa simi hukllachu achkachu rimay. SIL International nisqa tantanakuy ninmi, 42 rimaymi, nispa.",
		'romanian' => "Limba română este vorbită în toată lumea de aproximativ 26 de milioane de persoane.",
		'rumantsch' => "Mintga idiom ha sviluppà sia atgna lingua da scrittira ch'è dentant savens era puspè in cumpromiss tranter ils differents dialects regiunals e locals.",
		'russian' => "Русский язык — один из восточнославянских языков, один из крупнейших языков мира, в том числе самый распространённый из славянских языков и самый распространённый язык Европы, как географически, так и по числу носителей языка как родного (хотя также значительная и географически большая часть русского языкового ареала находится в Азии).",
		'sanskrit' => "एयं भाषा न केवलं भारतस्‍य अपितु विश्‍वस्‍यप्राचीनतमा भाषा मन्‍यते। इयं भाषा एतावती समृद्घा अस्‍ति यत्‌ प्राय: सर्वासु भारतीयभाषासु न्‍यूनाधिकरूपेण अस्‍या: शब्‍दा: प्रयुज्‍यन्‍ते. अत: भाषाविदां मते इयं सर्वासां भाषाणां जननी मन्‍यते। पुरा संस्कृतं लोकभाषा आसीत्‌। जना: संस्कृतं वदन्ति स्म॥ विश्‍वस्‍य आदिम: ग्रन्‍थ: ऋग्‍वेद: संस्‍कृतभाषायामेवास्‍ति। अन्‍ये च वेदा: यथा यजुर्वेद:, सामवेद:, अथर्ववेदश्‍च संस्‍कृतभाषायामेव सन्‍ति। आयुर्वेद धनुर्वेद गन्‍धर्ववेदार्थवेदाख्‍या: चत्‍वार: उपवेदा: अपि संस्‍कृते एव विरचिता:॥ सर्वा: उपनिषद: संस्‍कृते उपनिबद्घाः। अन्‍ये ग्रन्‍था: - शिक्षा, कल्‍प, निरुक्त, ज्‍योतिष, छन्‍द, व्‍याकरण, वेदाङ्ग, दर्शन, इतिहास, पुराण, काव्‍य, शास्‍त्र: चेत्यादयः ॥ महर्षि-पाणिनिना विरचिता अष्‍टाध्‍यायी इति संस्‍कृतव्‍याकरणम्‌ अधुनापि भारते विदेशेषु च भाषाविज्ञानिनां प्रेरणास्‍थानं वर्तते ॥ वाक्यकारं वररुचिं भाष्यकारं पतंजलिम् | पाणिनिं सूत्रकारं च प्रणतोस्मि मुनित्रयम् ॥",
		'scots' => "Anglian speakers wis weel staiblisht in sooth-east Scotland by the 7t century. In the 13t century Norman landawners an thair reteeners, speakin Northumbrian Middle Inglis, wis inveetit tae come an sattle by the Keeng.",
		'scots_gaelic' => "Ciamar a tha thu? Dè an t-ainm a tha ort? Dè a tha seo?",
		'serbian' => "Gajica je objavljena je 1830. godine u Zagrebu u „kratkoj osnovi horvatsko-slavonskoga pravopisa“. Razvio ju je Ljudevit Gaj.",
		'serbian_cyrillic' => "Као и када су други језици у питању, неопходно је разграничити појам језичких система којим се Срби како етницитет служе од стандардног језика који се употребљава у државним и културним институцијама.",
		'simpl_chinese' => "现代标准汉语，是普通话、国语、华语的统称，指通行于中国大陆和香港、澳门、台湾、海外华人的共通语文，为现代汉语共通的交际口语与书面语，是联合国官方语言之一，是国际人士学习汉语言的主要参照。",
		'slovak' => "Ak ste nedávno napísali tento článok, skúste vyčistiť jeho vyrovnávaciu pamäť alebo chvíľu počkať predtým, než ho znova vytvoríte.",
		'slovenian' => "Slovénščina je južnoslovanski jezik z okoli 2,2 milijonoma govorcev po svetu, od katerih jih večina živi v Sloveniji. Je eden redkih indoevropskih jezikov, ki je še ohranil dvojino.",
		'spanish' => "El castellano es lengua oficial de España. También se habla en Gibraltar[70] y en Andorra (donde es la lengua materna mayoritaria debido a la inmigración, pero no es la lengua propia y oficial como sí lo es el catalán[71] ).",
		'swahili' => "Lugha hii ina utajiri mkubwa wa misamiati na misemo na mithali na mashairi na mafumbo na vitendawili na nyimbo.",
		'swedish' => "Hej. Hur är det? Bara bra, tack. Förlåt, jag har glömt, varifrån kommer du nu igen?",
		'tagalog' => "Ang Wikibooks ay isang ambagang proyekto sa pagkagawa ng isang koleksyon ng mga libre at malayang-kontentong pang-araling aklat na pwede mong baguhin.",
		'tamil' => "நீங்கள் ஆங்கிலம் பேசுவீர்களா?",
		'thai' => "ภาษาไทย เป็นภาษาราชการของประเทศไทย และภาษาแม่ของชาวไทย และชนเชื้อสายอื่นในประเทศไทย ภาษาไทยเป็นภาษาในกลุ่มภาษาไต ซึ่งเป็นกลุ่มย่อยของตระกูลภาษาไท-กะได สันนิษฐานว่า",
		'trad_chinese' => "中華民國，在亞東之極，本都南京，因事失地泰半，暫遷於臺北。其東以鴨綠江界朝鮮國，隔東海望日本。其北與俄羅斯相接。其西有大山，天下至高者也。而皆失於內戰，今僅得臺灣及其周圍矣。",
		'turkish' => "Bu kitapta Türkçe konuşmak, yazmak ve okumak için gereken her şeyi bulacaksınız.",
		'ukrainian' => "Українська мова є мовою найбільшого корінного етносу України і невід'ємною базовою ознакою його ідентичності протягом багатьох століть.",
		'vietnamese' => "Tiếng Việt là ngôn ngữ chính thức tại Việt Nam, và cũng là ngôn ngữ phổ thông đối với các dân tộc thiểu số tại Việt Nam.",
		'welsh' => "Gan nad oedd y Frythoneg yn iaith ysgrifenedig tystiolaeth anuniongyrchol yn unig sydd i'r newidiadau a ddigwyddodd iddi.",
		'yiddish' => "יידיש אדער אידיש (Yiddish) גערופֿן ביי אידן אלס מאַמע לשון, איז אַ שפּראַך װאָס װערט הײַנט גערעדט ביי 1.5 מיליאָן יידן[1] און באַקאַנט ביי 3,142,560 מיליאָן [2] מענטשן איבער דער װעלט, בעיקר פֿונעם אַשכנזישן אָפּשטאַם.",
		'yoruba' => "Èdè Yorùbá Ní báyìí, tí a bá wo èdè Yorùbá, àwon onímò pín èdè náà sábée èyà wa nínú e bí èdè Niger-Congo. Wón tún fìdí rè múlè pé èyà wa yìí ló wópò jùlo ní síso, ní ìwò oòrùn aláwò dúdú fún egbe-egbèrún odún."
	);

	function TestInternationalization() {
        $this->UnitTestCase('Internationalization library - main/inc/lib/internationalization.lib.test.php');
	}


/**
 * ----------------------------------------------------------------------------
 * A safe way to calculate binary lenght of a string (as number of bytes)
 * ----------------------------------------------------------------------------
 */

	public function test_api_byte_count() {
		$string = 'xxxáéíóú?'; // UTF-8
		$res = api_byte_count($string);
		$this->assertTrue($res == 14);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Multibyte string conversion functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_convert_encoding() {
		$string = 'xxxáéíóú?€'; // UTF-8
		$from_encoding = 'UTF-8';
		$to_encoding = 'ISO-8859-15';
		$res = api_convert_encoding($string, $to_encoding, $from_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $from_encoding, $to_encoding) == $string);
		//var_dump($res);
		//var_dump(api_convert_encoding($res, $from_encoding, $to_encoding));
	}

	public function test_api_utf8_encode() {
		$string = 'xxxáéíóú?€'; // UTF-8
		$from_encoding = 'ISO-8859-15';
		$string1 = api_utf8_decode($string, $from_encoding);
		$res = api_utf8_encode($string1, $from_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == $string);
		//var_dump($res);
	}

	public function test_api_utf8_decode() {
		$string = 'xxxx1ws?!áéíóú@€'; // UTF-8
		$to_encoding = 'ISO-8859-15';
		$res = api_utf8_decode($string, $to_encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_utf8_encode($res, $to_encoding) == $string);
		//var_dump($res);
	}

	public function test_api_to_system_encoding() {
		$string = api_utf8_encode(get_lang('Title'), api_get_system_encoding());
		$from_encoding = 'UTF-8';
		$check_utf8_validity = false;
		$res = api_to_system_encoding($string, $from_encoding, $check_utf8_validity);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $from_encoding, api_get_system_encoding()) == $string);
		//var_dump(api_utf8_encode($res, api_get_system_encoding()));
	}

	public function test_api_htmlentities() {
		$string = 'áéíóú@!?/\-_`*ç´`'; // UTF-8
		$quote_style = ENT_QUOTES;
		$encoding = 'UTF-8';
		$res = api_htmlentities($string, $quote_style, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue(api_convert_encoding($res, $encoding, 'HTML-ENTITIES') == $string);
		//var_dump($res);
	}

	public function test_api_html_entity_decode() {
		$string = 'áéíóú@/\!?Ç´`+*?-_ '; // UTF-8
		$quote_style = ENT_QUOTES;
		$encoding = 'UTF-8';
		$res = api_html_entity_decode(api_convert_encoding($string, 'HTML-ENTITIES', $encoding), $quote_style, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == $string);
		//var_dump($res);
	}

	public function test_api_xml_http_response_encode() {
		$string='áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$from_encoding = 'UTF-8';
		$res = api_xml_http_response_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_file_system_encode() {
		$string = 'áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$from_encoding = 'UTF-8';
		$res = api_file_system_encode($string, $from_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_file_system_decode() {
		$string='áéíóú@/\!?Ç´`+*?-_'; // UTF-8
		$to_encoding = 'UTF-8';
		$res = api_file_system_decode($string, $to_encoding);
		$this->assertTrue(is_string($res));
		//var_dump($res);
	}

	public function test_api_transliterate() {
		$string = 'Фёдор Михайлович Достоевкий'; // UTF-8
		/*
		// If you have broken by mistake UTF-8 encoding of this source, try the following equivalent:
		$string = api_html_entity_decode(
			'&#1060;&#1105;&#1076;&#1086;&#1088; '.
			'&#1052;&#1080;&#1093;&#1072;&#1081;&#1083;&#1086;&#1074;&#1080;&#1095; '.
			'&#1044;&#1086;&#1089;&#1090;&#1086;&#1077;&#1074;&#1082;&#1080;&#1081;',
			ENT_QUOTES, 'UTF-8');
		*/
		$unknown = 'X';
		$from_encoding = 'UTF-8';
		$res = api_transliterate($string, $unknown, $from_encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Fyodor Mihaylovich Dostoevkiy');
		//var_dump($string);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Common multibyte string functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_ord() {
		$encoding = 'UTF-8';
		$characters = array('И', 'в', 'а', 'н', ' ', 'I', 'v', 'a', 'n'); // UTF-8
		$codepoints = array(1048, 1074, 1072, 1085, 32, 73, 118, 97, 110);
		$res = array();
		foreach ($characters as $character) {
			$res[] = api_ord($character, $encoding);
		}
		$this->assertTrue($res == $codepoints);
		//var_dump($res);
	}

	public function test_api_chr() {
		$encoding = 'UTF-8';
		$codepoints = array(1048, 1074, 1072, 1085, 32, 73, 118, 97, 110);
		$characters = array('И', 'в', 'а', 'н', ' ', 'I', 'v', 'a', 'n'); // UTF-8
		$res = array();
		foreach ($codepoints as $codepoint) {
			$res[] = api_chr($codepoint, $encoding);
		}
		$this->assertTrue($res == $characters);
		//var_dump($res);
	}

	public function test_api_str_ireplace() {
		$search = 'Á'; // UTF-8
		$replace = 'a';
		$subject = 'bájando'; // UTF-8
		$count = null;
		$encoding = 'UTF-8';
		$res = api_str_ireplace($search, $replace, $subject, & $count, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'bajando');
		//var_dump($res);
	}

	public function test_api_str_split() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$split_length = 1;
		$encoding = 'UTF-8';
		$res = api_str_split($string, $split_length, $encoding);
		$this->assertTrue(is_array($res));
		$this->assertTrue(count($res) == 15);
		//var_dump($res);
	}

	public function test_api_stripos() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'Á';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_stripos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res)|| is_bool($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_stristr() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'Á';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_stristr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res) || is_string($res));
		$this->assertTrue($res == 'ájando');
		//var_dump($res);
	}

	public function test_api_strlen() {
		$string='áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strlen($string, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strpos() {
		$haystack = 'bájando'; // UTF-8
		$needle = 'á';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res)|| is_bool($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_strrchr() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'ó';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_strrchr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_string($res)|| is_bool($res));
		$this->assertTrue($res == 'ón');
		//var_dump($res);
	}

	public function test_api_strrev() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strrev($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == ']ç[]Ç?/\|ºúóíéá');
		//var_dump($res);
	}

	public function test_api_strripos() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'Ó';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strripos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res) || is_bool($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strrpos() {
		$haystack = 'aviación aviación'; // UTF-8
		$needle = 'ó';
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_strrpos($haystack, $needle, $offset, $encoding);
		$this->assertTrue(is_numeric($res) || is_bool($res));
		$this->assertTrue($res == 15);
		//var_dump($res);
	}

	public function test_api_strstr() {
		$haystack = 'aviación'; // UTF-8
		$needle = 'ó';
		$part = false;
		$encoding = 'UTF-8';
		$res = api_strstr($haystack, $needle, $part, $encoding);
		$this->assertTrue(is_bool($res)|| is_string($res));
		$this->assertTrue($res == 'ón');
		//var_dump($res);
	}

	public function test_api_strtolower() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strtolower($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'áéíóúº|\/?ç][ç]');
		//var_dump($res);
	}

	public function test_api_strtoupper() {
		$string='áéíóúº|\/?Ç][ç]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_strtoupper($string, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res =='ÁÉÍÓÚº|\/?Ç][Ç]');
		//var_dump($res);
	}

	public function test_api_substr() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$start = 10;
		$length = 4;
		$encoding = 'UTF-8';
		$res = api_substr($string, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Ç][ç');
		//var_dump($res);
	}

	public function test_api_substr_replace() {
		$string = 'áéíóúº|\/?Ç][ç]'; // UTF-8
		$replacement = 'eiou';
		$start= 1;
		$length = 4;
		$encoding = 'UTF-8';
		$res = api_substr_replace($string, $replacement, $start, $length, $encoding);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'áeiouº|\/?Ç][ç]');
		//var_dump($res);
	}

	public function test_api_ucfirst() {
		$string = 'áéíóúº|\/? xx ][ xx ]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_ucfirst($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Áéíóúº|\/? xx ][ xx ]');
		//var_dump($res);
	}

	public function test_api_ucwords() {
		$string = 'áéíóúº|\/? xx ][ xx ]'; // UTF-8
		$encoding = 'UTF-8';
		$res = api_ucwords($string, $encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'Áéíóúº|\/? Xx ][ Xx ]');
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * String operations using regular expressions
 * ----------------------------------------------------------------------------
 */

	public function test_api_preg_match() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) --'; // UTF-8
		$matches = null;
		$flags = 0;
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_preg_match($pattern, $subject, $matches, $flags, $offset, $encoding);
		$this->assertTrue($res == 1);
		//var_dump($res);
		//var_dump($matches);
	}

	public function test_api_preg_match_all() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$matches = null;
		$flags = PREG_PATTERN_ORDER;
		$offset = 0;
		$encoding = 'UTF-8';
		$res = api_preg_match_all($pattern, $subject, $matches, $flags, $offset, $encoding);
		$this->assertTrue($res == 2);
		//var_dump($res);
		//var_dump($matches);
	}

	public function test_api_preg_replace() {
		$pattern = '/иван/i'; // UTF-8
		$replacement = 'ИВАН'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_replace($pattern, $replacement, $subject, $limit, $count, $encoding);
		$this->assertTrue($res == '-- Ivan (en) -- ИВАН (bg) -- ИВАН --'); // UTF-8
		//var_dump($res);
	}

	public function test_api_preg_replace_callback() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_replace_callback($pattern, create_function('$matches', 'return api_ucfirst($matches[0], \'UTF-8\');'), $subject, $limit, $count, $encoding);
		$this->assertTrue($res == '-- Ivan (en) -- Иван (bg) -- Иван --'); // UTF-8
		//var_dump($res);
	}

	public function test_api_preg_split() {
		$pattern = '/иван/i'; // UTF-8
		$subject = '-- Ivan (en) -- Иван (bg) -- иван --'; // UTF-8
		$limit = -1;
		$count = null;
		$encoding = 'UTF-8';
		$res = api_preg_split($pattern, $subject, $limit, $count, $encoding);
		$this->assertTrue($res[0] == '-- Ivan (en) -- '); // UTF-8
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Obsolete string operations using regular expressions, to be deprecated
 * ----------------------------------------------------------------------------
 */

	public function test_api_ereg() {
		$pattern = 'scorm/showinframes.php([^"\'&]*)(&|&amp;)file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$regs = array();
		$res = api_ereg($pattern, $string, $regs);
		$this->assertTrue(is_numeric($res));
		//var_dump($regs);
		//var_dump($res);
	}

	public function test_api_ereg_replace() {
		$pattern = 'file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$replacement = 'file=my_test.php';
		$option = null;
		$res = api_ereg_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		$this->assertTrue(strlen($res) == 77);
		//var_dump($res);
	}

	public function test_api_eregi() {
		$pattern = 'scorm/showinframes.php([^"\'&]*)(&|&amp;)file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$regs = array();
		$res = api_eregi($pattern, $string, $regs);
		$this->assertTrue(is_numeric($res));
		//var_dump($regs);
		//var_dump($res);
	}

	public function test_api_eregi_replace() {
		$pattern = 'file=([^"\'&]*)$';
		$string = 'http://localhost/dokeos/main/scorm/showinframes.php?id=5&amp;file=test.php';
		$replacement = 'file=my_test.php';
		$option = null;
		$res = api_eregi_replace($pattern, $replacement, $string, $option);
		$this->assertTrue(is_string($res));
		$this->assertTrue(strlen($res) == 77);
		//var_dump($res);
	}

	public function test_api_split() {
		$pattern = '[/.-]';
		$string = '08/22/2009';
		$limit = null;
		$res = api_split($pattern, $string, $limit);
		$this->assertTrue(is_array($res));
		$this->assertTrue(count($res) == 3);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * String comparison
 * ----------------------------------------------------------------------------
 */

	public function test_api_strcasecmp() {
		$string1 = 'áéíóu'; // UTF-8
		$string2 = 'Áéíóu'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 0);
		//var_dump($res);
	}

	public function test_api_strcmp() {
		$string1 = 'áéíóu'; // UTF-8
		$string2 = 'Áéíóu'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function test_api_strnatcasecmp() {
		$string1 = '201áéíóu.txt'; // UTF-8
		$string2 = '30Áéíóu.TXT'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strnatcasecmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}

	public function  test_api_strnatcmp() {
		$string1 = '201áéíóu.txt'; // UTF-8
		$string2 = '30áéíóu.TXT'; // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_strnatcmp($string1, $string2, $language, $encoding);
		$this->assertTrue(is_numeric($res));
		$this->assertTrue($res == 1);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Sorting arrays
 * ----------------------------------------------------------------------------
 */

	public function test_api_asort() {
		$array = array('úéo', 'aíó', 'áed'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_asort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'aíó' || $array[$keys[0]] == 'áed'); // The second result is given when intl php-extension is active.
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_arsort() {
		$array = array('aíó', 'úéo', 'áed'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_arsort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'úéo');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natsort() {
		$array = array('img12.png', 'img10.png', 'img2.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img1.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natrsort() {
		$array = array('img2.png', 'img10.png', 'img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natrsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img12.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natcasesort() {
		$array = array('img2.png', 'img10.png', 'Img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natcasesort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img1.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_natcasersort() {
		$array = array('img2.png', 'img10.png', 'Img12.png', 'img1.png'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_natcasersort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'Img12.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_ksort() {
		$array = array('aíó' => 'img2.png', 'úéo' => 'img10.png', 'áed' => 'img12.png', 'áedc' => 'img1.png'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_ksort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img2.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_krsort() {
		$array = array('aíó' => 'img2.png', 'úéo' => 'img10.png', 'áed' => 'img12.png', 'áedc' => 'img1.png'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_krsort($array, $sort_flag, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'img10.png');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatsort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'img12.png' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áedc');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatrsort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatrsort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'úéo' || $array[$keys[0]] == 'áed'); // The second result is given when intl php-extension is active.
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatcasesort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'img1.png' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatcasesort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áedc');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_knatcasersort() {
		$array = array('img2.png' => 'aíó', 'img10.png' => 'úéo', 'IMG12.PNG' => 'áed', 'IMG1.PNG' => 'áedc'); // UTF-8
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_knatcasersort($array, $language, $encoding);
		$keys = array_keys($array);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[$keys[0]] == 'áed');
		//var_dump($array);
		//var_dump($res);
	}

	public function test_api_sort() {
		$array = array('úéo', 'aíó', 'áed', 'áedc'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_sort($array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[0] == 'aíó' || $array[0] == 'áed');  // The second result is given when intl php-extension is active.
		//var_dump($array);
		//var_dump($res);
	}

	public function testapi_rsort() {
		$array = array('aíó', 'úéo', 'áed', 'áedc'); // UTF-8
		$sort_flag = SORT_REGULAR;
		$language = 'english';
		$encoding = 'UTF-8';
		$res = api_rsort($array, $sort_flag, $language, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($array[0] == 'úéo');
		//var_dump($array);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Common sting operations with arrays
 * ----------------------------------------------------------------------------
 */

	public function test_api_in_array_nocase() {
		$needle = 'áéíó'; // UTF-8
		$haystack = array('Áéíó', 'uáé', 'íóú'); // UTF-8
		$strict = false;
		$encoding = 'UTF-8';
		$res = api_in_array_nocase($needle, $haystack, $strict, $encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res === true);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Encoding management functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_refine_encoding_id() {
		$encoding = 'koI8-r';
		$res = api_refine_encoding_id($encoding);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'KOI8-R');
		//var_dump($res);
	}

	public function test_api_equal_encodings() {
		$encoding1 = 'cp65001';
		$encoding2 = 'utf-8';
		$encoding3 = 'WINDOWS-1251';
		$encoding4 = 'WINDOWS-1252';
		$encoding5 = 'win-1250';
		$encoding6 = 'windows-1250';
		$res1 = api_equal_encodings($encoding1, $encoding2);
		$res2 = api_equal_encodings($encoding3, $encoding4);
		$res3 = api_equal_encodings($encoding5, $encoding6);
		$res4 = api_equal_encodings($encoding5, $encoding6, true);
		$this->assertTrue(is_bool($res1));
		$this->assertTrue(is_bool($res2));
		$this->assertTrue(is_bool($res3));
		$this->assertTrue(is_bool($res4));
		$this->assertTrue($res1);
		$this->assertTrue(!$res2);
		$this->assertTrue($res3);
		$this->assertTrue(!$res4);
		//var_dump($res1);
		//var_dump($res2);
		//var_dump($res3);
		//var_dump($res4);
	}

	public function test_api_is_utf8() {
		$encoding = 'cp65001'; // This an alias of UTF-8.
		$res = api_is_utf8($encoding);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_latin1() {
		$encoding = 'ISO-8859-15';
		$strict = false;
		$res = api_is_latin1($encoding, false);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_get_system_encoding() {
		$res = api_get_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_get_file_system_encoding() {
		$res = api_get_file_system_encoding();
		$this->assertTrue(is_string($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_encoding_supported() {
		$encoding1 = 'UTF-8';
		$encoding2 = 'XXXX#%#%VR^%BBDNdjlrsg;d';
		$res1 = api_is_encoding_supported($encoding1);
		$res2 = api_is_encoding_supported($encoding2);
		$this->assertTrue(is_bool($res1) && is_bool($res2));
		$this->assertTrue($res1 && !$res2);
		//var_dump($res1);
		//var_dump($res2);
	}

	public function test_api_get_non_utf8_encoding() {
		$language = 'bulgarian';
		$res = api_get_non_utf8_encoding($language);
		$this->assertTrue($res);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'WINDOWS-1251');
		//var_dump($res);
	}

	public function test_api_get_valid_encodings() {
		$res = api_get_valid_encodings();
		$ok = is_array($res) && !empty($res);
		$this->assertTrue($ok);
		if ($ok) {
			foreach ($res as $value) {
				$ok = $ok && is_string($value);
			}
			$this->assertTrue($ok);
		}
		//var_dump($res);
	}

	public function test_api_detect_encoding_html() {
		$meta = '<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />'."\n";
		$head1 = '<head>'."\n".'<title>Sample Document</title>'."\n".'</head>'."\n";
		$head2 = '<head>'."\n".'<title>Sample Document</title>'."\n".$meta.'</head>'."\n";
		$body1 = '<p>This is a sample document for testing encoding detection.</p>'."\n";
		$body2 = '<body>'."\n".$body1.'</body>';
		$html1 = $body1; // A html-snippet, see for example some log-files created by the "Chat" tool.
		$html2 = '<html>'."\n".$head1.$body2."\n".'</html>'; // A full html-document, no encoding has been declared.
		$html3 = '<html>'."\n".$head2.$body2."\n".'</html>'; // A full html-document, encoding has been declared.
		$res1 = api_detect_encoding_html($html1);
		$res2 = api_detect_encoding_html($html2);
		$res3 = api_detect_encoding_html($html3);
		$this->assertTrue(
			$res1 === 'UTF-8'
			&& $res2 === 'UTF-8'
			&& $res3 === 'ISO-8859-15'
		);
		//var_dump($res1);
		//var_dump($res2);
		//var_dump($res3);
	}

	public function test_api_detect_encoding_xml() {
		$xml1 = '
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>xxx</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // US-ASCII
		$xml2 = '<?xml version="1.0" encoding="ISO-8859-15"?>'.$xml1;
		$xml3 = '<?xml version="1.0" encoding="utf-8"?>'.$xml1;
		$xml4 = str_replace('<Lastname>xxx</Lastname>', '<Lastname>x'.chr(192).'x</Lastname>', $xml1); // A non-UTF-8 character has been inserted.
		$res1 = api_detect_encoding_xml($xml1);
		$res2 = api_detect_encoding_xml($xml2);
		$res3 = api_detect_encoding_xml($xml3);
		$res4 = api_detect_encoding_xml($xml4);
		$res5 = api_detect_encoding_xml($xml4, 'windows-1251');
		$this->assertTrue(
			$res1 === 'UTF-8'
			&& $res2 === 'ISO-8859-15'
			&& $res3 === 'UTF-8'
			&& api_equal_encodings($res4, api_get_system_encoding())
			&& $res5 === 'WINDOWS-1251'
		);
		//var_dump($res1);
		//var_dump($res2);
		//var_dump($res3);
		//var_dump($res4);
		//var_dump($res5);
	}

	public function test_api_convert_encoding_xml() {
		$xml = '
			<?xml version="1.0" encoding="UTF-8"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>Иван</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // UTF-8
		$res1 = api_convert_encoding_xml($xml, 'WINDOWS-1251', 'UTF-8');
		$res2 = api_convert_encoding_xml($xml, 'WINDOWS-1251');
		$res3 = api_convert_encoding_xml($res1, 'UTF-8', 'WINDOWS-1251');
		$res4 = api_convert_encoding_xml($res2, 'UTF-8');
		$this->assertTrue(
			$res3 === $xml
			&& $res4 === $xml
		);
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res1)));
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res2)));
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res3)));
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res4)));
	}

	public function test_api_utf8_encode_xml() {
		$xml1 = '
			<?xml version="1.0" encoding="UTF-8"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>Иван</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // UTF-8
		$xml2 = '
			<?xml version="1.0" encoding="WINDOWS-1251"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>'.chr(200).chr(226).chr(224).chr(237).'</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // WINDOWS-1251
		$res1 = api_utf8_encode_xml($xml2);
		$this->assertTrue($res1 === $xml1);
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res1)));
	}

	public function test_api_utf8_decode_xml() {
		$xml1 = '
			<?xml version="1.0" encoding="UTF-8"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>Иван</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // UTF-8
		$xml2 = '
			<?xml version="1.0" encoding="WINDOWS-1251"?>
			<Users>
				<User>
					<Username>username1</Username>
					<Lastname>xxx</Lastname>
					<Firstname>'.chr(200).chr(226).chr(224).chr(237).'</Firstname>
					<Password>xxx</Password>
					<Email>xxx@xx.xx</Email>
					<OfficialCode>xxx</OfficialCode>
					<Phone>xxx</Phone>
					<Status>student</Status>
				</User>
			</Users>'; // WINDOWS-1251
		$res1 = api_utf8_decode_xml($xml1, 'WINDOWS-1251');
		$this->assertTrue($res1 === $xml2);
		//var_dump(preg_replace(array('/\r?\n/m', '/\t/m'), array('<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), htmlspecialchars($res1)));
	}

/**
 * ----------------------------------------------------------------------------
 * String validation functions concerning certain encodings
 * ----------------------------------------------------------------------------
 */

	public function test_api_is_valid_utf8() {
		$string = 'áéíóú1@\/-ḉ`´';
		$res = api_is_valid_utf8($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	public function test_api_is_valid_ascii() {
		$string = 'áéíóú'; // UTF-8
		$res = api_is_valid_ascii($string);
		$this->assertTrue(is_bool($res));
		$this->assertTrue(!$res);
		//var_dump($res);
	}


/**
 * ----------------------------------------------------------------------------
 * Language management functions
 * ----------------------------------------------------------------------------
 */

	public function test_api_is_language_supported() {
		$language1 = 'english';
		$language2 = 'english_org';
		$language3 = 'EnGlIsh';
		$language4 = 'EnGlIsh_oRg';
		$language5 = 'french';
		$language6 = 'french_corporate';
		$language7 = 'frEncH';
		$language8 = 'freNch_corPorAte';
		$language9 = 'xxxxxxxxxxxxxx';
		$res1 = api_is_language_supported($language1);
		$res2 = api_is_language_supported($language2);
		$res3 = api_is_language_supported($language3);
		$res4 = api_is_language_supported($language4);
		$res5 = api_is_language_supported($language5);
		$res6 = api_is_language_supported($language6);
		$res7 = api_is_language_supported($language7);
		$res8 = api_is_language_supported($language8);
		$res9 = api_is_language_supported($language9);
		$this->assertTrue(
			$res1 === true
			&& $res2 === true
			&& $res3 === true
			&& $res4 === true
			&& $res5 === true
			&& $res6 === true
			&& $res7 === true
			&& $res8 === true
			&& $res9 === false
		);
		//var_dump($res1);
		//var_dump($res2);
		//var_dump($res3);
		//var_dump($res4);
		//var_dump($res5);
		//var_dump($res6);
		//var_dump($res7);
		//var_dump($res8);
		//var_dump($res9);
	}

	public function test_api_get_valid_language() {
		$enabled_languages_info = api_get_languages();
		$enabled_languages = $enabled_languages_info['folder'];
		$language = array();
		$language[] = '   '.strtoupper(api_get_interface_language()).'    ';
		$language[] = " \t   ".strtoupper(api_get_setting('platformLanguage'))."   \t ";
		$language[] = 'xxxxxxxxxxxxxx';
		$language[] = '   \t'.strtoupper('bulgarian').'    ';
		$res = array();
		$res[] = api_get_valid_language($language[1]);
		$res[] = api_get_valid_language($language[2]);
		$res[] = api_get_valid_language($language[3]);
		$res[] = api_get_valid_language($language[4]);
		$expected = array();
		foreach ($language as $value) {
			$value = str_replace('_km', '_KM', strtolower(trim($value)));
			if (empty($value) || !in_array($value, $enabled_languages) || !api_is_language_supported($value)) {
				$value = api_get_setting('platformLanguage');
			}
			$expected = $value;
		}
		$is_ok = true;
		foreach ($language as $key => $value) {
			$is_ok = $is_ok && ($value === $res[$key]);
		}
		//var_dump($res);
		//var_dump($expected);
	}

	public function test_api_purify_language_id() {
		$language = 'english_org';
		$res = api_purify_language_id($language);
		$this->assertTrue(is_string($res));
		$this->assertTrue($res == 'english');
		//var_dump($res);
	}

	function test_api_get_language_isocode() {
		$test_language_table = array(
			'*** invalid entry ***' => 'en', // An invalid entry.
			'arabic' => 'ar',
			'arabic_unicode' => 'ar',
			'asturian' => 'ast',
			'bosnian' => 'bs',
			'brazilian' => 'pt-BR',
			'bulgarian' => 'bg',
			'catalan' => 'ca',
			'croatian' => 'hr',
			'czech' => 'cs',
			'danish' => 'da',
			'dari' => 'prs',
			'dutch' => 'nl',
			'dutch_corporate' => 'nl',
			'english' => 'en',
			'english_org' => 'en',
			'esperanto' => 'eo',
			'basque' => 'eu',
			'finnish' => 'fi',
			'french' => 'fr',
			'french_corporate' => 'fr',
			'french_KM' => 'fr',
			'french_org' => 'fr',
			'french_unicode' => 'fr',
			'friulian' => 'fur',
			'galician' => 'gl',
			'georgian' => 'ka',
			'german' => 'de',
			'greek' => 'el',
			'hebrew' => 'he',
			'hungarian' => 'hu',
			'indonesian' => 'id',
			'italian' => 'it',
			'japanese' => 'ja',
			'japanese_unicode' => 'ja',
			'korean' => 'ko',
			'latvian' => 'lv',
			'lithuanian' => 'lt',
			'macedonian' => 'mk',
			'malay' => 'ms',
			'norwegian' => 'no',
			'occitan' => 'oc',
			'pashto' => 'ps',
			'persian' => 'fa',
			'polish' => 'pl',
			'portuguese' => 'pt',
			'quechua_cusco' => 'qu',
			'romanian' => 'ro',
			'russian' => 'ru',
			'russian_unicode' => 'ru',
			'serbian' => 'sr',
			'simpl_chinese' => 'zh',
			'simpl_chinese_unicode' => 'zh',
			'slovak' => 'sk',
			'slovenian' => 'sl',
			'slovenian_unicode' => 'sl',
			'spanish' => 'es',
			'spanish_latin' => 'es',
			'swahili' => 'sw',
			'swedish' => 'sv',
			'thai' => 'th',
			'trad_chinese' => 'zh-TW',
			'trad_chinese_unicode' => 'zh-TW',
			'turkish' => 'tr',
			'ukrainian' => 'uk',
			'vietnamese' => 'vi',
			'yoruba' => 'yo'
		);
		$res = array();
		foreach ($test_language_table as $language => $expected_result) {
			$test_result = api_get_language_isocode($language);
			$res[$language] = array(
				'expected_result' => $expected_result,
				'test_result' => $test_result,
				'is_ok' => $expected_result === $test_result
			);
		}
		$this->assertTrue(is_array($res));
		$is_ok = true;
		foreach ($res as $language => $test_case) {
			$is_ok = $is_ok && $test_case['is_ok'];
		}
		$this->assertTrue($is_ok);
		//var_dump($res);
		//foreach ($res as $language => $test_case) { echo ($test_case['is_ok'] ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' '.$language.' => '.(is_null($test_case['test_result']) ? 'NULL' : $test_case['test_result']).'<br />'; }
	}

	public function test_api_get_text_direction() {
		$languages = array('english', 'en', 'arabic', 'ar');
		$expected_results = array('ltr', 'ltr', 'rtl', 'rtl');
		$res = array();
		foreach ($languages as $language) {
			$res[] = api_get_text_direction($language);
		}
		$this->assertTrue($res === $expected_results);
		//var_dump($res);
	}

	public function test_api_is_latin1_compatible() {
		$language = 'portuguese';
		$res = api_is_latin1_compatible($language);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($res);
		//var_dump($res);
	}

	/*
	// This test works. It has been disabled, because it is time-consuming.
	public function test_api_detect_language() {
		$encoding = 'UTF-8';
		$strings = $this->language_strings;
		$is_test_ok = true;
		foreach ($strings as $language => $string) {
			if (api_is_language_supported($language)) {
				$res = api_detect_language($string, $encoding);
				$non_utf8_encoding = api_get_non_utf8_encoding($res);
				if (!empty($non_utf8_encoding)) {
					$is_ok = ($res == $language) || (api_is_encoding_supported($non_utf8_encoding) ? $string == api_utf8_encode(api_utf8_decode($string, $non_utf8_encoding), $non_utf8_encoding) : true);
				} else {
					$is_ok = true;
				}
				$is_test_ok = $is_test_ok && $is_ok;
				echo ($is_ok ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' '.$language.': '.$string.' => <strong>'.$res.'</strong><br />';
			}
		}
		echo '<br />';
		$this->assertTrue($is_test_ok);
	}
	*/

	/*
	// This test works. It has been disabled, because it is time-consuming.
	public function test_api_detect_encoding() {
		$strings = $this->language_strings;
		$is_test_ok = true;

		foreach ($strings as $language => $string) {
			if (api_is_language_supported($language)) {
				$is_ok = api_is_utf8(api_detect_encoding($string)); // Checking whether the input string is UTF-8.
				$is_test_ok = $is_test_ok && $is_ok;
				$non_utf8_encoding = api_get_non_utf8_encoding($language);
				if (!empty($non_utf8_encoding) && api_is_encoding_supported($non_utf8_encoding)) {
					$res = api_detect_encoding(api_utf8_decode($string, $non_utf8_encoding));
					$test_string = api_utf8_encode(api_utf8_decode($string, $non_utf8_encoding), $res);
					$is_ok = api_equal_encodings($non_utf8_encoding, $res) || $string == $test_string;
					echo $language.'<br />';
					echo $string.'<br />';
					echo $test_string.'<br />';
					echo ($is_ok ? '<span style="color: green; font-weight: bold;">Ok</span>' : '<span style="color: red; font-weight: bold;">Failed</span>').' '.$non_utf8_encoding.' => <strong>'.$res.'</strong><br />';
					echo '<br />';
				} else {
					$is_ok = true;
				}
				$is_test_ok = $is_test_ok && $is_ok;
			}
		}

		$this->assertTrue($is_test_ok);
	}
	*/

	// The second function for testing api_detect_encoding().
	public function test_api_detect_encoding_2() {
		$string_utf8 = 'Това е тест на български език'; // Bulgarian language, UTF-8
		$string_utf8_broken = $string_utf8.chr(198);    // Intentionaly broken UTF-8, it should be detected as UTF-8
		$res1 = api_detect_encoding($string_utf8, 'bulgarian');
		$res2 = api_detect_encoding($string_utf8_broken, 'bulgarian');
		$this->assertTrue(api_is_utf8($res1) && api_is_utf8($res2));
		//var_dump($res1);
		//var_dump($res2);
	}

	public function test_api_get_local_time_with_datetime() {
		$datetime_not_converted = '2010-03-13 16:24:02';
		$datetime_gmtplus1 = api_get_local_time($datetime_not_converted, 'Europe/Paris', 'America/Lima');
		$this->assertEqual($datetime_gmtplus1, '2010-03-13 22:24:02');
	}

	public function test_api_get_local_time_with_timestamp() {
		$current_timestamp = time();
		$datetime = api_get_local_time($current_timestamp, 'Europe/Paris');
		$system_timezone = date_default_timezone_get();
		date_default_timezone_set('Europe/Paris');
		$this->assertEqual($datetime, date('Y-m-d H:i:s', $current_timestamp));
		date_default_timezone_set($system_timezone);
	}

	public function test_api_get_utc_datetime_with_string() {
		$timestamp = time();
		$timezone = _api_get_timezone();
		$system_timezone = date_default_timezone_get();
		date_default_timezone_set($timezone);
		$datetime = date('Y-m-d H:i:s', $timestamp);
		$datetime_utc = api_get_utc_datetime($datetime);
		$this->assertEqual($datetime_utc, gmdate('Y-m-d H:i:s', $timestamp));
		date_default_timezone_set($system_timezone);
	}

	public function test_api_get_utc_datetime_with_timestamp() {
		$timestamp = time();
		$this->assertEqual(api_get_utc_datetime($timestamp), gmdate("Y-m-d H:i:s", $timestamp));
	}

	/*
	// Enable the following test when you need to run it.
	// Testing whether all the language files load successfully. This means that their php-syntax is correct.
	public function test_all_the_language_files() {
		$files = array( // Only files with these names will be loaded/tested.
			'accessibility.inc.php',
			'admin.inc.php',
			'agenda.inc.php',
			'announcements.inc.php',
			'blog.inc.php',
			'chat.inc.php',
			'coursebackup.inc.php',
			'courses.inc.php',
			'course_description.inc.php',
			'course_home.inc.php',
			'course_info.inc.php',
			'create_course.inc.php',
			'document.inc.php',
			'dropbox.inc.php',
			'exercice.inc.php',
			'external_module.inc.php',
			'forum.inc.php',
			'glossary.inc.php',
			'gradebook.inc.php',
			'group.inc.php',
			'help.inc.php',
			'hotspot.inc.php',
			'import.inc.php',
			'index.inc.php',
			'install.inc.php',
			'learnpath.inc.php',
			'link.inc.php',
			'md_document.inc.php',
			'md_link.inc.php',
			'md_mix.inc.php',
			'md_scorm.inc.php',
			'messages.inc.php',
			'myagenda.inc.php',
			'notebook.inc.php',
			'notification.inc.php',
			'pedaSuggest.inc.php',
			'registration.inc.php',
			'reservation.inc.php',
			'resourcelinker.inc.php',
			'scorm.inc.php',
			'scormbuilder.inc.php',
			'scormdocument.inc.php',
			'slideshow.inc.php',
			'survey.inc.php',
			'tracking.inc.php',
			'trad4all.inc.php',
			'userInfo.inc.php',
			'videoconf.inc.php',
			'wiki.inc.php',
			'work.inc.php'
		);

		$languages = test_get_language_folder_list();
		$lang_dir = api_get_path(SYS_LANG_PATH);

		foreach ($languages as $language) {
			echo 'Language: <strong>'.ucwords($language).'</strong><br />';
			echo '-------------------------------------------------------------------------<br />';
			foreach ($files as $file) {
				echo 'Loading '.$lang_dir.$language.'/<strong>'.$file.'</strong> ...<br />';
				test_load_php_language_file($lang_dir.$language.'/'.$file);
			}
			echo '<br />';
		}
		$this->assertTrue(true); // Once we arrived here, the test is Ok.
	}
	*/

}


// An isolated namespace for testing whether language files load successfully.
function test_load_php_language_file($filename) {
	include $filename;
}

// A helper function.
function test_get_language_folder_list() {
	$result = array();
	$exceptions = array('.', '..', 'CVS', '.svn');
	$dirname = api_get_path(SYS_LANG_PATH);
	$handle = opendir($dirname);
	while ($entries = readdir($handle)) {
		if (in_array($entries, $exceptions)) {
			continue;
		}
		if (is_dir($dirname.$entries)) {
			$result[] = $entries;
		}
	}
	closedir($handle);
	sort($result);
	return $result;
}

?>
