/**
 * Check that every Vue route which renders a breadcrumb declares its label.
 *
 * The breadcrumb component falls back to the route name when `meta.breadcrumb` is missing. A
 * route name is a technical identifier, not a translation key, so that fallback shows raw text
 * such as "Ctoolintro" in the interface. The component warns in the browser console, but nobody
 * reads a console during a release. This script turns the same check into a build failure.
 *
 * It parses every file in assets/vue/router/ (except index.js, which holds the assembly), walks
 * each route tree, and reports two kinds of gap on the routes whose merged meta asks for a
 * breadcrumb:
 *   - a named root route with no `meta.breadcrumb`, used as the tool crumb;
 *   - a leaf route with no `meta.breadcrumb`.
 *
 * Usage: node tests/scripts/check-breadcrumb-routes.cjs
 * Exit code 0 when every route declares its label, 1 otherwise.
 */

const fs = require("fs")
const path = require("path")
const vm = require("vm")

const routerDir = path.resolve(__dirname, "../../assets/vue/router")

/**
 * Evaluate a router module in a sandbox and return the route tree it exports.
 *
 * The files are ES modules whose only imports are Vue components, so the imports are stripped and
 * the dynamic `import()` calls are replaced by resolved promises. Nothing is rendered.
 *
 * @param {string} file - File name inside the router directory.
 * @returns {object|null} The exported route object, or `null` when the file cannot be evaluated.
 */
function loadRouteTree(file) {
  let source = fs.readFileSync(path.join(routerDir, file), "utf8")
  const imported = []

  source = source.replace(/^import\s+(\w+)\s+from\s+.*$/gm, (match, name) => {
    imported.push(name)

    return ""
  })
  source = source.replace(/^import\s+.*$/gm, "")
  source = source.replace(/\bimport\(/g, "Promise.resolve(")
  source = source.replace(/export default/, "module.exports =")

  const sandbox = { module: { exports: {} } }

  imported.forEach((name) => {
    sandbox[name] = null
  })

  try {
    vm.runInNewContext(source, sandbox)
  } catch (error) {
    console.error(`[breadcrumb-routes] cannot parse ${file}: ${error.message}`)

    return null
  }

  return sandbox.module.exports
}

/**
 * Walk one route tree and collect the routes that render a breadcrumb without declaring a label.
 *
 * @param {string} file - File name, used in the report.
 * @param {object} root - The route tree exported by that file.
 * @param {Array} gaps - Accumulator the findings are pushed into.
 * @returns {void}
 */
function collectGaps(file, root, gaps) {
  const declaresLabel = (node) => Boolean(node && node.meta && "breadcrumb" in node.meta)

  const walk = (node, parentPath, inheritedMeta) => {
    if (!node) {
      return
    }

    const fullPath = (parentPath + "/" + (node.path || "")).replace(/\/+/g, "/")
    const meta = { ...inheritedMeta, ...(node.meta || {}) }

    if (node.children && node.children.length > 0) {
      node.children.forEach((child) => walk(child, fullPath, meta))

      return
    }

    if (!meta.showBreadcrumb) {
      return
    }

    if (root.name && !declaresLabel(root)) {
      gaps.push(`${file}: root route "${root.name}" has no meta.breadcrumb`)
    }

    if (!declaresLabel(node)) {
      gaps.push(`${file}: route "${node.name}" (${fullPath}) has no meta.breadcrumb`)
    }
  }

  walk(root, "", {})
}

const gaps = []

fs.readdirSync(routerDir)
  .filter((file) => file.endsWith(".js") && "index.js" !== file)
  .forEach((file) => {
    const root = loadRouteTree(file)

    if (root) {
      collectGaps(file, root, gaps)
    }
  })

const unique = [...new Set(gaps)]

if (0 === unique.length) {
  console.log("[breadcrumb-routes] every route that renders a breadcrumb declares its label")
  process.exit(0)
}

console.error("[breadcrumb-routes] routes render a breadcrumb without declaring meta.breadcrumb:")
unique.forEach((gap) => console.error("  " + gap))
console.error("\nDeclare meta.breadcrumb with a translation key on each route listed above.")
process.exit(1)
