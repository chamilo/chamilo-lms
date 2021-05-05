<template>
  <div class="grid gap-4">
    <q-card-section>
      <div class="text-h6">{{ course.title }}</div>
      <div class="text-subtitle2">{{ course.description }}</div>
    </q-card-section>

    <div v-for="categories in tools" class="grid gap-3 grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-6">
      <div v-for="tool in categories" class="bg-gray-100 rounded-xl p-2 shadow-md">
        <div class="flex flex-col flex-center">
          <div class="mx-auto" >
            <a :href="goToCourse(course, tool)">
            <img
                :alt="tool.name"
                :src="'/img/tools/' + tool.name + '.png'"
                class="w-32 h-32 object-contain"
            />
            </a>
          </div>

          <div class="flex flex-row gap-2 text-gray-500">
            <a
              :href="goToCourse(course, tool)"
            >
              {{ tool.nameToTranslate }}
            </a>
            <button v-if="isCurrentTeacher" @click="changeVisibility(course, tool)">
              <FontAwesomeIcon
                  v-if="tool.resourceNode.resourceLinks[0].visibility === 2"
                  icon="eye" size="lg"
              />
              <FontAwesomeIcon
                  v-else
                  icon="eye-slash"
                  size="lg"
              />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import { useRoute } from 'vue-router'
import axios from "axios";
import { ENTRYPOINT } from '../../config/entrypoint';
import { reactive, toRefs} from 'vue'
import {mapGetters} from "vuex";

// @todo use suspense
// @

export default {
  name: 'Home',
  servicePrefix: 'Courses',
  components: {
    Loading,
    Toolbar
  },
  setup() {
    const state = reactive({course: [], tools: [], shortcuts:[], goToCourse, changeVisibility});
    const route = useRoute()
    let id = route.params.id;

    axios.get(ENTRYPOINT + '../course/' + id + '/home.json').then(response => {
      state.course = response.data.course;
      state.tools = response.data.tools;
      state.shortcuts = response.data.shortcuts;
    }).catch(function (error) {
      console.log(error);
    });

    function goToCourse(course, tool) {
      let sessionId = this.$route.query.sid ?? 0;
      let url = '/course/' + course.id + '/tool/' + tool.name + '?sid=' + sessionId;

      return url;
    }

    function changeVisibility(course, tool) {
      axios.post(ENTRYPOINT + '../r/course_tool/links/' + tool.resourceNode.id + '/change_visibility').then(response => {
        if (response.data.ok) {
          tool.resourceNode.resourceLinks[0].visibility = response.data.visibility;
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
