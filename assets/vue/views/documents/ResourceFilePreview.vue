<template>
  <div>
    {{ file.name }}
  </div>
</template>

<script>
const servicePrefix = 'Documents';
export default {
  name: 'ResourceFilePreview',
  servicePrefix,
  components: {
  },
  data() {
    return {
      file: {},
    };
  },
  computed: {
  },
  created() {

  },
};
</script>
