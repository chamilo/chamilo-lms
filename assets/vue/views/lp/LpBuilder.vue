<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import LpBuilderItemForm from "../../components/lp/LpBuilderItemForm.vue"
import LpBulkAuthorPriceForm from "../../components/lp/LpBulkAuthorPriceForm.vue"
import LpBuilderPrerequisiteForm from "../../components/lp/LpBuilderPrerequisiteForm.vue"
import LpBuilderResourceList from "../../components/lp/LpBuilderResourceList.vue"
import LpBuilderTree from "../../components/lp/LpBuilderTree.vue"
import LpCertificateForm from "../../components/lp/LpCertificateForm.vue"
import LpInlineDocumentForm from "../../components/lp/LpInlineDocumentForm.vue"
import LpInlineDocumentUpload from "../../components/lp/LpInlineDocumentUpload.vue"
import LpItemAudioForm from "../../components/lp/LpItemAudioForm.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { showErrorNotification, showSuccessNotification } = useNotification()

const loading = ref(true)
const saving = ref(false)
const savingOrder = ref(false)
const addingResourceKey = ref("")
const builder = ref(null)
const tree = ref([])
const selectedId = ref(0)
const supportedToolKeys = new Set([
  "documents",
  "tests",
  "links",
  "assignments",
  "forums",
  "sections",
  "surveys",
  "certificate",
])
const requestedTool = String(route.query.lpTool || "")
const activeTool = ref(supportedToolKeys.has(requestedTool) ? requestedTool : "documents")
const activeDocumentTab = ref("files")
const activeDocumentAction = ref("list")
const prerequisiteAction = ref("")
const panelMode = ref("")
const formSubmitted = ref(false)
const sectionForm = reactive({ title: "", parentId: 0 })

const lpId = computed(() => Number(route.params.lpId || 0))
const nodeId = computed(() => Number(route.params.node || 0))
const context = computed(() => ({
  cid: Number(route.query.cid || 0),
  sid: Number(route.query.sid || 0),
  gid: Number(route.query.gid || 0),
}))

const canManage = computed(() => Boolean(builder.value?.canManageStructure))
const resources = computed(() => builder.value?.resources || {})
const selectedItem = computed(() => findItem(tree.value, selectedId.value))
const selectedSectionId = computed(() => (selectedItem.value?.isSection ? selectedItem.value.id : null))
const selectedTargetLabel = computed(() =>
  selectedItem.value ? selectedItem.value.displayTitle || selectedItem.value.title : builder.value?.title,
)
const sectionParentOptions = computed(() => buildParentOptions(0))
const selectedParentOptions = computed(() => buildParentOptions(selectedId.value))
const documentFolderOptions = computed(() => {
  const options = []
  const rootNodeId = Number(builder.value?.documentsRootNodeId || 0)

  if (rootNodeId > 0) {
    options.push({ label: t("Documents"), value: rootNodeId })
  }

  function appendFolders(items, depth = 0) {
    ;(items || []).forEach((item) => {
      if (!item?.isFolder || Number(item.resourceNodeId || 0) <= 0) {
        return
      }

      options.push({
        label: `${"— ".repeat(depth + 1)}${item.title}`,
        value: Number(item.resourceNodeId),
      })
      appendFolders(item.children || [], depth + 1)
    })
  }

  appendFolders(resources.value.documents?.files || [])

  return options
})

const titleEditorConfig = {
  height: 120,
  menubar: false,
  toolbar: "bold italic underline subscript superscript removeformat",
}

const tools = computed(() => [
  { key: "documents", label: t("Documents"), icon: "folder-open" },
  { key: "tests", label: t("Tests"), icon: "multiple-marked" },
  { key: "links", label: t("Links"), icon: "link" },
  { key: "assignments", label: t("Assignments"), icon: "inbox" },
  { key: "forums", label: t("Forums"), icon: "comment" },
  { key: "sections", label: t("Add section"), icon: "folder-multiple-plus" },
  { key: "surveys", label: t("Create survey"), icon: "form-dropdown" },
  { key: "certificate", label: t("Certificate"), icon: "gradebook" },
])

