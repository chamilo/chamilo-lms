<template>
  <Toolbar :handle-back="goBack" />

  <div class="mb-4 rounded-xl border border-gray-25 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div class="grid flex-1 grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <label class="flex flex-col gap-1 text-sm font-semibold text-gray-90">
          {{ t("Title") }}
          <input
            v-model.trim="title"
            class="rounded-lg border border-gray-25 px-3 py-2 font-normal"
            :placeholder="t('New drawing')"
            type="text"
          />
        </label>

        <label class="flex flex-col gap-1 text-sm font-semibold text-gray-90">
          {{ t("Tool") }}
          <select
            v-model="tool"
            class="rounded-lg border border-gray-25 px-3 py-2 font-normal"
          >
            <option value="select">{{ t("Select and move") }}</option>
            <option value="pen">{{ t("Freehand") }}</option>
            <option value="line">{{ t("Line") }}</option>
            <option value="rect">{{ t("Rectangle") }}</option>
            <option value="ellipse">{{ t("Ellipse") }}</option>
            <option value="text">{{ t("Text") }}</option>
          </select>
        </label>

        <label class="flex flex-col gap-1 text-sm font-semibold text-gray-90">
          {{ t("Stroke") }}
          <input
            v-model="strokeColor"
            class="h-10 rounded-lg border border-gray-25"
            type="color"
          />
        </label>

        <label class="flex flex-col gap-1 text-sm font-semibold text-gray-90">
          {{ t("Fill") }}
          <input
            v-model="fillColor"
            class="h-10 rounded-lg border border-gray-25"
            type="color"
          />
        </label>
      </div>

      <div class="flex flex-wrap gap-2">
        <input
          ref="importFileInput"
          accept=".svg,image/svg+xml"
          class="hidden"
          type="file"
          @change="importSvgFile"
        />
        <input
          ref="imageFileInput"
          accept="image/png,image/jpeg,image/webp,image/gif"
          class="hidden"
          type="file"
          @change="importImageFile"
        />
        <BaseButton
          class="!text-white"
          icon="upload"
          :label="t('Import SVG')"
          type="secondary"
          @click="openImportDialog"
        />
        <BaseButton
          class="!text-white"
          icon="image"
          :label="t('Import image')"
          type="secondary"
          @click="openImageImportDialog"
        />
        <BaseButton
          class="!text-white"
          :disabled="isSaving"
          icon="save"
          :label="isSaving ? t('In progress') : t('Save')"
          type="primary"
          @click="saveSvg"
        />
        <BaseButton
          :disabled="!drawnShapes.length"
          icon="restore"
          :label="t('Undo')"
          type="secondary"
          @click="undo"
        />
        <BaseButton
          class="!text-white"
          icon="delete"
          :label="t('Clear')"
          type="danger"
          @click="clearCanvas"
        />
      </div>
    </div>

    <div
      v-if="tool === 'text'"
      class="mt-4"
    >
      <label class="flex flex-col gap-1 text-sm font-semibold text-gray-90">
        {{ t("Text") }}
        <input
          v-model="textValue"
          class="rounded-lg border border-gray-25 px-3 py-2 font-normal"
          :placeholder="t('Click the drawing area to place this text')"
          type="text"
        />
      </label>
    </div>

    <p class="mt-3 text-sm text-gray-50">
      {{ t("Draw a new SVG, import an existing SVG or embed an image, then save it as a reusable document.") }}
      {{ t("Use Select and move to reposition or resize elements added in this editor.") }}
    </p>

    <div class="mt-3 flex flex-wrap items-center gap-4 text-sm">
      <label class="flex items-center gap-2">
        <span class="font-semibold">{{ t("Stroke width") }}</span>
        <input
          v-model.number="strokeWidth"
          class="w-28"
          max="30"
          min="1"
          type="range"
        />
        <span>{{ strokeWidth }}</span>
      </label>

      <label class="flex items-center gap-2">
        <input
          v-model="transparentFill"
          type="checkbox"
        />
        <span>{{ t("Transparent fill") }}</span>
      </label>
    </div>
  </div>

  <div class="rounded-xl border border-gray-25 bg-gray-10 p-3 shadow-sm">
    <svg
      ref="svgRef"
      class="h-[620px] w-full cursor-crosshair touch-none rounded-lg border border-gray-25 bg-white"
      preserveAspectRatio="none"
      role="img"
      viewBox="0 0 1000 600"
      xmlns="http://www.w3.org/2000/svg"
      @pointerdown="startDrawing"
      @pointermove="continueDrawing"
      @pointerup="finishDrawing"
      @pointerleave="finishDrawing"
    >
      <g v-html="existingSvgMarkup" />

      <template
        v-for="shape in visibleShapes"
        :key="shape.id"
      >
        <path
          v-if="shape.type === 'path'"
          :class="{ 'cursor-move': tool === 'select' }"
          :d="shape.d"
          fill="none"
          :stroke="shape.stroke"
          stroke-linecap="round"
          stroke-linejoin="round"
          :stroke-width="shape.strokeWidth"
          @pointerdown="startShapeMove(shape, $event)"
        />
        <line
          v-else-if="shape.type === 'line'"
          :class="{ 'cursor-move': tool === 'select' }"
          :stroke="shape.stroke"
          stroke-linecap="round"
          :stroke-width="shape.strokeWidth"
          :x1="shape.x1"
          :x2="shape.x2"
          :y1="shape.y1"
          :y2="shape.y2"
          @pointerdown="startShapeMove(shape, $event)"
        />
        <rect
          v-else-if="shape.type === 'rect'"
          :class="{ 'cursor-move': tool === 'select' }"
          :fill="shape.fill"
          :height="shape.height"
          :stroke="shape.stroke"
          :stroke-width="shape.strokeWidth"
          :width="shape.width"
          :x="shape.x"
          :y="shape.y"
          @pointerdown="startShapeMove(shape, $event)"
        />
        <ellipse
          v-else-if="shape.type === 'ellipse'"
          :class="{ 'cursor-move': tool === 'select' }"
          :cx="shape.cx"
          :cy="shape.cy"
          :fill="shape.fill"
          :rx="shape.rx"
          :ry="shape.ry"
          :stroke="shape.stroke"
          :stroke-width="shape.strokeWidth"
          @pointerdown="startShapeMove(shape, $event)"
        />
        <image
          v-else-if="shape.type === 'image'"
          :class="{ 'cursor-move': tool === 'select' }"
          :height="shape.height"
          :href="shape.href"
          preserveAspectRatio="xMidYMid meet"
          :width="shape.width"
          :x="shape.x"
          :y="shape.y"
          @pointerdown="startShapeMove(shape, $event)"
        />
        <text
          v-else-if="shape.type === 'text'"
          :class="{ 'cursor-move': tool === 'select' }"
          :fill="shape.stroke"
          font-family="Arial, Helvetica, sans-serif"
          :font-size="shape.fontSize"
          :x="shape.x"
          :y="shape.y"
          @pointerdown="startShapeMove(shape, $event)"
        >
          {{ shape.text }}
        </text>
      </template>

      <g v-if="selectedBox">
        <rect
          class="pointer-events-none"
          fill="none"
          stroke="#2563eb"
          stroke-dasharray="6 4"
          stroke-width="2"
          :height="selectedBox.height"
          :width="selectedBox.width"
          :x="selectedBox.x"
          :y="selectedBox.y"
        />
        <rect
          v-for="handle in resizeHandles"
          :key="handle.name"
          class="fill-white stroke-blue-600"
          :height="RESIZE_HANDLE_SIZE"
          :style="{ cursor: handle.cursor }"
          stroke-width="2"
          :width="RESIZE_HANDLE_SIZE"
          :x="handle.x"
          :y="handle.y"
          @pointerdown.stop.prevent="startShapeResize(handle.name, $event)"
        />
      </g>
    </svg>
  </div>

  <div
    v-if="errorMessage"
    class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
  >
    {{ errorMessage }}
  </div>

  <Loading :visible="isLoading" />
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Toolbar from "../../components/Toolbar.vue"
import Loading from "../../components/Loading.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import documentsService from "../../services/documents"
import { useNotification } from "../../composables/notification"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

