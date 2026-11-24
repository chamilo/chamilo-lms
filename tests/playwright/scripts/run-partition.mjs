#!/usr/bin/env node
//
// Runs ONE partition of the main Playwright batch (the CI "build" job runs one per matrix entry).
//
// Why partitions exist
// --------------------
// The main batch is deliberately serial (`workers: 1`, see playwright.config.ts): the feature files share fixtures,
// above all the TEMP course, so their relative order matters. That made the job take ~5.7h on a GitHub runner, and it
// is cancelled at 6h. Each CI job installs its own Chamilo, so jobs never share a database: the suite can be cut into
// groups that stay serial inside, as long as files that depend on each other stay in the SAME group.
//
//   temp    the files that use the shared TEMP course (cid=3): listed in ci-partitions.json
//   rest-1  every other file up to and including ci-partitions.json's `restSplitAfter` (alphabetical)
//   rest-2  every other file after it
//
// A new feature file needs no entry unless it uses TEMP; this script refuses to run a `rest-*` partition that contains
// a file referring to TEMP/cid=3, so the mistake cannot go unnoticed.
//
// SpecialCase files (@specialcases) and the install/seed scenarios are excluded exactly as `yarn test:playwright` does;
// the "specialcases" job owns the former.
//
// Usage:
//   node tests/playwright/scripts/run-partition.mjs <temp|rest-1|rest-2> [--files] [-- <extra playwright args>]
//   --files  print the feature files of the partition and exit

import { spawnSync } from "node:child_process"
import { readdirSync, readFileSync, statSync } from "node:fs"
import path from "node:path"
import { fileURLToPath } from "node:url"

const here = path.dirname(fileURLToPath(import.meta.url))
const playwrightDir = path.resolve(here, "..")
const repoRoot = path.resolve(playwrightDir, "../..")
const featuresDir = path.join(playwrightDir, "features")

const GREP_INVERT =
  "Installation process|Create tests users successfully|Create a course before testing|Create a private course before testing|Enable group categories before testing|@specialcases"
const USES_TEMP = /\bTEMP\b|cid=3\b/

function featureFiles(dir, prefix = "") {
  const out = []
  for (const name of readdirSync(dir).sort()) {
    const full = path.join(dir, name)
    if (statSync(full).isDirectory()) {
      out.push(...featureFiles(full, `${prefix}${name}/`))
    } else if (name.endsWith(".feature")) {
      out.push(`${prefix}${name}`)
    }
  }
  return out
}

function usesTemp(file) {
  const steps = readFileSync(path.join(featuresDir, file), "utf8")
    .split("\n")
    .filter((line) => /^\s*(Given|When|Then|And|But)\b/.test(line))
    .join("\n")
  return USES_TEMP.test(steps)
}

const args = process.argv.slice(2)
const name = args[0]
const listOnly = args.includes("--files")
const dashDash = args.indexOf("--")
const extra = dashDash >= 0 ? args.slice(dashDash + 1) : []

if (!["temp", "rest-1", "rest-2"].includes(name)) {
  console.error("Usage: run-partition.mjs <temp|rest-1|rest-2> [--files] [-- <extra playwright args>]")
  process.exit(2)
}

const config = JSON.parse(readFileSync(path.join(playwrightDir, "ci-partitions.json"), "utf8"))
const all = featureFiles(featuresDir).filter((file) => !path.basename(file).startsWith("specialCase"))
const tempSet = new Set(config.temp)
const unknown = config.temp.filter((file) => !all.includes(file))
if (unknown.length > 0) {
  console.error(`ci-partitions.json lists files that do not exist: ${unknown.join(", ")}`)
  process.exit(2)
}

const rest = all.filter((file) => !tempSet.has(file))
const stray = rest.filter(usesTemp)
if (stray.length > 0) {
  console.error(
    `These feature files refer to the TEMP course but are not listed under "temp" in ci-partitions.json:\n  ${stray.join("\n  ")}`,
  )
  process.exit(2)
}

let files
if ("temp" === name) {
  files = all.filter((file) => tempSet.has(file))
} else {
  // Alphabetical order of the path, the same order Playwright runs files in.
  const cut = rest.findIndex((file) => file > config.restSplitAfter)
  const splitAt = -1 === cut ? rest.length : cut
  files = "rest-1" === name ? rest.slice(0, splitAt) : rest.slice(splitAt)
}

if (listOnly) {
  console.log(files.join("\n"))
  process.exit(0)
}

const escape = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
const filters = files.map((file) => `/features/${escape(file)}\\.spec\\.js$`)
console.log(`Partition "${name}": ${files.length} feature files`)

const result = spawnSync(
  "node_modules/.bin/playwright",
  ["test", "--config=tests/playwright/playwright.config.ts", "--grep-invert", GREP_INVERT, ...extra, ...filters],
  { cwd: repoRoot, stdio: "inherit" },
)
process.exit(result.status ?? 1)
