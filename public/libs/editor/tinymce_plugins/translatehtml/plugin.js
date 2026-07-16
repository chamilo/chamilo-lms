(function () {
  "use strict";

  // Chamilo language ISO codes, not generic HTML language tags.
  // Keep a small fallback list for editors where the API configuration is unavailable.
  var fallbackLanguages = [
    { code: "en_US", label: "English" },
    { code: "fr_FR", label: "Français" },
    { code: "es", label: "Español" },
    { code: "de", label: "Deutsch" },
    { code: "pt_BR", label: "Português (Brasil)" },
    { code: "it", label: "Italiano" },
    { code: "nl", label: "Nederlands" },
  ];

  function normalizeLanguageCode(code) {
    var normalizedCode = String(code || "")
      .trim()
      .replace(/-/g, "_");
    var matches = normalizedCode.match(/^([a-z]{2})(?:_([a-z]{2}))?$/i);

    if (!matches) {
      return normalizedCode;
    }

    return (
      String(matches[1]).toLowerCase() +
      (matches[2] ? "_" + String(matches[2]).toUpperCase() : "")
    );
  }

  function isValidLanguageCode(code) {
    return /^[a-z]{2}(?:_[A-Z]{2})?$/.test(String(code || ""));
  }

  function languageCodesMatch(left, right) {
    var normalizedLeft = normalizeLanguageCode(left);
    var normalizedRight = normalizeLanguageCode(right);

    if (normalizedLeft === normalizedRight) {
      return true;
    }

    return normalizedLeft.split("_")[0] === normalizedRight.split("_")[0];
  }

  function encodeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function unwrapExistingTranslationBlocks(container) {
    var blocks = Array.prototype.slice.call(
      container.querySelectorAll(".mce-translatehtml[lang]"),
    );

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
    var blocks = Array.prototype.slice.call(
      container.querySelectorAll("p, div, section, article"),
    );

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
    var container = document.createElement("div");
    container.innerHTML = String(html || "");
    unwrapExistingTranslationBlocks(container);
    unwrapBlockElements(container);

    return String(container.innerHTML || "").trim();
  }

  function getErrorMessage(error, fallback) {
    if (error && typeof error === "object") {
      return String(
        error["hydra:description"] ||
          error.detail ||
          error.message ||
          error.error ||
          fallback,
      );
    }

    return fallback;
  }

  function positiveInteger(value) {
    var parsed = Number.parseInt(String(value || "0"), 10);

    return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
  }

  function addQueryValue(searchParams, name, value) {
    var parsed = positiveInteger(value);
    if (parsed > 0) {
      searchParams.set(name, String(parsed));
    }
  }

  tinymce.PluginManager.add("translatehtml", function (editor) {
    var configurationPromise = null;

    function notify(text, type, timeout) {
      editor.notificationManager.open({
        text: text,
        type: type || "info",
        timeout: timeout || 4000,
      });
    }

    function getConfiguredContext() {
      var context = editor.getParam("translatehtml_context");

      return context && typeof context === "object" ? context : {};
    }

    function getApiUrl() {
      var endpoint = String(
        editor.getParam("translatehtml_ai_endpoint") ||
          "/api/wysiwyg_translation",
      );
      var currentUrl = new URL(window.location.href);
      var endpointUrl = new URL(endpoint, window.location.origin);
      var context = getConfiguredContext();

      addQueryValue(
        endpointUrl.searchParams,
        "cid",
        context.courseId || currentUrl.searchParams.get("cid"),
      );
      addQueryValue(
        endpointUrl.searchParams,
        "sid",
        context.sessionId || currentUrl.searchParams.get("sid"),
      );
      addQueryValue(
        endpointUrl.searchParams,
        "gid",
        context.groupId || currentUrl.searchParams.get("gid"),
      );

      return endpointUrl.toString();
    }

    function fetchJson(url, options) {
      var requestOptions = Object.assign(
        {
          credentials: "same-origin",
          headers: {
            Accept: "application/ld+json, application/json",
          },
        },
        options || {},
      );

      return window.fetch(url, requestOptions).then(function (response) {
        return response
          .json()
          .catch(function () {
            return {};
          })
          .then(function (payload) {
            if (!response.ok) {
              throw new Error(
                getErrorMessage(payload, "The translation request failed."),
              );
            }

            return payload;
          });
      });
    }

    function loadConfiguration() {
      if (!configurationPromise) {
        configurationPromise = fetchJson(getApiUrl()).catch(function () {
          return null;
        });
      }

      return configurationPromise;
    }

    function normalizeLanguageOption(language) {
      var code = normalizeLanguageCode(
        language.code ||
          language.value ||
          language.isocode ||
          language.iso ||
          "",
      );
      var label = String(language.label || language.name || code);

      return code && isValidLanguageCode(code)
        ? { code: code, label: label }
        : null;
    }

    function getLanguages(configuration) {
      var configuredLanguages = editor.getParam("translatehtml_languages");
      var languageSource = [];

      if (
        Array.isArray(configuredLanguages) &&
        configuredLanguages.length > 0
      ) {
        languageSource = configuredLanguages;
      } else if (
        configuration &&
        Array.isArray(configuration.languages) &&
        configuration.languages.length > 0
      ) {
        languageSource = configuration.languages;
      } else {
        languageSource = fallbackLanguages;
      }

      return languageSource.map(normalizeLanguageOption).filter(Boolean);
    }

    function notifyInvalidLanguage() {
      notify(
        "Please enter a Chamilo language ISO code, for example en_US, fr_FR, es or de.",
        "warning",
        3000,
      );
    }

    function insertTranslatedSpan(languageCode) {
      var code = normalizeLanguageCode(languageCode);
      if (!isValidLanguageCode(code)) {
        notifyInvalidLanguage();
        return;
      }

      var selectedContent = cleanSelectedContent(
        editor.selection.getContent({ format: "html" }),
      );
      if (!selectedContent) {
        selectedContent = encodeHtml("Translated content");
      }

      editor.insertContent(
        '<span class="mce-translatehtml" lang="' +
          code +
          '">' +
          selectedContent +
          "</span>",
      );
    }

    function openCustomLanguageDialog() {
      editor.windowManager.open({
        title: "Translated HTML span",
        body: {
          type: "panel",
          items: [
            {
              type: "input",
              name: "languageCode",
              label: "Chamilo language ISO code",
            },
          ],
        },
        buttons: [
          {
            type: "cancel",
            text: "Cancel",
          },
          {
            type: "submit",
            text: "Insert",
            primary: true,
          },
        ],
        initialData: {
          languageCode: "en_US",
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
        },
      });
    }

    function getExistingLanguages() {
      var container = document.createElement("div");
      container.innerHTML = editor.getContent({ format: "html" });
      var elements = container.querySelectorAll(
        ".mce-translatehtml[lang], span[lang]",
      );
      var existing = [];

      Array.prototype.forEach.call(elements, function (element) {
        var code = normalizeLanguageCode(element.getAttribute("lang"));
        if (isValidLanguageCode(code) && existing.indexOf(code) === -1) {
          existing.push(code);
        }
      });

      return existing;
    }

    function hasMatchingLanguage(languages, targetLanguage) {
      return languages.some(function (language) {
        return languageCodesMatch(language, targetLanguage);
      });
    }

    function getTargetLanguages(configuration) {
      var sourceLanguage = normalizeLanguageCode(configuration.sourceLanguage);
      var existingLanguages = getExistingLanguages();

      return getLanguages(configuration).filter(function (language) {
        return (
          !languageCodesMatch(language.code, sourceLanguage) &&
          !hasMatchingLanguage(existingLanguages, language.code)
        );
      });
    }

    function setDialogBusy(api, busy) {
      if (busy && typeof api.block === "function") {
        api.block("Translating...");
      } else if (!busy && typeof api.unblock === "function") {
        api.unblock();
      }
    }

    function applyTranslatedHtml(html) {
      editor.undoManager.transact(function () {
        editor.setContent(String(html || ""));
      });
      editor.nodeChanged();
      editor.fire("change");
    }

    function submitAiTranslation(api, data, configuration, targetLanguages) {
      var selectedTarget = String(data.targetLanguage || "");
      var requestedLanguages = [];

      if (selectedTarget === "__all__") {
        requestedLanguages = targetLanguages.map(function (language) {
          return language.code;
        });
      } else {
        requestedLanguages = [selectedTarget];
      }

      var providers = Array.isArray(configuration.providers)
        ? configuration.providers
        : [];
      var provider =
        providers.length > 1
          ? String(data.provider || "")
          : String((providers[0] || {}).value || "");
      var payload = {
        csrfToken: String(configuration.csrfToken || ""),
        html: editor.getContent({ format: "html" }),
        targetLanguages: requestedLanguages,
        provider: provider,
      };

      setDialogBusy(api, true);

      fetchJson(getApiUrl(), {
        method: "POST",
        headers: {
          Accept: "application/ld+json, application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      })
        .then(function (result) {
          applyTranslatedHtml(result.html);
          api.close();

          var added = Array.isArray(result.addedLanguages)
            ? result.addedLanguages
            : [];
          var skipped = Array.isArray(result.skippedLanguages)
            ? result.skippedLanguages
            : [];
          var message =
            added.length > 0
              ? "Translation added successfully."
              : "No new translation was added.";
          if (skipped.length > 0) {
            message += " Existing or source-language blocks were preserved.";
          }

          notify(message, added.length > 0 ? "success" : "info", 5000);
        })
        .catch(function (error) {
          setDialogBusy(api, false);
          notify(
            getErrorMessage(error, "The translation request failed."),
            "error",
            7000,
          );
        });
    }

    function openAiTranslationDialog(configuration) {
      if (!configuration || !configuration.enabled) {
        notify("AI translation is not available for this editor.", "warning");
        return;
      }

      var html = String(editor.getContent({ format: "html" }) || "").trim();
      if (!html) {
        notify("The editor content is empty.", "warning");
        return;
      }

      var targetLanguages = getTargetLanguages(configuration);
      if (targetLanguages.length === 0) {
        notify(
          "All active target languages are already present in this content.",
          "info",
        );
        return;
      }

      var languageOptions = targetLanguages.map(function (language) {
        return {
          text: language.label + " (" + language.code + ")",
          value: language.code,
        };
      });

      if (configuration.allowAllLanguages && targetLanguages.length > 1) {
        languageOptions.unshift({
          text: "All languages",
          value: "__all__",
        });
      }

      var providers = Array.isArray(configuration.providers)
        ? configuration.providers
        : [];
      if (providers.length === 0) {
        notify("No AI text provider is configured.", "warning");
        return;
      }

      var items = [
        {
          type: "selectbox",
          name: "targetLanguage",
          label: "Add translation to...",
          items: languageOptions,
        },
      ];

      if (providers.length > 1) {
        items.push({
          type: "selectbox",
          name: "provider",
          label: "AI provider",
          items: providers.map(function (provider) {
            return {
              text: String(provider.label || provider.value || ""),
              value: String(provider.value || ""),
            };
          }),
        });
      }

      items.push({
        type: "htmlpanel",
        html: "<p>The source-language content will be sent to an AI model for translation. Existing language blocks will be preserved.</p>",
      });

      var initialData = {
        targetLanguage: languageOptions[0].value,
      };
      if (providers.length > 1) {
        initialData.provider = String(providers[0].value || "");
      }

      editor.windowManager.open({
        title: "Add translation to...",
        body: {
          type: "panel",
          items: items,
        },
        buttons: [
          {
            type: "cancel",
            text: "Cancel",
          },
          {
            type: "submit",
            text: "Translate",
            primary: true,
          },
        ],
        initialData: initialData,
        onSubmit: function (api) {
          submitAiTranslation(
            api,
            api.getData(),
            configuration,
            targetLanguages,
          );
        },
      });
    }

    editor.ui.registry.addMenuButton("translatehtml", {
      text: "Lang ISO",
      tooltip: "Insert translated HTML span with Chamilo ISO code",
      fetch: function (callback) {
        loadConfiguration().then(function (configuration) {
          var items = [];

          if (configuration && configuration.enabled) {
            items.push({
              type: "menuitem",
              text: "Add translation to...",
              onAction: function () {
                openAiTranslationDialog(configuration);
              },
            });
            items.push({ type: "separator" });
          }

          getLanguages(configuration).forEach(function (language) {
            items.push({
              type: "menuitem",
              text: language.label + " (" + language.code + ")",
              onAction: function () {
                insertTranslatedSpan(language.code);
              },
            });
          });

          items.push({ type: "separator" });
          items.push({
            type: "menuitem",
            text: "Custom Chamilo ISO code...",
            onAction: openCustomLanguageDialog,
          });

          callback(items);
        });
      },
    });

    return {
      getMetadata: function () {
        return {
          name: "Chamilo translated HTML ISO spans",
          url: "https://chamilo.org",
        };
      },
    };
  });
})();
