<template>
  <div class="space-y-4">
    <BaseToolbar>
      <template #start>
        <h3 class="text-lg font-semibold m-0">{{ t("Tasks") }}</h3>
      </template>
      <template #end>
        <BaseButton type="primary" icon="plus" :label="t('New task')" @click="openCreate" />
        <BaseButton type="black" icon="account-plus" :label="t('Assign task')" @click="showAssign = true" />
      </template>
    </BaseToolbar>

    <div class="grid md:grid-cols-2 gap-4">
      <!-- Tasks list -->
      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b text-sm font-medium">{{ t("Task list") }}</div>
        <div class="p-4 space-y-3">
          <div v-for="task in tasks" :key="task.id" class="p-3 border rounded bg-gray-20">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="font-semibold truncate">{{ task.title }}</div>
                <div class="text-sm text-gray-600 whitespace-pre-wrap">{{ task.description }}</div>
                <div class="mt-1 text-xs text-gray-500">#{{ task.id }}</div>
              </div>

              <div class="flex flex-col items-end gap-2 shrink-0">
                <span class="px-2 py-1 text-white text-xs rounded" :style="{ background: task.color }">
                  {{ task.system ? 'System' : 'Task' }}
                </span>

                <div class="flex gap-1" v-if="canEditTask(task) || canDeleteTask(task)">
                  <BaseButton
                    v-if="canEditTask(task)"
                    type="black"
                    :onlyIcon="true"
                    icon="edit"
                    :tooltip="t('Edit')"
                    @click="openEdit(task)"
                  />
                  <BaseButton
                    v-if="canDeleteTask(task)"
                    type="danger"
                    :onlyIcon="true"
                    icon="trash"
                    :tooltip="t('Delete')"
                    @click="removeTask(task)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Assignments -->
      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b text-sm font-medium">{{ t("Assignments") }}</div>

        <div class="p-4 space-y-2">
          <div v-if="!assignments.length" class="text-sm text-gray-500">
            {{ t("No assignments available") }}
          </div>

          <div v-for="a in assignments" :key="a.id" class="flex items-center justify-between border rounded p-3 gap-3">
            <div class="text-sm min-w-0">
              <div class="font-medium truncate">{{ taskTitle(a.taskId) }}</div>
              <div class="text-gray-500">
                {{ t("Assigned to") }} {{ a.user.name }}
              </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
              <div class="text-xs text-gray-500">{{ a.targetDate }}</div>
              <!-- Assignee can change their status; teacher/creator too -->
              <select
                v-if="canChangeAssignmentStatus(a)"
                class="border rounded h-8 px-2 text-sm"
                :value="a.status"
                @change="onChangeStatus(a, $event.target.value)"
                :aria-label="t('Change status')"
              >
                <option :value="0">Open</option>
                <option :value="1">In progress</option>
                <option :value="2">Waiting for validation</option>
                <option :value="3">Done</option>
              </select>

              <span v-else class="text-xs px-2 py-1 rounded border">
                {{ statusLabel(a.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialogs -->
    <TaskCreateDialog
      v-if="showCreate"
      mode="create"
      @close="showCreate=false"
      @created="reloadTasks"
    />
    <TaskCreateDialog
      v-if="editingTask"
      mode="edit"
      :task-id="editingTask.id"
      :initial="editingTask"
      :can-edit-status-template="isTeacherOrAdmin || isCreator(editingTask)"
      @close="editingTask=null"
      @saved="onEdited"
    />
    <AssignTaskDialog
      v-if="showAssign"
      :tasks="tasks"
      :members="membersForSelect"
      @close="showAssign=false"
      @assigned="reloadAssignments"
      blog-id=""
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import service from "../../services/blogs"
import TaskCreateDialog from "../../components/blog/TaskCreateDialog.vue"
import AssignTaskDialog from "../../components/blog/AssignTaskDialog.vue"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()
const currentUserId = computed(() => securityStore.user?.id || null)
const isTeacherOrAdmin = computed(() => securityStore.isTeacher || securityStore.isAdmin)

const tasks = ref([])
const assignments = ref([])
const members = ref([])
const showCreate = ref(false)
const showAssign = ref(false)
const editingTask = ref(null)

const membersForSelect = computed(() => members.value.map(m => ({ id: m.userId ?? m.id, name: m.name })))

function taskTitle(id){ return tasks.value.find(t=>t.id===id)?.title || `#${id}` }
function statusLabel(s){
  switch(Number(s)){
    case 1: return t("In progress")
    case 2: return t("Waiting for validation")
    case 3: return t("Done")
    default: return t("Open")
  }
}

// Permissions
function isCreator(task){
  return task?.authorId && currentUserId.value && task.authorId === currentUserId.value
}
function canEditTask(task){
  return isTeacherOrAdmin.value || isCreator(task)
}
function canDeleteTask(task){
  // Students cannot delete; only creator/teacher/admin
  return isTeacherOrAdmin.value || isCreator(task)
}
function canChangeAssignmentStatus(a){
  // Assignee can change; teacher/admin and creator of the task can change anyone's status
  if (isTeacherOrAdmin.value) return true
  const task = tasks.value.find(t => t.id === a.taskId)
  if (task && isCreator(task)) return true
  return a.user?.id && currentUserId.value && a.user.id === currentUserId.value
}

// Loaders
async function reloadTasks(){ tasks.value = await service.listTasks() }
async function reloadAssignments(){ assignments.value = await service.listAssignments() }
async function reloadMembers(){ members.value = await service.listMembers() }

onMounted(async () => {
  await reloadTasks()
  await reloadAssignments()
  await reloadMembers()
})

// Actions
function openCreate(){ showCreate.value = true }
function openEdit(task){ editingTask.value = { ...task } }
async function onEdited(){ editingTask.value = null; await reloadTasks() }

async function removeTask(task){
  if (!canDeleteTask(task)) return
  if (!confirm(t("Delete this task?"))) return
  try {
    await service.deleteTask(task.id)
    await reloadTasks()
    await reloadAssignments()
  } catch (e) {
    alert(t("Failed to delete the task."))
  }
}

async function onChangeStatus(a, val){
  try {
    await service.updateAssignment(a.id, { status: Number(val) })
    a.status = Number(val)
  } catch (e) {
    alert(t("Failed to update status."))
  }
}
</script>
