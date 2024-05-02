<template>
  <div class="template-list">
    <div
      v-for="template in templates"
      :key="template.id"
      class="template-item"
      @click="selectTemplate(template.content)"
    >
      <img :src="template.image" />
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
