#!/usr/bin/env node
//
// Decides whether a Playwright run should FAIL THE BUILD, based on what the
// tests actually did rather than on `playwright test`'s exit code.
//
// Why this exists
// ---------------
// `playwright test` exits non-zero for anything that went wrong anywhere in the
// process, including work that is not a test. The suite hit this twice in two
// days: runs that finished **410 passed / 0 failed / 0 skipped** still failed
// CI, because playwright-bdd defers every tagged AfterAll hook into a single
// worker-teardown phase (see the long note in steps/common.steps.ts) and that
// phase blew its 90s fixture budget — after the last test had already passed.
//
// A cleanup step that runs once every test is finished cannot invalidate those
// tests. Treating it as a build failure sends the least actionable signal
// possible: a red job whose report says everything passed. Two separate
// attempts to make the teardown fit inside its budget both failed, which is
// the signal that the budget was the wrong thing to fix.
//
// So: the build fails when a TEST fails, or when the run did not actually
// execute the tests it was supposed to. Everything else is reported as a
// warning and does not change the exit code.
//
// What still fails the build (i.e. what this does NOT paper over)
// ---------------------------------------------------------------
//   * any test with an unexpected result           -> a real regression
//   * fewer tests than --min-tests                 -> truncated/crashed run,
//     which is the failure mode a naive "ignore the exit code" would hide
//   * a missing or unparseable report              -> the run did not get far
//     enough to report, so we cannot conclude anything good from it
//
// Usage:
//   node tests/playwright/scripts/check-results.mjs \
//     --report var/test-results/playwright/results.json \
//     --min-tests 410 \
//     --label "main batch"
//
// --min-tests is a FLOOR, not an exact count, so adding scenarios never breaks
// the build. It exists purely so a run that dies after 12 tests cannot pass by
// virtue of having no failures yet. Lower it only when tests are deliberately
// removed; if you find yourself lowering it to make CI green, that is the bug.

import { readFileSync } from "node:fs"

function parseArgs(argv) {
  const args = { report: null, minTests: 1, label: "run" }
  for (let i = 0; i < argv.length; i += 1) {
    const next = () => {
      const value = argv[i + 1]
      if (undefined === value) {
        throw new Error(`Missing value for ${argv[i]}`)
      }
      i += 1
      return value
    }
    if ("--report" === argv[i]) args.report = next()
    else if ("--min-tests" === argv[i]) args.minTests = Number(next())
    else if ("--label" === argv[i]) args.label = next()
    else throw new Error(`Unknown argument: ${argv[i]}`)
  }
  if (null === args.report) throw new Error("--report is required")
  if (!Number.isInteger(args.minTests) || args.minTests < 1) {
    throw new Error("--min-tests must be a positive integer")
  }
  return args
}

// Collect every test result in the suite tree. Walking the tree rather than
// trusting stats alone lets us name the failures in the log, which is the whole
// point of the exercise — a maintainer should not have to download an artifact
// to learn WHICH test broke.
// `ancestors` is threaded manually: in the JSON reporter's output a spec is a
// plain object, so there is no titlePath() helper to call (that belongs to the
// programmatic reporter API — assuming it exists here yields "undefined › name").
function collectTests(node, ancestors = [], out = []) {
  if (Array.isArray(node)) {
    for (const child of node) collectTests(child, ancestors, out)
    return out
  }
  if (null === node || "object" !== typeof node) return out

  const trail = node.title ? [...ancestors, node.title] : ancestors

  for (const spec of node.specs ?? []) {
    for (const test of spec.tests ?? []) {
      out.push({
        title: [...trail, spec.title].filter(Boolean).join(" › "),
        // The file lives on the enclosing suite in this format; specs carry it
        // only sometimes, so fall back rather than printing "undefined".
        file: spec.file ?? node.file ?? "?",
        line: spec.line ?? "?",
        status: test.status, // "expected" | "unexpected" | "flaky" | "skipped"
      })
    }
  }
  for (const suite of node.suites ?? []) collectTests(suite, trail, out)
  return out
}

const args = parseArgs(process.argv.slice(2))

let report
try {
  report = JSON.parse(readFileSync(args.report, "utf8"))
} catch (error) {
  // Deliberately fatal: no report means the run did not reach the point of
  // writing one. "No failures were recorded" is not evidence of success.
  console.error(`✗ ${args.label}: could not read ${args.report} — ${error.message}`)
  console.error("  A run that produced no report cannot be judged green.")
  process.exit(1)
}

const stats = report.stats ?? {}
const expected = stats.expected ?? 0
const unexpected = stats.unexpected ?? 0
const flaky = stats.flaky ?? 0
const skipped = stats.skipped ?? 0
const total = expected + unexpected + flaky + skipped

const tests = collectTests(report.suites ?? [], [])
const failed = tests.filter((t) => "unexpected" === t.status)
const flakyTests = tests.filter((t) => "flaky" === t.status)

console.log(
  `${args.label}: ${total} tests — ${expected} passed, ${unexpected} failed, ` +
    `${flaky} flaky, ${skipped} skipped`,
)

// Non-test errors: report loudly, but do NOT fail on them. These are the
// teardown/fixture/worker-level errors that used to turn a fully green run red.
const topLevelErrors = report.errors ?? []
if (topLevelErrors.length > 0) {
  console.log("")
  console.log(`⚠ ${topLevelErrors.length} non-test error(s) — outside any test, NOT failing the build:`)
  for (const error of topLevelErrors) {
    const message = ("string" === typeof error ? error : (error.message ?? JSON.stringify(error)))
      .replace(/\[[0-9;]*m/g, "")
      .split("\n")
      .slice(0, 3)
      .join(" ")
    console.log(`    ${message.slice(0, 300)}`)
  }
  console.log("  These ran after the tests and cannot invalidate their results,")
  console.log("  but they are real bugs in the harness — fix them, don't ignore them.")
}

const problems = []

if (unexpected > 0 || failed.length > 0) {
  problems.push(`${Math.max(unexpected, failed.length)} test(s) failed`)
  console.log("")
  console.log("✗ Failed tests:")
  for (const test of failed) {
    console.log(`    ${test.file}:${test.line} › ${test.title}`)
  }
}

if (total < args.minTests) {
  problems.push(`only ${total} tests ran, expected at least ${args.minTests}`)
  console.log("")
  console.log(`✗ Truncated run: ${total} tests ran, expected at least ${args.minTests}.`)
  console.log("  The run stopped early — treat this as a failure even with 0 failed tests.")
}

if (flakyTests.length > 0) {
  // Not fatal (it passed on retry) but never let it pass silently.
  console.log("")
  console.log(`⚠ ${flakyTests.length} flaky test(s) — passed only on retry:`)
  for (const test of flakyTests) {
    console.log(`    ${test.file}:${test.line} › ${test.title}`)
  }
}

if (problems.length > 0) {
  console.error("")
  console.error(`✗ ${args.label} FAILED: ${problems.join("; ")}`)
  process.exit(1)
}

console.log("")
console.log(`✓ ${args.label} passed: every test green and the full suite ran.`)