const SVG_WIDTH = 1000
const SVG_HEIGHT = 600
const RESIZE_HANDLE_SIZE = 12
const MIN_SHAPE_SIZE = 8

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notification = useNotification()

const svgRef = ref(null)
const importFileInput = ref(null)
const imageFileInput = ref(null)
const title = ref("")
const comment = ref("")
const language = ref("")
const tool = ref("pen")
const strokeColor = ref("#1f76a8")
const fillColor = ref("#ffffff")
const transparentFill = ref(true)
const strokeWidth = ref(4)
const textValue = ref("Text")
const drawnShapes = ref([])
const currentShape = ref(null)
const selectedShapeId = ref("")
const transformState = ref(null)
const existingSvgMarkup = ref("")
const isDrawing = ref(false)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const documentItem = ref(null)

const visibleShapes = computed(() => {
  return currentShape.value ? [...drawnShapes.value, currentShape.value] : drawnShapes.value
})

const selectedShape = computed(() => {
  if (!selectedShapeId.value) {
    return null
  }

  return drawnShapes.value.find((shape) => shape.id === selectedShapeId.value) || null
})

const selectedBox = computed(() => {
  const box = selectedShape.value ? getShapeBox(selectedShape.value) : null

  if (!box) {
    return null
  }

  return {
    ...box,
    width: Math.max(MIN_SHAPE_SIZE, box.width),
    height: Math.max(MIN_SHAPE_SIZE, box.height),
  }
})

