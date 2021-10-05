<template>
  <div class="p-2" style="margin-left: -0.25rem; margin-bottom: 0.5rem">
    <q-breadcrumbs
        active-color="primary"
        large
    >
<!--      <q-breadcrumbs-el v-for ="item in items" :label="item.text" :to="item.href" />-->
      <q-breadcrumbs-el v-for="item in items" :label="item.text" :to="item.href" exact-path />
    </q-breadcrumbs>

<!--    <v-breadcrumbs-->
<!--        rounded-->
<!--        density="compact"-->
<!--    >-->

<!--        <a  v-for="item in items" :href="item.href"  >-->
<!--          <v-breadcrumbs-item>-->
<!--          {{item.text}}-->
<!--            </v-breadcrumbs-item>-->
<!--        </a>-->
<!--    </v-breadcrumbs>-->
  </div>
</template>

<script>
import {mapGetters} from "vuex";

export default {
  name: 'Breadcrumb',
  props: ['layoutClass', 'legacy'],
  computed: {
    ...mapGetters('resourcenode', {
      resourceNode: 'getResourceNode',
    }),
    ...mapGetters('course', {
      course: 'getCourse',
    }),
    items() {
      console.log('Breadcrumb.vue');
      console.log(this.$route.name);

      const items = [
        {
          text: this.$t('Home'),
          href: '/'
        }
      ];

      const list = [
        'CourseHome',
        'MyCourses',
        'MySessions',
        'Home',
      ];

      if (list.includes(this.$route.name)) {
        return items;
      }

      if (this.legacy) {
        console.log('legacy');
        // Checking data from legacy main (1.11.x)
        const mainUrl = window.location.href;
        const mainPath = mainUrl.indexOf("main/");

        for (let i = 0, len = this.legacy.length; i < len; i += 1) {
          console.log(this.legacy[i]['name']);
          let url = this.legacy[i]['url'].toString();

          let newUrl = url;
          if (url.indexOf("main/") > 0) {
            newUrl = '/' + url.substring(mainPath, url.length);
          }

          if (newUrl === '/') {
            newUrl = '#';
          }

          items.push({
            text: this.legacy[i]['name'],
            //disabled: route.path === path || lastItem.path === route.path,
            href: newUrl
          });
        }
      }

      let folderParams = this.$route.query;
      var queryParams = '';
      for (var key in folderParams) {
        if (queryParams != '') {
          queryParams += "&";
        }
        queryParams += key + '=' + encodeURIComponent(folderParams[key]);
      }

      // course is set in documents/List.vue
      if (this.course) {
        console.log('copursssss');
        // First node
        items.push({
          text:  this.course.title,
          href: '/course/' + this.course.id + '/home?'+queryParams
        });
      }

      console.log(items);
      if (this.resourceNode) {
        console.log('resourceNode');
        console.log(this.resourceNode);
        console.log(this.resourceNode.path);

        const parts = this.resourceNode.path.split('/');
        const { path, matched } = this.$route;
        const lastItem = matched[matched.length - 1];

        for (let i = 0, len = parts.length; i < len; i += 1) {
          let route = parts[i];
          let routeParts = route.split('-');
          if (0 === i) {
            let firstParts = parts[i + 1].split('-');
            items.push({
              text: matched[0].name,
              href: '/resources/document/' + firstParts[1] + '/?' + queryParams
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
      }

      console.log('BREADCRUMB');
      console.log(items);
      return items;
    }
  }
};
</script>
