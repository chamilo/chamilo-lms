<template>
  <div class="space-y-4">
    <BaseToolbar>
      <template #start>
        <h3 class="text-lg font-semibold m-0">{{ t("Tasks") }}</h3>
      </template>
      <template #end>
        <BaseButton type="primary" icon="plus" :label="t('New Task')" @click="showCreate = true" />
        <BaseButton type="black" icon="account-plus" :label="t('Assign Task')" @click="showAssign = true" />
      </template>
    </BaseToolbar>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b text-sm font-medium">{{ t("Tasks list") }}</div>
        <div class="p-4 space-y-3">
          <div v-for="task in tasks" :key="task.id" class="p-3 border rounded bg-gray-20">
            <div class="flex items-start justify-between">
              <div>
                <div class="font-semibold">{{ task.title }}</div>
                <div class="text-sm text-gray-600">{{ task.description }}</div>
              </div>
              <span class="px-2 py-1 text-white text-xs rounded" :style="{ background: task.color }">
                {{ task.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border bg-white shadow-sm">
        <div class="p-3 border-b text-sm font-medium">{{ t("Assignments") }}</div>
        <div class="p-4 space-y-2">
          <div v-if="!assignments.length" class="text-sm text-gray-500">
            {{ t("No assignments yet.") }}
          </div>
          <div v-for="a in assignments" :key="a.id" class="flex items-center justify-between border rounded p-3">
            <div class="text-sm">
              <div class="font-medium">{{ taskTitle(a.taskId) }}</div>
              <div class="text-gray-500">{{ t("Assigned to") }} {{ a.user.name }}</div>
            </div>
            <div class="text-xs text-gray-500">{{ a.targetDate }}</div>
          </div>
        </div>
      </div>
    </div>

    <TaskCreateDialog v-if="showCreate" @close="showCreate=false" @created="reloadTasks" />
    <!-- English: Pass mapped members so BaseSelect sees {id,name} -->
    <AssignTaskDialog
      v-if="showAssign"
      :tasks="tasks"
      :members="membersForSelect"
      @close="showAssign=false"
      @assigned="reloadAssignments"
     blog-id=""/>
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

const { t } = useI18n()
const tasks = ref([])
const assignments = ref([])
const members = ref([])
const showCreate = ref(false)
const showAssign = ref(false)

const membersForSelect = computed(() =>
  members.value.map(m => ({ id: m.userId ?? m.id, name: m.name }))
)

function taskTitle(id){ return tasks.value.find(t=>t.id===id)?.title || `#${id}` }

async function reloadTasks(){ tasks.value = await service.listTasks() }
async function reloadAssignments(){ assignments.value = await service.listAssignments() }
async function reloadMembers(){
  members.value = await service.listMembers()
}

onMounted(async () => {
  await reloadTasks()
  await reloadAssignments()
  await reloadMembers()
})
</script>