const resizeHandles = computed(() => {
  const box = selectedBox.value

  if (!box) {
    return []
  }

  const half = RESIZE_HANDLE_SIZE / 2
  const right = box.x + box.width
  const bottom = box.y + box.height

  return [
    { name: "nw", cursor: "nwse-resize", x: box.x - half, y: box.y - half },
    { name: "ne", cursor: "nesw-resize", x: right - half, y: box.y - half },
    { name: "sw", cursor: "nesw-resize", x: box.x - half, y: bottom - half },
    { name: "se", cursor: "nwse-resize", x: right - half, y: bottom - half },
  ]
})

const isEditMode = computed(() => Boolean(route.query.id))

onMounted(async () => {
  if (isEditMode.value) {
    await loadExistingSvg()
  } else {
    title.value = t("New drawing") + ".svg"
  }
})

function normalizeDocumentIri(value) {
  const raw = String(value || "").trim()

  if (!raw) {
    return ""
  }

  if (raw.startsWith("/api/documents/")) {
    return raw
  }

  const match = raw.match(/\/api\/documents\/(\d+)/)

  if (match) {
    return `/api/documents/${match[1]}`
  }

  if (/^\d+$/.test(raw)) {
    return `/api/documents/${raw}`
  }

  return raw
}

async function loadExistingSvg() {
  const iri = normalizeDocumentIri(route.query.id)
  if (!iri) {
    errorMessage.value = t("Invalid id")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const data = await documentsService.getDocumentByIri(iri)
    documentItem.value = data
    title.value = String(data?.title || data?.resourceNode?.title || "drawing.svg")
    comment.value = String(data?.comment || "")
    language.value = extractLanguage(data)

    const contentUrl = data?.contentUrl || data?.resourceNode?.firstResourceFile?.contentUrl || ""
    let svg = String(data?.resourceNode?.content || "")

    if (contentUrl) {
      svg = String((await documentsService.fetchTextContent(contentUrl)) || svg)
    }

    existingSvgMarkup.value = extractSvgInnerMarkup(sanitizeSvg(svg))
  } catch (e) {
    console.error("[Documents] Failed to load SVG drawing:", e?.response || e)
    errorMessage.value = t("Unable to load the drawing")
  } finally {
    isLoading.value = false
  }
}

function extractLanguage(data) {
  const lang =
    data?.resourceNode?.language ||
    data?.resourceNode?.firstResourceFile?.language ||
    data?.firstResourceFile?.language ||
    data?.language

  if (!lang) return ""

  if (typeof lang === "string") {
    const match = lang.match(/\/api\/languages\/(\d+)/)
    if (!match) return lang

    const languages = Array.isArray(window.languages) ? window.languages : []
    const found = languages.find((item) => String(item?.id || "") === match[1])

    return String(found?.isocode || "")
  }

  return String(lang?.isocode || lang?.isoCode || "")
}

function getPoint(event) {
  const svg = svgRef.value
  if (!svg) {
    return { x: 0, y: 0 }
  }

  const point = new DOMPoint(event.clientX, event.clientY)
  const matrix = svg.getScreenCTM()

  if (!matrix) {
    return { x: 0, y: 0 }
  }

  const svgPoint = point.matrixTransform(matrix.inverse())

  return {
    x: Math.max(0, Math.min(SVG_WIDTH, Math.round(svgPoint.x))),
    y: Math.max(0, Math.min(SVG_HEIGHT, Math.round(svgPoint.y))),
  }
}

