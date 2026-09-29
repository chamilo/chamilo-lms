<template>
  <div class="template-list">
    <div
      v-for="template in templates"
      :key="template.id"
      class="template-item"
      @click="selectTemplate(template.content)"
    >
      <img
        v-if="template.image"
        :alt="template.title"
        :src="template.image"
      />
      <div
        v-else
        aria-hidden="true"
        class="flex h-[70px] w-[100px] flex-col gap-2 rounded border border-gray-20 bg-white p-2"
      >
        <span class="h-2 w-2/3 rounded bg-gray-40" />
        <span class="h-1 w-full rounded bg-gray-20" />
        <span class="h-1 w-5/6 rounded bg-gray-20" />
        <span class="h-1 w-3/4 rounded bg-gray-20" />
      </div>
      <div>{{ template.title }}</div>
    </div>
  </div>
</template>

<script>
export default {
  name: "TemplateList",
  props: {
    templates: {
      type: Array,
      required: true,
    },
  },
  methods: {
    selectTemplate(content) {
      this.$emit("template-selected", content)
    },
  },
}
</script>
