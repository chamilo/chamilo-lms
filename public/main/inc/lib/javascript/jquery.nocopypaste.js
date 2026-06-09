/**
 * When included, this snippet prevents contextual menus and keystrokes that
 * make it possible to cut, copy or paste text from the page.
 * This is useful for very secure exams.
 *
 * The file name is kept for backward compatibility with legacy includes, but
 * the implementation does not depend on jQuery.
 *
 * @author Alberto Torreblanca
 */
;(function () {
  "use strict"

  const blockedKeys = new Set(["c", "x", "v", "p", "s"])
  const blockedEvents = ["cut", "copy", "paste", "drop", "contextmenu"]
  const tinyGuardKey = "__chamiloCopyPasteBlockerAttached"
  const documentGuardKey = "__chamiloCopyPasteDocumentBlockerAttached"
  const inputTypes = new Set(["insertFromPaste", "insertFromPasteAsQuotation", "insertFromDrop", "insertReplacementText"])

  let tinyPatchAttempts = 0
  const maxTinyPatchAttempts = 80

  function preventAction(event) {
    if (event && typeof event.preventDefault === "function") {
      event.preventDefault()
    }

    if (event && typeof event.stopPropagation === "function") {
      event.stopPropagation()
    }

    if (event && typeof event.stopImmediatePropagation === "function") {
      event.stopImmediatePropagation()
    }

    return false
  }

  function getPressedKey(event) {
    const key = String(event?.key || "").toLowerCase()

    if (key) {
      return key
    }

    const code = event?.keyCode || event?.which || 0

    return String.fromCharCode(code).toLowerCase()
  }

  function isBlockedShortcut(event) {
    if (!event?.ctrlKey && !event?.metaKey) {
      return false
    }

    return blockedKeys.has(getPressedKey(event))
  }

  function isBlockedBeforeInput(event) {
    const inputType = String(event?.inputType || "")

    return inputTypes.has(inputType) || inputType.startsWith("insertFromPaste") || inputType.startsWith("insertFromDrop")
  }

  function attachDocumentBlocker(doc) {
    if (!doc || doc[documentGuardKey]) {
      return
    }

    doc[documentGuardKey] = true

    blockedEvents.forEach(function (eventName) {
      doc.addEventListener(eventName, preventAction, true)
    })

    doc.addEventListener(
      "keydown",
      function (event) {
        if (isBlockedShortcut(event)) {
          preventAction(event)
        }
      },
      true,
    )

    doc.addEventListener(
      "beforeinput",
      function (event) {
        if (isBlockedBeforeInput(event)) {
          preventAction(event)
        }
      },
      true,
    )
  }

  function notifyTinyEditor(editor) {
    try {
      editor.notificationManager.open({
        text: "Copy and paste is disabled in this editor.",
        type: "warning",
        timeout: 2000,
      })
    } catch (e) {
      // Ignore notification errors.
    }
  }

  function preventTinyEditorAction(editor, event) {
    notifyTinyEditor(editor)

    return preventAction(event)
  }

  function attachTinyEditorBlocker(editor) {
    if (!editor || editor[tinyGuardKey]) {
      return
    }

    editor[tinyGuardKey] = true

    const attachEditorDocument = function () {
      try {
        attachDocumentBlocker(editor.getDoc())
      } catch (e) {
        // Ignore TinyMCE document access errors.
      }

      try {
        const body = editor.getBody()

        if (body && !body.__chamiloCopyPasteBodyBlockerAttached) {
          body.__chamiloCopyPasteBodyBlockerAttached = true

          blockedEvents.forEach(function (eventName) {
            body.addEventListener(
              eventName,
              function (event) {
                preventTinyEditorAction(editor, event)
              },
              true,
            )
          })
        }
      } catch (e) {
        // Ignore TinyMCE body access errors.
      }
    }

    editor.on("init", attachEditorDocument)
    editor.on("focus", attachEditorDocument)
    editor.on("keydown", function (event) {
      if (isBlockedShortcut(event)) {
        preventTinyEditorAction(editor, event)
      }
    })

    editor.on("BeforeExecCommand", function (event) {
      const command = String(event?.command || "").toLowerCase()

      if (command.includes("paste") || command.includes("copy") || command.includes("cut")) {
        preventTinyEditorAction(editor, event)
      }
    })

    editor.on("PastePreProcess", function (event) {
      if (event) {
        event.content = ""
      }

      preventTinyEditorAction(editor, event)
    })

    editor.on("drop", function (event) {
      preventTinyEditorAction(editor, event)
    })

    editor.on("beforeinput", function (event) {
      if (isBlockedBeforeInput(event)) {
        preventTinyEditorAction(editor, event)
      }
    })

    attachEditorDocument()
  }

  function attachExistingTinyEditors(tinymce) {
    try {
      const editorCollection = tinymce?.editors || []
      const editors = Array.isArray(editorCollection)
        ? editorCollection
        : Object.keys(editorCollection).map(function (key) {
            return editorCollection[key]
          })

      editors.forEach(attachTinyEditorBlocker)

      if (tinymce?.activeEditor) {
        attachTinyEditorBlocker(tinymce.activeEditor)
      }
    } catch (e) {
      // Ignore optional TinyMCE integration errors.
    }
  }

  function patchTinyMceInit(tinymce) {
    if (!tinymce || tinymce.__chamiloCopyPasteInitPatched || typeof tinymce.init !== "function") {
      return
    }

    const originalInit = tinymce.init.bind(tinymce)
    tinymce.__chamiloCopyPasteInitPatched = true

    tinymce.init = function (config) {
      const currentSetup = config && typeof config.setup === "function" ? config.setup : null
      const nextConfig = Object.assign({}, config || {})

      nextConfig.paste_block_drop = true
      nextConfig.chamiloEditorFeatures = Object.assign({}, nextConfig.chamiloEditorFeatures || {}, {
        disableCopyPaste: true,
      })

      nextConfig.setup = function (editor) {
        attachTinyEditorBlocker(editor)

        if (currentSetup) {
          currentSetup(editor)
        }
      }

      const initResult = originalInit(nextConfig)

      try {
        if (initResult && typeof initResult.then === "function") {
          initResult.then(function (editors) {
            if (Array.isArray(editors)) {
              editors.forEach(attachTinyEditorBlocker)
            }

            attachExistingTinyEditors(tinymce)
          })
        }
      } catch (e) {
        // Ignore Promise handling errors.
      }

      return initResult
    }
  }

  function attachTinyMceBlockers() {
    const tinymce = window.tinymce || window.tinyMCE

    if (!tinymce) {
      return false
    }

    patchTinyMceInit(tinymce)
    attachExistingTinyEditors(tinymce)

    return true
  }

  function scheduleTinyMceBlockers() {
    tinyPatchAttempts += 1
    attachTinyMceBlockers()

    if (tinyPatchAttempts < maxTinyPatchAttempts) {
      window.setTimeout(scheduleTinyMceBlockers, 250)
    }
  }

  function boot() {
    attachDocumentBlocker(document)
    scheduleTinyMceBlockers()
  }

  if ("loading" === document.readyState) {
    document.addEventListener("DOMContentLoaded", boot)
  } else {
    boot()
  }
})()
