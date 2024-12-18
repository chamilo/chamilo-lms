import { ref, unref, watch } from "vue"
import * as d3 from "d3"
import { getSkillTree } from "../../services/skillService"
import { useNotification } from "../notification"

export function useSkillWheel() {
  const isLoading = ref(true)

  const skillList = ref([])
  const wheelContainer = ref(null)

  const { showErrorNotification } = useNotification()

  const colorList = ["#deebf7", "#9ecae1", "#3182bd"]

  let root
  let centralCircle
  let path

  const width = 928
  const height = width
  const radius = width / 6

  function transformSkillToWheelItem({
    id,
    title,
    shortCode,
    status,
    children,
    hasGradebook,
    isSearched,
    isAchievedByUser,
  }) {
    const item = {
      id,
      name: title,
      shortCode,
      status,
      children: [],
      hasGradebook,
      isSearched,
      isAchievedByUser,
    }

    if (children.length) {
      for (const child of children) {
        item.children.push(transformSkillToWheelItem(child))
      }
    } else {
      item.value = 1
    }

    return item
  }

  function render() {
    const data = {
      name: "root",
      children: unref(skillList).map(transformSkillToWheelItem),
    }

    // Compute the layout.
    const hierarchy = d3
      .hierarchy(data)
      .sum((d) => d.value)
      .sort((a, b) => b.id - a.id)

    root = d3.partition().size([2 * Math.PI, hierarchy.height + 1])(hierarchy)

    root.each((d) => (d.current = d))

    // Create the arc generator.
    const arc = d3
      .arc()
      .startAngle((d) => d.x0)
      .endAngle((d) => d.x1)
      .padAngle((d) => Math.min((d.x1 - d.x0) / 2, 0.005))
      .padRadius(radius * 1.5)
      .innerRadius((d) => d.y0 * radius)
      .outerRadius((d) => Math.max(d.y0 * radius, d.y1 * radius - 1))

    // Create the SVG container.
    const svg = d3
      .create("svg")
      .attr("viewBox", [-width / 2, -height / 2, width, width])
      .style("font", "10px sans-serif")

    // Append the arcs.
    path = svg
      .append("g")
      .selectAll("path")
      .data(root.descendants().slice(1))
      .join("path")
      .attr("fill", setFillColor)
      .attr("fill-opacity", (d) => (arcVisible(d.current) ? 1 : 0))
      .attr("pointer-events", (d) => (arcVisible(d.current) ? "auto" : "none"))
      .attr("d", (d) => arc(d.current))
      .attr("id", (d) => "skill-" + d.data.id)

    // Make them clickable if they have children.
    path
      .filter((d) => d.children)
      .style("cursor", "pointer")
      .on("click", clicked)

    path.append("title").text(
      (d) =>
        `${d
          .ancestors()
          .filter((d) => d.depth > 0)
          .map(setNodeText)
          .reverse()
          .join("/")}`,
    )

    const label = svg
      .append("g")
      .attr("pointer-events", "none")
      .attr("text-anchor", "middle")
      .style("user-select", "none")
      .selectAll("text")
      .data(root.descendants().slice(1))
      .join("text")
      .attr("dy", "0.35em")
      .attr("fill-opacity", (d) => +labelVisible(d.current))
      .attr("transform", (d) => labelTransform(d.current))
      .text(setNodeText)

    centralCircle = svg
      .append("circle")
      .datum(root)
      .attr("r", radius)
      .attr("fill", "none")
      .attr("pointer-events", "all")
      .on("click", clicked)

    // Handle zoom on click.
    function clicked(event, p) {
      centralCircle.datum(p.parent || root)

      root.each(
        (d) =>
          (d.target = {
            x0: Math.max(0, Math.min(1, (d.x0 - p.x0) / (p.x1 - p.x0))) * 2 * Math.PI,
            x1: Math.max(0, Math.min(1, (d.x1 - p.x0) / (p.x1 - p.x0))) * 2 * Math.PI,
            y0: Math.max(0, d.y0 - p.depth),
            y1: Math.max(0, d.y1 - p.depth),
          }),
      )

      const t = svg.transition().duration(750)

      // Transition the data on all arcs, even the ones that aren’t visible,
      // so that if this transition is interrupted, entering arcs will start
      // the next transition from the desired position.
      path
        .transition(t)
        .tween("data", (d) => {
          const i = d3.interpolate(d.current, d.target)

          return (t) => (d.current = i(t))
        })
        .filter(function (d) {
          return +this.getAttribute("fill-opacity") || arcVisible(d.target)
        })
        .attr("fill-opacity", (d) => (arcVisible(d.target) ? 1 : 0))
        .attr("pointer-events", (d) => (arcVisible(d.target) ? "auto" : "none"))
        .attrTween("d", (d) => () => arc(d.current))

      label
        .filter(function (d) {
          return +this.getAttribute("fill-opacity") || labelVisible(d.target)
        })
        .transition(t)
        .attr("fill-opacity", (d) => +labelVisible(d.target))
        .attrTween("transform", (d) => () => labelTransform(d.current))
    }

    function arcVisible(d) {
      return d.y1 <= 3 && d.y0 >= 1 && d.x1 > d.x0
    }

    function labelVisible(d) {
      return d.y1 <= 3 && d.y0 >= 1 && (d.y1 - d.y0) * (d.x1 - d.x0) > 0.03
    }

    function labelTransform(d) {
      const x = (((d.x0 + d.x1) / 2) * 180) / Math.PI
      const y = ((d.y0 + d.y1) / 2) * radius

      return `rotate(${x - 90}) translate(${y},0) rotate(${x < 180 ? 0 : 180})`
    }

    function setFillColor(d, i) {
      if (d.data.hasGradebook) {
        return "#F89406"
      }

      if (d.data.isSearched) {
        return "#B94A48"
      }

      if (d.data.isAchievedByUser) {
        return "#A1D99B"
      }

      if (!d.data.status) {
        return "#48616C"
      }

      return colorList[i % colorList.length]
    }

    function setNodeText(d) {
      if (d.data.shortCode) {
        return d.data.shortCode
      }

      return d.data.name
    }

    return svg.node()
  }

  function showRoot() {
    if (isLoading.value) {
      return
    }

    centralCircle.datum(root).dispatch("click")
  }

  function showSkill(skillId) {
    if (isLoading.value) {
      return
    }

    const skillNode = root.descendants().find((d) => d.data.id === skillId)

    if (!skillNode) {
      return
    }

    if (skillNode.children && skillNode.children.length > 0) {
      centralCircle.datum(skillNode).dispatch("click")

      return
    }

    if (skillNode.parent) {
      centralCircle.datum(skillNode.parent).dispatch("click")

      return
    }

    showRoot()
  }

  async function loadSkills() {
    isLoading.value = true

    try {
      skillList.value = await getSkillTree()
    } catch (e) {
      showErrorNotification(e)
    } finally {
      isLoading.value = false
    }
  }

  watch(skillList, () => {
    if (wheelContainer.value) {
      wheelContainer.value.innerHTML = ""
      wheelContainer.value.appendChild(render())
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
