markItUp! 1.1.14

CHANGE LOG
markItUp! 1.1.14 2013-02-04
- Compatibility patch for jQuery 1.9+
- Click on menu bubbles up so it could be reused (michilehr)
- Fixed default selection after using a tag having either openBlockWith or closeBlockWith (r3c)

markItUp! 1.1.13 2012-08-30
- Added ability to render preview in a DOM element (ytjohn & jaysalvat)
- Fixed Ctrl+Enter syntax error (UltCombo)
- Added the previewHandler option to allow complete control over preview (lecterror)
- Updated events to use namespace (JoyceBabu)
- Added $(elm).markItUp('insert', { }) and $(elm).markItUp('remove');
- Updated the demo
- Added package.json

markItUp! 1.1.12 2011-08-11
- Fixed the insertion on empty selection
- Enhanced the trailing space management

markItUp! 1.1.11 2011-07-19
- Enabled parsing the markup in javascript (amroth)
- Added multiline support (alevchuk)
- Updated with jQuery 1.6.2
- Added quotes to attribute selectors
- Tried to fix the endless IE caret position bug
- Added metakey support for Mac users (michal-krause)

markItUp! 1.1.10 2011-02-20
- Improved: Ajax property 'dataType' set to 'text'
- Improved: Ajax property 'global' set to 'false'
- Fixed: Ctrl+click selection problem

markItUp! 1.1.9 2010-11-04
- Improved: Selection accuracy in Internet Explorer
- Improved: Replace focusin() by bind('focusin') to solve some backward compatibility issues (Nick B. C.)
- Cosmetic: Add new logo
- Cosmetic: Remove jQuery library and use CDN

markItUp! 1.1.8 2010-08-27
- Improved: Some skin PNG have been optimized (lukescammell)
- Fixed: Opera 10 selection bug (Marius G.)
- Fixed: Accessibility issue with the background default color in the default skin
- Improved: Avoid giving the preview focus each time autorefresh kicks in. It still gets the focus when the preview button is pressed (DrSlump)
- Improved: In pop-up mode the preview is closed when the page containing the editor is unloaded (DrSlump)
- Improved: Press TAB jump right inside the textarea (Yakir)

markItUp! 1.1.7 2010-04-06
- Fixed: Empty lines are removed at insertion on Webkit
- Fixed: Focus lost when shortcut is used on FF
- Fixed: var missing before $$ in markItUpRemove()

markItUp! 1.1.6 2010-01-12
- Improved: Ajax requests are now asynchronous
- Fixed: Double empty line problem with preview and parsers
- Fixed: IE8 now close the preview properly

markItUp! 1.1.5 2009-05-01
- Modified: http://drupal.org/project/wysiwyg compatibility
- Modified: Alt/Ctrl/Alt+Tab are now disabled

markItUp! 1.1.4 2008-12-03
- Fixed: Extra quote deleted line 95

markItUp! 1.1.3 2008-09-12
- Fixed: IE7 preview problem

markItUp! 1.1.2 2008-07-17
- Fixed: Quick fix for Opera 9.5 caret position problem after insertion

markItUp! 1.1.1 2008-06-02
- Fixed: Key events status are passed to callbacks properly
- Improved: ScrollPosition is kept in the preview when its refreshed

markItUp! 1.1.0 2008-05-04
- Modified: Textarea's id is no more moved to the main container
- Modified: NameSpace Span become a Div to remain strict
- Added: Relative path to the script is computed
- Added: Relative path to the script passed to callbacks
- Added: Global instance ID property
- Added: $(element).markItUpRemove() to remove markItUp!
- Added: Resize handle is now optional with resizeHandle property
- Added: Property previewInWindow is added and accept window parameter
- Added: Property previewPosition is added
- Modified: Resize handle is no more displayed in Safari to avoid repetition with the native handle
- Modified: Property previewIframeRefresh become previewAutorefresh
- Modified: Built-in Html Preview call a template file
- Improved: Autorefreshing is now apply for preview in window too
- Improved: Cancel button in prompt window cancel now the whole insertion process
- Improved: Cleaner markItUp! code added to the DOM
- Removed: Depreciated preview properties as previewBaseUrl, previewCharset, previewCssPath, previewBodyId, previewBodyClassName
- Removed: Property previewIframe not longer exists
- Fixed: "Magic markups" works with line feeds
- Fixed: Key events are initialized after insertion
- Fixed: Internet Explorer line feed offset bug
- Fixed: Shortcut keys on Mac OS
- Fixed: Ctrl+click works and doesn't open Mac context menu anymore
- Fixed: Ctrl+click works and doesn't open the page in a new tab anymore
- Fixed: Minor Css modifications

markItUp! 1.0.3 2008-04-04
- Fixed: IE7 Preview empty baseurl problem
- Fixed: IE7 external targeted insertion
- Added: Property scrollPosition is passed to callbacks functions

markItUp! 1.0.2 2008-03-31
- Fixed: IE7 Html preview problems
- Fixed: Selection is kept if nothing is inserted
- Improved: Code minified

markItUp! 1.0.1 2008-03-21
- Removed: Global PlaceHolder
- Modified: Property previewCharset is setted to "utf-8" by default

markItUp! 1.0.0 2008-03-01
- First public release
