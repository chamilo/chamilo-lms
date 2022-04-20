<template>
  <div class="p-2" style="margin-left: -0.25rem; margin-bottom: 0.5rem;">
    <q-breadcrumbs
        active-color="primary"
        large
    >
<!--      <q-breadcrumbs-el v-for ="item in items" :label="item.text" :to="item.href" />-->
      <q-breadcrumbs-el v-for="item in items" :label="item.text" :to="item.href" exact-path />
    </q-breadcrumbs>

      <a href="#" id="view-as-student-link" class="hidden btn btn-info mr-2 text-xs position-absolute right-20 top-0 z-10">
        <v-icon icon="mdi-eye" class="pr-2" />
        <span v-if="isCurrentTeacher">
          {{ $t('See as student') }}
        </span>
        <span v-else>
          {{ $t('See as trainer') }}
        </span>
      </a>
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
import isEmpty from 'lodash/isEmpty';

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
    ...mapGetters('session', {
      session: 'getSession',
    }),
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
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
        'MySessionsUpcoming',
        'MySessionsPast',
        'Home',
        'MessageList',
        'MessageNew',
        'MessageShow',
        'MessageCreate',
      ];

      if (!isEmpty(this.$route.name) && this.$route.name.includes('Page')) {
        items.push({
          text: this.$t('Pages'),
          href: '/resources/pages'
        });
      }

      if (!isEmpty(this.$route.name) && this.$route.name.includes('Message')) {
        items.push({
          text: this.$t('Messages'),
          //disabled: route.path === path || lastItem.path === route.path,
          href: '/resources/messages'
        });
      }


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

        let sessionTitle = '';
        if (this.session) {
          sessionTitle = ' (' + this.session.name + ') ';
        }

        items.push({
          text:  this.course.title + sessionTitle,
          href: '/course/' + this.course.id + '/home?'+queryParams
        });
      }

      console.log(items);

      const { path, matched } = this.$route;
      const lastItem = matched[matched.length - 1];

      if (this.resourceNode) {
        const parts = this.resourceNode.path.split('/');

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
      }

      for (let i = 1, len = matched.length; i < len; i += 1) {
        const route = matched[i];
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
      return items;
    }
  }
};
</script>
