
(function () {
    'use strict';

    var global = tinymce.util.Tools.resolve('tinymce.PluginManager');
    var translatedSelector = 'span[class="mce-translatehtml"]';

    var removeTranslated = function (editor) {
      var translated = editor.dom.getParent(editor.selection.getStart(), translatedSelector);
      if (translated) {
        $(translated).replaceWith(function() {
          return this.innerHTML;
        });
      }
      editor.addVisual();
    };

    var register = function (editor) {
        var languagesConfigStrings = editor.getParam("translatehtml_lenguage_list");
        editor.ui.registry.addSplitButton('translatehtml', {
            tooltip: 'Translate html',
            icon: 'translate',
            onAction: function () {},
            onItemAction: function (api, value) {

              if (value[0] != 'remove') {
                var span = '<span dir="'+value[0]+'" lang="'+value[1]+'" class="mce-translatehtml" >'+value[2]+'</span>'; // $('<span />').attr({'className' : 'mce-translatehtml-tmp', 'dir' : value[0], 'lang' : value[1]}).html(value[2]);
                editor.insertContent(span);
              }
              removeTranslated(editor);
            },
            fetch: function (callback) {
              var items = [], parts, curLanguageId, dir, itemText, i;
              for ( i = 0; i < languagesConfigStrings.length; i++ ) {
                    parts = languagesConfigStrings[i].split(':');
                    curLanguageId = parts[0];
                    dir = 'rtl';
                    if ((''+parts[2]).toLowerCase() != 'rtl') {
                        dir = 'ltr';
                    }
                    itemText = editor.selection.getContent({format: "text"});
                    items.push({
                            type: 'choiceitem',
                            text: parts[1],
                            value: [dir , curLanguageId, itemText]
                    });
                }
                items.push({
                            type: 'choiceitem',
                            icon: 'remove',
                            text: 'Remove translation',
                            value: ['remove']
                });
                callback(items);
            }                              
        });             
      
    } // end register


    function Plugin () {
      global.add('translatehtml', function (editor) {
        console.warn('translatehtml plugin is enabled');       
        register(editor);
      });
    }

    Plugin();

}());
