<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import supportBotService from "../../services/supportBotService"
import ticketService from "../../services/ticketService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notification = useNotification()
const { requireConfirmation } = useConfirmation()

const currentTreeId = ref(Number(route.params.id || 0))
const isLoading = ref(false)
const isSaving = ref(false)
const selectedNodeRef = ref(null)
const ticketProjects = ref([])
const ticketCategories = ref([])
let nextNodeNumber = 1

const form = reactive({
  title: "",
  description: "",
  status: "draft",
  version: 1,
  scopeLabel: "",
  editable: true,
  entryNodeRef: null,
  defaultTicketProjectId: null,
  nodes: [],
})

const actionOptions = computed(() => [
  { label: t("Go to node"), value: "goto_node" },
  { label: t("Open link"), value: "open_link" },
  { label: t("Create support ticket"), value: "escalate_ticket" },
  { label: t("End conversation"), value: "end_conversation" },
])

const projectOptions = computed(() =>
  ticketProjects.value.map((project) => ({
    label: project.title,
    value: Number(project.id),
  })),
)

const categoryOptions = computed(() =>
  ticketCategories.value
    .filter((category) => category.courseRequired !== true)
    .map((category) => ({
      label: category.title,
      value: Number(category.id),
    })),
)

const nodeOptions = computed(() =>
  form.nodes.map((node, index) => ({
    label: nodeLabel(node, index),
    value: node.ref,
  })),
)

const selectedNode = computed(() => form.nodes.find((node) => node.ref === selectedNodeRef.value) || null)

function treeStatusLabel(status) {
  if (status === "published") {
    return t("Published")
  }
  if (status === "archived") {
    return t("Archived")
  }

  return t("Draft")
}

function nodeLabel(node, index) {
  const content = String(node?.content || "")
    .trim()
    .replace(/\s+/g, " ")
  const shortContent = content.length > 42 ? `${content.slice(0, 42)}…` : content

  return shortContent || `${t("Node")} ${index + 1}`
}

function targetNodeOptions(nodeRef) {
  return nodeOptions.value.filter((item) => item.value !== nodeRef)
}

function makeNodeRef() {
  let refValue = `tmp-${nextNodeNumber}`
  while (form.nodes.some((node) => node.ref === refValue)) {
    nextNodeNumber += 1
    refValue = `tmp-${nextNodeNumber}`
  }
  nextNodeNumber += 1

  return refValue
}

function makeOption() {
  return {
    label: "",
    actionType: "end_conversation",
    targetRef: null,
    targetUrl: "",
    ticketCategoryId: null,
    closingMessage: "",
  }
}

function addNode() {
  if (!form.editable) {
    return
  }

  const node = {
    ref: makeNodeRef(),
    content: "",
    options: [makeOption()],
  }
  form.nodes.push(node)
  selectedNodeRef.value = node.ref
  if (!form.entryNodeRef) {
    form.entryNodeRef = node.ref
  }
}

function requestRemoveNode(node) {
  if (!form.editable) {
    return
  }
  if (form.nodes.length <= 1) {
    notification.showErrorNotification(t("A support bot tree requires at least one node."))
    return
  }

  requireConfirmation({
    message: t("Are you sure you want to delete this node? Links to it will be cleared."),
    accept: () => removeNode(node.ref),
  })
}

function removeNode(nodeRef) {
  const index = form.nodes.findIndex((node) => node.ref === nodeRef)
  if (index < 0) {
    return
  }

  form.nodes.splice(index, 1)
  for (const node of form.nodes) {
    for (const option of node.options) {
      if (option.targetRef === nodeRef) {
        option.targetRef = null
      }
    }
  }

  if (form.entryNodeRef === nodeRef) {
    form.entryNodeRef = form.nodes[0]?.ref || null
  }
  selectedNodeRef.value = form.nodes[Math.min(index, form.nodes.length - 1)]?.ref || null
}

function moveNode(index, direction) {
  if (!form.editable) {
    return
  }
  const targetIndex = index + direction
  if (targetIndex < 0 || targetIndex >= form.nodes.length) {
    return
  }

  const [node] = form.nodes.splice(index, 1)
  form.nodes.splice(targetIndex, 0, node)
}

function addOption(node) {
  if (form.editable) {
    node.options.push(makeOption())
  }
}

function removeOption(node, optionIndex) {
  if (!form.editable) {
    return
  }
  if (node.options.length <= 1) {
    notification.showErrorNotification(t("Every support bot node requires at least one option."))
    return
  }

  node.options.splice(optionIndex, 1)
}

function onActionTypeChange(option) {
  option.targetRef = null
  option.targetUrl = ""
  option.ticketCategoryId = null
  option.closingMessage = ""
}

