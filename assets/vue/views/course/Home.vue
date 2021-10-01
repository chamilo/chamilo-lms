<template>
  <div v-if="course" class="grid gap-4">
    <div class="flex flex-row justify-between border-b-2 border-gray-200 ">
      <div class=" line-clamp-1 text-2xl font-bold">
        {{ course.title }}
      </div>

      <div>
        <div class="flex flex-row" v-if="isCurrentTeacher && course">
          <a class="btn btn-info mr-2 text-xs">
            <v-icon icon="mdi-eye" class="pr-2" />
            {{ $t('See as student') }}
          </a>

          <div class="relative">
            <!--          shadow rounded-full-->
            <button
                @click="dropdownOpen = !dropdownOpen"
                class="relative z-10 block h-8 w-8  overflow-hidden  focus:outline-none"
            >
              <v-icon>mdi-cog</v-icon>
            </button>

            <div
                v-show="dropdownOpen"
                @click="dropdownOpen = false"
                class="fixed inset-0 h-full w-full z-10"
            ></div>

            <div
                v-show="dropdownOpen"
                class="absolute right-0 mt-2 py-2 w-48 bg-white rounded-md shadow-xl z-20"
            >
              <q-list dense>
                <!--            <q-item replace :to="'/main/messages/index.php'" clickable class="">-->
                <!--              <q-item-section>Inbox</q-item-section>-->
                <!--            </q-item>-->
                <q-item
                    :href="goToCourseTool(course, tool)"
                    tag="a"
                    class=""
                    v-for="tool in tools.admin"
                >
                  <q-item-section>{{ tool.ctool.nameToTranslate }}</q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div v-if="isCurrentTeacher && course" class="bg-gradient-to-r from-gray-100 to-gray-50 flex flex-col rounded-md text-center p-2">
      <div class="p-10 text-center">
        <div>
          <v-icon
              icon="mdi-book-open-page-variant"
              size="72px"
              class="font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-ch-primary to-ch-primary-light"
          />

        </div>

        <div class="mt-2 font-bold">
          {{ $t("You don't have course content") }}
        </div>
        <div>
          {{ $t('Add a course introduction to display to your students.') }}
        </div>
        <a class="mt-2 btn btn-info">
          <v-icon>mdi-plus</v-icon>
          {{ $t('Course introduction') }}
        </a>
      </div>
    </div>

    <div v-if="isCurrentTeacher && course" class="flex justify-between border-b-2 border-gray-200">
      <div class="text-h6 font-bold">
        {{ $t('Tools') }}
      </div>
      <!--      <div>-->
      <!--        <v-icon>-->
      <!--          mdi-format-paint-->
      <!--        </v-icon>-->
      <!--        Customize-->
      <!--      </div>-->
    </div>

    <div
        class="grid gap-5 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-6"
    >
      <CourseToolList
          v-for="tool in tools.authoring"
          :course="course"
          :tool="tool"
          :go-to-course-tool="goToCourseTool"
          :change-visibility="changeVisibility"
          :go-to-setting-course-tool="goToSettingCourseTool"
      />

      <CourseToolList
          v-for="tool in tools.interaction"
          :course="course"
          :tool="tool"
          :go-to-course-tool="goToCourseTool"
          :change-visibility="changeVisibility"
          :go-to-setting-course-tool="goToSettingCourseTool"
      />

      <CourseToolList
          v-for="tool in tools.plugin"
          :course="course"
          :tool="tool"
          :go-to-course-tool="goToCourseTool"
          :change-visibility="changeVisibility"
          :go-to-setting-course-tool="goToSettingCourseTool"
      />

      <ShortCutList
          v-for="shortcut in shortcuts"
          :shortcut="shortcut"
          :go-to-short-cut="goToShortCut"
          :change-visibility="changeVisibility"
      />
    </div>
  </div>
</template>

<script>
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CourseToolList from '../../components/course/CourseToolList.vue';
import ShortCutList from '../../components/course/ShortCutList.vue';

import {useRoute} from 'vue-router'
import axios from "axios";
import {ENTRYPOINT} from '../../config/entrypoint';
import {reactive, toRefs} from 'vue'
import {mapGetters} from "vuex";

export default {
  name: 'Home',
  servicePrefix: 'Courses',
  components: {
    Loading,
    Toolbar,
    CourseToolList,
    ShortCutList
  },
  setup() {
    const state = reactive({
      course: [],
      tools: [],
      shortcuts: [],
      dropdownOpen: false,
      goToCourseTool,
      changeVisibility,
      goToSettingCourseTool,
      goToShortCut
    });
    const route = useRoute()
    let courseId = route.params.id;
    let sessionId = route.query.sid ?? 0;

    axios.get(ENTRYPOINT + '../course/' + courseId + '/home.json').then(response => {
      state.course = response.data.course;
      state.tools = response.data.tools;
      state.shortcuts = response.data.shortcuts;
    }).catch(function (error) {
      console.log(error);
    });

    function goToSettingCourseTool(course, tool) {
      return '/course/' + courseId + '/settings/' + tool.tool.name + '?sid=' + sessionId;
    }

    function goToCourseTool(course, tool) {
      return '/course/' + courseId + '/tool/' + tool.tool.name + '?sid=' + sessionId;
    }

    function goToShortCut(shortcut) {
      var url = new URLSearchParams('?')
      //let url = new URL(shortcut.url);
      url.append('cid', courseId);
      url.append('sid', sessionId);

      return shortcut.url + '?' + url;
    }

    function changeVisibility(course, tool) {
      axios.post(ENTRYPOINT + '../r/course_tool/links/' + tool.ctool.resourceNode.id + '/change_visibility').then(response => {
        if (response.data.ok) {
          tool.ctool.resourceNode.resourceLinks[0].visibility = response.data.visibility;
        }
      }).catch(function (error) {
        console.log(error);
      });
    }

    return toRefs(state);
  },
  computed: {
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
};
</script>
