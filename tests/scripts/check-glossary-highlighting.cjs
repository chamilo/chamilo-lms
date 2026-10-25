/* Regression check for automatic glossary HTML annotation without a browser. */
"use strict"

const assert = require("node:assert/strict")
const fs = require("node:fs")
const path = require("node:path")
const vm = require("node:vm")

const source = fs.readFileSync(path.resolve(__dirname, "../../assets/js/glossary-auto.js"), "utf8")

function createDocument(content) {
  const result = []
  const paragraph = {
    nodeName: "P",
    closest: () => null,
    replaceChild(fragment) {
      result.push(...fragment.children)
    },
  }
  const original = { nodeValue: content, parentNode: paragraph }
  let listener = null

  return {
    result,
    get readyState() {
      return "loading"
    },
    addEventListener(event, callback) {
      if (event === "DOMContentLoaded") {
        listener = callback
      }
    },
    get onReady() {
      return listener
    },
    body: {},
    createTreeWalker(_root, _nodeType, filter) {
      let consumed = false
      return {
        nextNode() {
          if (consumed) {
            return null
          }
          consumed = true
          return filter.acceptNode(original) === 1 ? original : null
        },
      }
    },
    createDocumentFragment() {
      return {
        children: [],
        appendChild(child) {
          this.children.push(child)
        },
      }
    },
    createTextNode(text) {
      return { textContent: text, nodeName: "#text" }
    },
    createElement(tag) {
      const attrs = {}
      return {
        nodeName: tag.toUpperCase(),
        className: "",
        textContent: "",
        dataset: {},
        attrs,
        setAttribute(name, value) {
          attrs[name] = value
        },
      }
    },
  }
}

async function annotate(content, terms) {
  const document = createDocument(content)
  const requests = []
  const config = {
    courseId: 10,
    sessionId: 0,
    resourceNodeParentId: 123,
    termsEndpoint: "/api/glossaries",
  }
  const context = {
    document,
    window: { chamiloGlossaryConfig: config },
    NodeFilter: { SHOW_TEXT: 4, FILTER_ACCEPT: 1, FILTER_REJECT: 2 },
    URLSearchParams,
    console: { log() {}, warn() {}, error() {} },
    fetch: async (url) => {
      requests.push(url)
      return { ok: true, json: async () => terms }
    },
  }
  vm.runInNewContext(source, context, { filename: "glossary-auto.js" })
  assert.equal(typeof document.onReady, "function")
  await document.onReady()
  assert.deepEqual(requests, ["/api/glossaries?resourceNode.parent=123&cid=10"])
  return document.result
}

async function main() {
  const annotated = await annotate(
    "El árbol da sombra; ARBOL no es árbol. El aprendizaje automático supera al aprendizaje.",
    [
      { title: "árbol", description: "Planta leñosa" },
      { title: "aprendizaje", description: "Proceso" },
      { title: "aprendizaje automático", description: "Machine learning" },
    ],
  )
  const marked = annotated.filter((n) => n.className === "glossary-term")
  assert.deepEqual(
    marked.map((n) => n.textContent),
    ["árbol", "árbol", "aprendizaje automático", "aprendizaje"],
  )
  assert.equal(marked[0].attrs.title, "Planta leñosa")
  assert.equal(marked[2].attrs.title, "Machine learning")
  assert.equal(
    annotated.map((n) => n.textContent).join(""),
    "El árbol da sombra; ARBOL no es árbol. El aprendizaje automático supera al aprendizaje.",
  )

  const distinct = await annotate("árboles, cárbol, ÁRBOL y árbol", [{ title: "árbol", description: "Definition" }])
  assert.deepEqual(
    distinct.filter((n) => n.className === "glossary-term").map((n) => n.textContent),
    ["ÁRBOL", "árbol"],
  )

  console.log("Glossary auto-highlighting: unicode boundaries, longer terms and unchanged text OK")
}

main().catch((error) => {
  console.error(error)
  process.exitCode = 1
})