function startDrawing(event) {
  if (tool.value === "select") {
    selectedShapeId.value = ""
    return
  }

  selectedShapeId.value = ""

  if (svgRef.value && event.pointerId !== undefined) {
    svgRef.value.setPointerCapture?.(event.pointerId)
  }

  const point = getPoint(event)

  if (tool.value === "text") {
    const value = String(textValue.value || "").trim()
    if (!value) {
      return
    }

    const textShape = {
      id: makeId(),
      type: "text",
      x: point.x,
      y: point.y,
      text: value,
      stroke: strokeColor.value,
      fontSize: Math.max(18, strokeWidth.value * 6),
    }

    drawnShapes.value.push(textShape)
    selectedShapeId.value = textShape.id
    return
  }

  isDrawing.value = true

  const common = {
    id: makeId(),
    stroke: strokeColor.value,
    fill: transparentFill.value ? "none" : fillColor.value,
    strokeWidth: strokeWidth.value,
    startX: point.x,
    startY: point.y,
  }

  if (tool.value === "pen") {
    currentShape.value = {
      ...common,
      type: "path",
      points: [point],
      d: `M ${point.x} ${point.y}`,
    }
    return
  }

  if (tool.value === "line") {
    currentShape.value = {
      ...common,
      type: "line",
      x1: point.x,
      y1: point.y,
      x2: point.x,
      y2: point.y,
    }
    return
  }

  if (tool.value === "rect") {
    currentShape.value = {
      ...common,
      type: "rect",
      x: point.x,
      y: point.y,
      width: 0,
      height: 0,
    }
    return
  }

  if (tool.value === "ellipse") {
    currentShape.value = {
      ...common,
      type: "ellipse",
      cx: point.x,
      cy: point.y,
      rx: 0,
      ry: 0,
    }
  }
}

function continueDrawing(event) {
  if (transformState.value) {
    transformSelectedShape(event)
    return
  }

  if (!isDrawing.value || !currentShape.value) {
    return
  }

  const point = getPoint(event)
  const shape = currentShape.value

  if (shape.type === "path") {
    const points = [...shape.points, point]
    currentShape.value = {
      ...shape,
      points,
      d: points.map((p, index) => `${index === 0 ? "M" : "L"} ${p.x} ${p.y}`).join(" "),
    }
    return
  }

  if (shape.type === "line") {
    currentShape.value = {
      ...shape,
      x2: point.x,
      y2: point.y,
    }
    return
  }

  if (shape.type === "rect") {
    currentShape.value = normalizeRect(shape, point)
    return
  }

  if (shape.type === "ellipse") {
    currentShape.value = normalizeEllipse(shape, point)
  }
}

function finishDrawing(event) {
  if (transformState.value) {
    if (svgRef.value && event?.pointerId !== undefined) {
      svgRef.value.releasePointerCapture?.(event.pointerId)
    }

    transformState.value = null
    return
  }

  if (svgRef.value && event?.pointerId !== undefined) {
    svgRef.value.releasePointerCapture?.(event.pointerId)
  }

  if (!isDrawing.value || !currentShape.value) {
    isDrawing.value = false
    currentShape.value = null
    return
  }

  const finishedShape = stripInternalFields(currentShape.value)
  drawnShapes.value.push(finishedShape)
  selectedShapeId.value = finishedShape.id
  currentShape.value = null
  isDrawing.value = false
}

function startShapeMove(shape, event) {
  if (tool.value !== "select") {
    return
  }

  event.preventDefault()
  event.stopPropagation()

  selectedShapeId.value = shape.id
  currentShape.value = null
  isDrawing.value = false
  transformState.value = {
    mode: "move",
    shapeId: shape.id,
    startPoint: getPoint(event),
    originalShape: cloneShape(shape),
  }

  if (svgRef.value && event.pointerId !== undefined) {
    svgRef.value.setPointerCapture?.(event.pointerId)
  }
}

function startShapeResize(handle, event) {
  const shape = selectedShape.value

  if (!shape) {
    return
  }

  event.preventDefault()
  event.stopPropagation()

  transformState.value = {
    mode: "resize",
    shapeId: shape.id,
    handle,
    startPoint: getPoint(event),
    originalShape: cloneShape(shape),
    originalBox: getShapeBox(shape),
  }

  if (svgRef.value && event.pointerId !== undefined) {
    svgRef.value.setPointerCapture?.(event.pointerId)
  }
}

function transformSelectedShape(event) {
  const state = transformState.value

  if (!state) {
    return
  }

  const index = drawnShapes.value.findIndex((shape) => shape.id === state.shapeId)
  if (index === -1) {
    return
  }

  const point = getPoint(event)
  const dx = point.x - state.startPoint.x
  const dy = point.y - state.startPoint.y
  const nextShape =
    state.mode === "resize"
      ? resizeShape(state.originalShape, state.originalBox, state.handle, point)
      : moveShape(state.originalShape, dx, dy)

  drawnShapes.value.splice(index, 1, nextShape)
}