async function loadTicketConfiguration(projectId = null, preserveCategoryIds = false) {
  try {
    const params = projectId ? { projectId: Number(projectId) } : {}
    const data = await ticketService.getAdminConfiguration(params)
    ticketProjects.value = Array.isArray(data.projects) ? data.projects : []
    ticketCategories.value = Array.isArray(data.categories) ? data.categories : []

    if (!currentTreeId.value && !form.defaultTicketProjectId && Number(data.projectId || 0) > 0) {
      form.defaultTicketProjectId = Number(data.projectId)
    }

    if (!preserveCategoryIds) {
      const allowedIds = new Set(categoryOptions.value.map((category) => Number(category.value)))
      for (const node of form.nodes) {
        for (const option of node.options) {
          if (option.actionType === "escalate_ticket" && !allowedIds.has(Number(option.ticketCategoryId))) {
            option.ticketCategoryId = null
          }
        }
      }
    }
  } catch (error) {
    notification.showErrorNotification(error)
  }
}

async function onDefaultProjectChange() {
  await loadTicketConfiguration(form.defaultTicketProjectId, false)
}

function hydrate(data) {
  form.title = data.title || ""
  form.description = data.description || ""
  form.status = data.status || "draft"
  form.version = Number(data.version || 1)
  form.scopeLabel = data.scopeLabel || ""
  form.editable = data.editable !== false
  form.entryNodeRef = data.entryNodeRef || null
  form.defaultTicketProjectId = data.defaultTicketProjectId ? Number(data.defaultTicketProjectId) : null
  form.nodes = Array.isArray(data.nodes)
    ? data.nodes.map((node) => ({
        ref: node.ref,
        content: node.content || "",
        options: Array.isArray(node.options)
          ? node.options.map((option) => ({
              label: option.label || "",
              actionType: option.actionType || "end_conversation",
              targetRef: option.targetRef || null,
              targetUrl: option.targetUrl || "",
              ticketCategoryId: option.ticketCategoryId ? Number(option.ticketCategoryId) : null,
              closingMessage: option.closingMessage || "",
            }))
          : [],
      }))
    : []

  if (form.nodes.length === 0 && form.editable) {
    addNode()
  }
  selectedNodeRef.value = form.entryNodeRef || form.nodes[0]?.ref || null
}