const prerequisiteOptions = computed(() => [
  { label: t("Prerequisites options"), value: "" },
  { label: t("Set previous step as prerequisite for each step"), value: "set_previous" },
  { label: t("Clear all prerequisites"), value: "clear" },
])

const previewUrl = computed(() =>
  lpService.buildRuntimeUrl(lpId.value, {
    ...context.value,
    node: nodeId.value,
    gradebook: Number(route.query.gradebook || 0),
    origin: String(route.query.origin || ""),
    isStudentView: "false",
  }),
)


const newTestUrl = computed(() => {
  const search = new URLSearchParams({
    cid: String(context.value.cid),
    sid: String(context.value.sid),
    gid: String(context.value.gid),
    lp_id: String(lpId.value),
    origin: "learnpath",
    returnToLp: "1",
    node: String(nodeId.value),
    parent: String(selectedSectionId.value || route.query.parent || ""),
    type: "step",
    isStudentView: "false",
    gradebook: String(Number(route.query.gradebook || 0)),
    lpTool: "tests",
  })

  return `/main/exercise/exercise_admin.php?${search.toString()}`
})

const learningPathQuery = computed(() => ({
  ...route.query,
  cid: context.value.cid,
  sid: context.value.sid,
  gid: context.value.gid,
  origin: "learnpath",
  lp_id: lpId.value,
  node: nodeId.value,
  type: "step",
  returnToLp: 1,
  parent: selectedSectionId.value || route.query.parent || "",
  isStudentView: "false",
  lpTool: activeTool.value,
}))

onMounted(async () => {
  await loadBuilder()

  if ("author-price" === String(route.query.panel || "") && builder.value?.bulkAuthorPrice?.enabled) {
    selectedId.value = 0
    panelMode.value = "author-price"
    return
  }

  const requestedItemId = Number(route.query.item_id || route.query.id || 0)
  const requestedItem = requestedItemId > 0 ? findItem(tree.value, requestedItemId) : null
  if (requestedItem) {
    selectedId.value = requestedItemId
    if ("audio" === String(route.query.panel || "") && !requestedItem.isSection && !requestedItem.isFinal) {
      panelMode.value = "audio"
    }
    return
  }

  const parentId = Number(route.query.parent || 0)
  const parent = parentId > 0 ? findItem(tree.value, parentId) : null
  if (parent?.isSection) {
    selectedId.value = parentId
  }
})

async function loadBuilder(showLoading = true) {
  if (showLoading) {
    loading.value = true
  }

  try {
    builder.value = await lpService.getBuilder(lpId.value, context.value)
    tree.value = cloneItems(builder.value.items || [])
    if (selectedId.value && !findItem(tree.value, selectedId.value)) {
      selectedId.value = 0
    }
  } catch (error) {
    showErrorNotification(error)
  } finally {
    if (showLoading) {
      loading.value = false
    }
  }
}

function cloneItems(items) {
  return items.map((item) => ({ ...item, children: cloneItems(item.children || []) }))
}

function findItem(items, id) {
  for (const item of items) {
    if (item.id === id) {
      return item
    }
    const child = findItem(item.children || [], id)
    if (child) {
      return child
    }
  }

  return null
}

function selectItem(id) {
  selectedId.value = id

  const item = findItem(tree.value, id)
  if ("audio" === String(route.query.panel || "") && item && !item.isSection && !item.isFinal) {
    panelMode.value = "audio"
  }
}

function openItemEditor(id) {
  const item = findItem(tree.value, id)
  if (!item) {
    return
  }

  selectedId.value = id
  panelMode.value = "edit"
}

function openItemPrerequisite(id) {
  const item = findItem(tree.value, id)
  if (!item || item.isSection) {
    return
  }

  selectedId.value = id
  panelMode.value = "prerequisite"
}

function openItemAudio(id) {
  const item = findItem(tree.value, id)
  if (!item || item.isSection || item.isFinal) {
    return
  }

  selectedId.value = id
  panelMode.value = "audio"
}

function openBulkAuthorPrice() {
  if (!builder.value?.bulkAuthorPrice?.enabled) {
    return
  }

  selectedId.value = 0
  panelMode.value = "author-price"
}