function cloneShape(shape) {
  return JSON.parse(JSON.stringify(shape))
}

function moveShape(shape, dx, dy) {
  if (shape.type === "path") {
    return {
      ...shape,
      d: translatePathD(shape.d, dx, dy),
    }
  }

  if (shape.type === "line") {
    return {
      ...shape,
      x1: shape.x1 + dx,
      y1: shape.y1 + dy,
      x2: shape.x2 + dx,
      y2: shape.y2 + dy,
    }
  }

  if (shape.type === "rect" || shape.type === "image") {
    return {
      ...shape,
      x: shape.x + dx,
      y: shape.y + dy,
    }
  }

  if (shape.type === "ellipse") {
    return {
      ...shape,
      cx: shape.cx + dx,
      cy: shape.cy + dy,
    }
  }

  if (shape.type === "text") {
    return {
      ...shape,
      x: shape.x + dx,
      y: shape.y + dy,
    }
  }

  return shape
}

function resizeShape(shape, originalBox, handle, point) {
  if (!originalBox) {
    return shape
  }

  const box = buildResizedBox(originalBox, handle, point)

  if (shape.type === "rect" || shape.type === "image") {
    return {
      ...shape,
      x: box.x,
      y: box.y,
      width: box.width,
      height: box.height,
    }
  }

  if (shape.type === "ellipse") {
    return {
      ...shape,
      cx: box.x + box.width / 2,
      cy: box.y + box.height / 2,
      rx: box.width / 2,
      ry: box.height / 2,
    }
  }

  if (shape.type === "line") {
    return scaleLineToBox(shape, originalBox, box)
  }

  if (shape.type === "path") {
    return {
      ...shape,
      d: scalePathD(shape.d, originalBox, box),
    }
  }

  if (shape.type === "text") {
    const fontSize = Math.max(10, Math.round(box.height))
    return {
      ...shape,
      x: box.x,
      y: box.y + fontSize,
      fontSize,
    }
  }

  return shape
}

function buildResizedBox(originalBox, handle, point) {
  let left = originalBox.x
  let top = originalBox.y
  let right = originalBox.x + originalBox.width
  let bottom = originalBox.y + originalBox.height

  if (handle.includes("w")) {
    left = Math.min(point.x, right - MIN_SHAPE_SIZE)
  }

  if (handle.includes("e")) {
    right = Math.max(point.x, left + MIN_SHAPE_SIZE)
  }

  if (handle.includes("n")) {
    top = Math.min(point.y, bottom - MIN_SHAPE_SIZE)
  }

  if (handle.includes("s")) {
    bottom = Math.max(point.y, top + MIN_SHAPE_SIZE)
  }

  return {
    x: clamp(left, 0, SVG_WIDTH - MIN_SHAPE_SIZE),
    y: clamp(top, 0, SVG_HEIGHT - MIN_SHAPE_SIZE),
    width: clamp(right - left, MIN_SHAPE_SIZE, SVG_WIDTH),
    height: clamp(bottom - top, MIN_SHAPE_SIZE, SVG_HEIGHT),
  }
}

function getShapeBox(shape) {
  if (shape.type === "path") {
    const points = parsePathPoints(shape.d)
    return getPointsBox(points)
  }

  if (shape.type === "line") {
    const x = Math.min(shape.x1, shape.x2)
    const y = Math.min(shape.y1, shape.y2)
    return {
      x,
      y,
      width: Math.max(MIN_SHAPE_SIZE, Math.abs(shape.x2 - shape.x1)),
      height: Math.max(MIN_SHAPE_SIZE, Math.abs(shape.y2 - shape.y1)),
    }
  }

  if (shape.type === "rect" || shape.type === "image") {
    return {
      x: shape.x,
      y: shape.y,
      width: Math.max(MIN_SHAPE_SIZE, shape.width),
      height: Math.max(MIN_SHAPE_SIZE, shape.height),
    }
  }

  if (shape.type === "ellipse") {
    return {
      x: shape.cx - shape.rx,
      y: shape.cy - shape.ry,
      width: Math.max(MIN_SHAPE_SIZE, shape.rx * 2),
      height: Math.max(MIN_SHAPE_SIZE, shape.ry * 2),
    }
  }

  if (shape.type === "text") {
    const fontSize = Number(shape.fontSize || 24)
    return {
      x: shape.x,
      y: shape.y - fontSize,
      width: Math.max(MIN_SHAPE_SIZE, String(shape.text || "").length * fontSize * 0.6),
      height: Math.max(MIN_SHAPE_SIZE, fontSize),
    }
  }

  return null
}