async function loadTree() {
  isLoading.value = true
  try {
    const data = currentTreeId.value
      ? await supportBotService.getTree(currentTreeId.value)
      : await supportBotService.getConfiguration()
    hydrate(data)
    await loadTicketConfiguration(form.defaultTicketProjectId, true)
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

function validateBeforeSave() {
  if (!form.title.trim()) {
    notification.showErrorNotification(t("The support bot tree title is required."))
    return false
  }
  if (!form.entryNodeRef || form.nodes.length === 0) {
    notification.showErrorNotification(t("A support bot entry node is required."))
    return false
  }

  for (const node of form.nodes) {
    if (!String(node.content || "").trim()) {
      notification.showErrorNotification(t("Every support bot node requires content."))
      selectedNodeRef.value = node.ref
      return false
    }
    if (!Array.isArray(node.options) || node.options.length === 0) {
      notification.showErrorNotification(t("Every support bot node requires at least one option."))
      selectedNodeRef.value = node.ref
      return false
    }

    for (const option of node.options) {
      if (!String(option.label || "").trim()) {
        notification.showErrorNotification(t("Every support bot option requires a label."))
        selectedNodeRef.value = node.ref
        return false
      }
      if (option.actionType === "goto_node" && !option.targetRef) {
        notification.showErrorNotification(t("Select a target node for every go-to option."))
        selectedNodeRef.value = node.ref
        return false
      }
      if (option.actionType === "open_link" && !String(option.targetUrl || "").trim()) {
        notification.showErrorNotification(t("Enter a relative URL for every link option."))
        selectedNodeRef.value = node.ref
        return false
      }
      if (option.actionType === "escalate_ticket" && Number(option.ticketCategoryId || 0) <= 0) {
        notification.showErrorNotification(t("Select a ticket category for every escalation option."))
        selectedNodeRef.value = node.ref
        return false
      }
    }
  }

  return true
}

function buildPayload() {
  return {
    title: form.title,
    description: form.description,
    entryNodeRef: form.entryNodeRef,
    defaultTicketProjectId: Number(form.defaultTicketProjectId || 0) || null,
    nodes: form.nodes.map((node) => ({
      ref: node.ref,
      content: node.content,
      options: node.options.map((option) => ({
        label: option.label,
        actionType: option.actionType,
        targetRef: option.targetRef || null,
        targetUrl: option.targetUrl || null,
        ticketCategoryId: Number(option.ticketCategoryId || 0) || null,
        closingMessage: option.closingMessage || null,
      })),
    })),
  }
}

async function saveTree() {
  if (!form.editable || !validateBeforeSave()) {
    return
  }

  isSaving.value = true
  try {
    const result = await supportBotService.saveTree(buildPayload(), currentTreeId.value || null)
    currentTreeId.value = Number(result.id)
    notification.showSuccessNotification(t("The support bot tree has been saved."))

    if (route.name === "SupportBotTreeCreate") {
      await router.replace({ name: "SupportBotTreeEdit", params: { id: result.id } })
    }
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isSaving.value = false
  }
}

onMounted(loadTree)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="currentTreeId ? t('Edit support bot tree') : t('Create support bot tree')">
      <div class="flex gap-2">
        <BaseButton
          :label="t('Back')"
          :route="{ name: 'SupportBotTreeList' }"
          icon="back"
          only-icon
          type="secondary-text"
        />
        <BaseButton
          v-if="form.editable"
          :is-loading="isSaving"
          :label="t('Save')"
          icon="save"
          only-icon
          type="success"
          @click="saveTree"
        />
      </div>
    </SectionHeader>

    <div
      v-if="!form.editable && !isLoading"
      class="rounded-xl border border-gray-20 bg-gray-10 p-4 text-sm text-gray-700"
    >
      {{
        t(
          "Published and archived support bot versions are read-only. Duplicate this tree from the list to edit a new draft version.",
        )
      }}
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
      <div class="space-y-5 rounded-xl border border-gray-20 bg-white p-5 shadow-sm">
        <BaseInputText
          id="support_bot_tree_title"
          v-model="form.title"
          :disabled="!form.editable"
          :label="t('Title')"
          name="title"
          required
          show-required-marker
        />

        <BaseTextArea
          id="support_bot_tree_description"
          v-model="form.description"
          :disabled="!form.editable"
          :label="t('Description')"
          name="description"
          rows="4"
        />

        <BaseSelect
          id="support_bot_default_ticket_project"
          v-model="form.defaultTicketProjectId"
          :disabled="!form.editable"
          :label="t('Default ticket project')"
          :options="projectOptions"
          allow-clear
          name="defaultTicketProjectId"
          @change="onDefaultProjectChange"
        />

        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <div class="font-medium text-gray-700">{{ t("Status") }}</div>
            <div class="text-gray-600">{{ treeStatusLabel(form.status) }}</div>
          </div>
          <div>
            <div class="font-medium text-gray-700">{{ t("Version") }}</div>
            <div class="text-gray-600">{{ form.version }}</div>
          </div>
          <div class="col-span-2">
            <div class="font-medium text-gray-700">{{ t("Portal scope") }}</div>
            <div class="break-all text-gray-600">{{ form.scopeLabel }}</div>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-5 text-sm text-gray-600 shadow-sm">
        <p class="font-medium text-gray-700">{{ t("Decision tree rules") }}</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
          <li>{{ t("Every node must contain at least one option.") }}</li>
          <li>{{ t("A final node must end the conversation or create a support ticket.") }}</li>
          <li>{{ t("Go-to links must not create cycles in v1.") }}</li>
          <li>{{ t("External URLs are not allowed in v1; link actions must use a relative Chamilo path.") }}</li>
        </ul>
      </div>
    </div>

    <div class="grid min-h-[36rem] gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
      <aside class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-2">
          <h2 class="font-semibold text-gray-800">{{ t("Nodes") }}</h2>
          <BaseButton
            v-if="form.editable"
            :label="t('Add node')"
            icon="plus"
            only-icon
            size="small"
            type="success"
            @click="addNode"
          />
        </div>

        <div class="space-y-2">
          <div
            v-for="(node, index) in form.nodes"
            :key="node.ref"
            :class="selectedNodeRef === node.ref ? 'border-primary bg-primary/5' : 'border-gray-20 bg-white'"
            class="rounded-lg border p-2"
          >
            <button
              :aria-label="nodeLabel(node, index)"
              class="w-full text-left text-sm font-medium text-gray-800"
              type="button"
              @click="selectedNodeRef = node.ref"
            >
              <span class="block truncate">{{ nodeLabel(node, index) }}</span>
              <span
                v-if="form.entryNodeRef === node.ref"
                class="mt-1 inline-flex rounded-full bg-success/10 px-2 py-0.5 text-xs text-success"
              >
                {{ t("Entry node") }}
              </span>
            </button>

            <div
              v-if="form.editable"
              class="mt-2 flex justify-end gap-1"
            >
              <BaseButton
                :disabled="index === 0"
                :label="t('Move up')"
                icon="arrow-up"
                only-icon
                size="small"
                type="secondary-text"
                @click="moveNode(index, -1)"
              />
              <BaseButton
                :disabled="index === form.nodes.length - 1"
                :label="t('Move down')"
                icon="arrow-down"
                only-icon
                size="small"
                type="secondary-text"
                @click="moveNode(index, 1)"
              />
              <BaseButton
                :disabled="form.entryNodeRef === node.ref"
                :label="t('Set as entry node')"
                icon="check"
                only-icon
                size="small"
                type="secondary-text"
                @click="form.entryNodeRef = node.ref"
              />
              <BaseButton
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="requestRemoveNode(node)"
              />
            </div>
          </div>
        </div>
      </aside>

      <main class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm">
        <template v-if="selectedNode">
          <div class="mb-5 flex items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold text-gray-800">{{ t("Node") }}</h2>
              <p class="text-xs text-gray-500">{{ selectedNode.ref }}</p>
            </div>
            <BaseButton
              v-if="form.editable"
              :label="t('Add option')"
              icon="plus"
              type="success"
              @click="addOption(selectedNode)"
            />
          </div>

          <BaseTextArea
            :id="`support_bot_node_${selectedNode.ref}_content`"
            v-model="selectedNode.content"
            :disabled="!form.editable"
            :label="t('Message')"
            :name="`node_${selectedNode.ref}_content`"
            rows="5"
          />

          <div class="mt-6 space-y-4">
            <div
              v-for="(option, optionIndex) in selectedNode.options"
              :key="`${selectedNode.ref}-${optionIndex}`"
              class="rounded-lg border border-gray-20 bg-gray-10 p-4"
            >
              <div class="mb-4 flex items-center justify-between gap-2">
                <strong class="text-sm text-gray-800">{{ t("Option") }} {{ optionIndex + 1 }}</strong>
                <BaseButton
                  v-if="form.editable"
                  :label="t('Delete')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="removeOption(selectedNode, optionIndex)"
                />
              </div>

              <div class="space-y-4">
                <BaseInputText
                  :id="`support_bot_${selectedNode.ref}_option_${optionIndex}_label`"
                  v-model="option.label"
                  :disabled="!form.editable"
                  :label="t('Label')"
                  :name="`option_${selectedNode.ref}_${optionIndex}_label`"
                  required
                  show-required-marker
                />

                <BaseSelect
                  :id="`support_bot_${selectedNode.ref}_option_${optionIndex}_action`"
                  v-model="option.actionType"
                  :disabled="!form.editable"
                  :label="t('Action')"
                  :name="`option_${selectedNode.ref}_${optionIndex}_action`"
                  :options="actionOptions"
                  @change="onActionTypeChange(option)"
                />

                <BaseSelect
                  v-if="option.actionType === 'goto_node'"
                  :id="`support_bot_${selectedNode.ref}_option_${optionIndex}_target`"
                  v-model="option.targetRef"
                  :disabled="!form.editable"
                  :label="t('Target node')"
                  :name="`option_${selectedNode.ref}_${optionIndex}_target`"
                  :options="targetNodeOptions(selectedNode.ref)"
                />

                <BaseInputText
                  v-else-if="option.actionType === 'open_link'"
                  :id="`support_bot_${selectedNode.ref}_option_${optionIndex}_url`"
                  v-model="option.targetUrl"
                  :disabled="!form.editable"
                  :help-text="t('Use a relative Chamilo path, for example /tickets.')"
                  :label="t('URL')"
                  :name="`option_${selectedNode.ref}_${optionIndex}_url`"
                />

                <BaseSelect
                  v-else-if="option.actionType === 'escalate_ticket'"
                  :id="`support_bot_${selectedNode.ref}_option_${optionIndex}_category`"
                  v-model="option.ticketCategoryId"
                  :disabled="!form.editable || !form.defaultTicketProjectId"
                  :label="t('Ticket category')"
                  :message-text="!form.defaultTicketProjectId ? t('Select a default ticket project first.') : null"
                  :name="`option_${selectedNode.ref}_${optionIndex}_ticket_category`"
                  :options="categoryOptions"
                />

                <BaseTextArea
                  v-else-if="option.actionType === 'end_conversation'"
                  :id="`support_bot_${selectedNode.ref}_option_${optionIndex}_closing`"
                  v-model="option.closingMessage"
                  :disabled="!form.editable"
                  :label="t('Closing message')"
                  :name="`option_${selectedNode.ref}_${optionIndex}_closing_message`"
                  rows="3"
                />
              </div>
            </div>
          </div>
        </template>

        <div
          v-else
          class="flex min-h-64 items-center justify-center text-sm text-gray-500"
        >
          {{ t("Select a node to edit it.") }}
        </div>
      </main>
    </div>
  </div>
</template>
