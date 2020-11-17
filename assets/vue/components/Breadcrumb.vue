<template>
  <div>
    <b-breadcrumb
      :items="items"
      divider="/"
      :class="layoutClass"
    />
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: 'Breadcrumb',
  props: ['layoutClass', 'legacy'],
  data() {
    return {
      //legacy:[],
    };
  },
  computed: {
    ...mapGetters('resourcenode', {
      resourceNode: 'getResourceNode',
    }),
    items() {
      const items = [
        {
          text: 'Home',
          href: '/'
        }
      ];

      // Course
      /*if (this.$route.query.cid) {
        items.push({
          text: this.$route.query.cid,
          //disabled: route.path === path || lastItem.path === route.path,
          href: '/course/' + this.$route.query.cid + '/home'
        });
      }*/
      for (let i = 0, len = this.legacy.length; i < len; i += 1) {
          items.push({
            text: this.legacy[i]['name'] ,
            //disabled: route.path === path || lastItem.path === route.path,
            href: this.legacy[i]['url']
          });
      }

      const { path, matched } = this.$route;
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

      if (this.resourceNode) {
        let folderParams = this.$route.query;
        var queryParams = '';
        for (var key in folderParams) {
          if (queryParams != '') {
            queryParams += "&";
          }
          queryParams += key + '=' + encodeURIComponent(folderParams[key]);
        }

        let path = this.resourceNode.path;
        const parts = path.split('`');

        for (let i = 0, len = parts.length; i < len; i += 1) {
          let route = parts[i];
          let routeParts = route.split('-');

          if ('localhost' === routeParts[0]) {
            continue;
          }

          items.push({
            text:  routeParts[0],
            href: '/resources/document/' + routeParts[1]+ '/?'+queryParams
          });
        }
      }

      return items;
    }
  }
};
</script>
