import { ref, unref, watch } from "vue"
import * as d3 from "d3"
import { getSkillTree } from "../../services/skillService"
import { useNotification } from "../notification"

let skillWheelInstanceCounter = 0

export function useSkillWheel({ onSkillDetail, rootLabel = "", skillsLabel = "" } = {}) {
  const isLoading = ref(true)
  const currentPath = ref("")
  const breadcrumbs = ref([])
  const visibleSkillCount = ref(0)

  const skillList = ref([])
  const wheelContainer = ref(null)

  const { showErrorNotification } = useNotification()

  const width = 1040
  const outerRadius = width / 2 - 72
  const innerRadius = 142
  const ringGap = 5
  const minimumLabelLength = 62
  const transitionDuration = 560
  const instanceId = `skill-wheel-${++skillWheelInstanceCounter}`

  const basicPalette = ["#dbeafe", "#93c5fd", "#3b82f6", "#1d4ed8", "#1e3a8a"]
  const semanticColors = {
    learning: "#f89406",
    searched: "#b94a48",
    achieved: "#5aa469",
    disabled: "#64748b",
  }

  let treeRoot = null
  let currentNode = null
  let selectedSkillId = null
  let nodeIndex = new Map()
  let perspectiveFrame = null

  const prefersReducedMotion = () => window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches ?? false
  const defId = (name) => `${instanceId}-${name}`

  function transformSkillToWheelItem(
    { id, title, shortCode, status, children = [], hasGradebook, isSearched, isAchievedByUser, description },
    parent,
  ) {
    const item = {
      id,
      name: title,
      shortCode,
      status,
      children: [],
      hasGradebook,
      isSearched,
      isAchievedByUser,
      description,
      parent,
    }

    nodeIndex.set(id, item)
    item.children = children.map((child) => transformSkillToWheelItem(child, item))

    return item
  }

  function buildTree() {
    nodeIndex = new Map()

    treeRoot = {
      id: null,
      name: rootLabel,
      children: [],
      parent: null,
      isSyntheticRoot: true,
    }

    treeRoot.children = unref(skillList).map((skill) => transformSkillToWheelItem(skill, treeRoot))
    currentNode = treeRoot
    selectedSkillId = null
  }

  function getNodeText(node) {
    return node?.name || node?.shortCode || ""
  }

  function getNodePath(node) {
    const names = []
    let current = node

    while (current && !current.isSyntheticRoot) {
      names.unshift(getNodeText(current))
      current = current.parent
    }

    return names.join(" / ")
  }

  function getParentPath(node) {
    const names = []
    let current = node?.parent

    while (current && !current.isSyntheticRoot) {
      names.unshift(getNodeText(current))
      current = current.parent
    }

    return names.join(" / ")
  }

  function countDescendants(node) {
    if (!node) {
      return 0
    }

    let count = 0
    const stack = [...(node.children || [])]

    while (stack.length) {
      const current = stack.pop()
      count += 1
      stack.push(...(current.children || []))
    }

    return count
  }

  function emitSkillDetail(node) {
    if (!onSkillDetail || !node || null === node.id) {
      return
    }

    onSkillDetail({
      id: node.id,
      name: node.name,
      shortCode: node.shortCode || "",
      description: node.description || "",
      parentPath: getParentPath(node),
    })
  }

  function truncateLabel(text, maxLength = 24) {
    const value = String(text || "")

    if (value.length <= maxLength) {
      return value
    }

    return value.substring(0, maxLength) + "…"
  }

  function splitCenterText(text) {
    const words = String(text || "")
      .trim()
      .split(/\s+/)
      .filter(Boolean)

    if (!words.length) {
      return []
    }

    const lines = []
    let line = ""

    for (const word of words) {
      const candidate = line ? `${line} ${word}` : word

      if (candidate.length <= 20 || !line) {
        line = candidate
        continue
      }

      lines.push(line)
      line = word

      if (3 === lines.length) {
        break
      }
    }

    if (line && lines.length < 3) {
      lines.push(line)
    }

    if (3 === lines.length && words.join(" ").length > lines.join(" ").length) {
      lines[2] = truncateLabel(lines[2], 16)
    }

    return lines
  }

  function setCenterLabel(centerText, node) {
    centerText.selectAll("tspan").remove()

    const label = node?.isSyntheticRoot ? rootLabel : getNodeText(node)
    const lines = splitCenterText(label)

    if (!lines.length) {
      return
    }

    const descendantCount = countDescendants(node)
    const hasSubtitle = descendantCount > 0 && skillsLabel
    const startY = -((lines.length - 1) * 13) - (hasSubtitle ? 8 : 0)

    lines.forEach((line, index) => {
      centerText
        .append("tspan")
        .attr("x", 0)
        .attr("y", startY + index * 26)
        .text(line)
    })

    if (hasSubtitle) {
      centerText
        .append("tspan")
        .attr("x", 0)
        .attr("y", startY + lines.length * 26 + 5)
        .attr("font-size", 11)
        .attr("font-weight", 600)
        .attr("fill", "#475569")
        .text(`${descendantCount} ${skillsLabel}`)
    }
  }

  function getSemanticColor(node, depth = 1) {
    if (node.hasGradebook) {
      return semanticColors.learning
    }

    if (node.isSearched) {
      return semanticColors.searched
    }

    if (node.isAchievedByUser) {
      return semanticColors.achieved
    }

    if (!node.status) {
      return semanticColors.disabled
    }

    return basicPalette[Math.min(Math.max(depth - 1, 0), basicPalette.length - 1)]
  }

  function makeGradientId(nodeId, index) {
    return defId(`gradient-${nodeId ?? "root"}-${index}`)
  }

  function colorStops(color) {
    const base = d3.color(color)

    if (!base) {
      return [color, color, color]
    }

    return [base.brighter(0.85).formatHex(), base.formatHex(), base.darker(0.85).formatHex()]
  }

  function readableTextColor(color) {
    const value = d3.rgb(color)
    const luminance = (0.299 * value.r + 0.587 * value.g + 0.114 * value.b) / 255

    return luminance > 0.67 ? "#0f172a" : "#ffffff"
  }

  function updateVisibleMetadata(maxDepth = 0) {
    if (!currentNode) {
      currentPath.value = ""
      breadcrumbs.value = []
      visibleSkillCount.value = 0
      return
    }

    currentPath.value = currentNode.isSyntheticRoot ? "" : getNodePath(currentNode)

    const trail = []
    let breadcrumbNode = currentNode
    while (breadcrumbNode && !breadcrumbNode.isSyntheticRoot) {
      trail.unshift({ id: breadcrumbNode.id, label: getNodeText(breadcrumbNode) })
      breadcrumbNode = breadcrumbNode.parent
    }
    breadcrumbs.value = [{ id: null, label: rootLabel }, ...trail]

    visibleSkillCount.value = countDescendants(currentNode)

    if (wheelContainer.value) {
      wheelContainer.value.dataset.currentSkillId = currentNode.id ? String(currentNode.id) : ""
      wheelContainer.value.dataset.selectedSkillId = selectedSkillId ? String(selectedSkillId) : ""
      wheelContainer.value.dataset.renderMode = "3d"
      wheelContainer.value.dataset.depthLevels = String(maxDepth)
    }
  }

  function createHierarchy() {
    const hierarchy = d3.hierarchy(currentNode, (node) => node.children).sum((node) => (node.children?.length ? 0 : 1))

    return d3.partition().size([2 * Math.PI, hierarchy.height + 1])(hierarchy)
  }

  function arcMidpoint(d, arcInnerRadius, arcOuterRadius) {
    const angle = (d.x0 + d.x1) / 2 - Math.PI / 2
    const radius = (arcInnerRadius(d) + arcOuterRadius(d)) / 2

    return [Math.cos(angle) * radius, Math.sin(angle) * radius]
  }

  function hoverOffset(d) {
    const angle = (d.x0 + d.x1) / 2 - Math.PI / 2
    const distance = 11

    return [Math.cos(angle) * distance, Math.sin(angle) * distance]
  }

  function isRelatedNode(candidate, reference) {
    if (candidate === reference) {
      return true
    }

    return candidate.ancestors().includes(reference) || reference.ancestors().includes(candidate)
  }

  function bindPerspective(interactionSurface, visualLayer) {
    if (!interactionSurface || !visualLayer) {
      return
    }

    const baseTransform = "rotateX(1.8deg) rotateY(0deg) scale(0.995)"
    let pointerLocked = false

    visualLayer.style.transformOrigin = "50% 50%"
    visualLayer.style.transformStyle = "preserve-3d"
    visualLayer.style.willChange = "transform"
    visualLayer.style.transition = "transform 260ms cubic-bezier(0.2, 0.8, 0.2, 1)"
    visualLayer.style.transform = baseTransform

    if (prefersReducedMotion()) {
      return
    }

    interactionSurface.addEventListener("pointerdown", () => {
      pointerLocked = true
    })

    interactionSurface.addEventListener("pointerup", () => {
      pointerLocked = false
    })

    interactionSurface.addEventListener("pointercancel", () => {
      pointerLocked = false
    })

    interactionSurface.addEventListener("pointermove", (event) => {
      if (pointerLocked || "touch" === event.pointerType || perspectiveFrame) {
        return
      }

      perspectiveFrame = window.requestAnimationFrame(() => {
        perspectiveFrame = null

        const bounds = wheelContainer.value?.getBoundingClientRect()
        if (!bounds?.width || !bounds.height) {
          return
        }

        const horizontal = ((event.clientX - bounds.left) / bounds.width - 0.5) * 2
        const vertical = ((event.clientY - bounds.top) / bounds.height - 0.5) * 2
        const rotateX = 1.8 - vertical * 5.5
        const rotateY = horizontal * 7.5

        visualLayer.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.005)`
      })
    })

    interactionSurface.addEventListener("pointerleave", () => {
      pointerLocked = false

      if (perspectiveFrame) {
        window.cancelAnimationFrame(perspectiveFrame)
        perspectiveFrame = null
      }

      visualLayer.style.transform = baseTransform
    })
  }

  function renderCurrentNode({ animate = true } = {}) {
    if (!wheelContainer.value || !currentNode) {
      return
    }

    if (perspectiveFrame) {
      window.cancelAnimationFrame(perspectiveFrame)
      perspectiveFrame = null
    }

    wheelContainer.value.innerHTML = ""
    wheelContainer.value.style.position = "relative"
    wheelContainer.value.style.perspective = "1600px"

    const hoverTooltip = document.createElement("div")
    hoverTooltip.dataset.skillWheelTooltip = "true"
    hoverTooltip.dataset.tooltipMode = "rich"
    hoverTooltip.style.position = "absolute"
    hoverTooltip.style.zIndex = "40"
    hoverTooltip.style.width = "min(340px, calc(100% - 24px))"
    hoverTooltip.style.padding = "12px 14px"
    hoverTooltip.style.border = "1px solid rgba(203, 213, 225, 0.82)"
    hoverTooltip.style.borderRadius = "14px"
    hoverTooltip.style.background = "rgba(255, 255, 255, 0.97)"
    hoverTooltip.style.boxShadow = "0 18px 48px rgba(15, 23, 42, 0.18)"
    hoverTooltip.style.backdropFilter = "blur(10px)"
    hoverTooltip.style.color = "#0f172a"
    hoverTooltip.style.pointerEvents = "none"
    hoverTooltip.style.opacity = "0"
    hoverTooltip.style.transform = "translateY(5px) scale(0.985)"
    hoverTooltip.style.transition = "opacity 120ms ease, transform 120ms ease"

    const tooltipHeader = document.createElement("div")
    tooltipHeader.style.display = "flex"
    tooltipHeader.style.alignItems = "center"
    tooltipHeader.style.gap = "8px"

    const tooltipDot = document.createElement("span")
    tooltipDot.style.display = "inline-block"
    tooltipDot.style.width = "9px"
    tooltipDot.style.height = "9px"
    tooltipDot.style.flex = "0 0 auto"
    tooltipDot.style.borderRadius = "9999px"
    tooltipDot.style.boxShadow = "0 0 0 4px rgba(59, 130, 246, 0.10)"

    const tooltipTitle = document.createElement("div")
    tooltipTitle.style.fontSize = "14px"
    tooltipTitle.style.fontWeight = "700"
    tooltipTitle.style.lineHeight = "1.25"

    const tooltipPath = document.createElement("div")
    tooltipPath.style.marginTop = "5px"
    tooltipPath.style.color = "#64748b"
    tooltipPath.style.fontSize = "11px"
    tooltipPath.style.fontWeight = "500"
    tooltipPath.style.lineHeight = "1.35"

    const tooltipMeta = document.createElement("div")
    tooltipMeta.style.marginTop = "8px"
    tooltipMeta.style.color = "#334155"
    tooltipMeta.style.fontSize = "11px"
    tooltipMeta.style.fontWeight = "600"

    tooltipHeader.appendChild(tooltipDot)
    tooltipHeader.appendChild(tooltipTitle)
    hoverTooltip.appendChild(tooltipHeader)
    hoverTooltip.appendChild(tooltipPath)
    hoverTooltip.appendChild(tooltipMeta)
    wheelContainer.value.appendChild(hoverTooltip)

    const children = Array.isArray(currentNode.children) ? currentNode.children : []

    if (!children.length) {
      updateVisibleMetadata()
      return
    }

    const root = createHierarchy()
    const nodes = root.descendants().filter((node) => node.depth > 0)
    const maxDepth = Math.max(root.height, 1)
    const radialStep = (outerRadius - innerRadius) / maxDepth
    const extrusionLayers = nodes.length > 120 ? 2 : nodes.length > 60 ? 3 : 5

    updateVisibleMetadata(maxDepth)

    const arcInnerRadius = (d) => innerRadius + Math.max(0, d.y0 - 1) * radialStep + ringGap / 2
    const arcOuterRadius = (d) => innerRadius + Math.max(1, d.y1 - 1) * radialStep - ringGap / 2

    const arc = d3
      .arc()
      .startAngle((d) => d.x0)
      .endAngle((d) => d.x1)
      .padAngle((d) => Math.min((d.x1 - d.x0) / 2, 0.007))
      .padRadius(outerRadius)
      .innerRadius(arcInnerRadius)
      .outerRadius(arcOuterRadius)
      .cornerRadius(4)

    const svg = d3
      .create("svg")
      .attr("viewBox", [-width / 2, -width / 2, width, width])
      .attr("preserveAspectRatio", "xMidYMid meet")
      .attr("role", "group")
      .attr("aria-label", rootLabel)
      .attr("data-render-mode", "3d")
      .style("display", "block")
      .style("width", "100%")
      .style("height", "100%")
      .style("overflow", "visible")
      .style("font", "12px sans-serif")

    const defs = svg.append("defs")

    const backgroundGradient = defs
      .append("radialGradient")
      .attr("id", defId("background"))
      .attr("cx", "45%")
      .attr("cy", "35%")

    backgroundGradient.append("stop").attr("offset", "0%").attr("stop-color", "#ffffff")
    backgroundGradient.append("stop").attr("offset", "68%").attr("stop-color", "#f8fafc")
    backgroundGradient.append("stop").attr("offset", "100%").attr("stop-color", "#dbeafe")

    const centerGradient = defs.append("radialGradient").attr("id", defId("center")).attr("cx", "36%").attr("cy", "28%")

    centerGradient.append("stop").attr("offset", "0%").attr("stop-color", "#ffffff")
    centerGradient.append("stop").attr("offset", "60%").attr("stop-color", "#eff6ff")
    centerGradient.append("stop").attr("offset", "100%").attr("stop-color", "#93c5fd")

    const ambientGlow = defs
      .append("radialGradient")
      .attr("id", defId("ambient-glow"))
      .attr("cx", "50%")
      .attr("cy", "50%")

    ambientGlow.append("stop").attr("offset", "0%").attr("stop-color", "#60a5fa").attr("stop-opacity", 0.2)
    ambientGlow.append("stop").attr("offset", "100%").attr("stop-color", "#60a5fa").attr("stop-opacity", 0)

    const softShadow = defs
      .append("filter")
      .attr("id", defId("shadow"))
      .attr("x", "-35%")
      .attr("y", "-35%")
      .attr("width", "170%")
      .attr("height", "180%")

    softShadow
      .append("feDropShadow")
      .attr("dx", 0)
      .attr("dy", 8)
      .attr("stdDeviation", 9)
      .attr("flood-color", "#0f172a")
      .attr("flood-opacity", 0.2)

    const liftedShadow = defs
      .append("filter")
      .attr("id", defId("lift"))
      .attr("x", "-45%")
      .attr("y", "-45%")
      .attr("width", "190%")
      .attr("height", "200%")

    liftedShadow
      .append("feDropShadow")
      .attr("dx", 0)
      .attr("dy", 14)
      .attr("stdDeviation", 12)
      .attr("flood-color", "#0f172a")
      .attr("flood-opacity", 0.34)

    const selectedGlow = defs
      .append("filter")
      .attr("id", defId("selected-glow"))
      .attr("x", "-45%")
      .attr("y", "-45%")
      .attr("width", "190%")
      .attr("height", "200%")

    selectedGlow
      .append("feDropShadow")
      .attr("dx", 0)
      .attr("dy", 4)
      .attr("stdDeviation", 7)
      .attr("flood-color", "#2563eb")
      .attr("flood-opacity", 0.72)

    nodes.forEach((node, index) => {
      const baseColor = getSemanticColor(node.data, node.depth)
      const [light, middle, dark] = colorStops(baseColor)
      const gradient = defs
        .append("linearGradient")
        .attr("id", makeGradientId(node.data.id, index))
        .attr("x1", "0%")
        .attr("y1", "0%")
        .attr("x2", "100%")
        .attr("y2", "100%")

      gradient.append("stop").attr("offset", "0%").attr("stop-color", light)
      gradient.append("stop").attr("offset", "52%").attr("stop-color", middle)
      gradient.append("stop").attr("offset", "100%").attr("stop-color", dark)
    })

    const viewport = svg.append("g")
    const stage = viewport.append("g")

    stage
      .append("ellipse")
      .attr("cx", 0)
      .attr("cy", outerRadius + 28)
      .attr("rx", outerRadius * 0.72)
      .attr("ry", 32)
      .attr("fill", "#0f172a")
      .attr("opacity", 0.12)
      .attr("filter", `url(#${defId("shadow")})`)
      .attr("aria-hidden", "true")

    stage
      .append("circle")
      .attr("r", outerRadius + 43)
      .attr("fill", `url(#${defId("ambient-glow")})`)
      .attr("aria-hidden", "true")

    stage
      .append("circle")
      .attr("r", outerRadius + 28)
      .attr("fill", `url(#${defId("background")})`)
      .attr("stroke", "#dbeafe")
      .attr("stroke-width", 1.5)
      .attr("filter", `url(#${defId("shadow")})`)

    stage
      .append("circle")
      .attr("r", outerRadius + 15)
      .attr("fill", "none")
      .attr("stroke", "#93c5fd")
      .attr("stroke-width", 1)
      .attr("stroke-dasharray", "2 10")
      .attr("opacity", 0.72)

    d3.range(72).forEach((index) => {
      const angle = (index / 72) * 2 * Math.PI - Math.PI / 2
      const radius = outerRadius + 15

      stage
        .append("circle")
        .attr("cx", Math.cos(angle) * radius)
        .attr("cy", Math.sin(angle) * radius)
        .attr("r", index % 6 === 0 ? 2.5 : 1.15)
        .attr("fill", index % 6 === 0 ? "#3b82f6" : "#cbd5e1")
        .attr("opacity", index % 6 === 0 ? 0.78 : 0.5)
    })

    d3.range(1, maxDepth + 1).forEach((depth) => {
      stage
        .append("circle")
        .attr("r", innerRadius + depth * radialStep)
        .attr("fill", "none")
        .attr("stroke", "#cbd5e1")
        .attr("stroke-width", 1)
        .attr("opacity", 0.32)
    })

    const segmentLayer = stage.append("g")
    const segmentGroups = segmentLayer
      .selectAll("g")
      .data(nodes)
      .join("g")
      .attr("data-skill-group-id", (d) => String(d.data.id))

    segmentGroups.each(function (node) {
      const group = d3.select(this)
      const baseColor = getSemanticColor(node.data, node.depth)
      const dark = d3.color(baseColor)?.darker(1.15).formatHex() || "#334155"

      for (let layer = extrusionLayers; layer >= 1; layer -= 1) {
        group
          .append("path")
          .attr("d", arc(node))
          .attr("transform", `translate(0,${layer * 1.65})`)
          .attr("fill", dark)
          .attr("opacity", 0.12 + layer * 0.055)
          .attr("stroke", dark)
          .attr("stroke-width", 0.7)
          .attr("pointer-events", "none")
          .attr("aria-hidden", "true")
      }
    })

    const topPaths = segmentGroups
      .append("path")
      .attr("d", arc)
      .attr("data-visual-skill-id", (d) => String(d.data.id))
      .attr("aria-hidden", "true")
      .attr("fill", (d, index) => `url(#${makeGradientId(d.data.id, index)})`)
      .attr("stroke", (d) => (d.data.id === selectedSkillId ? "#0f172a" : "#ffffff"))
      .attr("stroke-width", (d) => (d.data.id === selectedSkillId ? 5 : 1.7))
      .attr("filter", (d) => (d.data.id === selectedSkillId ? `url(#${defId("selected-glow")})` : null))
      .style("pointer-events", "none")

    const labelLayer = stage.append("g").attr("pointer-events", "none").style("user-select", "none")
    const labelMetrics = new Map()

    nodes.forEach((node) => {
      const [x, y] = arcMidpoint(node, arcInnerRadius, arcOuterRadius)
      const radius = Math.hypot(x, y)
      const availableLength = (node.x1 - node.x0) * radius
      const fontSize = node.depth > 2 ? 10.5 : node.depth > 1 ? 11.5 : 12.5
      const labelText = getNodeText(node.data)
      const estimatedTextWidth = Math.min(labelText.length, 30) * fontSize * 0.57
      const minimumLength =
        node.depth > 2
          ? Math.max(minimumLabelLength + 46, estimatedTextWidth * 0.72)
          : Math.max(minimumLabelLength + 12, estimatedTextWidth * 0.58)
      const maxLength = Math.max(8, Math.min(28, Math.floor(availableLength / Math.max(fontSize * 0.56, 1))))

      labelMetrics.set(node, {
        availableLength,
        fontSize,
        maxLength,
        minimumLength,
      })
    })

    const labelNodes = nodes.filter((node) => {
      const metrics = labelMetrics.get(node)

      return node.depth <= 4 && metrics.availableLength >= metrics.minimumLength && node.x1 - node.x0 >= 0.035
    })

    const labels = labelLayer
      .selectAll("text")
      .data(labelNodes)
      .join("text")
      .attr("data-skill-label-id", (d) => String(d.data.id))
      .attr("transform", (node) => {
        const [x, y] = arcMidpoint(node, arcInnerRadius, arcOuterRadius)
        const radius = Math.hypot(x, y)
        const angle = ((node.x0 + node.x1) / 2) * (180 / Math.PI) - 90
        const flip = angle > 90 ? 180 : 0

        return `rotate(${angle}) translate(${radius},0) rotate(${flip})`
      })
      .attr("text-anchor", "middle")
      .attr("dominant-baseline", "middle")
      .attr("font-size", (node) => labelMetrics.get(node).fontSize)
      .attr("font-weight", 650)
      .attr("fill", (node) => readableTextColor(getSemanticColor(node.data, node.depth)))
      .attr("paint-order", "stroke")
      .attr("stroke", (node) => {
        const textColor = readableTextColor(getSemanticColor(node.data, node.depth))
        return "#ffffff" === textColor ? "#0f172a" : "#ffffff"
      })
      .attr("stroke-opacity", 0.22)
      .attr("stroke-width", 2)
      .text((node) => truncateLabel(getNodeText(node.data), labelMetrics.get(node).maxLength))

    const interactionSvg = d3
      .create("svg")
      .attr("viewBox", [-width / 2, -width / 2, width, width])
      .attr("preserveAspectRatio", "xMidYMid meet")
      .attr("role", "group")
      .attr("aria-label", rootLabel)
      .attr("data-skill-interaction-layer", "overlay")
      .style("position", "absolute")
      .style("inset", "0")
      .style("z-index", "3")
      .style("display", "block")
      .style("width", "100%")
      .style("height", "100%")
      .style("overflow", "visible")
      .style("pointer-events", "none")

    const interactionStage = interactionSvg.append("g")
    const interactionPaths = interactionStage
      .selectAll("path")
      .data(nodes)
      .join("path")
      .attr("d", arc)
      .attr("data-skill-id", (d) => String(d.data.id))
      .attr("role", "button")
      .attr("tabindex", 0)
      .attr("aria-label", (d) => getNodePath(d.data))
      .attr("fill", "transparent")
      .attr("stroke", "transparent")
      .attr("stroke-width", 8)
      .style("cursor", "pointer")
      .style("outline", "none")
      .style("pointer-events", "all")

    const centerText = stage
      .append("text")
      .attr("text-anchor", "middle")
      .attr("dominant-baseline", "middle")
      .attr("font-size", 21)
      .attr("font-weight", 700)
      .attr("fill", "#0f172a")
      .attr("pointer-events", "none")

    function restoreCenterLabel() {
      const selectedNode = selectedSkillId ? nodeIndex.get(selectedSkillId) : null
      setCenterLabel(centerText, selectedNode || currentNode)
    }

    function updateSelectionStyle() {
      topPaths
        .attr("stroke", (d) => (d.data.id === selectedSkillId ? "#0f172a" : "#ffffff"))
        .attr("stroke-width", (d) => (d.data.id === selectedSkillId ? 5 : 1.7))
        .attr("filter", (d) => (d.data.id === selectedSkillId ? `url(#${defId("selected-glow")})` : null))

      updateVisibleMetadata(maxDepth)
    }

    function setRelationshipFocus(reference) {
      segmentGroups
        .interrupt()
        .transition()
        .duration(prefersReducedMotion() ? 0 : 150)
        .attr("opacity", (candidate) => (isRelatedNode(candidate, reference) ? 1 : 0.2))

      labels
        .interrupt()
        .transition()
        .duration(prefersReducedMotion() ? 0 : 150)
        .attr("opacity", (candidate) => (isRelatedNode(candidate, reference) ? 1 : 0.14))
    }

    function clearRelationshipFocus() {
      segmentGroups
        .interrupt()
        .transition()
        .duration(prefersReducedMotion() ? 0 : 150)
        .attr("opacity", 1)
      labels
        .interrupt()
        .transition()
        .duration(prefersReducedMotion() ? 0 : 150)
        .attr("opacity", 1)
    }

    function focusNode(node) {
      if (node.children.length) {
        currentNode = node
        selectedSkillId = null
        renderCurrentNode({ animate: true })
        return
      }

      selectedSkillId = node.id
      updateSelectionStyle()
      restoreCenterLabel()
      emitSkillDetail(node)
    }

    function visualGroupFor(reference) {
      return segmentGroups.filter((candidate) => candidate === reference)
    }

    function visualPathFor(reference) {
      return topPaths.filter((candidate) => candidate === reference)
    }

    function positionTooltip(event, d) {
      const bounds = wheelContainer.value?.getBoundingClientRect()
      if (!bounds) {
        return
      }

      const left = Math.min(Math.max(event.clientX - bounds.left + 18, 12), Math.max(bounds.width - 352, 12))
      const top = Math.min(Math.max(event.clientY - bounds.top + 18, 12), Math.max(bounds.height - 132, 12))
      const parentPath = getParentPath(d.data)
      const childCount = Array.isArray(d.data.children) ? d.data.children.length : 0

      tooltipDot.style.background = getSemanticColor(d.data, d.depth)
      tooltipTitle.textContent = getNodeText(d.data)
      tooltipPath.textContent = parentPath || rootLabel
      tooltipMeta.textContent = childCount
        ? `${childCount} ${skillsLabel}`
        : d.data.shortCode && d.data.shortCode !== getNodeText(d.data)
          ? d.data.shortCode
          : ""

      tooltipMeta.style.display = tooltipMeta.textContent ? "block" : "none"
      hoverTooltip.style.left = `${left}px`
      hoverTooltip.style.top = `${top}px`
      hoverTooltip.style.opacity = "1"
      hoverTooltip.style.transform = "translateY(0) scale(1)"
    }

    function hideTooltip() {
      hoverTooltip.style.opacity = "0"
      hoverTooltip.style.transform = "translateY(4px)"
    }

    interactionPaths
      .on("mouseenter", function (event, d) {
        const [x, y] = hoverOffset(d)
        const visualGroup = visualGroupFor(d)

        visualGroup
          .raise()
          .interrupt()
          .transition()
          .duration(prefersReducedMotion() ? 0 : 150)
          .attr("transform", `translate(${x},${y}) scale(1.018)`)

        visualPathFor(d).attr("filter", `url(#${defId("lift")})`)
        setRelationshipFocus(d)
        setCenterLabel(centerText, d.data)
        positionTooltip(event, d)
      })
      .on("mousemove", positionTooltip)
      .on("mouseleave", function (event, d) {
        visualGroupFor(d)
          .interrupt()
          .transition()
          .duration(prefersReducedMotion() ? 0 : 170)
          .attr("transform", null)

        visualPathFor(d).attr("filter", d.data.id === selectedSkillId ? `url(#${defId("selected-glow")})` : null)
        clearRelationshipFocus()
        restoreCenterLabel()
        hideTooltip()
      })
      .on("focus", function (event, d) {
        visualPathFor(d).attr("stroke", "#0f172a").attr("stroke-width", 5)
        setRelationshipFocus(d)
        setCenterLabel(centerText, d.data)
      })
      .on("blur", function (event, d) {
        visualPathFor(d)
          .attr("stroke", d.data.id === selectedSkillId ? "#0f172a" : "#ffffff")
          .attr("stroke-width", d.data.id === selectedSkillId ? 5 : 1.7)
        clearRelationshipFocus()
        restoreCenterLabel()
        hideTooltip()
      })
      .on("click", (event, d) => {
        event.preventDefault()
        event.stopPropagation()
        hideTooltip()
        focusNode(d.data)
      })
      .on("keydown", (event, d) => {
        if ("Enter" !== event.key && " " !== event.key) {
          return
        }

        event.preventDefault()
        event.stopPropagation()
        hideTooltip()
        focusNode(d.data)
      })
      .on("contextmenu", (event, d) => {
        event.preventDefault()
        event.stopPropagation()
        selectedSkillId = d.data.id
        updateSelectionStyle()
        restoreCenterLabel()
        hideTooltip()
        emitSkillDetail(d.data)
      })

    interactionPaths.append("title").text((d) => getNodePath(d.data))

    const canGoBack = Boolean(currentNode.parent)
    const centerGroup = stage
      .append("g")
      .attr("role", canGoBack ? "button" : null)
      .attr("tabindex", canGoBack ? 0 : null)
      .attr(
        "aria-label",
        canGoBack ? (currentNode.parent?.isSyntheticRoot ? rootLabel : getNodePath(currentNode.parent)) : rootLabel,
      )
      .style("cursor", canGoBack ? "pointer" : "default")

    centerGroup
      .append("circle")
      .attr("r", innerRadius - 15)
      .attr("cy", 7)
      .attr("fill", "#1e3a8a")
      .attr("opacity", 0.2)
      .attr("pointer-events", "none")

    centerGroup
      .append("circle")
      .attr("r", innerRadius - 18)
      .attr("fill", `url(#${defId("center")})`)
      .attr("stroke", "#60a5fa")
      .attr("stroke-width", 2)
      .attr("filter", `url(#${defId("shadow")})`)

    centerGroup
      .append("circle")
      .attr("r", innerRadius - 31)
      .attr("fill", "none")
      .attr("stroke", "#93c5fd")
      .attr("stroke-width", 1.5)
      .attr("stroke-dasharray", "4 8")
      .attr("opacity", 0.88)

    centerGroup
      .append("circle")
      .attr("r", 7)
      .attr("cy", innerRadius - 52)
      .attr("fill", canGoBack ? "#2563eb" : "#94a3b8")
      .attr("opacity", 0.95)

    if (canGoBack) {
      centerGroup
        .append("path")
        .attr("d", "M -11 53 L 0 42 L 11 53")
        .attr("fill", "none")
        .attr("stroke", "#2563eb")
        .attr("stroke-width", 3)
        .attr("stroke-linecap", "round")
        .attr("stroke-linejoin", "round")
    }

    function goBack() {
      if (!currentNode.parent) {
        return
      }

      currentNode = currentNode.parent
      selectedSkillId = null
      renderCurrentNode({ animate: true })
    }

    const centerHitTarget = interactionStage
      .append("circle")
      .attr("r", innerRadius - 5)
      .attr("fill", "transparent")
      .attr("role", canGoBack ? "button" : null)
      .attr("tabindex", canGoBack ? 0 : null)
      .attr(
        "aria-label",
        canGoBack ? (currentNode.parent?.isSyntheticRoot ? rootLabel : getNodePath(currentNode.parent)) : rootLabel,
      )
      .style("cursor", canGoBack ? "pointer" : "default")
      .style("pointer-events", canGoBack ? "all" : "none")

    centerHitTarget.on("click", goBack).on("keydown", (event) => {
      if ("Enter" !== event.key && " " !== event.key) {
        return
      }

      event.preventDefault()
      goBack()
    })

    setCenterLabel(centerText, selectedSkillId ? nodeIndex.get(selectedSkillId) : currentNode)
    centerText.raise()

    if (animate && !prefersReducedMotion()) {
      stage.attr("opacity", 0).attr("transform", "scale(0.965)")
      stage
        .transition()
        .duration(transitionDuration)
        .ease(d3.easeCubicOut)
        .attr("opacity", 1)
        .attr("transform", "scale(1)")

      segmentGroups
        .attr("opacity", 0)
        .transition()
        .delay((d, index) => Math.min(45 * d.depth + index * 5, 360))
        .duration(420)
        .ease(d3.easeCubicOut)
        .attr("opacity", 1)

      labels
        .attr("opacity", 0)
        .transition()
        .delay((d, index) => Math.min(120 + 40 * d.depth + index * 4, 420))
        .duration(340)
        .attr("opacity", 1)
    }

    const svgNode = svg.node()
    const interactionSvgNode = interactionSvg.node()
    const visualLayer = document.createElement("div")
    visualLayer.dataset.skillWheelVisualLayer = "true"
    visualLayer.style.position = "relative"
    visualLayer.style.zIndex = "1"
    visualLayer.style.width = "100%"
    visualLayer.style.height = "100%"
    visualLayer.style.pointerEvents = "none"
    visualLayer.style.transformStyle = "preserve-3d"
    visualLayer.appendChild(svgNode)
    wheelContainer.value.appendChild(visualLayer)
    wheelContainer.value.appendChild(interactionSvgNode)
    wheelContainer.value.dataset.interactionMode = "overlay"
    wheelContainer.value.dataset.labelMode = "adaptive"
    wheelContainer.value.dataset.tooltipMode = "rich"
    bindPerspective(wheelContainer.value, visualLayer)
  }

  function showRoot() {
    if (isLoading.value || !treeRoot) {
      return
    }

    currentNode = treeRoot
    selectedSkillId = null
    renderCurrentNode({ animate: true })
  }

  function showNode(skillId) {
    if (isLoading.value || !treeRoot) {
      return
    }

    if (null === skillId || undefined === skillId || "" === skillId) {
      showRoot()
      return
    }

    const skillNode = nodeIndex.get(Number(skillId)) || nodeIndex.get(skillId)
    if (!skillNode) {
      return
    }

    currentNode = skillNode
    selectedSkillId = null
    renderCurrentNode({ animate: true })
  }

  function showSkill(skillId) {
    if (isLoading.value || !treeRoot) {
      return
    }

    const skillNode = nodeIndex.get(Number(skillId)) || nodeIndex.get(skillId)

    if (!skillNode) {
      return
    }

    currentNode = skillNode.parent || treeRoot
    selectedSkillId = skillNode.id
    renderCurrentNode({ animate: true })
  }

  async function loadSkills() {
    isLoading.value = true

    try {
      const skills = await getSkillTree()
      skillList.value = Array.isArray(skills) ? skills : []
    } catch (e) {
      showErrorNotification(e)
    } finally {
      isLoading.value = false
    }
  }

  watch(skillList, () => {
    if (!wheelContainer.value) {
      return
    }

    try {
      buildTree()
      renderCurrentNode({ animate: true })
    } catch (e) {
      showErrorNotification(e)
    }
  })

  return {
    wheelContainer,
    isLoading,
    currentPath,
    breadcrumbs,
    visibleSkillCount,
    loadSkills,
    showRoot,
    showNode,
    showSkill,
  }
}
