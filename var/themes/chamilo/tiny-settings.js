/* Shared TinyMCE base config (Vue + Legacy) */
(function () {
  "use strict";

  var BASE_URL_TINYMCE = "/libs/editor";
  var SUFFIX = ".min";

  function normalizePlugins(p) {
    if (!p) return [];
    if (Array.isArray(p)) return p.flatMap(s => String(s).split(/\s+/)).filter(Boolean);
    return String(p).split(/\s+/).filter(Boolean);
  }
  function unionPlugins(a, b) {
    var set = new Set();
    normalizePlugins(a).forEach(x => set.add(x));
    normalizePlugins(b).forEach(x => set.add(x));
    return Array.from(set);
  }
  function dedupeToolbar(a, b) {
    const tok = t => (t || "").split("|").map(s => s.trim()).filter(Boolean);
    const norm = s => s.replace(/\s+/g, " ");
    const seen = new Set();
    const out = [];
    for (const block of [...tok(a), ...tok(b)]) {
      const k = norm(block);
      if (!seen.has(k)) { seen.add(k); out.push(block); }
    }
    return out.join(" | ");
  }

  // Merge policy:
  // - TOOLBAR: we will prefer the base toolbar from this file (ignore legacy's)
  // - PLUGINS: union base + local (so PHP can add *conditional* extras if needed)
  const TOOLBAR_POLICY = "base";   // "base" | "concat"
  const PLUGINS_POLICY = "union";  // "union" | "base"

  var BASE = {
    height: 320,
    menubar: false,
    branding: false,
    statusbar: true,
    toolbar_mode: "wrap",
    language: "auto",
    plugins: [
      "advlist anchor autolink charmap code codesample directionality",
      "fullscreen emoticons image insertdatetime link lists media",
      "paste preview print pagebreak save searchreplace table template",
      "visualblocks wordcount"
    ].join(" "),
    toolbar: [
      "undo redo | styles | bold italic underline strikethrough |",
      "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent |",
      "fontselect fontsizeselect forecolor backcolor removeformat |",
      "link image media table | pagebreak charmap emoticons |",
      "preview save print | code fullscreen | ltr rtl"
    ].join(" "),
    base_url: BASE_URL_TINYMCE,
    suffix: SUFFIX
  };

  // Autogenerate external_plugins for self-hosted plugins
  (function ensureExternalPluginsMap(cfg) {
    var baseUrl = cfg.base_url || BASE_URL_TINYMCE;
    var list = normalizePlugins(cfg.plugins);
    var map = Object.assign({}, cfg.external_plugins || {});
    list.forEach(function (name) {
      if (!map[name]) map[name] = baseUrl + "/plugins/" + name + "/plugin" + (cfg.suffix || SUFFIX) + ".js";
    });
    cfg.external_plugins = map;
  })(BASE);

  window.CHAMILO_TINYMCE_BASE_CONFIG = BASE;

  // Builder: enforce policy (toolbar base-only, plugins union by default)
  window.buildTinyMceConfig = function (local) {
    var base = window.CHAMILO_TINYMCE_BASE_CONFIG || {};
    var merged = Object.assign({}, base, local || {});

    // PLUGINS policy
    if (PLUGINS_POLICY === "base") {
      merged.plugins = base.plugins;
    } else {
      var allPlugins = unionPlugins(base.plugins, local && local.plugins);
      merged.plugins = allPlugins.join(" ");
    }

    // external_plugins (let local override URLs if provided)
    var externalBase = base.external_plugins || {};
    var externalLocal = (local && local.external_plugins) || {};
    merged.external_plugins = Object.assign({}, externalBase, externalLocal);

    // TOOLBAR policy
    if (base.toolbar && local && local.toolbar) {
      if (TOOLBAR_POLICY === "concat") {
        merged.toolbar = dedupeToolbar(base.toolbar, local.toolbar);
      } else {
        merged.toolbar = base.toolbar; // prefer shared toolbar
      }
    }

    // Merge setup handlers
    var baseSetup = base.setup;
    var localSetup = (local || {}).setup;
    merged.setup = function (editor) {
      if (typeof baseSetup === "function") try { baseSetup(editor); } catch (e) {}
      if (typeof localSetup === "function") try { localSetup(editor); } catch (e) {}
    };

    return merged;
  };
})();
