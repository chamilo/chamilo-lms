(function () {
  'use strict';

  var languages = [
    { code: 'en', label: 'English' },
    { code: 'fr', label: 'Français' },
    { code: 'es', label: 'Español' },
    { code: 'pt', label: 'Português' },
    { code: 'de', label: 'Deutsch' },
    { code: 'it', label: 'Italiano' },
    { code: 'nl', label: 'Nederlands' }
  ];

  function normalizeLanguageCode(code) {
    return String(code || '').trim().toLowerCase().substring(0, 2);
  }

  function isValidLanguageCode(code) {
    return /^[a-z]{2}$/.test(String(code || ''));
  }

  function encodeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function unwrapExistingTranslationBlocks(container) {
    var blocks = Array.prototype.slice.call(container.querySelectorAll('.mce-translatehtml[lang]'));

    blocks.forEach(function (block) {
      var parent = block.parentNode;

      if (!parent) {
        return;
      }

      while (block.firstChild) {
        parent.insertBefore(block.firstChild, block);
      }

      parent.removeChild(block);
    });
  }

  function removeEmptyBlocks(container) {
    var blocks = Array.prototype.slice.call(container.querySelectorAll('p, div'));

    blocks.forEach(function (block) {
      if (block.querySelector('img, video, audio, iframe, table, ul, ol')) {
        return;
      }

      var text = String(block.textContent || '').replace(/ /g, ' ').trim();
      var html = String(block.innerHTML || '').replace(/&nbsp;/gi, '').replace(/<br\s*\/?\s*>/gi, '').trim();

      if ('' === text && '' === html && block.parentNode) {
        block.parentNode.removeChild(block);
      }
    });
  }

  function cleanSelectedContent(html) {
    var container = document.createElement('div');
    container.innerHTML = String(html || '');

    unwrapExistingTranslationBlocks(container);
    removeEmptyBlocks(container);

    return String(container.innerHTML || '').trim();
  }

  tinymce.PluginManager.add('translatehtml', function (editor) {
    function notifyInvalidLanguage() {
      editor.notificationManager.open({
        text: 'Please enter a two-letter language code, for example en, fr or es.',
        type: 'warning',
        timeout: 3000
      });
    }

    function insertTranslatedBlock(languageCode) {
      var code = normalizeLanguageCode(languageCode);

      if (!isValidLanguageCode(code)) {
        notifyInvalidLanguage();
        return;
      }

      var selectedContent = cleanSelectedContent(editor.selection.getContent({ format: 'html' }));

      if (!selectedContent) {
        selectedContent = '<p>' + encodeHtml('Translated content') + '</p>';
      }

      editor.insertContent('<div class="mce-translatehtml" lang="' + code + '">' + selectedContent + '</div>');
    }

    function openCustomLanguageDialog() {
      editor.windowManager.open({
        title: 'Translated HTML block',
        body: {
          type: 'panel',
          items: [
            {
              type: 'input',
              name: 'languageCode',
              label: 'Language code'
            }
          ]
        },
        buttons: [
          {
            type: 'cancel',
            text: 'Cancel'
          },
          {
            type: 'submit',
            text: 'Insert',
            primary: true
          }
        ],
        initialData: {
          languageCode: 'en'
        },
        onSubmit: function (api) {
          var data = api.getData();
          var code = normalizeLanguageCode(data.languageCode);

          if (!isValidLanguageCode(code)) {
            notifyInvalidLanguage();
            return;
          }

          insertTranslatedBlock(code);
          api.close();
        }
      });
    }

    editor.ui.registry.addMenuButton('translatehtml', {
      text: 'Lang',
      tooltip: 'Insert translated HTML block',
      fetch: function (callback) {
        var items = languages.map(function (language) {
          return {
            type: 'menuitem',
            text: language.label + ' (' + language.code + ')',
            onAction: function () {
              insertTranslatedBlock(language.code);
            }
          };
        });

        items.push({
          type: 'separator'
        });

        items.push({
          type: 'menuitem',
          text: 'Custom language code...',
          onAction: openCustomLanguageDialog
        });

        callback(items);
      }
    });

    return {
      getMetadata: function () {
        return {
          name: 'Chamilo translated HTML blocks',
          url: 'https://chamilo.org'
        };
      }
    };
  });
}());