function buildParentOptions(excludedItemId) {
  const excludedIds = new Set()
  const currentItem = excludedItemId ? findItem(tree.value, excludedItemId) : null

  function excludeBranch(item) {
    excludedIds.add(item.id)
    ;(item.children || []).forEach(excludeBranch)
  }

  if (currentItem) {
    excludeBranch(currentItem)
  }

  const options = [{ label: builder.value?.title || t("Learning path"), value: 0 }]

  function appendSections(items, depth = 0) {
    items.forEach((item) => {
      if (!item.isSection || excludedIds.has(item.id)) {
        return
      }

      options.push({
        label: `${"— ".repeat(depth + 1)}${item.displayTitle || item.title}`,
        value: item.id,
      })
      appendSections(item.children || [], depth + 1)
    })
  }

  appendSections(tree.value)

  return options
}

async function createSection() {
  formSubmitted.value = true
  if (!String(sectionForm.title || "").replace(/<[^>]*>/g, "").trim()) {
    showErrorNotification(t("Title is required"))
    return
  }

  saving.value = true
  try {
    const created = await lpService.createBuilderSection(lpId.value, context.value, {
      title: sectionForm.title,
      parentId: sectionForm.parentId,
      csrfToken: builder.value.csrfToken,
    })
    sectionForm.title = ""
    selectedId.value = Number(created.id || 0)
    showSuccessNotification(t("Added"))
    await loadBuilder()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

function confirmDeleteItem(id) {
  const item = findItem(tree.value, id)
  if (!item) {
    return
  }

  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: async () => {
      try {
        await lpService.deleteBuilderItem(lpId.value, id, context.value, {
          csrfToken: builder.value.csrfToken,
        })
        if (selectedId.value === id) {
          selectedId.value = 0
          panelMode.value = ""
        }
        showSuccessNotification(t("Deleted"))
        await loadBuilder()
      } catch (error) {
        showErrorNotification(error)
      }
    },
  })
}

async function addResource(resource, parentId = selectedSectionId.value, exportAllowed = false) {
  if (!resource?.canAdd || !canManage.value) {
    return
  }

  const key = `${resource.resourceType}-${resource.id}`
  addingResourceKey.value = key
  try {
    const created = await lpService.addBuilderResource(lpId.value, context.value, {
      resourceType: resource.resourceType,
      resourceId: Number(resource.id),
      parentId: parentId || null,
      exportAllowed: Boolean(exportAllowed),
      csrfToken: builder.value.csrfToken,
    })
    selectedId.value = Number(created.id || 0)
    showSuccessNotification(t("Added"))
    await loadBuilder(false)
  } catch (error) {
    showErrorNotification(error)
  } finally {
    addingResourceKey.value = ""
  }
}

async function addResources(resourceItems, parentId = selectedSectionId.value, exportAllowed = false) {
  const validResources = (resourceItems || []).filter((resource) => resource?.canAdd)
  if (validResources.length === 0 || !canManage.value) {
    return false
  }

  addingResourceKey.value = "upload"
  let addedCount = 0

  try {
    let lastCreatedId = 0

    for (const resource of validResources) {
      const created = await lpService.addBuilderResource(lpId.value, context.value, {
        resourceType: resource.resourceType,
        resourceId: Number(resource.id),
        parentId: parentId || null,
        exportAllowed: Boolean(exportAllowed),
        csrfToken: builder.value.csrfToken,
      })
      lastCreatedId = Number(created.id || lastCreatedId)
      addedCount += 1
    }

    selectedId.value = lastCreatedId
    showSuccessNotification(t("Added"))
    await loadBuilder(false)

    return true
  } catch (error) {
    if (addedCount > 0) {
      await loadBuilder(false)
    }
    showErrorNotification(error)

    return false
  } finally {
    addingResourceKey.value = ""
  }
}

async function handleItemSaved(itemId) {
  selectedId.value = Number(itemId || selectedId.value)
  panelMode.value = ""
  await loadBuilder(false)
}

