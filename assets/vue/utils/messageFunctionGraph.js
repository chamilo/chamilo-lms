/* For licensing terms, see /license.txt */

// Function graphs are data, not executable HTML. Only a small mathematical
// grammar is accepted; a message never supplies JavaScript or SVG markup.
const FUNCTIONS = {
  sin: Math.sin,
  cos: Math.cos,
  tan: Math.tan,
  sqrt: Math.sqrt,
  abs: Math.abs,
  log: Math.log,
  exp: Math.exp,
}

const SVG_NS = "http://www.w3.org/2000/svg"
const WIDTH = 520
const HEIGHT = 300
const LEFT = 42
const TOP = 18
const PLOT_WIDTH = 456
const PLOT_HEIGHT = 250
const X_LIMIT = 3
const Y_LIMIT = 10
const SELECTOR = "span.ch-message-function-graph[data-expression]"

export function compileMessageGraph(expression) {
  const source = String(expression ?? "")
    .trim()
    .replace(/^y\s*=\s*/i, "")

  if (!source || source.length > 120) {
    throw new Error("Enter a function of x (maximum 120 characters).")
  }

  const tokens = []
  const tokenPattern = /\s*(?:([0-9]+(?:\.[0-9]*)?|\.[0-9]+)|([a-z]+)|([()+\-*/^]))/iy
  let position = 0
  while (position < source.length) {
    tokenPattern.lastIndex = position
    const result = tokenPattern.exec(source)
    if (!result) {
      throw new Error("Unsupported mathematical expression.")
    }
    tokens.push(result[1] || result[2] || result[3])
    position = tokenPattern.lastIndex
  }

  let index = 0
  let nodes = 0
  const next = () => tokens[index]
  const take = () => tokens[index++]
  const node = (kind, ...args) => {
    if (++nodes > 120) {
      throw new Error("The expression is too complex.")
    }
    return [kind, ...args]
  }

  const sum = () => {
    let value = product()
    while (next() === "+" || next() === "-") {
      const operator = take()
      value = node(operator, value, product())
    }
    return value
  }
  const product = () => {
    let value = unary()
    while (next() === "*" || next() === "/") {
      const operator = take()
      value = node(operator, value, unary())
    }
    return value
  }
  const unary = () => {
    if (next() === "+") {
      take()
      return unary()
    }
    if (next() === "-") {
      take()
      return node("neg", unary())
    }
    return power()
  }
  const power = () => {
    let value = atom()
    if (next() === "^") {
      take()
      value = node("^", value, unary())
    }
    return value
  }
  const atom = () => {
    const token = take()
    if (!token) {
      throw new Error("Incomplete mathematical expression.")
    }
    if (token === "(") {
      const value = sum()
      if (take() !== ")") {
        throw new Error("Unbalanced parentheses.")
      }
      return value
    }
    if (/^(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)$/.test(token)) {
      const number = Number(token)
      if (!Number.isFinite(number)) throw new Error("Invalid number.")
      return node("number", number)
    }
    if (token === "x" || token === "pi" || token === "e") {
      return node(token)
    }
    if (Object.hasOwn(FUNCTIONS, token) && next() === "(") {
      take()
      const argument = sum()
      if (take() !== ")") throw new Error("Unbalanced function parentheses.")
      return node("function", token, argument)
    }
    throw new Error("Use x, numbers, operators, or supported functions.")
  }

  const tree = sum()
  if (index !== tokens.length) {
    throw new Error("Unexpected characters in the expression.")
  }

  const evaluate = (term, x) => {
    const [kind, first, second] = term
    switch (kind) {
      case "number":
        return first
      case "x":
        return x
      case "pi":
        return Math.PI
      case "e":
        return Math.E
      case "neg":
        return -evaluate(first, x)
      case "+":
        return evaluate(first, x) + evaluate(second, x)
      case "-":
        return evaluate(first, x) - evaluate(second, x)
      case "*":
        return evaluate(first, x) * evaluate(second, x)
      case "/":
        return evaluate(first, x) / evaluate(second, x)
      case "^":
        return Math.pow(evaluate(first, x), evaluate(second, x))
      case "function":
        return FUNCTIONS[first](evaluate(second, x))
      default:
        throw new Error("Unsupported function.")
    }
  }

  return { expression: source, evaluate: (x) => evaluate(tree, x) }
}

export function getMessageGraphSegments(graph) {
  const segments = []
  let current = []

  // Out-of-range points break the line; poles must never be connected across
  // the graph. This is an intentionally bounded, fixed-size visualization.
  for (let i = 0; i <= 360; i++) {
    const x = -X_LIMIT + (2 * X_LIMIT * i) / 360
    const y = graph.evaluate(x)
    if (!Number.isFinite(y) || Math.abs(y) > Y_LIMIT) {
      if (current.length > 1) segments.push(current)
      current = []
      continue
    }
    const px = LEFT + ((x + X_LIMIT) / (2 * X_LIMIT)) * PLOT_WIDTH
    const py = TOP + ((Y_LIMIT - y) / (2 * Y_LIMIT)) * PLOT_HEIGHT
    current.push([Number(px.toFixed(2)), Number(py.toFixed(2))])
  }
  if (current.length > 1) segments.push(current)
  return segments
}

function appendSvg(doc, parent, element, attributes = {}, text = null) {
  const child = doc.createElementNS(SVG_NS, element)
  for (const [name, value] of Object.entries(attributes)) {
    child.setAttribute(name, String(value))
  }
  if (text !== null) child.textContent = String(text)
  parent.appendChild(child)
  return child
}

