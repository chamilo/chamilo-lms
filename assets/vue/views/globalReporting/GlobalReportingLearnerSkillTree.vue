<template>
  <div
    v-if="'hierarchical' === mode"
    class="flex items-start gap-8 overflow-x-auto"
    data-testid="learner-skill-tree"
  >
    <ul
      v-for="node in nodes"
      :key="node.id"
      class="shrink-0 space-y-2"
    >
      <GlobalReportingLearnerSkillNode
        :node="node"
        :mode="mode"
        :level="1"
        :skill-url="skillUrl"
      />
    </ul>
  </div>

  <div
    v-else
    class="space-y-4"
    data-testid="learner-skill-tree"
  >
    <section
      v-for="category in nodes"
      :key="category.id"
    >
      <h3 class="mb-2 text-base font-semibold text-gray-90">{{ category.title }}</h3>
      <div
        v-if="category.children.length"
        class="flex flex-wrap items-start gap-4"
      >
        <GlobalReportingLearnerSkillNode
          v-for="node in category.children"
          :key="node.id"
          :node="node"
          :mode="mode"
          :level="2"
          :skill-url="skillUrl"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import GlobalReportingLearnerSkillNode from "./GlobalReportingLearnerSkillNode.vue"

// The two skill tree layouts of SkillModel::getUserSkillsTable(): one column per branch under the root
// ("hierarchical"), or one heading per branch with its skills side by side ("categories").
defineProps({
  mode: { type: String, required: true },
  nodes: { type: Array, required: true },
  skillUrl: { type: Function, required: true },
})
</script>
