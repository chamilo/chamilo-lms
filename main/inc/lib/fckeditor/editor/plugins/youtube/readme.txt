=============================================================
=
=　　　YouTube Plugin For FCKeditor 2.5
=
=             http://www.sukekun.com/
=　　　　　   http://uprush.net/
=============================================================

Thank you for using YouTube Plugin For FCKeditor.

Install

1.Copy the extraced 'youtube' folder to fckeditor/editor/plugins

2.Add it to the end of fckeditor/fckconfig.js
  FCKConfig.Plugins.Add( 'youtube', 'en,ja' ) ;

3.Modify fckeditor/fckconfig.js
  Add YouTube button to the toolbar of fckeditor.
  eg.
  BEFORE:
	FCKConfig.ToolbarSets["Default"] = [
		['Image','Flash']
	]

  AFTER:
	FCKConfig.ToolbarSets["Default"] = [
		['Image','Flash','YouTube']
	]


YouTube Plugin For FCKeditor をご利用いただき、有難うございます。

インストール手順

１．解凍された youtube フォルダを fckeditor/editor/plugins/ にコピーする

２．fckeditor/fckconfig.js の最後に追加する
　　FCKConfig.Plugins.Add( 'youtube', 'en,ja' ) ;

３．fckeditor/fckconfig.js を修正する
　　ツールバー設定のところに、YouTube ボタンを追加する。

　　例）修正前
	FCKConfig.ToolbarSets["Default"] = [
		['Image','Flash']
	]

　　修正後
	FCKConfig.ToolbarSets["Default"] = [
		['Image','Flash','YouTube']
	]


