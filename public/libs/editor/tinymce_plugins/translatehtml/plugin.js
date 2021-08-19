
(function () {
    'use strict';

    var global = tinymce.util.Tools.resolve('tinymce.PluginManager');
    var translatedSelector = 'span[class="mce-translatehtml"]';

    var cleanTranslated = function (newElement) {
        var nodes = $(newElement).find(".mce-translatehtml");
        if (nodes.length > 0) {
            $(newElement).find(".mce-translatehtml").each(function () {
                $(this).replaceWith($(this).html());
            });
        }
    };

    var tinyWrap = function (open_tag, close_tag) {
      var ed = (tinymce.activeEditor || opener.tinymce.activeEditor); /* get editor instance */
      var selection = ed.selection.getContent(); /* get user selection, if any */
      var temp_name  = new Date().getTime().toString(36).toLowerCase(); /* generate a unique string */
      var span_open  = '<span id="' + temp_name + '">'; /* generate '<span id="unique">' */
      var span_close = '</span>'; /* generate closing '</span>' */
      selection = selection.replace(/<p>/g,'').replace(/<\/p>/g,'<br />'); /* convert <p></p> to <br /> */
      if (selection.substr(selection.length-6) === '<br />') {
        selection = selection.substr(0, selection.length-6); /* strip last <br /> if present */
      }
      /* create complete selection replacement: '<span id="unique">OPEN_TAGselectionCLOSE_TAG</span>' */
      var content = open_tag + span_open + selection + span_close + close_tag;
      ed.execCommand('mceReplaceContent', false, content); /* replace selection with new */
      var span_elem = ed.dom.get(temp_name); /* get the element of the temp span */
      ed.selection.select(span_elem); /* select (highlight) the selection span */
      cleanTranslated(span_elem);
      ed.dom.remove(temp_name, true); /* remove the span, leave it's highlighted text behind(true) */
      ed.focus(); /* insure editor has focus */
      return; /* taa-daa! all done! */
    }

    var register = function (editor) {
        var languagesConfigStrings = editor.getParam("translatehtml_lenguage_list");
        editor.ui.registry.addSplitButton('translatehtml', {
            tooltip: 'Translate html',
            icon: 'translate',
            onAction: function () {},
            onItemAction: function (api, value) {
                if (value[0] != 'remove') {
                    tinyWrap('<span class="mce-translatehtml hidden" dir="' + value[0] + '" lang="' + value[1] + '">', '</span>');
                } else {
                    tinyWrap('', '');
                }
            },
            fetch: function (callback) {
              var items = [], parts, curLanguageId, dir, i;
                for (i = 0; i < languagesConfigStrings.length; i++) {
                    parts = languagesConfigStrings[i].split(':');
                    curLanguageId = parts[0];
                    dir = 'rtl';
                    if (('' + parts[2]).toLowerCase() != 'rtl') {
                        dir = 'ltr';
                    }
                    items.push({
                        type: 'choiceitem',
                        text: parts[1],
                        value: [dir, curLanguageId]
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
    }

    function Plugin () {
      global.add('translatehtml', function (editor) {
        console.warn('translatehtml plugin is enabled');       
        register(editor);
      });
    }

    Plugin();
}());