function makeGraph(doc, graph, segments) {
  const svg = doc.createElementNS(SVG_NS, "svg")
  svg.setAttribute("class", "ch-message-function-graph-svg")
  svg.setAttribute("viewBox", `0 0 ${WIDTH} ${HEIGHT}`)
  svg.setAttribute("width", String(WIDTH))
  svg.setAttribute("height", String(HEIGHT))
  svg.setAttribute("style", "max-width:100%;height:auto;display:block")
  svg.setAttribute("role", "img")
  svg.setAttribute("aria-label", `Graph of y = ${graph.expression}`)
  appendSvg(doc, svg, "rect", {
    x: LEFT,
    y: TOP,
    width: PLOT_WIDTH,
    height: PLOT_HEIGHT,
    fill: "none",
    stroke: "#a3a3a3",
  })

  for (let tick = -3; tick <= 3; tick++) {
    const px = LEFT + ((tick + X_LIMIT) / (2 * X_LIMIT)) * PLOT_WIDTH
    appendSvg(doc, svg, "line", { x1: px, y1: TOP, x2: px, y2: TOP + PLOT_HEIGHT, stroke: "#e5e7eb" })
    appendSvg(
      doc,
      svg,
      "text",
      { x: px, y: TOP + PLOT_HEIGHT + 16, "text-anchor": "middle", "font-size": 11, fill: "#525252" },
      tick,
    )
  }
  for (let tick = -10; tick <= 10; tick += 5) {
    const py = TOP + ((Y_LIMIT - tick) / (2 * Y_LIMIT)) * PLOT_HEIGHT
    appendSvg(doc, svg, "line", { x1: LEFT, y1: py, x2: LEFT + PLOT_WIDTH, y2: py, stroke: "#e5e7eb" })
    appendSvg(
      doc,
      svg,
      "text",
      { x: LEFT - 7, y: py + 4, "text-anchor": "end", "font-size": 11, fill: "#525252" },
      tick,
    )
  }
  appendSvg(doc, svg, "line", {
    x1: LEFT + PLOT_WIDTH / 2,
    y1: TOP,
    x2: LEFT + PLOT_WIDTH / 2,
    y2: TOP + PLOT_HEIGHT,
    stroke: "#737373",
    "stroke-width": 1.3,
  })
  appendSvg(doc, svg, "line", {
    x1: LEFT,
    y1: TOP + PLOT_HEIGHT / 2,
    x2: LEFT + PLOT_WIDTH,
    y2: TOP + PLOT_HEIGHT / 2,
    stroke: "#737373",
    "stroke-width": 1.3,
  })
  for (const segment of segments) {
    const points = segment.map(([x, y]) => `${x},${y}`).join(" ")
    appendSvg(doc, svg, "polyline", {
      points,
      fill: "none",
      stroke: "#2563eb",
      "stroke-width": 2.5,
      "stroke-linejoin": "round",
    })
  }
  return svg
}

/**
 * Recover previously saved graphs if an HTML serializer removed the span
 * metadata but preserved its plain-text fallback. Only an entire standalone
 * line generated by the graph tool is accepted, never HTML or script markup.
 */
function restorePlainTextMessageGraphs(root) {
  const candidates = [root, ...root.querySelectorAll("p, span, div")]

  candidates.forEach((element) => {
    if (!element?.ownerDocument?.createElement || !element.replaceChildren) return
    if (element.getAttribute?.("data-expression")) return

    // Do not replace a container with meaningful nested content. A trailing
    // <br> is harmless and sometimes inserted by TinyMCE after a graph.
    if (Array.from(element.children || []).some((child) => child.tagName !== "BR")) return

    const match = /^Graph: y = (.{1,120})$/.exec(String(element.textContent || "").trim())
    if (!match) return

    let graph
    try {
      graph = compileMessageGraph(match[1])
      if (!getMessageGraphSegments(graph).length) return
    } catch {
      return
    }

    const placeholder = element.ownerDocument.createElement("span")
    placeholder.setAttribute("class", "ch-message-function-graph")
    placeholder.setAttribute("data-expression", graph.expression)
    placeholder.textContent = `Graph: y = ${graph.expression}`
    element.replaceChildren(placeholder)
  })
}

export function renderMessageFunctionGraphs(root) {
  if (!root) return

  restorePlainTextMessageGraphs(root)

  root.querySelectorAll(SELECTOR).forEach((placeholder) => {
    if (placeholder.querySelector("svg.ch-message-function-graph-svg")) return
    const expression = placeholder.getAttribute("data-expression") || ""
    try {
      const graph = compileMessageGraph(expression)
      const segments = getMessageGraphSegments(graph)
      if (!segments.length) throw new Error("No graph points in the visible range.")
      const doc = placeholder.ownerDocument
      placeholder.replaceChildren(doc.createTextNode(`Graph: y = ${graph.expression}`), makeGraph(doc, graph, segments))
    } catch {
      // Even old or malformed messages only display inert, escaped text.
      placeholder.textContent = "Graph unavailable"
    }
  })
}

// The editor may contain generated SVG previews. Save only a validated
// expression and a text fallback, never client-rendered SVG or user markup.
export function stripMessageFunctionGraphPreviews(html) {
  const parser = new DOMParser()
  const document = parser.parseFromString(String(html ?? ""), "text/html")
  document.querySelectorAll(SELECTOR).forEach((placeholder) => {
    const expression = placeholder.getAttribute("data-expression") || ""
    try {
      const graph = compileMessageGraph(expression)
      placeholder.replaceChildren(document.createTextNode(`Graph: y = ${graph.expression}`))
    } catch {
      placeholder.replaceWith(document.createTextNode("Graph unavailable"))
    }
  })
  return document.body.innerHTML
}
