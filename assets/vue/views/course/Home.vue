<template>
  <div v-if="course" class="grid gap-4">
    <div class="flex flex-row justify-between border-b-2 border-gray-200 ">
      <div class="line-clamp-1 text-2xl font-bold">
        {{ course.title }}
        <span v-if="session">
          ({{ session.name }})
        </span>
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
                  <q-item-section>
                    {{ $t(tool.tool.nameToShow) }}
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="isCurrentTeacher"
         class="bg-gradient-to-r from-gray-100 to-gray-50 flex flex-col rounded-md text-center p-2"
    >
      <div v-if="intro" class="p-10 text-center">
        <span v-html="intro.introText" />

        <div v-if="createInSession">
          <button
              v-if="introTool"
              class="mt-2 btn btn-info"
              @click="addIntro(course, introTool)"
          >
            <v-icon>mdi-plus</v-icon>
            {{ $t('Course introduction') }}
          </button>
        </div>
        <div v-else>
          <button
              class="mt-2 btn btn-info"
              @click="updateIntro(intro)"
          >
            <v-icon>mdi-pencil</v-icon>
            {{ $t('Update') }}
          </button>
        </div>

      </div>
      <div v-else>
          <div>
            <v-icon
                icon="mdi-book-open-page-variant"
                size="72px"
                class="font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-ch-primary to-ch-primary-light"
            />
          </div>

          <div class="mt-2 font-bold">
            {{ $t("You don't have any course content yet.") }}
          </div>

          <div v-if="introTool">
            {{ $t('Add a course introduction to display to your students.') }}
          </div>

          <button
              v-if="introTool"
              class="mt-2 btn btn-info"
              @click="addIntro(course, introTool)"
          >
            <v-icon>mdi-plus</v-icon>
            {{ $t('Course introduction') }}
          </button>
      </div>
    </div>
    <div v-else>
      <div v-if="intro" class="p-10 text-center">
        <span v-html="intro.introText" />
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

import isEmpty from "lodash/isEmpty";
import {useRoute, useRouter} from 'vue-router'
import axios from "axios";
import {ENTRYPOINT} from '../../config/entrypoint';
import {computed, onMounted, reactive, toRefs} from 'vue'
import {mapGetters, useStore} from "vuex";
import translateHtml from '../../../js/translatehtml.js';

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
      session: [],
      tools: [],
      shortcuts: [],
      dropdownOpen: false,
      intro: null,
      introTool: null,
      createInSession: false,
      goToCourseTool,
      changeVisibility,
      goToSettingCourseTool,
      goToShortCut,
      addIntro,
      updateIntro
    });
    const route = useRoute()
    const store = useStore();
    const router = useRouter();

    let courseId = route.params.id;
    let sessionId = route.query.sid ?? 0;

    // Remove the course session state.
    store.dispatch('session/cleanSession');

    axios.get(ENTRYPOINT + '../course/' + courseId + '/home.json?sid=' + sessionId).then(response => {
      state.course = response.data.course;
      state.session = response.data.session;
      state.tools = response.data.tools;
      state.shortcuts = response.data.shortcuts;
      getIntro();
    }).catch(function (error) {
      console.log(error);
    });

    async function getIntro() {
      // Searching for the CTool called 'course_homepage'.
      let introTool = state.course.tools.find(element => element.name === 'course_homepage');

      if (!isEmpty(introTool)) {
        state.introTool = introTool;

        // Search CToolIntro for this
        const filter = {
          courseTool: introTool.iid,
          cid: courseId,
        };

        store.dispatch('ctoolintro/findAll', filter).then(response => {
          if (!isEmpty(response)) {
            // first item
            state.intro = response[0];
            translateHtml();
          }
        });

        if (!isEmpty(sessionId)) {
          state.createInSession = true;
          const filter = {
            courseTool: introTool.iid,
            cid: courseId,
            sid: sessionId,
          };

          store.dispatch('ctoolintro/findAll', filter).then(response => {
            if (!isEmpty(response)) {
              state.createInSession = false;
              state.intro = response[0];
              translateHtml();
            }
          });
        }
      }
    }

    function addIntro(course, introTool) {
      return router.push({
        name: 'ToolIntroCreate',
        params: {'courseTool': introTool.iid },
        query: {
          'cid': courseId,
          'sid': sessionId,
          'parentResourceNodeId': course.resourceNode.id
        }
      });
    }

    function updateIntro(intro) {
      return router.push({
        name: 'ToolIntroUpdate',
        params: {'id': intro['@id'] },
        query: {
          'cid': courseId,
          'sid': sessionId,
          'id': intro['@id']
        }
      });
    }

    function goToSettingCourseTool(course, tool) {
      return '/course/' + courseId + '/settings/' + tool.tool.name + '?sid=' + sessionId;
    }

    function goToCourseTool(course, tool) {
      return '/course/' + courseId + '/tool/' + tool.tool.name + '?sid=' + sessionId;
    }

    function goToShortCut(shortcut) {
      const url = new URLSearchParams('?')

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
