import { ref, unref, watch } from "vue"
import * as d3 from "d3"
import { getSkillTree } from "../../services/skillService"
import { useNotification } from "../notification"

export function useSkillWheel({ onSkillDetail } = {}) {
  const isLoading = ref(true)

  const skillList = ref([])
  const wheelContainer = ref(null)

  const { showErrorNotification } = useNotification()

  const colorList = ["#deebf7", "#9ecae1", "#3182bd"]

  const width = 928
  const outerRadius = width / 2 - 32
  const innerRadius = 116
  const labelMinAngle = 0.2

  let treeRoot = null
  let currentNode = null
  let selectedSkillId = null
  let nodeIndex = new Map()

  function transformSkillToWheelItem(
    {
      id,
      title,
      shortCode,
      status,
      children = [],
      hasGradebook,
      isSearched,
      isAchievedByUser,
      description,
    },
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
      name: "",
      children: [],
      parent: null,
      isSyntheticRoot: true,
    }

    treeRoot.children = unref(skillList).map((skill) => transformSkillToWheelItem(skill, treeRoot))
    currentNode = treeRoot
    selectedSkillId = null
  }

  function getNodeText(node) {
    return node?.shortCode || node?.name || ""
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
    if (text.length <= maxLength) {
      return text
    }

    return text.substring(0, maxLength) + "…"
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

      if (candidate.length <= 22 || !line) {
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
      lines[2] = truncateLabel(lines[2], 18)
    }

    return lines
  }

  function setCenterLabel(centerText, node) {
    centerText.selectAll("tspan").remove()

    const lines = splitCenterText(node?.isSyntheticRoot ? "" : getNodeText(node))

    if (!lines.length) {
      return
    }

    const startY = -((lines.length - 1) * 13)

    lines.forEach((line, index) => {
      centerText
        .append("tspan")
        .attr("x", 0)
        .attr("y", startY + index * 26)
        .text(line)
    })
  }

  function setFillColor(node, index) {
    if (node.hasGradebook) {
      return "#f89406"
    }

    if (node.isSearched) {
      return "#b94a48"
    }

    if (node.isAchievedByUser) {
      return "#a1d99b"
    }

    if (!node.status) {
      return "#48616c"
    }

    return colorList[index % colorList.length]
  }

  function labelTransform(d) {
    const angle = ((d.startAngle + d.endAngle) / 2) * (180 / Math.PI) - 90
    const radius = (innerRadius + outerRadius) / 2
    const flip = angle > 90 ? 180 : 0

    return `rotate(${angle}) translate(${radius},0) rotate(${flip})`
  }

  function renderCurrentNode() {
    if (!wheelContainer.value || !currentNode) {
      return
    }

    wheelContainer.value.innerHTML = ""

    const children = Array.isArray(currentNode.children) ? currentNode.children : []

    if (!children.length) {
      return
    }

    const pie = d3
      .pie()
      .sort(null)
      .value(() => 1)

    const arc = d3.arc().innerRadius(innerRadius).outerRadius(outerRadius).padAngle(0.008).padRadius(outerRadius)

    const svg = d3
      .create("svg")
      .attr("viewBox", [-width / 2, -width / 2, width, width])
      .attr("preserveAspectRatio", "xMidYMid meet")
      .style("display", "block")
      .style("width", "100%")
      .style("height", "auto")
      .style("font", "12px sans-serif")

    const centerText = svg
      .append("text")
      .attr("text-anchor", "middle")
      .attr("dominant-baseline", "middle")
      .attr("font-size", 20)
      .attr("font-weight", 600)
      .attr("pointer-events", "none")

    const arcs = pie(children)

    const path = svg
      .append("g")
      .selectAll("path")
      .data(arcs)
      .join("path")
      .attr("d", arc)
      .attr("fill", (d, index) => setFillColor(d.data, index))
      .attr("fill-opacity", 1)
      .attr("stroke", (d) => (d.data.id === selectedSkillId ? "#111827" : "#ffffff"))
      .attr("stroke-width", (d) => (d.data.id === selectedSkillId ? 5 : 2))
      .style("cursor", "pointer")
      .on("mouseenter", function (event, d) {
        d3.select(this).attr("fill-opacity", 0.82)
        setCenterLabel(centerText, d.data)
      })
      .on("mouseleave", function () {
        d3.select(this).attr("fill-opacity", 1)

        const selectedNode = selectedSkillId ? nodeIndex.get(selectedSkillId) : null
        setCenterLabel(centerText, selectedNode || currentNode)
      })
      .on("click", (event, d) => {
        event.stopPropagation()

        if (d.data.children.length) {
          currentNode = d.data
          selectedSkillId = null
          renderCurrentNode()
          return
        }

        selectedSkillId = d.data.id
        renderCurrentNode()
        setCenterLabel(centerText, d.data)
        emitSkillDetail(d.data)
      })
      .on("contextmenu", (event, d) => {
        event.preventDefault()
        event.stopPropagation()
        selectedSkillId = d.data.id
        renderCurrentNode()
        emitSkillDetail(d.data)
      })

    path.append("title").text((d) => getNodePath(d.data))

    svg
      .append("g")
      .attr("pointer-events", "none")
      .attr("text-anchor", "middle")
      .style("user-select", "none")
      .selectAll("text")
      .data(arcs.filter((d) => d.endAngle - d.startAngle >= labelMinAngle))
      .join("text")
      .attr("dy", "0.35em")
      .attr("transform", labelTransform)
      .attr("font-size", 13)
      .attr("font-weight", 500)
      .text((d) => truncateLabel(getNodeText(d.data)))

    const canGoBack = Boolean(currentNode.parent)

    svg
      .append("circle")
      .attr("r", innerRadius - 10)
      .attr("fill", "#ffffff")
      .attr("stroke", "#d1d5db")
      .attr("stroke-width", 2)
      .style("cursor", canGoBack ? "pointer" : "default")
      .on("click", () => {
        if (!currentNode.parent) {
          return
        }

        currentNode = currentNode.parent
        selectedSkillId = null
        renderCurrentNode()
      })

    setCenterLabel(centerText, selectedSkillId ? nodeIndex.get(selectedSkillId) : currentNode)
    centerText.raise()

    wheelContainer.value.appendChild(svg.node())
  }

  function showRoot() {
    if (isLoading.value || !treeRoot) {
      return
    }

    currentNode = treeRoot
    selectedSkillId = null
    renderCurrentNode()
  }

  function showSkill(skillId) {
    if (isLoading.value || !treeRoot) {
      return
    }

    const skillNode = nodeIndex.get(Number(skillId)) || nodeIndex.get(skillId)

    if (!skillNode) {
      return
    }

    if (skillNode.children.length) {
      currentNode = skillNode
      selectedSkillId = null
      renderCurrentNode()
      return
    }

    currentNode = skillNode.parent || treeRoot
    selectedSkillId = skillNode.id
    renderCurrentNode()
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
      renderCurrentNode()
    } catch (e) {
      showErrorNotification(e)
    }
  })

  return {
    wheelContainer,
    isLoading,
    loadSkills,
    showRoot,
    showSkill,
  }
}