function getPointsBox(points) {
  if (!points.length) {
    return null
  }

  const xs = points.map((point) => point.x)
  const ys = points.map((point) => point.y)
  const minX = Math.min(...xs)
  const maxX = Math.max(...xs)
  const minY = Math.min(...ys)
  const maxY = Math.max(...ys)

  return {
    x: minX,
    y: minY,
    width: Math.max(MIN_SHAPE_SIZE, maxX - minX),
    height: Math.max(MIN_SHAPE_SIZE, maxY - minY),
  }
}

function parsePathPoints(d) {
  return [...String(d || "").matchAll(/[ML]\s*(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)/gi)].map((match) => ({
    x: Number(match[1]),
    y: Number(match[2]),
  }))
}

function translatePathD(d, dx, dy) {
  return String(d || "").replace(/[ML]\s*(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)/gi, (match, x, y) => {
    const command = match.trim().charAt(0).toUpperCase()
    return `${command} ${Number(x) + dx} ${Number(y) + dy}`
  })
}

function scalePathD(d, originalBox, newBox) {
  return String(d || "").replace(/[ML]\s*(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)/gi, (match, x, y) => {
    const command = match.trim().charAt(0).toUpperCase()
    const nextX = scaleValue(Number(x), originalBox.x, originalBox.width, newBox.x, newBox.width)
    const nextY = scaleValue(Number(y), originalBox.y, originalBox.height, newBox.y, newBox.height)

    return `${command} ${Math.round(nextX)} ${Math.round(nextY)}`
  })
}

function scaleLineToBox(shape, originalBox, newBox) {
  return {
    ...shape,
    x1: Math.round(scaleValue(shape.x1, originalBox.x, originalBox.width, newBox.x, newBox.width)),
    y1: Math.round(scaleValue(shape.y1, originalBox.y, originalBox.height, newBox.y, newBox.height)),
    x2: Math.round(scaleValue(shape.x2, originalBox.x, originalBox.width, newBox.x, newBox.width)),
    y2: Math.round(scaleValue(shape.y2, originalBox.y, originalBox.height, newBox.y, newBox.height)),
  }
}

function scaleValue(value, fromStart, fromSize, toStart, toSize) {
  if (!fromSize) {
    return toStart
  }

  return toStart + ((value - fromStart) / fromSize) * toSize
}

function clamp(value, min, max) {
  return Math.max(min, Math.min(max, value))
}

function normalizeRect(shape, point) {
  const x = Math.min(shape.startX, point.x)
  const y = Math.min(shape.startY, point.y)
  const width = Math.abs(point.x - shape.startX)
  const height = Math.abs(point.y - shape.startY)

  return {
    ...shape,
    x,
    y,
    width,
    height,
  }
}

function normalizeEllipse(shape, point) {
  const rx = Math.abs(point.x - shape.startX) / 2
  const ry = Math.abs(point.y - shape.startY) / 2
  const cx = Math.min(shape.startX, point.x) + rx
  const cy = Math.min(shape.startY, point.y) + ry

  return {
    ...shape,
    cx,
    cy,
    rx,
    ry,
  }
}

function stripInternalFields(shape) {
  const copy = { ...shape }
  delete copy.startX
  delete copy.startY
  delete copy.points

  return copy
}

function openImportDialog() {
  errorMessage.value = ""

  if (importFileInput.value) {
    importFileInput.value.value = ""
    importFileInput.value.click()
  }
}

function openImageImportDialog() {
  errorMessage.value = ""

  if (imageFileInput.value) {
    imageFileInput.value.value = ""
    imageFileInput.value.click()
  }
}

