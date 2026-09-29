(function () {

  // Escapes a raw LaTeX source string for safe reuse as an HTML attribute value.
  // The same string is later read back via getAttribute() and must never be
  // assigned to innerHTML directly (see renderMathInEditor/cleanMathHTML below) —
  // only to textContent, which is what actually keeps this safe end to end.
  function escapeHTML(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Returns the window of the editor iframe, or null when there is none.
  //
  // Some legacy pages hand TinyMCE a document that is no longer attached to a
  // browsing context (editor.getDoc() !== iframe.contentDocument, confirmed on
  // main/admin/system_announcements.php, and true there with or without this
  // plugin). A <script> appended to such a document never runs, and MathJax
  // itself throws on the missing window. Every caller below skips its work
  // instead: the formula is still stored, and the page-wide renderer
  // (assets/js/mathjax-render.js) still typesets it wherever it is displayed.
  function getIframeWindow(iframeDoc) {
    return (iframeDoc && iframeDoc.defaultView) || null;
  }

  // Loads MathJax inside the editor iframe, with its own configuration.
  async function ensureMathJaxInIframe(iframeDoc) {
    const iframeWin = getIframeWindow(iframeDoc);

    if (!iframeWin) {
      return;
    }

    if (!iframeWin.MathJax) {
      iframeWin.MathJax = {
        tex: {
          inlineMath: [['\\(', '\\)']],
          displayMath: [['\\[', '\\]'], ['$$', '$$']]
        },
        options: {
          skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
          ignoreHtmlClass: 'math-latex'
        },
        startup: {
          typeset: false // Do not typeset the whole iframe on load.
        }
      };
    }

    // Already requested by an earlier call: wait for MathJax to finish starting
    // up, not merely for the file to be there, or typesetPromise may not exist yet.
    if (iframeDoc.getElementById("mathjax-script")) {
      return iframeWin.MathJax && iframeWin.MathJax.startup ? iframeWin.MathJax.startup.promise : undefined;
    }

    const script = iframeDoc.createElement("script");
    script.id = "mathjax-script";
    script.type = "text/javascript";
    script.src = "/build/libs/mathjax/tex-svg.js";
    iframeDoc.head.appendChild(script);

    return new Promise(resolve => {
      script.onload = () => resolve(iframeWin.MathJax?.startup?.promise);
    });
  }

  // Turns raw LaTeX delimiters typed in the editor into math-latex spans.
  function convertFormulasToSpans(editor) {
    const iframeDoc = editor.getDoc();
    let html = iframeDoc.body.innerHTML;
    let hasChanges = false;

    // Protect existing math-latex spans (whole element) before scanning for raw
    // delimiters. cleanMathHTML() nests the formula's own \(...\) source text
    // inside the span (so "View Code" / round-tripping through SetContent shows
    // it), and a bare match.includes('class="math-latex"') check on the *matched
    // substring* can never see that — the class attribute belongs to the
    // enclosing tag, not the delimited text itself — so the regexes below would
    // treat that inner text as a brand-new formula and wrap it a second time,
    // nesting/corrupting the span. Pulling spans out first makes that impossible.
    const protectedSpans = [];
    html = html.replace(/<span class="math-latex"[^>]*>[\s\S]*?<\/span>/g, (match) => {
      protectedSpans.push(match);
      return `\u0000MATHLATEX${protectedSpans.length - 1}\u0000`;
    });

    html = html.replace(/\\\[([\s\S]*?)\\\]/g, (match, latex) => {
      hasChanges = true;
      const escaped = escapeHTML(latex);
      return `<span class="math-latex" contenteditable="false" data-latex="${escaped}"></span>`;
    });

    html = html.replace(/\$\$([\s\S]*?)\$\$/g, (match, latex) => {
      hasChanges = true;
      const escaped = escapeHTML(latex);
      return `<span class="math-latex" contenteditable="false" data-latex="${escaped}"></span>`;
    });

    html = html.replace(/\\\(([\s\S]*?)\\\)/g, (match, latex) => {
      hasChanges = true;
      const escaped = escapeHTML(latex);
      return `<span class="math-latex" contenteditable="false" data-latex="${escaped}"></span>`;
    });

    html = html.replace(/\u0000MATHLATEX(\d+)\u0000/g, (_, i) => protectedSpans[Number(i)]);

    if (hasChanges) {
      iframeDoc.body.innerHTML = html;
    }
  }

  // Typesets every math-latex span that is not rendered yet.
  async function renderMathInEditor(editor) {
    const iframeDoc = editor.getDoc();
    const iframeWin = getIframeWindow(iframeDoc);

    if (!iframeWin?.MathJax?.typesetPromise) return;

    try {
      const spans = iframeDoc.querySelectorAll('span.math-latex');

      spans.forEach(span => {
        // Already rendered.
        if (span.querySelector('mjx-container')) return;

        const latex = span.getAttribute('data-latex');
        if (!latex) return;

        // textContent, never innerHTML: `latex` comes back from getAttribute()
        // as raw unescaped text.
        const renderDiv = iframeDoc.createElement('span');
        renderDiv.className = 'math-rendered';
        renderDiv.textContent = `\\(\\displaystyle ${latex}\\)`;
        span.innerHTML = '';

        // Hidden copy of the source, so the original LaTeX survives a round trip.
        const latexCode = iframeDoc.createElement('span');
        latexCode.style.display = 'none';
        latexCode.className = 'latex-source';
        latexCode.textContent = `\\(${latex}\\)`;

        span.appendChild(latexCode);
        span.appendChild(renderDiv);
      });

      const toRender = iframeDoc.querySelectorAll('.math-rendered:not(.mathjax-processed)');
      if (toRender.length > 0) {
        await iframeWin.MathJax.typesetPromise(Array.from(toRender));
        toRender.forEach(el => el.classList.add('mathjax-processed'));
      }
    } catch (err) {
      console.error('MathJax typeset error:', err);
    }
  }

  tinymce.PluginManager.add("mathjax", function (editor) {

    async function openLatexDialog(initialLatex = "", targetSpan = null) {
      // Start loading MathJax in the main document now: at this point there's
      // usually no `.math-latex` node on the page yet for mathjax-render.js's
      // own lazy-load trigger to react to, so without this the dialog's live
      // preview below would never get a working MathJax.typesetPromise.
      window.__chamiloEnsureMathJaxLoaded?.()

      editor.windowManager.open({
        title: editor.translate("Insert formula"),
        body: {
          type: "panel",
          items: [
            { type: "textarea", name: "latex", label: editor.translate("LaTeX code"), placeholder: "Ex: x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}" },
            { type: "htmlpanel", name: "preview", html: '<div style="padding:10px;border:1px solid #ccc;min-height:60px;background:#f9f9f9;"><strong>'+escapeHTML(editor.translate("Preview"))+'</strong><div id="latex-preview" style="margin-top:10px;"></div></div>' }
          ]
        },
        initialData: { latex: initialLatex },
        buttons: [
          { type: "cancel", text: editor.translate("Cancel") },
          { type: "submit", text: editor.translate("Insert"), primary: true }
        ],
        onChange: async (api, details) => {
          if (details.name === "latex") {
            const formula = api.getData().latex.trim();
            const preview = document.getElementById("latex-preview");
            if (preview) {
              // textContent: `formula` is text the user typed, never HTML.
              if (formula) {
                preview.textContent = `\\[\\displaystyle ${formula}\\]`;
              } else {
                preview.innerHTML = '<em style="color:#999;">'+escapeHTML(editor.translate("Enter a formula"))+'</em>';
              }
              if (window.MathJax?.typesetPromise && formula) {
                try { await window.MathJax.typesetPromise([preview]); } catch (err) { console.error('MathJax preview error:', err); }
              }
            }
          }
        },
        onSubmit: async (api) => {
          const formula = api.getData().latex.trim();
          if (!formula) { api.close(); return; }

          // The formula lives in the span's escaped data-latex attribute. On the
          // display side, MathJax is loaded once per page by
          // assets/js/mathjax-render.js, never through a <script> tag written
          // into the saved content itself.
          const escaped = escapeHTML(formula);
          const htmlToInsert = `<span class="math-latex" contenteditable="false" data-latex="${escaped}">&nbsp;</span>`;
          if (targetSpan) { editor.dom.setOuterHTML(targetSpan, htmlToInsert); }
          else { editor.insertContent(htmlToInsert); }

          await ensureMathJaxInIframe(editor.getDoc());
          await renderMathInEditor(editor);

          api.close();
        }
      });

      setTimeout(async () => {
        const preview = document.getElementById("latex-preview");
        if (preview && initialLatex) {
          // textContent: `initialLatex` comes back from getAttribute(), so it is raw text again.
          preview.textContent = `\\[\\displaystyle ${initialLatex}\\]`;
          if (window.MathJax?.typesetPromise) {
            try { await window.MathJax.typesetPromise([preview]); } catch (err) { console.error('MathJax preview error:', err); }
          }
        }
      }, 150);
    }

    editor.ui.registry.addIcon("sigmaIcon", `<svg viewBox="0 0 256 256" width="24" height="24"><path fill="#000000" d="M184 72V56H80.65l53.6 67a8 8 0 0 1 0 10l-53.6 67H184v-16a8 8 0 0 1 16 0v24a8 8 0 0 1-8 8H64a8 8 0 0 1-6.25-13l60-75l-60-75A8 8 0 0 1 64 40h128a8 8 0 0 1 8 8v24a8 8 0 0 1-16 0"/></svg>`);

    editor.ui.registry.addButton("mathjax", { tooltip: editor.translate("Insert formula"), icon: "sigmaIcon", onAction: () => openLatexDialog() });

    editor.on("click", (e) => {
      const target = e.target;
      let span = target.classList?.contains('math-latex') ? target : target.closest?.('span.math-latex');
      if (span) openLatexDialog(span.getAttribute("data-latex") || "", span);
    });

    function cleanMathHTML(html) {
      const doc = new DOMParser().parseFromString(html, "text/html");
      doc.querySelectorAll("span.math-latex").forEach(span => {
        const latex = span.getAttribute('data-latex');
        // textContent : `latex` vient de getAttribute(), donc à nouveau du texte brut.
        if (latex) span.textContent = `\\(${latex}\\)`;
      });
      return doc.body.innerHTML;
    }

    editor.on('GetContent', e => { if (e?.content) e.content = cleanMathHTML(e.content); });
    editor.on('SaveContent', e => { if (e?.content) e.content = cleanMathHTML(e.content); });

    editor.on('init', async () => {
      await ensureMathJaxInIframe(editor.getDoc());
      const content = editor.getContent();
      if (content && (content.includes('\\[') || content.includes('$$') || content.includes('\\('))) {
        convertFormulasToSpans(editor);
        setTimeout(async () => { await renderMathInEditor(editor); }, 300);
      }
    });

    editor.on('SetContent', async () => {
      convertFormulasToSpans(editor);
      await ensureMathJaxInIframe(editor.getDoc());
      await renderMathInEditor(editor);
    });

  });

})();
