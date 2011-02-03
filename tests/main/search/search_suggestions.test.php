<?php
require_once api_get_path(SYS_CODE_PATH).'search/search_suggestions.php';
class TestSearch extends UnitTestCase {

    public function __construct() {
        $this->UnitTestCase('Search suggestions library - main/search/search_suggestions.test.php');
    }

	function testGetSuggestionsFromSearchEngine() {
		//ob_start();
		$q=1;
		$res = get_suggestions_from_search_engine($q);
		$this->assertTrue(is_null($res));
		//ob_end_clean();
		//var_dump($res);
	}
}
?>