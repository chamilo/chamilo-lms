const assert = require("node:assert/strict")
const { copyFileSync, mkdirSync } = require("node:fs")
const { join } = require("node:path")
const { pathToFileURL } = require("node:url")
const { describe, it, before } = require("node:test")

const helperSrc = join(__dirname, "../../assets/vue/utils/introductionPosition.js")
const helperCopy = join("/tmp/jsdomtest", "introductionPosition.mjs")

let normalizeIntroductionPosition
let resolveIntroductionLayout
let INTRODUCTION_POSITION_DEFAULT

before(async () => {
  mkdirSync("/tmp/jsdomtest", { recursive: true })
  copyFileSync(helperSrc, helperCopy)
  const mod = await import(pathToFileURL(helperCopy).href)
  normalizeIntroductionPosition = mod.normalizeIntroductionPosition
  resolveIntroductionLayout = mod.resolveIntroductionLayout
  INTRODUCTION_POSITION_DEFAULT = mod.INTRODUCTION_POSITION_DEFAULT
})

describe("normalizeIntroductionPosition", () => {
  it("keeps allowed values", () => {
    assert.equal(normalizeIntroductionPosition("below"), "below")
    assert.equal(normalizeIntroductionPosition("left"), "left")
    assert.equal(normalizeIntroductionPosition("right"), "right")
    assert.equal(normalizeIntroductionPosition("above"), "above")
  })

  it("falls back to above for missing or invalid values", () => {
    assert.equal(normalizeIntroductionPosition(null), INTRODUCTION_POSITION_DEFAULT)
    assert.equal(normalizeIntroductionPosition(""), INTRODUCTION_POSITION_DEFAULT)
    assert.equal(normalizeIntroductionPosition("diagonal"), INTRODUCTION_POSITION_DEFAULT)
  })
})

describe("resolveIntroductionLayout", () => {
  it("keeps above, below and left on desktop, including when the sequence column is shown", () => {
    assert.equal(resolveIntroductionLayout("above", { showSequence: false, isDesktop: true }), "above")
    assert.equal(resolveIntroductionLayout("below", { showSequence: true, isDesktop: true }), "below")
    assert.equal(resolveIntroductionLayout("left", { showSequence: true, isDesktop: true }), "left")
  })

  it("keeps right on desktop when the sequence column is hidden", () => {
    assert.equal(resolveIntroductionLayout("right", { showSequence: false, isDesktop: true }), "right")
  })

  it("falls back from right to above when the sequence column is shown", () => {
    assert.equal(resolveIntroductionLayout("right", { showSequence: true, isDesktop: true }), "above")
  })

  it("stacks left and right above the tools on smaller screens", () => {
    assert.equal(resolveIntroductionLayout("left", { showSequence: false, isDesktop: false }), "above")
    assert.equal(resolveIntroductionLayout("right", { showSequence: false, isDesktop: false }), "above")
    assert.equal(resolveIntroductionLayout("below", { showSequence: false, isDesktop: false }), "below")
  })
})
