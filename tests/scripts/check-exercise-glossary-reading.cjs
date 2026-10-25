/* Regression checks for glossary highlighting in progressive reading questions. */
"use strict"

const assert = require("node:assert/strict")
const fs = require("node:fs")
const path = require("node:path")
const vm = require("node:vm")

const playerPath = path.resolve(__dirname, "../../assets/vue/views/exercise/ExercisePlayerView.vue")
const source = fs.readFileSync(playerPath, "utf8")
const start = source.indexOf("function wrapGlossaryTermsInContainer(")
const end = source.indexOf("\nfunction wrapGlossaryTermsInTextNode(", start)
assert.ok(start >= 0 && end > start, "Glossary text walker must be present")

let filter
const NodeFilter = { SHOW_TEXT: 4, FILTER_ACCEPT: 1, FILTER_REJECT: 2 }
const document = {
  createTreeWalker(_root, _type, options) {
    filter = options
    return { nextNode: () => null }
  },
}
const context = { document, NodeFilter }
vm.runInNewContext(source.slice(start, end), context)
context.wrapGlossaryTermsInContainer({}, /AFP/giu, new Map())
assert.equal(typeof filter.acceptNode, "function")

function makeNode(text, visibility, insideLink = false) {
  const readingWord = visibility
    ? { classList: { contains: (name) => name === "exercise-reading-word-active" && visibility === "active" } }
    : null
  return {
    nodeValue: text,
    parentElement: {
      closest(selector) {
        if (selector === ".exercise-reading-word") {
          return readingWord
        }
        if (insideLink && selector.includes("a, button")) {
          return { tagName: "A" }
        }
        return null
      },
    },
  }
}

assert.equal(filter.acceptNode(makeNode("AFP", "blurred")), NodeFilter.FILTER_REJECT)
assert.equal(filter.acceptNode(makeNode("AFP", "border")), NodeFilter.FILTER_REJECT)
assert.equal(filter.acceptNode(makeNode("AFP", "active")), NodeFilter.FILTER_ACCEPT)
assert.equal(filter.acceptNode(makeNode("AFP", null)), NodeFilter.FILTER_ACCEPT)
assert.equal(filter.acceptNode(makeNode("AFP", null, true)), NodeFilter.FILTER_REJECT)
assert.equal(filter.acceptNode(makeNode("Other", "active")), NodeFilter.FILTER_REJECT)

// A new reading step assigns a fresh progress object; the watcher must see it
// so that v-html replacements receive glossary markup again after each tick.
const watcher = source.slice(source.lastIndexOf("watch(\n  () => ["))
assert.match(watcher, /readingProgress\.value/)
assert.match(watcher, /scheduleRuntimeHtmlEnhancement\(\)/)
console.log("Exercise reading glossary: active words highlighted; blurred words protected; progress watched OK")