async function handlePrerequisiteSaved(itemId) {
  selectedId.value = Number(itemId || selectedId.value)
  panelMode.value = ""
  await loadBuilder(false)
}

async function handleAudioSaved(itemId) {
  selectedId.value = Number(itemId || selectedId.value)
  panelMode.value = "audio"
  await loadBuilder(false)
}

async function handleBulkAuthorPriceSaved() {
  panelMode.value = "author-price"
  await loadBuilder(false)
}

function handleResourceDrop({ resource, parentId }) {
  addResource(resource, parentId)
}

async function onStructureChanged() {
  if (!canManage.value || savingOrder.value) {
    return
  }

  moveFinalItemToEnd()
  savingOrder.value = true
  try {
    await lpService.reorderBuilderItems(lpId.value, context.value, {
      order: flattenTree(tree.value),
      csrfToken: builder.value.csrfToken,
    })
    showSuccessNotification(t("Updated"))
    await loadBuilder()
  } catch (error) {
    showErrorNotification(error)
    await loadBuilder()
  } finally {
    savingOrder.value = false
  }
}

function flattenTree(items, parentId = null, result = []) {
  items.forEach((item) => {
    result.push({ id: item.id, parentId })
    flattenTree(item.children || [], item.id, result)
  })

  return result
}

function moveFinalItemToEnd() {
  const index = tree.value.findIndex((item) => item.isFinal)
  if (index < 0 || index === tree.value.length - 1) {
    return
  }
  const [finalItem] = tree.value.splice(index, 1)
  finalItem.children = []
  tree.value.push(finalItem)
}

async function applyPrerequisiteAction() {
  const action = prerequisiteAction.value
  if (!action) {
    return
  }

  try {
    await lpService.updateBuilderPrerequisites(lpId.value, context.value, {
      action,
      csrfToken: builder.value.csrfToken,
    })
    showSuccessNotification(t("Updated"))
    await loadBuilder()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    prerequisiteAction.value = ""
  }
}

function openRoute(name, params = {}, query = {}) {
  return router.push({
    name,
    params: { node: nodeId.value, ...params },
    query: { ...learningPathQuery.value, ...query },
  })
}

function openCreateForum() {
  return openRoute("ForumList", {}, { action: "add", create: "forum" })
}

function openCreateAssignment() {
  return openRoute("AssignmentsCreate")
}

function selectTool(toolKey) {
  if (!supportedToolKeys.has(toolKey)) {
    return
  }

  activeTool.value = toolKey
  panelMode.value = ""
  if ("documents" === toolKey) {
    activeDocumentAction.value = "list"
  }

  router.replace({
    name: route.name,
    params: route.params,
    query: { ...route.query, lpTool: toolKey },
  })
}

function selectDocumentList(tab) {
  panelMode.value = ""
  activeDocumentTab.value = tab
  activeDocumentAction.value = "list"
}

async function handleInlineDocumentCreated({ resource, parentId, exportAllowed }) {
  await addResource(resource, parentId, exportAllowed)
  activeDocumentAction.value = "list"
}

async function handleInlineDocumentUploaded({ resources: uploadedResources, parentId, exportAllowed }) {
  const wasAdded = await addResources(uploadedResources, parentId, exportAllowed)
  if (wasAdded) {
    activeDocumentAction.value = "list"
  }
}

async function handleCertificateSaved(result) {
  selectedId.value = Number(result?.itemId || 0)
  panelMode.value = ""
  await loadBuilder()
}

