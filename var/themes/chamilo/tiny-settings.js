// ──────────────────────────────────────────────────────────────────────────────
// TinyMCE – Theme-level shared base configuration (applies to Vue + legacy)
// ──────────────────────────────────────────────────────────────────────────────
//
// PURPOSE
//   • Provide a single, theme-scoped TinyMCE base config that both Vue editors
//     and legacy editors will inherit, so the UI/behavior is consistent.
//   • Enforce default font family and size (e.g., Arial 12pt) everywhere.
//
// WHERE TO PUT THIS FILE
//   • One copy per active theme:
//       var/themes/<theme-name>/tiny-settings.js
//     Example used here: var/themes/parkur1/tiny-settings.js
//
// HOW IT IS LOADED
//   • The base template includes (with fallback):
//       <script src="{{ theme_asset('tiny-settings.js') }}" ...></script>
//   • Both Vue and legacy editors then call window.buildTinyMceConfig(localCfg)
//     to merge their local config with this base, respecting merge policies.
//
// HOW TO ENFORCE “ARIAL 12PT EVERYWHERE”
//   1) Set `font_family_formats` with Arial first (so it’s the default choice).
//   2) Set `font_size_formats` to include 12pt.
//   3) Use `content_style` to render Arial 12pt in the editing area, even for
//      content with no inline styles.
//   4) In `setup(init)` force the editor body to Arial 12pt at initialization,
//      which also helps legacy forms or empty documents.
//   5) Keep TOOLBAR_POLICY = "base" so the toolbar layout from this file wins.
//      (Plugins use union so features added elsewhere are preserved.)
//
// MULTI-THEME NOTE
//   • If you use multiple themes (e.g., chamilo + parkur1), copy this file into
//     each theme to ensure consistent behavior across the whole platform.
//
// CLEARING CACHES / VERIFYING
//   • Clear Symfony/Chamilo caches and your browser cache.
//   • Check a Vue editor (e.g., Social) and a legacy editor (e.g., Tickets):
//     both should show and render Arial 12pt by default.
//
// TROUBLESHOOTING
//   • If the toolbar still shows “Times New Roman” as the *label* when the
//     cursor is on old content, that is just the label reflecting inline styles
//     of existing HTML. New text and overall rendering will follow Arial 12pt.
//   • If your local component explicitly overrides `content_style` or `setup`,
//     both are merged here (base runs, then local).
//
;(function () {
  ;("use strict")

  // ── Basic TinyMCE path/suffix (self-hosted)
  var BASE_URL_TINYMCE = "/libs/editor"
  var SUFFIX = ".min"

  // (Helper functions may already exist in your current version. Keep them.)
  function normalizePlugins(p) {
    if (!p) return []
    if (Array.isArray(p)) return p.flatMap((s) => String(s).split(/\s+/)).filter(Boolean)
    return String(p).split(/\s+/).filter(Boolean)
  }

  function dedupeToolbar(a, b) {
    const tok = (t) =>
      (t || "")
        .split("|")
        .map((s) => s.trim())
        .filter(Boolean)

    const norm = (s) => s.replace(/\s+/g, " ")
    const seen = new Set()
    const out = []

    for (const block of [...tok(a), ...tok(b)]) {
      const k = norm(block)
      if (!seen.has(k)) {
        seen.add(k)
        out.push(block)
      }
    }

    return out.join(" | ")
  }

  function toBool(value) {
    if (typeof value === "boolean") {
      return value
    }

    return ["1", "true", "yes", "on"].includes(
      String(value ?? "")
        .trim()
        .toLowerCase(),
    )
  }

  function removePlugins(plugins, namesToRemove) {
    const removeSet = new Set((namesToRemove || []).map((item) => String(item).trim()).filter(Boolean))
    return normalizePlugins(plugins)
      .filter((plugin) => !removeSet.has(plugin))
      .join(" ")
  }

  function removeToolbarCommands(toolbar, commandsToRemove) {
    const removeSet = new Set((commandsToRemove || []).map((item) => String(item).trim()).filter(Boolean))

    return String(toolbar || "")
      .split("|")
      .map((group) =>
        group
          .trim()
          .split(/\s+/)
          .filter((cmd) => cmd && !removeSet.has(cmd))
          .join(" "),
      )
      .map((group) => group.trim())
      .filter(Boolean)
      .join(" | ")
  }

  function appendExtendedValidElements(raw, addition) {
    const current = String(raw || "").trim()
    const next = String(addition || "").trim()

    if (!next) {
      return current
    }

    if (!current) {
      return next
    }

    if (current.includes(next)) {
      return current
    }

    return `${current},${next}`
  }

  function appendValidChildren(raw, additions) {
    const current = String(raw || "")
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean)

    for (const item of additions || []) {
      const normalized = String(item || "").trim()
      if (!normalized) {
        continue
      }

      if (!current.includes(normalized)) {
        current.push(normalized)
      }
    }

    return current.join(",")
  }

  function appendInvalidElements(raw, additions) {
    const current = String(raw || "")
      .split(",")
      .map((item) => item.trim())
      .filter(Boolean)

    for (const item of additions || []) {
      const normalized = String(item || "").trim()
      if (!normalized) {
        continue
      }

      if (!current.includes(normalized)) {
        current.push(normalized)
      }
    }

    return current.join(",")
  }

  function wrapSetup(existingSetup, extraSetup) {
    return function (editor) {
      if (typeof existingSetup === "function") {
        try {
          existingSetup(editor)
        } catch (e) {
          // Ignore
        }
      }

      if (typeof extraSetup === "function") {
        try {
          extraSetup(editor)
        } catch (e) {
          // Ignore
        }
      }
    }
  }

  function stripInlineSvg(html) {
    return String(html || "").replace(/<svg[\s\S]*?<\/svg>/gi, "")
  }

  function stripSvgReferences(html) {
    let out = stripInlineSvg(html)

    out = out.replace(
      /<img\b([^>]*?)\bsrc=(["'])([^"']*(?:\.svg(?:\?[^"']*)?|data:image\/svg\+xml[^"']*))\2([^>]*)>/gi,
      "",
    )

    out = out.replace(
      /<object\b([^>]*?)\bdata=(["'])([^"']*(?:\.svg(?:\?[^"']*)?|data:image\/svg\+xml[^"']*))\2([^>]*)>[\s\S]*?<\/object>/gi,
      "",
    )

    out = out.replace(
      /<embed\b([^>]*?)\bsrc=(["'])([^"']*(?:\.svg(?:\?[^"']*)?|data:image\/svg\+xml[^"']*))\2([^>]*)>/gi,
      "",
    )

    return out
  }

  function applyChamiloEditorPolicies(config, features) {
    const flags = features || {}

    const isLearner = toBool(flags.isLearner)
    const blockCopyPasteForStudents = toBool(flags.blockCopyPasteForStudents)
    const youtubeForStudents = toBool(flags.youtubeForStudents)
    const enabledInsertHtml = toBool(flags.enabledInsertHtml)
    const enableIframeInclusion = toBool(flags.enableIframeInclusion)
    const enabledSupportSvg = toBool(flags.enabledSupportSvg)

    const allowIframeEmbeds = enabledInsertHtml && enableIframeInclusion

    if (isLearner && !youtubeForStudents) {
      config.plugins = removePlugins(config.plugins, ["media"])
      config.toolbar = removeToolbarCommands(config.toolbar, ["media"])
    }

    if (allowIframeEmbeds) {
      config.extended_valid_elements = appendExtendedValidElements(
        config.extended_valid_elements,
        "iframe[src|title|width|height|name|class|style|frameborder|allow|allowfullscreen|loading|referrerpolicy]",
      )

      config.valid_children = appendValidChildren(config.valid_children, [
        "+body[iframe]",
        "+div[iframe]",
        "+p[iframe]",
      ])
    } else {
      config.invalid_elements = appendInvalidElements(config.invalid_elements, ["iframe"])
    }

    const svgPolicySetup = function (editor) {
      const sanitizeIfNeeded = function (event) {
        if (!event) {
          return
        }

        if (enabledSupportSvg) {
          event.content = stripInlineSvg(event.content)
          return
        }

        event.content = stripSvgReferences(event.content)
      }

      editor.on("BeforeSetContent", sanitizeIfNeeded)
      editor.on("PastePreProcess", sanitizeIfNeeded)

      editor.on("GetContent", function (event) {
        if (!event?.content) {
          return
        }

        if (enabledSupportSvg) {
          event.content = stripInlineSvg(event.content)
          return
        }

        event.content = stripSvgReferences(event.content)
      })
    }

    config.setup = wrapSetup(config.setup, svgPolicySetup)

    if (isLearner && blockCopyPasteForStudents) {
      config.paste_block_drop = true

      const policySetup = function (editor) {
        const blockedMessage = "Copy and paste is disabled in this editor."

        const notifyBlockedAction = function () {
          try {
            editor.notificationManager.open({
              text: blockedMessage,
              type: "warning",
              timeout: 2000,
            })
          } catch (e) {
            // Ignore
          }
        }

        const preventEditorAction = function (event) {
          try {
            if (event && typeof event.preventDefault === "function") {
              event.preventDefault()
            }

            if (event && typeof event.stopPropagation === "function") {
              event.stopPropagation()
            }
          } catch (e) {
            // Ignore
          }

          notifyBlockedAction()

          return false
        }

        editor.on("BeforeExecCommand", function (event) {
          const command = String(event?.command || "").toLowerCase()

          if (["paste", "copy", "cut"].includes(command)) {
            preventEditorAction(event)
          }
        })

        editor.on("init", function () {
          try {
            const doc = editor.getDoc()

            if (!doc) {
              return
            }

            doc.addEventListener("paste", preventEditorAction, true)
            doc.addEventListener("drop", preventEditorAction, true)
            doc.addEventListener("copy", preventEditorAction, true)
            doc.addEventListener("cut", preventEditorAction, true)

            doc.addEventListener(
              "beforeinput",
              function (event) {
                const inputType = String(event?.inputType || "")

                if (["insertFromPaste", "insertFromDrop"].includes(inputType)) {
                  preventEditorAction(event)
                }
              },
              true,
            )
          } catch (e) {
            // Ignore
          }
        })
      }

      config.setup = wrapSetup(config.setup, policySetup)
    }

    return config
  }

  // ── Merge policies (how base + local config are combined)
  // TOOLBAR: "base" → the toolbar defined here wins (ensures consistent UI).
  // PLUGINS: "union" → combine base + local (keeps extra features).
  const TOOLBAR_POLICY = "base"
  const PLUGINS_POLICY = "union"

  // ── THEME-LEVEL BASE CONFIG (applies to all editors)
  var BASE = {
    height: 320,
    menubar: false,
    branding: false,
    statusbar: true,
    toolbar_mode: "wrap",
    language: "auto",
    media_live_embeds: false,

    // Core plugin set. Local configs can add more; we’ll union them below.
    plugins: [
      "advlist anchor autolink charmap code codesample directionality",
      "fullscreen emoticons image insertdatetime link lists media",
      "paste preview print pagebreak save searchreplace table template",
      "visualblocks wordcount",
    ].join(" "),

    // Keep font selectors visible so users can see/select Arial 12pt.
    toolbar: [
      "undo redo | styles | bold italic underline strikethrough |",
      "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent |",
      "fontselect fontsizeselect forecolor backcolor removeformat |",
      "link image media table | pagebreak charmap emoticons |",
      "preview save print | code fullscreen | ltr rtl",
    ].join(" "),

    // 1) Fonts menu: put Arial first to make it the natural default.
    font_family_formats: [
      "Arial=Arial, Helvetica, sans-serif",
      "Helvetica=Helvetica, Arial, sans-serif",
      "Times New Roman='Times New Roman', Times, serif",
      "Georgia=Georgia, serif",
      "Tahoma=Tahoma, Geneva, sans-serif",
      "Verdana=Verdana, Geneva, sans-serif",
      "Courier New='Courier New', Courier, monospace",
    ].join(";"),

    // 2) Size menu: ensure 12pt is available and visible.
    font_size_formats: "8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 24pt 36pt",

    // 3) Render default face/size in the editor body for new/unstyled content.
    //    This ensures both Vue and legacy widgets show Arial 12pt consistently.
    content_style: [
      "html,body{font-family: Arial, Helvetica, sans-serif; font-size:12pt;}",
      ".tiny-content{font-family: Arial, Helvetica, sans-serif; font-size:12pt;}",
      // TinyMCE media fake-object placeholders
      "img[data-mce-object]{display:inline-block;vertical-align:middle;max-width:100%;border:1px dashed #94a3b8;border-radius:12px;background-color:#f8fafc;background-repeat:no-repeat;background-position:center center;background-size:56px 56px;}",
      "img[data-mce-object='video'],img.mce-object-video{min-width:300px;min-height:150px;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='52' fill='%230f172ab8'/%3E%3Cpolygon points='50,38 88,60 50,82' fill='white'/%3E%3C/svg%3E\");}",
      "img[data-mce-object='audio'],img.mce-object-audio{min-width:300px;min-height:72px;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='52' fill='%230f172ab8'/%3E%3Cpath d='M50 46 L68 46 L82 34 L82 86 L68 74 L50 74 Z' fill='white'/%3E%3Cpath d='M88 46 Q98 60 88 74' fill='none' stroke='white' stroke-width='6' stroke-linecap='round'/%3E%3C/svg%3E\");}",
      "img[data-mce-selected='1']{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.18);}",
    ].join(" "),

    base_url: BASE_URL_TINYMCE,
    suffix: SUFFIX,

    // 4) Enforce Arial 12pt on init (helps empty docs and legacy forms).
    setup: function (editor) {
      editor.on("init", function () {
        const body = editor.getBody()
        if (body) {
          body.style.fontFamily = "Arial, Helvetica, sans-serif"
          body.style.fontSize = "12pt"
        }
      })
    },
  }

  // ── Autogenerate external_plugins map for self-hosted plugins
  ;(function ensureExternalPluginsMap(cfg) {
    var baseUrl = cfg.base_url || BASE_URL_TINYMCE
    var list = String(cfg.plugins || "")
      .split(/\s+/)
      .filter(Boolean)
    var map = Object.assign({}, cfg.external_plugins || {})

    list.forEach(function (name) {
      if (!map[name]) {
        map[name] = baseUrl + "/plugins/" + name + "/plugin" + (cfg.suffix || SUFFIX) + ".js"
      }
    })

    cfg.external_plugins = map
  })(BASE)

  // Expose the base object globally so local configs can merge with it.
  window.CHAMILO_TINYMCE_BASE_CONFIG = BASE

  // ── Builder: merges local editor config with this base, per policy
  window.buildTinyMceConfig = function (local) {
    var base = window.CHAMILO_TINYMCE_BASE_CONFIG || {}
    var localConfig = Object.assign({}, local || {})
    var features = Object.assign({}, localConfig.chamiloEditorFeatures || {})

    delete localConfig.chamiloEditorFeatures

    var merged = Object.assign({}, base, localConfig)

    var basePlugins = base.plugins || ""
    var localPlugins = localConfig.plugins || ""

    merged.plugins =
      PLUGINS_POLICY === "base"
        ? basePlugins
        : Array.from(new Set((basePlugins + " " + localPlugins).trim().split(/\s+/))).join(" ")

    merged.external_plugins = Object.assign({}, base.external_plugins || {}, localConfig.external_plugins || {})

    if (TOOLBAR_POLICY === "base" && base.toolbar) {
      merged.toolbar = base.toolbar
    } else if (base.toolbar && localConfig.toolbar) {
      merged.toolbar = dedupeToolbar(base.toolbar, localConfig.toolbar)
    }

    var csBase = base.content_style || ""
    var csLocal = localConfig.content_style || ""
    merged.content_style = (csBase + " " + csLocal).trim()

    var baseSetup = base.setup
    var localSetup = localConfig.setup
    merged.setup = function (ed) {
      if (typeof baseSetup === "function") {
        try {
          baseSetup(ed)
        } catch (e) {
          // Ignore
        }
      }

      if (typeof localSetup === "function") {
        try {
          localSetup(ed)
        } catch (e) {
          // Ignore
        }
      }
    }

    merged = applyChamiloEditorPolicies(merged, features)

    return merged
  }
})()
