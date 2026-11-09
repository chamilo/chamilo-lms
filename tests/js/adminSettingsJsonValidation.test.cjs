const assert = require("node:assert/strict")
const fs = require("node:fs")
const path = require("node:path")
const test = require("node:test")
const vm = require("node:vm")

const templatePath = path.join(
  __dirname,
  "../../src/CoreBundle/Resources/views/Admin/Settings/json_validation.html.twig",
)
const template = fs.readFileSync(templatePath, "utf8")
const script = template.match(/<script>([\s\S]*?)<\/script>/)?.[1]
assert.ok(script, "The settings JSON validator script must exist")
const javascript = script.replace(/{{\s*'Invalid JSON syntax'\|trans\|e\('js'\)\s*}}/, "Invalid JSON syntax")
new vm.Script(javascript)

function element(id, form, value = "") {
  const attrs = new Map()
  const classes = new Set(["hidden"])
  const listeners = new Map()

  return {
    id,
    form,
    value,
    disabled: false,
    textContent: "",
    focused: false,
    classList: {
      add(value) { classes.add(value) },
      remove(value) { classes.delete(value) },
      contains(value) { return classes.has(value) },
    },
    addEventListener(type, callback) { listeners.set(type, callback) },
    dispatch(type, event = {}) { listeners.get(type)?.(event) },
    getAttribute(name) { return attrs.has(name) ? attrs.get(name) : null },
    setAttribute(name, value) { attrs.set(name, value) },
    removeAttribute(name) { attrs.delete(name) },
    focus() { this.focused = true },
  }
}

function setup(values, { disabled = [], descriptions = {} } = {}) {
  const forms = [element("form-a", null), element("form-b", null)]
  const fields = values.map((value, index) => element(`setting-${index}`, forms[index % 2], value))
  const errors = fields.map((field) => element(`${field.id}_json_validation_error`, null))
  fields.forEach((field, i) => {
    field.disabled = disabled.includes(i)
    if (descriptions[i]) field.setAttribute("aria-describedby", descriptions[i])
  })
  const byId = new Map(errors.map((item) => [item.id, item]))
  const scheduled = new Map()
  let nextId = 1
  const document = {
    readyState: "complete",
    querySelectorAll(selector) {
      assert.equal(selector, 'textarea[data-json-setting="true"]')
      return fields
    },
    getElementById(id) { return byId.get(id) ?? null },
  }
  vm.runInNewContext(javascript, {
    document,
    JSON,
    Map,
    Array,
    setTimeout(callback, delay) {
      assert.equal(delay, 3000, "JSON validation must be delayed by three seconds")
      const id = nextId++
      scheduled.set(id, callback)
      return id
    },
    clearTimeout(id) { scheduled.delete(id) },
  })
  function tick() {
    const callbacks = [...scheduled.values()]
    scheduled.clear()
    callbacks.forEach((callback) => callback())
  }
  function submit(formIndex) {
    const event = { prevented: false, preventDefault() { this.prevented = true } }
    forms[formIndex].dispatch("submit", event)
    return event
  }
  return { fields, errors, forms, tick, scheduled, submit }
}

test("reports invalid JSON only after three seconds and clears the error once fixed", () => {
  const env = setup(["{}"])
  env.tick()
  assert.ok(env.errors[0].classList.contains("hidden"))

  env.fields[0].value = '{"enabled":}'
  env.fields[0].dispatch("input")
  assert.ok(env.errors[0].classList.contains("hidden"))
  env.fields[0].dispatch("input")
  assert.equal(env.scheduled.size, 1, "Repeated input must restart the validation timer")
  env.tick()
  assert.match(env.errors[0].textContent, /^Invalid JSON syntax: /)
  assert.equal(env.fields[0].getAttribute("aria-invalid"), "true")
  assert.equal(env.fields[0].getAttribute("aria-describedby"), env.errors[0].id)

  env.fields[0].value = '{"enabled":true}'
  env.fields[0].dispatch("input")
  env.tick()
  assert.ok(env.errors[0].classList.contains("hidden"))
  assert.equal(env.fields[0].getAttribute("aria-invalid"), null)
  assert.equal(env.fields[0].getAttribute("aria-describedby"), null)
})

test("blocks only the form containing invalid JSON, even if saved immediately", () => {
  const env = setup(['{"invalid":}', '{"valid":true}'])
  const failedSubmit = env.submit(0)
  assert.equal(failedSubmit.prevented, true)
  assert.equal(env.fields[0].focused, true)
  assert.equal(env.submit(1).prevented, false)
})

test("allows an empty optional JSON textarea and preserves aria-describedby", () => {
  const env = setup([""], { descriptions: { 0: "help-text" } })
  assert.equal(env.submit(0).prevented, false)
  env.fields[0].value = "{"
  env.fields[0].dispatch("input")
  env.tick()
  assert.equal(env.fields[0].getAttribute("aria-describedby"), "help-text setting-0_json_validation_error")
  env.fields[0].value = "  "
  env.fields[0].dispatch("input")
  env.tick()
  assert.equal(env.fields[0].getAttribute("aria-describedby"), "help-text")
  assert.equal(env.submit(0).prevented, false)
})

test("does not validate or block disabled JSON settings", () => {
  const env = setup(["{invalid"], { disabled: [0] })
  assert.equal(env.submit(0).prevented, false)
  assert.equal(env.scheduled.size, 0)
})