function goBack() {
  router.push({ name: "LpList", query: route.query })
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-20 pb-3">
      <div class="flex items-center gap-1">
        <BaseButton
          :label="t('Back')"
          icon="back"
          only-icon
          type="primary-text"
          @click="goBack"
        />
        <BaseButton
          :label="t('Preview')"
          :to-url="previewUrl"
          icon="eye-on"
          only-icon
          type="primary-text"
        />
        <BaseButton
          :label="t('Settings')"
          :route="{ name: 'LpSettings', params: { lpId }, query: route.query }"
          icon="settings"
          only-icon
          type="secondary-text"
        />
        <BaseButton
          v-if="builder?.bulkAuthorPrice?.enabled"
          :label="t('Author')"
          icon="join-group"
          only-icon
          type="secondary-text"
          @click="openBulkAuthorPrice"
        />
      </div>

      <div class="w-full sm:w-80">
        <BaseSelect
          id="lp-builder-prerequisite-options"
          v-model="prerequisiteAction"
          :disabled="!canManage"
          :label="t('Prerequisites options')"
          :options="prerequisiteOptions"
          name="prerequisiteAction"
          option-label="label"
          option-value="value"
          @change="applyPrerequisiteAction"
        />
      </div>
    </div>

    <div
      v-if="loading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-gray-50"
    >
      {{ t("Loading") }}...
    </div>

    <div
      v-else
      class="grid gap-4 xl:h-[calc(100vh-12rem)] xl:grid-cols-[minmax(320px,34%)_minmax(0,1fr)] xl:overflow-hidden"
    >
      <div class="self-start xl:h-full xl:min-h-0 xl:overflow-y-auto xl:overscroll-contain">
        <BaseCard>
          <template #header>
            <button
              class="w-full border-l-4 border-secondary px-3 py-2 text-left text-body-1 font-semibold text-gray-90"
              type="button"
              @click="selectedId = 0; panelMode = ''"
            >
              {{ builder?.title || t("Learning path") }}
            </button>
          </template>

          <div class="p-3">
            <LpBuilderTree
              v-model:items="tree"
              :can-manage="canManage && !savingOrder"
              :parent-id="0"
              :selected-id="selectedId"
              @delete="confirmDeleteItem"
              @edit="openItemEditor"
              @prerequisite="openItemPrerequisite"
              @resource-drop="handleResourceDrop"
              @select="selectItem"
              @structure-changed="onStructureChanged"
            />
            <div
              v-if="savingOrder"
              class="pt-2 text-center text-caption text-gray-50"
            >
              {{ t("Loading") }}...
            </div>
          </div>
        </BaseCard>
      </div>

      <div class="min-w-0 space-y-3 xl:h-full xl:min-h-0 xl:overflow-y-auto xl:overscroll-contain xl:pr-1">
        <BaseCard>
          <div class="flex flex-wrap items-center justify-start gap-2 p-3">
            <BaseButton
              v-for="tool in tools"
              :key="tool.key"
              :label="tool.label"
              :icon="tool.icon"
              class="lp-builder-tool-button !h-20 !w-20"
              only-icon
              :type="activeTool === tool.key ? 'primary' : 'primary-text'"
              @click="selectTool(tool.key)"
            />
          </div>
        </BaseCard>

        <BaseCard>
          <div class="space-y-4 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div
                class="font-semibold"
                :class="selectedItem ? 'text-body-1 text-gray-90' : 'text-body-2 text-gray-50'"
              >
                {{ selectedTargetLabel || t("Learning path") }}
              </div>

              <div
                v-if="selectedItem && canManage"
                class="flex items-center gap-1"
              >
                <BaseButton
                  :label="t('Edit')"
                  icon="edit"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="openItemEditor(selectedItem.id)"
                />
                <BaseButton
                  v-if="!selectedItem.isSection"
                  :label="t('Prerequisites')"
                  icon="graph"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="openItemPrerequisite(selectedItem.id)"
                />
                <BaseButton
                  v-if="!selectedItem.isSection && !selectedItem.isFinal"
                  :label="t('Add audio')"
                  icon="record-add"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="openItemAudio(selectedItem.id)"
                />
                <BaseButton
                  :label="t('Delete')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmDeleteItem(selectedItem.id)"
                />
              </div>
            </div>

            <LpCertificateForm
              v-if="panelMode === 'edit' && selectedItem?.isFinal"
              :certificate="builder?.certificate || {}"
              :context="context"
              :csrf-token="builder?.csrfToken || ''"
              :documents-root-node-id="Number(builder?.documentsRootNodeId || 0)"
              :lp-id="lpId"
              @saved="handleCertificateSaved"
            />

            <LpBuilderItemForm
              v-else-if="panelMode === 'edit' && selectedItem"
              :context="context"
              :csrf-token="builder?.csrfToken || ''"
              :item="selectedItem"
              :lp-id="lpId"
              :parent-options="selectedParentOptions"
              :title-as-html="Boolean(builder?.titleAsHtml)"
              @saved="handleItemSaved"
            />

            <LpBuilderPrerequisiteForm
              v-else-if="panelMode === 'prerequisite' && selectedItem"
              :context="context"
              :csrf-token="builder?.csrfToken || ''"
              :item="selectedItem"
              :items="tree"
              :lp-id="lpId"
              @saved="handlePrerequisiteSaved"
            />

            <LpItemAudioForm
              v-else-if="panelMode === 'audio' && selectedItem"
              :audio-items="resources.audio?.items || []"
              :context="context"
              :csrf-token="builder?.csrfToken || ''"
              :documents-root-node-id="Number(builder?.documentsRootNodeId || 0)"
              :item="selectedItem"
              :lp-id="lpId"
              @saved="handleAudioSaved"
            />

            <LpBulkAuthorPriceForm
              v-else-if="panelMode === 'author-price' && builder?.bulkAuthorPrice?.enabled"
              :configuration="builder.bulkAuthorPrice"
              :context="context"
              :csrf-token="builder?.csrfToken || ''"
              :items="tree"
              :lp-id="lpId"
              @saved="handleBulkAuthorPriceSaved"
            />

            <template v-else-if="activeTool === 'documents'">
              <div class="flex flex-wrap gap-2 border-b border-gray-20 pb-3">
                <BaseButton
                  :label="t('Files')"
                  :type="activeDocumentAction === 'list' && activeDocumentTab === 'files' ? 'primary' : 'plain'"
                  @click="selectDocumentList('files')"
                />
                <BaseButton
                  :label="t('Videos')"
                  :type="activeDocumentAction === 'list' && activeDocumentTab === 'videos' ? 'primary' : 'plain'"
                  @click="selectDocumentList('videos')"
                />
                <BaseButton
                  :label="t('Create a new document')"
                  icon="file-add"
                  :type="activeDocumentAction === 'create' ? 'primary' : 'success-text'"
                  @click="panelMode = ''; activeDocumentAction = 'create'"
                />
                <BaseButton
                  :label="t('Upload')"
                  icon="file-upload"
                  :type="activeDocumentAction === 'upload' ? 'primary' : 'success-text'"
                  @click="panelMode = ''; activeDocumentAction = 'upload'"
                />
              </div>

              <template v-if="activeDocumentAction === 'list'">
                <LpBuilderResourceList
                  v-if="activeDocumentTab === 'files'"
                  :can-manage="canManage"
                  :items="resources.documents?.files || []"
                  @add="addResource"
                />
                <LpBuilderResourceList
                  v-else
                  :can-manage="canManage"
                  :items="resources.documents?.videos || []"
                  @add="addResource"
                />
              </template>

              <LpInlineDocumentForm
                v-else-if="activeDocumentAction === 'create'"
                :context="context"
                :default-document-parent-id="Number(builder?.documentsRootNodeId || 0)"
                :default-lp-parent-id="selectedSectionId"
                :document-folder-options="documentFolderOptions"
                :lp-parent-options="sectionParentOptions"
                :search-enabled="Boolean(builder?.searchEnabled)"
                @created="handleInlineDocumentCreated"
              />

              <LpInlineDocumentUpload
                v-else
                :context="context"
                :default-document-parent-id="Number(builder?.documentsRootNodeId || 0)"
                :document-folder-options="documentFolderOptions"
                :file-kind="activeDocumentTab"
                :lp-parent-id="selectedSectionId"
                :search-enabled="Boolean(builder?.searchEnabled)"
                @uploaded="handleInlineDocumentUploaded"
              />
            </template>

            <template v-else-if="activeTool === 'tests'">
              <div class="flex justify-end">
                <BaseButton
                  :label="t('New test')"
                  :to-url="newTestUrl"
                  icon="multiple-marked"
                  type="success"
                />
              </div>
              <LpBuilderResourceList
                :can-manage="canManage"
                :items="resources.tests?.items || []"
                @add="addResource"
              />
            </template>

            <template v-else-if="activeTool === 'links'">
              <div class="flex justify-end">
                <BaseButton
                  :label="t('Add a link')"
                  icon="link-add"
                  type="success"
                  @click="openRoute('CreateLink')"
                />
              </div>
              <LpBuilderResourceList
                :can-manage="canManage"
                :items="resources.links?.items || []"
                @add="addResource"
              />
            </template>

            <template v-else-if="activeTool === 'assignments'">
              <div class="flex justify-end">
                <BaseButton
                  :label="t('Create assignment')"
                  icon="plus"
                  type="success"
                  @click="openCreateAssignment"
                />
              </div>
              <LpBuilderResourceList
                :can-manage="canManage"
                :items="resources.assignments?.items || []"
                @add="addResource"
              />
            </template>

            <template v-else-if="activeTool === 'forums'">
              <div class="flex justify-end">
                <BaseButton
                  :label="t('Create a new forum')"
                  icon="plus"
                  type="success"
                  @click="openCreateForum"
                />
              </div>
              <div
                v-if="resources.forums?.items?.length"
                class="space-y-3"
              >
                <div
                  v-for="forum in resources.forums.items"
                  :key="forum.id"
                  class="rounded-lg border border-gray-20"
                >
                  <div class="p-2">
                    <LpBuilderResourceList
                      :can-manage="canManage"
                      :items="[forum]"
                      @add="addResource"
                    />
                  </div>
                  <div
                    v-if="forum.threads?.length"
                    class="border-t border-gray-20 bg-gray-10 p-2 pl-6"
                  >
                    <LpBuilderResourceList
                      :can-manage="canManage"
                      :items="forum.threads"
                      @add="addResource"
                    />
                  </div>
                </div>
              </div>
              <div
                v-else
                class="rounded-lg border border-dashed border-gray-25 p-6 text-center text-gray-50"
              >
                {{ t("No data available") }}
              </div>
            </template>

            <template v-else-if="activeTool === 'sections'">
              <div class="grid gap-4 lg:grid-cols-2">
                <BaseTinyEditor
                  v-if="builder?.titleAsHtml"
                  editor-id="lp-builder-new-section-title"
                  v-model="sectionForm.title"
                  :editor-config="titleEditorConfig"
                  :title="t('Title')"
                  required
                />
                <BaseInputText
                  v-else
                  id="lp-builder-new-section-title"
                  v-model="sectionForm.title"
                  :error-text="t('This field cannot be empty')"
                  :form-submitted="formSubmitted"
                  :is-invalid="formSubmitted && !String(sectionForm.title || '').trim()"
                  :label="t('Title')"
                  name="sectionTitle"
                  required
                />
                <BaseSelect
                  id="lp-builder-new-section-parent"
                  v-model="sectionForm.parentId"
                  :label="t('Parent')"
                  :options="sectionParentOptions"
                  name="sectionParentId"
                  option-label="label"
                  option-value="value"
                />
              </div>
              <div class="flex justify-end">
                <BaseButton
                  :is-loading="saving"
                  :label="t('Add section')"
                  icon="save"
                  type="success"
                  @click="createSection"
                />
              </div>
            </template>

            <template v-else-if="activeTool === 'surveys'">
              <div class="flex justify-end">
                <BaseButton
                  :label="t('Create survey')"
                  icon="plus"
                  type="success"
                  @click="openRoute('SurveyCreate')"
                />
              </div>
              <LpBuilderResourceList
                :can-manage="canManage"
                :items="resources.surveys?.items || []"
                @add="addResource"
              />
            </template>

            <LpCertificateForm
              v-else-if="activeTool === 'certificate'"
              :certificate="builder?.certificate || {}"
              :context="context"
              :csrf-token="builder?.csrfToken || ''"
              :documents-root-node-id="Number(builder?.documentsRootNodeId || 0)"
              :lp-id="lpId"
              @saved="handleCertificateSaved"
            />
          </div>
        </BaseCard>
      </div>
    </div>

  </div>
</template>
<style scoped>
:deep(.lp-builder-tool-button .p-button-icon) {
  font-size: 3rem;
  line-height: 1;
}
</style>
