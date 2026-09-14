/**
 * Report remaining physical-direction (left/right) CSS in source files that should use
 * logical (start/end) properties instead, so RTL languages (Arabic, Hebrew, Persian,
 * Pashto, Dari) render mirrored instead of staying left-to-right.
 *
 * Chamilo's RTL support relies on `dir="rtl"` being set on <html> (see LanguageHelper::
 * getTextDirection()) plus Tailwind's logical utilities (ms-/me-/ps-/pe-/start-/end-/
 * text-start/text-end/border-s/border-e/rounded-s/rounded-e) and the rtl:/ltr: variants,
 * all built into Tailwind 3.4 with no plugin. A `left-4` or `text-right` never mirrors
 * under `dir="rtl"` -- only its logical equivalent does.
 *
 * This is a burn-down tool, not a merge gate: as of the app-shell and design-system
 * conversion, hundreds of pre-existing occurrences remain in views, legacy templates and
 * plugins, and clearing them is deliberately staged across several follow-up efforts. It
 * is a manual command, not wired into CI (matching check-breadcrumb-routes.cjs).
 *
 * Usage:
 *   node tests/scripts/check-rtl-classes.cjs [path...]
 *   yarn check:rtl-classes [path...]
 * With no path, scans the whole app (assets/, src/CoreBundle/Resources/views/,
 * public/main/, public/plugin/). Pass one or more paths to scope a report to files you
 * just touched.
 * Exit code 0 when nothing is found, 1 otherwise -- informational, not a CI gate.
 *
 * Known blind spot: a 4-value `margin:`/`padding:` shorthand with an asymmetric last
 * value (e.g. `margin: 0 0 0 20px`) is NOT detected -- these need a manual read. Several
 * were found this way during the PR4 sweep (e.g. .scorm_section_level_1..5 in
 * assets/css/scorm.scss). When editing a legacy file with 4-value shorthands, grep for
 * `(margin|padding):\s*\S+\s+\S+\s+\S+\s+\S+` and check each asymmetric one by hand.
 */

const fs = require("fs")
const path = require("path")

const repoRoot = path.resolve(__dirname, "../..")

const defaultRoots = [
  "assets/css/scss",
  "assets/vue",
  "src/CoreBundle/Resources/views",
  "public/main",
  "public/plugin",
]

const scannedExtensions = new Set([".scss", ".css", ".vue", ".js", ".twig", ".php", ".tpl"])

const skippedDirNames = new Set(["node_modules", "vendor", "build", ".git"])

// Each pattern is checked with word/quote-boundary guards baked in, so "border-red-500"
// or "rounded-lg" never false-positive as "border-r" / "rounded-l".
const patterns = [
  { name: "ml-*", regex: /(^|["'\s:])ml-[\w.[\]/-]+/g },
  { name: "mr-*", regex: /(^|["'\s:])mr-[\w.[\]/-]+/g },
  { name: "pl-*", regex: /(^|["'\s:])pl-[\w.[\]/-]+/g },
  { name: "pr-*", regex: /(^|["'\s:])pr-[\w.[\]/-]+/g },
  { name: "left-*", regex: /(^|["'\s:])-?left-[\w.[\]/-]+/g },
  { name: "right-*", regex: /(^|["'\s:])-?right-[\w.[\]/-]+/g },
  { name: "text-left/right", regex: /\btext-(left|right)\b/g },
  { name: "border-l/r", regex: /\bborder-[lr](-[\w-]+)?(?=["'\s]|$)/g },
  { name: "rounded-l/r", regex: /\brounded-[lr](-[\w]+)?(?=["'\s]|$)/g },
  { name: "float-left/right", regex: /\bfloat-(left|right)\b/g },
  { name: "origin-left/right", regex: /\borigin-(left|right)\b/g },
  { name: "margin-left/right", regex: /\bmargin-(left|right)\s*:/g },
  { name: "padding-left/right", regex: /\bpadding-(left|right)\s*:/g },
  { name: "raw left:/right:", regex: /(?<![-\w])(left|right)\s*:\s*[-\d.]/g },
  { name: "text-align: left/right", regex: /text-align\s*:\s*(left|right)/g },
  { name: "border-left/right:", regex: /\bborder-(left|right)(-\w+)?\s*:/g },
  { name: "float: left/right", regex: /float\s*:\s*(left|right)/g },
  { name: "border-*-radius (physical corner)", regex: /\bborder-(top|bottom)-(left|right)-radius\s*:/g },
  { name: "align=\"left/right\" (legacy HTML attribute)", regex: /\balign\s*=\s*["'](left|right)["']/g },
]

/**
 * Recursively collect files with a scanned extension under a directory.
 *
 * @param {string} dir - Absolute directory path.
 * @param {string[]} out - Accumulator the file paths are pushed into.
 * @returns {void}
 */
function collectFiles(dir, out) {
  let entries
  try {
    entries = fs.readdirSync(dir, { withFileTypes: true })
  } catch {
    return
  }

  for (const entry of entries) {
    if (entry.isDirectory()) {
      if (!skippedDirNames.has(entry.name)) {
        collectFiles(path.join(dir, entry.name), out)
      }
      continue
    }

    if (scannedExtensions.has(path.extname(entry.name))) {
      out.push(path.join(dir, entry.name))
    }
  }
}

/**
 * Scan one file's contents and return the offending line numbers grouped by pattern name.
 *
 * @param {string} filePath - Absolute file path.
 * @returns {Array<{line: number, matches: string[]}>} One entry per line with a hit.
 */
function scanFile(filePath) {
  const lines = fs.readFileSync(filePath, "utf8").split("\n")
  const hits = []

  lines.forEach((line, index) => {
    const names = []
    for (const { name, regex } of patterns) {
      regex.lastIndex = 0
      if (regex.test(line)) {
        names.push(name)
      }
    }
    if (names.length > 0) {
      hits.push({ line: index + 1, matches: names })
    }
  })

  return hits
}

const roots = process.argv.slice(2)
const targets = roots.length > 0 ? roots : defaultRoots

const files = []
targets.forEach((target) => {
  const absolute = path.resolve(repoRoot, target)
  const stat = fs.existsSync(absolute) ? fs.statSync(absolute) : null

  if (!stat) {
    return
  }

  if (stat.isDirectory()) {
    collectFiles(absolute, files)
  } else if (scannedExtensions.has(path.extname(absolute))) {
    files.push(absolute)
  }
})

let totalHits = 0
const report = []

files.forEach((filePath) => {
  const hits = scanFile(filePath)
  if (hits.length > 0) {
    totalHits += hits.length
    report.push({ file: path.relative(repoRoot, filePath), hits })
  }
})

if (0 === totalHits) {
  console.log("[rtl-classes] no physical-direction (left/right) CSS found in the scanned files")
  process.exit(0)
}

report
  .sort((a, b) => b.hits.length - a.hits.length)
  .forEach(({ file, hits }) => {
    console.log(`${file} (${hits.length})`)
    hits.forEach(({ line, matches }) => {
      console.log(`  ${line}: ${matches.join(", ")}`)
    })
  })

console.log(`\n[rtl-classes] ${totalHits} physical-direction occurrence(s) in ${report.length} file(s)`)
console.log("Convert to logical properties (ms-/me-/ps-/pe-/start-/end-/text-start/text-end/border-s/border-e/")
console.log("rounded-s/rounded-e) so they mirror under dir=\"rtl\". Numeric/date/currency alignment and an")
console.log("element's own documented multi-corner API (e.g. BaseIcon's --top-left/--top-right badge")
console.log("positions) are legitimate exceptions -- judge each hit, don't convert mechanically.")
process.exit(1)
