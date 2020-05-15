<template>
  <div>
    <v-breadcrumbs :items="items" divider="/" :class="layoutClass" />
  </div>
</template>

<script>
export default {
  name: 'Breadcrumb',
  props: ['layoutClass'],
  data() {
    return {};
  },
  computed: {
    items() {
      const { path, matched } = this.$route;
      const items = [
        {
          text: 'Home',
          href: '/'
        }
      ];
      const lastItem = matched[matched.length - 1];
      for (let i = 0, len = matched.length; i < len; i += 1) {
        const route = matched[i];

        if (route.path) {
          items.push({
            text: route.name,
            disabled: route.path === path || lastItem.path === route.path,
            href: route.path
          });
        }
      }

      return items;
    }
  }
};
</script>
