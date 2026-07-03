(function () {
  'use strict';

  // Chamilo language ISO codes, not generic HTML language tags.
  // Keep the list small and allow custom codes for installations with more languages.
  var languages = [
    { code: 'en_US', label: 'English' },
    { code: 'fr_FR', label: 'Français' },
    { code: 'es', label: 'Español' },
    { code: 'de', label: 'Deutsch' },
    { code: 'pt_BR', label: 'Português (Brasil)' },
    { code: 'it', label: 'Italiano' },
    { code: 'nl', label: 'Nederlands' }
  ];

  function normalizeLanguageCode(code) {
    var normalizedCode = String(code || '').trim().replace(/-/g, '_');
    var matches = normalizedCode.match(/^([a-z]{2})(?:_([a-z]{2}))?$/i);

    if (!matches) {
      return normalizedCode;
    }

    return String(matches[1]).toLowerCase() + (matches[2] ? '_' + String(matches[2]).toUpperCase() : '');
  }

  function isValidLanguageCode(code) {
    return /^[a-z]{2}(?:_[A-Z]{2})?$/.test(String(code || ''));
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

  function unwrapBlockElements(container) {
    var blocks = Array.prototype.slice.call(container.querySelectorAll('p, div, section, article'));

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

  function cleanSelectedContent(html) {
    var container = document.createElement('div');
    container.innerHTML = String(html || '');

    unwrapExistingTranslationBlocks(container);
    unwrapBlockElements(container);

    return String(container.innerHTML || '').trim();
  }

  tinymce.PluginManager.add('translatehtml', function (editor) {
    function getLanguages() {
      var configuredLanguages = editor.getParam('translatehtml_languages');

      if (Array.isArray(configuredLanguages) && configuredLanguages.length > 0) {
        return configuredLanguages
          .map(function (language) {
            var code = normalizeLanguageCode(language.code || language.isocode || language.iso || '');
            var label = String(language.label || language.name || code);

            return code && isValidLanguageCode(code) ? { code: code, label: label } : null;
          })
          .filter(Boolean);
      }

      return languages;
    }

    function notifyInvalidLanguage() {
      editor.notificationManager.open({
        text: 'Please enter a Chamilo language ISO code, for example en_US, fr_FR, es or de.',
        type: 'warning',
        timeout: 3000
      });
    }

    function insertTranslatedSpan(languageCode) {
      var code = normalizeLanguageCode(languageCode);

      if (!isValidLanguageCode(code)) {
        notifyInvalidLanguage();
        return;
      }

      var selectedContent = cleanSelectedContent(editor.selection.getContent({ format: 'html' }));

      if (!selectedContent) {
        selectedContent = encodeHtml('Translated content');
      }

      editor.insertContent('<span class="mce-translatehtml" lang="' + code + '">' + selectedContent + '</span>');
    }

    function openCustomLanguageDialog() {
      editor.windowManager.open({
        title: 'Translated HTML span',
        body: {
          type: 'panel',
          items: [
            {
              type: 'input',
              name: 'languageCode',
              label: 'Chamilo language ISO code'
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
          languageCode: 'en_US'
        },
        onSubmit: function (api) {
          var data = api.getData();
          var code = normalizeLanguageCode(data.languageCode);

          if (!isValidLanguageCode(code)) {
            notifyInvalidLanguage();
            return;
          }

          insertTranslatedSpan(code);
          api.close();
        }
      });
    }

    editor.ui.registry.addMenuButton('translatehtml', {
      text: 'Lang ISO',
      tooltip: 'Insert translated HTML span with Chamilo ISO code',
      fetch: function (callback) {
        var items = getLanguages().map(function (language) {
          return {
            type: 'menuitem',
            text: language.label + ' (' + language.code + ')',
            onAction: function () {
              insertTranslatedSpan(language.code);
            }
          };
        });

        items.push({
          type: 'separator'
        });

        items.push({
          type: 'menuitem',
          text: 'Custom Chamilo ISO code...',
          onAction: openCustomLanguageDialog
        });

        callback(items);
      }
    });

    return {
      getMetadata: function () {
        return {
          name: 'Chamilo translated HTML ISO spans',
          url: 'https://chamilo.org'
        };
      }
    };
  });
}());
