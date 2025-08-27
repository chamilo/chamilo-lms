/* For licensing terms, see /license.txt */

;(function (window, $) {
  "use strict"

  /* ---------------------- i18n shim ---------------------- */
  function T(key, fallback, params) {
    try {
      if (window.i18n && window.i18n.global && typeof window.i18n.global.t === "function") {
        return window.i18n.global.t(key, params)
      }
      if (typeof window.t === "function") return window.t(key, params)
      if (typeof window.$t === "function") return window.$t(key, params)
      if (window.Translator && typeof window.Translator.trans === "function") {
        return window.Translator.trans(key, params || {}, "messages")
      }
    } catch (e) {}
    return fallback || key
  }

  /* ---------------------- Helpers ---------------------- */
  function getPointOnImage(referenceElement, x, y) {
    var pointerPosition = { left: x + window.scrollX, top: y + window.scrollY },
      canvasOffset = {
        x: referenceElement.getBoundingClientRect().left + window.scrollX,
        y: referenceElement.getBoundingClientRect().top + window.scrollY,
      }

    return {
      x: Math.round(pointerPosition.left - canvasOffset.x),
      y: Math.round(pointerPosition.top - canvasOffset.y),
    }
  }

  /* ---------------------- Base model ---------------------- */
  var SvgElementModel = function (attributes) {
    this.attributes = attributes
    this.id = 0
    this.name = ""

    this.changeEvent = null
  }
  SvgElementModel.prototype.set = function (key, value) {
    this.attributes[key] = value
    if (this.changeEvent) this.changeEvent(this)
  }
  SvgElementModel.prototype.get = function (key) {
    return this.attributes[key]
  }
  SvgElementModel.prototype.onChange = function (cb) {
    this.changeEvent = cb
  }
  SvgElementModel.decode = function () {
    return new this()
  }
  SvgElementModel.prototype.encode = function () {
    return ""
  }

  /* ---------------------- Path model/view ---------------------- */
  var SvgPathModel = function (attrs) {
    SvgElementModel.call(this, attrs)
  }
  SvgPathModel.prototype = Object.create(SvgElementModel.prototype)
  SvgPathModel.prototype.addPoint = function (x, y) {
    x = parseInt(x)
    y = parseInt(y)

    var points = this.get("points")
    points.push([x, y])

    this.set("points", points)
  }
  SvgPathModel.prototype.encode = function () {
    var paired = []
    this.get("points").forEach(function (p) {
      paired.push(p.join(";"))
    })
    return "P)(" + paired.join(")(")
  }
  SvgPathModel.decode = function (pathInfo) {
    var points = []
    $(pathInfo).each(function (_, point) {
      points.push([point.x, point.y])
    })

    return new SvgPathModel({ points: points })
  }

  var SvgPathView = function (model) {
    var self = this

    this.model = model
    this.model.onChange(function () {
      self.render()
    })

    this.el = document.createElementNS("http://www.w3.org/2000/svg", "path")
    this.el.setAttribute("fill", "transparent")
    this.el.setAttribute("stroke", "red")
    this.el.setAttribute("stroke-width", "3")
  }
  SvgPathView.prototype.render = function () {
    var d = ""

    $.each(this.model.get("points"), function (i, point) {
      d += i === 0 ? "M" : " L "
      d += point[0] + " " + point[1]
    })

    this.el.setAttribute("d", d)

    return this
  }

  /* ---------------------- Text model/view ---------------------- */
  var TextModel = function (userAttributes) {
    var attributes = $.extend({ text: "", x: 0, y: 0, color: "red", fontSize: 20, isNew: false }, userAttributes)
    SvgElementModel.call(this, attributes)
  }
  TextModel.prototype = Object.create(SvgElementModel.prototype)
  TextModel.prototype.encode = function () {
    return "T)(" + this.get("text") + ")(" + this.get("x") + ";" + this.get("y")
  }
  TextModel.decode = function (textInfo) {
    return new TextModel({
      text: textInfo.text,
      x: textInfo.x,
      y: textInfo.y,
      isNew: false,
    })
  }

  var TextView = function (model) {
    var self = this

    this.model = model
    this.model.onChange(function () {
      self.render()
    })

    this.el = document.createElementNS("http://www.w3.org/2000/svg", "text")
    this.el.setAttribute("fill", this.model.get("color"))
    this.el.setAttribute("font-size", this.model.get("fontSize"))
    this.el.setAttribute("stroke", "none")
  }
  TextView.prototype.render = function () {
    this.el.setAttribute("x", this.model.get("x"))
    this.el.setAttribute("y", this.model.get("y"))
    this.el.textContent = this.model.get("text")

    return this
  }

  /* --------- Undo/Redo/Clear --------- */
  var ElementsCollection = function () {
    this.models = []
    this.length = 0
    this.addEvent = null
    this.removeEvent = null
    this.clearEvent = null
    this.undone = []
  }
  ElementsCollection.prototype.add = function (m) {
    m.id = ++this.length
    this.models.push(m)
    if (this.addEvent) this.addEvent(m)
    this.undone = []
  }
  ElementsCollection.prototype.get = function (i) {
    return this.models[i]
  }
  ElementsCollection.prototype.onAdd = function (cb) {
    this.addEvent = cb
  }
  ElementsCollection.prototype.onRemove = function (cb) {
    this.removeEvent = cb
  }
  ElementsCollection.prototype.onClear = function (cb) {
    this.clearEvent = cb
  }
  ElementsCollection.prototype.removeLast = function () {
    if (!this.models.length) return null
    var m = this.models.pop()
    if (this.removeEvent) this.removeEvent(m)
    this.undone.push(m)
    return m
  }
  ElementsCollection.prototype.redo = function () {
    if (!this.undone.length) return null
    var m = this.undone.pop()
    this.models.push(m)
    if (this.addEvent) this.addEvent(m)
    return m
  }
  ElementsCollection.prototype.clear = function () {
    if (!this.models.length) return
    var old = this.models.slice()
    this.models = []
    if (this.clearEvent) this.clearEvent(old)
    this.undone = []
  }

  /* ---------------------- Canvas ---------------------- */
  var AnnotationCanvasView = function (elementsCollection, image, questionId) {
    var self = this

    this.questionId = parseInt(questionId)
    this.image = image

    this.el = document.createElementNS("http://www.w3.org/2000/svg", "svg")
    this.el.setAttribute("version", "1.1")
    this.el.setAttribute("viewBox", "0 0 " + this.image.width + " " + this.image.height)
    this.el.setAttribute("width", this.image.width)
    this.el.setAttribute("height", this.image.height)

    var svgImage = document.createElementNS("http://www.w3.org/2000/svg", "image")
    svgImage.setAttributeNS("http://www.w3.org/1999/xlink", "href", this.image.src)
    svgImage.setAttribute("width", this.image.width)
    svgImage.setAttribute("height", this.image.height)

    this.el.appendChild(svgImage)

    this.$el = $(this.el)

    this.elementsCollection = elementsCollection
    this._views = {}
    this._inputs = {}
    this._textToolbarItems = {} // id -> {li, labelSpan}

    this.elementsCollection.onAdd(function (m) {
      self.renderElement(m)
      self._updateToolbarButtons()
    })
    this.elementsCollection.onRemove(function (m) {
      self._removeElementById(m.id)
      self._updateToolbarButtons()
    })
    this.elementsCollection.onClear(function (ms) {
      for (var i = 0; i < ms.length; i++) self._removeElementById(ms[i].id)
      self._updateToolbarButtons()
    })

    this.$rdbOptions = null
    this.$toolbarRoot = null
    this.$btnUndo = null
    this.$btnRedo = null
    this.$btnClear = null
  }

  AnnotationCanvasView.prototype.render = function () {
    this.setEvents()
    this.$rdbOptions = $('[name="' + this.questionId + '-options"]')
    this._ensureToolbar()
    this._updateToolbarButtons()
    return this
  }

  AnnotationCanvasView.prototype._ensureToolbar = function () {
    var qid = this.questionId
    var $ul = $("#annotation-toolbar-" + qid + " ul")
    if ($ul.length === 0) {
      var $host = $("#annotation-toolbar-" + qid)
      if ($host.length === 0) {
        $host = $('<div id="annotation-toolbar-' + qid + '"></div>')
        this.$el.before($host)
      }
      $ul = $('<ul class="list-none p-0 m-0"></ul>')
      $host.append($ul)
    }
    this.$toolbarRoot = $ul

    this._ensureModeLabels()
    var $btnWrap = $('<li class="mt-2"><div class="flex gap-2"></div></li>')
    this.$toolbarRoot.append($btnWrap)

    var self = this
    function makeBtn(text, id, handler, title) {
      var $b = $('<button type="button" id="' + id + '" title="' + (title || text) + '">' + text + "</button>")
      $b.addClass(
        "px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium shadow-sm disabled:opacity-40 disabled:cursor-not-allowed",
      )
      $b.on("click", function (e) {
        e.preventDefault()
        handler()
      })
      return $b
    }

    this.$btnUndo = makeBtn(
      "↶ " + T("annotation.undo", "Undo"),
      "annotation-undo-" + qid,
      this.elementsCollection.removeLast.bind(this.elementsCollection),
      T("annotation.undo", "Undo"),
    )
    this.$btnRedo = makeBtn(
      "↷ " + T("annotation.redo", "Redo"),
      "annotation-redo-" + qid,
      this.elementsCollection.redo.bind(this.elementsCollection),
      T("annotation.redo", "Redo"),
    )
    this.$btnClear = makeBtn(
      "✕ " + T("annotation.clear", "Clear"),
      "annotation-clear-" + qid,
      this.elementsCollection.clear.bind(this.elementsCollection),
      T("annotation.clear", "Clear all"),
    )

    $btnWrap.find("div").append(this.$btnUndo, this.$btnRedo, this.$btnClear)
  }

  AnnotationCanvasView.prototype._updateToolbarButtons = function () {
    if (!this.$btnUndo || !this.$btnRedo || !this.$btnClear) return
    var hasAny = this.elementsCollection.models.length > 0
    var canRedo = this.elementsCollection.undone.length > 0
    this.$btnUndo.prop("disabled", !hasAny)
    this.$btnClear.prop("disabled", !hasAny)
    this.$btnRedo.prop("disabled", !canRedo)
  }

  AnnotationCanvasView.prototype._removeElementById = function (id) {
    var view = this._views[id]
    if (view && view.el && view.el.parentNode) view.el.parentNode.removeChild(view.el)
    delete this._views[id]

    var ins = this._inputs[id]
    if (ins && ins.length)
      for (var i = 0; i < ins.length; i++)
        try {
          ins[i].remove()
        } catch (e) {}
    delete this._inputs[id]

    var item = this._textToolbarItems[id]
    if (item && item.li && item.li.length) {
      try {
        item.li.remove()
      } catch (e) {}
    }
    delete this._textToolbarItems[id]
  }

  AnnotationCanvasView.prototype._promptText = function (model) {
    var current = model.get("text") || ""
    var out = window.prompt(T("annotation.enter_text", "Type your text"), current)
    if (out !== null) model.set("text", out)
  }

  AnnotationCanvasView.prototype._ensureTextToolbarItem = function (model) {
    var item = this._textToolbarItems[model.id]
    var self = this

    var $ul = $("#annotation-toolbar-" + this.questionId + " ul")
    if ($ul.length === 0) $ul = this.$toolbarRoot
    if (!$ul || !$ul.length) return

    if (!item) {
      var $li = $('<li class="mt-1"></li>')
      var $wrap = $(
        '<div class="inline-flex items-center gap-2 px-2 py-1 rounded-lg bg-slate-50 ring-1 ring-slate-200"></div>',
      )

      var btnBase = "px-2 py-1 rounded-md bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-medium"
      var $btnText = $(
        '<button type="button" class="' + btnBase + '" title="' + T("annotation.text", "Text") + '">A</button>',
      )
      var $label = $('<span class="text-slate-600 text-sm max-w-[220px] truncate"></span>').text(
        model.get("text") ? model.get("text") : T("annotation.text", "Text"),
      )
      var $btnEdit = $(
        '<button type="button" class="' +
        btnBase +
        '" title="' +
        T("common.edit", "Edit") +
        '">✎</button>',
      )

      $btnText.on("click", function (e) {
        e.preventDefault()
        self._promptText(model)
      })
      $btnEdit.on("click", function (e) {
        e.preventDefault()
        self._promptText(model)
      })

      $wrap.append($btnText, $label, $btnEdit)
      $li.append($wrap)
      $ul.append($li)

      this._textToolbarItems[model.id] = { li: $li, label: $label }
    } else {
      var txt = model.get("text") || T("annotation.text", "Text")
      item.label.text(txt)
    }
  }

  AnnotationCanvasView.prototype.setEvents = function () {
    var self = this,
      isMoving = false,
      elementModel = null

    self.$el
      .on("dragstart", function (e) {
        e.preventDefault()
      })
      .on("click", function (e) {
        e.preventDefault()
        if ("1" !== self.$rdbOptions.filter(":checked").val()) return
        var p = getPointOnImage(self.el, e.clientX, e.clientY)
        elementModel = new TextModel({ x: p.x, y: p.y, text: "", isNew: true })
        self.elementsCollection.add(elementModel)
        self._promptText(elementModel)
        elementModel = null
        isMoving = false
      })
      .on("mousedown", function (e) {
        e.preventDefault()
        var p = getPointOnImage(self.el, e.clientX, e.clientY)
        if (isMoving || "0" !== self.$rdbOptions.filter(":checked").val() || elementModel) return
        elementModel = new SvgPathModel({ points: [[p.x, p.y]] })
        self.elementsCollection.add(elementModel)
        isMoving = true
      })
      .on("mousemove", function (e) {
        e.preventDefault()
        if (!isMoving || "0" !== self.$rdbOptions.filter(":checked").val() || !elementModel) return
        var p = getPointOnImage(self.el, e.clientX, e.clientY)
        elementModel.addPoint(p.x, p.y)
      })
      .on("mouseup", function (e) {
        e.preventDefault()
        if (!isMoving || "0" !== self.$rdbOptions.filter(":checked").val() || !elementModel) return
        elementModel = null
        isMoving = false
      })
  }

  AnnotationCanvasView.prototype.renderElement = function (elementModel) {
    var elementView = null,
      self = this

    if (elementModel instanceof SvgPathModel) elementView = new SvgPathView(elementModel)
    else if (elementModel instanceof TextModel) elementView = new TextView(elementModel)
    if (!elementView) return

    var $inChoice = $("<input>")
      .attr({ type: "hidden", name: "choice[" + this.questionId + "][" + elementModel.id + "]" })
      .val(elementModel.encode())
      .appendTo(this.el.parentNode)
    var $inHotspot = $("<input>")
      .attr({ type: "hidden", name: "hotspot[" + this.questionId + "][" + elementModel.id + "]" })
      .val(elementModel.encode())
      .appendTo(this.el.parentNode)
    this._inputs[elementModel.id] = [$inChoice, $inHotspot]

    this.el.appendChild(elementView.render().el)
    this._views[elementModel.id] = elementView

    elementModel.onChange(function () {
      elementView.render()
      $('input[name="choice[' + self.questionId + "][" + elementModel.id + ']"]').val(elementModel.encode())
      $('input[name="hotspot[' + self.questionId + "][" + elementModel.id + ']"]').val(elementModel.encode())
      if (elementModel instanceof TextModel) self._ensureTextToolbarItem(elementModel)
    })

    if (elementModel instanceof TextModel) {
      self._ensureTextToolbarItem(elementModel)
    }
  }

  AnnotationCanvasView.prototype._ensureModeLabels = function () {
    var qid = this.questionId
    var $inputs = $('input[name="' + qid + '-options"]')

    if (!$inputs.length) return

    $inputs.each(function () {
      var $input = $(this)
      var $label = $input.closest('label')

      if ($label.find('.ann-mode-text').length) return

      var txt =
        $input.val() === "0"
          ? T("annotation.mode.draw", "Draw")
          : T("annotation.mode.text", "Text")

      $('<span class="ann-mode-text ml-2 text-slate-700 text-sm"></span>')
        .text(txt)
        .appendTo($label)
    })
  }

  /* ---------------------- Entry point ---------------------- */
  window.AnnotationQuestion = function (userSettings) {
    $(function () {
      var settings = $.extend({ questionId: 0, exerciseId: 0, relPath: "/" }, userSettings)
      var xhrUrl = "exercise/annotation_user.php?"
      var $container = $("#annotation-canvas-" + settings.questionId)

      $.getJSON(settings.relPath + xhrUrl, {
        question_id: parseInt(settings.questionId),
        exe_id: parseInt(settings.exerciseId),
        course_id: parseInt(settings.courseId),
      }).done(function (questionInfo) {
        var image = new Image()
        image.onload = function () {
          var col = new ElementsCollection(),
            canvas = new AnnotationCanvasView(col, this, settings.questionId)

          $container.html(canvas.render().el)

          $.each(questionInfo.answers.paths, function (_, p) {
            col.add(SvgPathModel.decode(p))
          })
          $(questionInfo.answers.texts).each(function (_, t) {
            col.add(TextModel.decode(t))
          })
        }
        image.src = questionInfo.image.path
      })
    })
  }
})(window, window.jQuery)
