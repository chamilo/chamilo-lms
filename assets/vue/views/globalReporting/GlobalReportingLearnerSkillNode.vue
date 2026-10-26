<template>
  <li
    v-if="'hierarchical' === mode"
    :data-testid="`learner-skill-node-${node.id}`"
  >
    <component
      :is="node.acquired ? 'a' : 'span'"
      :href="node.acquired ? skillUrl(node) : undefined"
      :class="{ 'opacity-40': !node.acquired, 'hover:text-primary': node.acquired }"
      class="inline-flex items-center gap-2 text-sm text-gray-90"
    >
      <img
        :src="node.image"
        :alt="node.title"
        class="h-8 w-8 shrink-0 object-contain"
      />
      <span>{{ caption }}</span>
    </component>
    <ul
      v-if="node.children.length"
      class="ms-4 mt-2 space-y-2 border-s border-gray-25 ps-4"
    >
      <GlobalReportingLearnerSkillNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :mode="mode"
        :level="level + 1"
        :skill-url="skillUrl"
      />
    </ul>
  </li>

  <div
    v-else
    :data-testid="`learner-skill-node-${node.id}`"
    class="inline-flex flex-col items-center text-center align-top"
  >
    <component
      :is="node.acquired ? 'a' : 'span'"
      :href="node.acquired ? skillUrl(node) : undefined"
      :title="caption"
      :class="{ 'opacity-40': !node.acquired, 'hover:text-primary': node.acquired }"
      class="flex flex-col items-center gap-1 text-sm text-gray-90"
    >
      <img
        :src="node.image"
        :alt="node.title"
        :class="2 === level ? 'h-16 w-16' : 'h-8 w-8'"
        class="object-contain"
      />
      <span v-if="level <= 2">{{ caption }}</span>
    </component>
    <div
      v-if="node.children.length"
      class="mt-1 flex flex-wrap justify-center gap-2"
    >
      <GlobalReportingLearnerSkillNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :mode="mode"
        :level="level + 1"
        :skill-url="skillUrl"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue"

// One skill of the learner's skill tree, drawn as SkillModel::processVertex() does on the legacy
// learner report: a skill the learner has not acquired is only there to place the others, so it is faded.
const props = defineProps({
  node: { type: Object, required: true },
  // "hierarchical" nests the skills in lists; "categories" lays them out side by side, where the
  // first level under a category shows a bigger badge and the deeper levels show no title.
  mode: { type: String, required: true },
  level: { type: Number, required: true },
  skillUrl: { type: Function, required: true },
})

// As SkillModel::processSkillListSimple(), the short code replaces the title when there is one
const caption = computed(() => props.node.shortCode || props.node.title)
</script>