function importImageFile(event) {
  const file = event?.target?.files?.[0]
  if (!file) {
    return
  }

  const fileName = String(file.name || "")
    .trim()
    .toLowerCase()
  const mimeType = String(file.type || "").toLowerCase()
  const isAllowedMime = ["image/png", "image/jpeg", "image/webp", "image/gif"].includes(mimeType)
  const isAllowedExtension = /\.(png|jpe?g|webp|gif)$/.test(fileName)

  if (!isAllowedMime && !isAllowedExtension) {
    errorMessage.value = t("Only PNG, JPG, WEBP or GIF images are supported.")
    return
  }

  const reader = new FileReader()

  reader.onload = () => {
    const dataUrl = String(reader.result || "")
    if (!dataUrl.startsWith("data:image/")) {
      errorMessage.value = t("Unable to read the image file.")
      return
    }

    const image = new Image()
    image.onload = () => {
      const naturalWidth = Math.max(1, image.naturalWidth || 1)
      const naturalHeight = Math.max(1, image.naturalHeight || 1)
      const scale = Math.min(500 / naturalWidth, 350 / naturalHeight, 1)
      const width = Math.round(naturalWidth * scale)
      const height = Math.round(naturalHeight * scale)

      const imageShape = {
        id: makeId(),
        type: "image",
        href: dataUrl,
        x: Math.round((SVG_WIDTH - width) / 2),
        y: Math.round((SVG_HEIGHT - height) / 2),
        width,
        height,
      }

      drawnShapes.value.push(imageShape)
      selectedShapeId.value = imageShape.id
      tool.value = "select"

      errorMessage.value = ""
    }

    image.onerror = () => {
      errorMessage.value = t("Unable to read the image file.")
    }

    image.src = dataUrl
  }

  reader.onerror = () => {
    errorMessage.value = t("Unable to read the image file.")
  }

  reader.readAsDataURL(file)
}

function importSvgFile(event) {
  const file = event?.target?.files?.[0]
  if (!file) {
    return
  }

  const fileName = String(file.name || "").trim()
  const mimeType = String(file.type || "").toLowerCase()

  if (!fileName.toLowerCase().endsWith(".svg") && mimeType !== "image/svg+xml") {
    errorMessage.value = t("Only SVG files are supported.")
    return
  }

  const reader = new FileReader()

  reader.onload = () => {
    const rawSvg = String(reader.result || "")
    const sanitizedSvg = sanitizeSvg(rawSvg)
    const importedMarkup = extractSvgInnerMarkup(sanitizedSvg)

    if (!sanitizedSvg || !importedMarkup) {
      errorMessage.value = t("The SVG file is empty or invalid.")
      return
    }

    existingSvgMarkup.value = importedMarkup
    selectedShapeId.value = ""
    drawnShapes.value = []
    currentShape.value = null
    title.value = normalizeSvgTitle(fileName)
    errorMessage.value = ""
  }

  reader.onerror = () => {
    errorMessage.value = t("Unable to read the SVG file.")
  }

  reader.readAsText(file)
}

function undo() {
  drawnShapes.value = drawnShapes.value.slice(0, -1)
  if (selectedShapeId.value && !drawnShapes.value.some((shape) => shape.id === selectedShapeId.value)) {
    selectedShapeId.value = ""
  }
}

function clearCanvas() {
  existingSvgMarkup.value = ""
  drawnShapes.value = []
  currentShape.value = null
  selectedShapeId.value = ""
  transformState.value = null
}

function makeId() {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

function escapeXml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&apos;")
}

function shapeToSvg(shape) {
  const stroke = escapeXml(shape.stroke || "#1f76a8")
  const strokeWidthAttr = Number(shape.strokeWidth || 1)
  const fill = escapeXml(shape.fill || "none")

  if (shape.type === "path") {
    return `<path d="${escapeXml(shape.d)}" fill="none" stroke="${stroke}" stroke-width="${strokeWidthAttr}" stroke-linecap="round" stroke-linejoin="round"/>`
  }

  if (shape.type === "line") {
    return `<line x1="${shape.x1}" y1="${shape.y1}" x2="${shape.x2}" y2="${shape.y2}" stroke="${stroke}" stroke-width="${strokeWidthAttr}" stroke-linecap="round"/>`
  }

  if (shape.type === "rect") {
    return `<rect x="${shape.x}" y="${shape.y}" width="${shape.width}" height="${shape.height}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeWidthAttr}"/>`
  }

  if (shape.type === "ellipse") {
    return `<ellipse cx="${shape.cx}" cy="${shape.cy}" rx="${shape.rx}" ry="${shape.ry}" fill="${fill}" stroke="${stroke}" stroke-width="${strokeWidthAttr}"/>`
  }

  if (shape.type === "image") {
    return `<image x="${shape.x}" y="${shape.y}" width="${shape.width}" height="${shape.height}" href="${escapeXml(shape.href)}" preserveAspectRatio="xMidYMid meet"/>`
  }

  if (shape.type === "text") {
    return `<text x="${shape.x}" y="${shape.y}" fill="${stroke}" font-family="Arial, Helvetica, sans-serif" font-size="${Number(shape.fontSize || 24)}">${escapeXml(shape.text)}</text>`
  }

  return ""
}

