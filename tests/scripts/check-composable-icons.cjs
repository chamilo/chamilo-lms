/* Verify the icon registry against the installed MDI font and compile the affected Vue components. */
"use strict"

const assert = require("node:assert/strict")
const fs = require("node:fs")
const path = require("node:path")

const root = path.resolve(__dirname, "../..")
const base = "assets/vue/components/basecomponents/"
const read = (file) => fs.readFileSync(path.join(root, file), "utf8")

async function main() {
  const registry = read(`${base}ChamiloIcons.js`)
  const { chamiloIconToClass } = await import(`data:text/javascript;base64,${Buffer.from(registry).toString("base64")}`)
  const entries = [...registry.matchAll(/^\s*"([^"]+)":\s*"([^"]*)",/gm)]
  assert.equal(new Set(entries.map((entry) => entry[1])).size, entries.length, "Duplicate registry keys")

  // A captured icon list makes the registry check reproducible without copying node_modules.
  const listIndex = process.argv.indexOf("--mdi-list")
  const installed =
    listIndex >= 0
      ? new Set(
          fs
            .readFileSync(process.argv[listIndex + 1], "utf8")
            .trim()
            .split(/\s+/),
        )
      : new Set(
          [
            ...read("node_modules/@mdi/font/css/materialdesignicons.css").matchAll(/\.mdi-([a-z0-9-]+):{1,2}before/g),
          ].map((match) => match[1]),
        )
  assert.ok(installed.size > 0, "MDI catalogue is empty")
  for (const [key, value] of Object.entries(chamiloIconToClass)) {
    const glyph = value.match(/\bmdi-([a-z0-9-]+)\b/)?.[1]
    assert.ok(glyph && installed.has(glyph), `Invalid or empty MDI mapping: ${key} (${value})`)
  }

  const modifiers = [
    "star",
    "plus",
    "minus",
    "delete",
    "close",
    "arrow-up",
    "arrow-down",
    "arrow-left",
    "arrow-right",
    "eye-on",
    "eye-off",
    "cog",
  ]
  for (const modifier of modifiers) {
    assert.ok(chamiloIconToClass[modifier], `Missing action modifier: ${modifier}`)
  }
  assert.equal(chamiloIconToClass.pencil, chamiloIconToClass.edit)
  assert.equal(chamiloIconToClass.delete, chamiloIconToClass.trash)
  assert.equal(chamiloIconToClass["arrow-left"], "mdi mdi-arrow-left rtl:rotate-180")
  console.log(
    `PASS: ${entries.length} registry keys, no empty or missing glyphs, ${modifiers.length} action modifiers.`,
  )

  if (listIndex >= 0) {
    console.log("Registry-only mode: Vue compilation requires the project's installed dependencies.")
    return
  }

  const { parse, compileScript } = require("@vue/compiler-sfc")
  const components = [
    `${base}BaseIcon.vue`,
    `${base}BaseButton.vue`,
    "assets/vue/views/documents/DocumentsList.vue",
    "assets/vue/views/exercise/ExerciseListView.vue",
    "assets/vue/components/layout/TopbarLoggedIn.vue",
  ]
  for (const file of components) {
    const { descriptor, errors } = parse(read(file), { filename: file })
    assert.deepEqual(errors, [], `SFC parse failed: ${file}`)
    compileScript(descriptor, { id: file, inlineTemplate: true })
    console.log(`PASS: compiled ${file}`)
  }
}

main().catch((error) => {
  console.error(error)
  process.exitCode = 1
})
