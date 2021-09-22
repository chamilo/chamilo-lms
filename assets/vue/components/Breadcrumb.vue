<template>
  <div class="q-card p-4">
    <q-breadcrumbs
        active-color="primary"
        large
    >
<!--      <q-breadcrumbs-el v-for ="item in items" :label="item.text" :to="item.href" />-->
      <q-breadcrumbs-el v-for="item in items" :label="item.text" :to="item.href" />
    </q-breadcrumbs>
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
      console.log('Breadcrumb.vue');
      const items = [
        {
          text: this.$t('Home'),
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
      if (this.legacy) {
        for (let i = 0, len = this.legacy.length; i < len; i += 1) {
          console.log(this.legacy[i]);
          items.push({
            text: this.legacy[i]['name'],
            //disabled: route.path === path || lastItem.path === route.path,
            href: this.legacy[i]['url']
          });
        }
      }


      console.log('resourceNode');
      if (this.resourceNode) {
        let folderParams = this.$route.query;
        var queryParams = '';
        for (var key in folderParams) {
          if (queryParams != '') {
              queryParams += "&";
          }
          queryParams += key + '=' + encodeURIComponent(folderParams[key]);
        }

        console.log(this.resourceNode);
        console.log(this.resourceNode.path);

        const parts = this.resourceNode.path.split('/');
        const { path, matched } = this.$route;
        const lastItem = matched[matched.length - 1];

        // Get course
        let courseId = document.body.getAttribute("data-course-id")
        if (courseId) {
          let courseCode = document.body.getAttribute("data-course-code")
          items.push({
            text:  courseCode,
            href: '/course/' + courseId + '/home?'+queryParams
          });
        }

        for (let i = 0, len = parts.length; i < len; i += 1) {
          let route = parts[i];
          let routeParts = route.split('-');
          if (0 === i) {
            let firstParts = parts[i+1].split('-');
            items.push({
              text:  matched[0].name,
              href: '/resources/document/' + firstParts[1]+ '/?'+queryParams
            });
            i++;
            continue;
          }
          if (routeParts[0]) {
            items.push({
              text: routeParts[0],
              href: '/resources/document/' + routeParts[1] + '/?' + queryParams
            });
          }
        }

        console.log('legacy');
        for (let i = 1, len = matched.length; i < len; i += 1) {
          const route = matched[i];
          console.log(route.name);
          if (route.path) {
            items.push({
              text: route.name,
              disabled: route.path === path || lastItem.path === route.path,
              href: route.path
            });
          }
        }
        console.log('BREADCRUMB');
        console.log(items);
      }

      return items;
    }
  }
};
</script>