function buildSvgMarkup() {
  const safeTitle = escapeXml(normalizeSvgTitle(title.value))
  const currentContent = drawnShapes.value.map(shapeToSvg).join("\n")
  const preservedContent = existingSvgMarkup.value ? `${existingSvgMarkup.value}\n` : ""

  return sanitizeSvg(`<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${SVG_WIDTH}" height="${SVG_HEIGHT}" viewBox="0 0 ${SVG_WIDTH} ${SVG_HEIGHT}" role="img">
<title>${safeTitle}</title>
<g data-chamilo-svg-editor="1">
${preservedContent}${currentContent}
</g>
</svg>`)
}

function sanitizeSvg(rawSvg) {
  const value = String(rawSvg || "").trim()
  if (!value) {
    return ""
  }

  try {
    const parser = new DOMParser()
    const doc = parser.parseFromString(value, "image/svg+xml")
    const parserError = doc.querySelector("parsererror")

    if (parserError || !doc.documentElement || doc.documentElement.nodeName.toLowerCase() !== "svg") {
      return ""
    }

    const dangerousTags = ["script", "foreignObject", "iframe", "object", "embed", "audio", "video"]
    dangerousTags.forEach((tag) => {
      doc.querySelectorAll(tag).forEach((node) => node.remove())
    })

    doc.querySelectorAll("*").forEach((node) => {
      ;[...node.attributes].forEach((attr) => {
        const name = attr.name.toLowerCase()
        const value = String(attr.value || "")
          .trim()
          .toLowerCase()

        if (name.startsWith("on")) {
          node.removeAttribute(attr.name)
          return
        }

        if (["href", "xlink:href", "src"].includes(name) && value.startsWith("javascript:")) {
          node.removeAttribute(attr.name)
        }
      })
    })

    return new XMLSerializer().serializeToString(doc.documentElement)
  } catch {
    return ""
  }
}

function extractSvgInnerMarkup(svgMarkup) {
  const value = String(svgMarkup || "").trim()

  if (!value) {
    return ""
  }

  try {
    const doc = new DOMParser().parseFromString(value, "image/svg+xml")
    const svg = doc.documentElement

    if (!svg || svg.nodeName.toLowerCase() !== "svg") {
      return ""
    }

    return [...svg.childNodes]
      .filter((node) => node.nodeName.toLowerCase() !== "title")
      .map((node) => new XMLSerializer().serializeToString(node))
      .join("\n")
  } catch {
    return ""
  }
}

function normalizeSvgTitle(value) {
  const raw = String(value || "").trim() || "drawing.svg"
  return raw.toLowerCase().endsWith(".svg") ? raw : `${raw}.svg`
}

function getParentResourceNodeId() {
  return route.params.node ?? route.params.id ?? route.query.node ?? null
}

async function saveSvg() {
  const normalizedTitle = normalizeSvgTitle(title.value)
  const svg = buildSvgMarkup()

  if (!svg) {
    errorMessage.value = t("The drawing is empty or invalid.")
    return
  }

  isSaving.value = true
  errorMessage.value = ""

  try {
    if (isEditMode.value && documentItem.value?.["@id"]) {
      await documentsService.updateWithFormData({
        "@id": documentItem.value["@id"],
        title: normalizedTitle,
        comment: comment.value,
        contentFile: svg,
        contentFileExtension: "svg",
        contentFileMimeType: "image/svg+xml",
        language: language.value || "",
      })
    } else {
      await documentsService.createWithFormData({
        title: normalizedTitle,
        comment: comment.value,
        filetype: "file",
        contentFile: svg,
        contentFileExtension: "svg",
        contentFileMimeType: "image/svg+xml",
        parentResourceNodeId: getParentResourceNodeId(),
        // Course context derived server-side from the gated session course.
        resourceLinkList: JSON.stringify([{ visibility: RESOURCE_LINK_PUBLISHED }]),
        indexDocumentContent: false,
        language: language.value || "",
      })
    }

    notification.showSuccessNotification(t("Saved"))
    await goBack()
  } catch (e) {
    console.error("[Documents] Failed to save SVG drawing:", e?.response || e)
    errorMessage.value =
      e?.response?.data?.detail || e?.response?.data?.message || e?.message || t("Unable to save the drawing")
  } finally {
    isSaving.value = false
  }
}

async function goBack() {
  await router.push({
    name: "DocumentsList",
    params: { node: getParentResourceNodeId() },
    query: {
      ...route.query,
      loadNode: 1,
    },
  })
}
</script>
