<template>
  <div class="grid gap-4">
    <q-card-section>
      <div class="text-h6">{{ course.title }}</div>
      <div class="text-subtitle2">{{ course.description }}</div>
    </q-card-section>

    <div v-for="categories in tools" class="grid gap-4 grid-cols-2 md:grid-cols-4 lg:grid-cols-6">
      <div v-for="tool in categories" class="bg-gray-100 rounded-xl p-4 shadow-md">
        <a  :href="goToCourse(course, tool)" class="flex flex-col flex-center">


          <q-avatar rounded >
            <img
                :alt="tool.name"
                :src="'/img/tools/' + tool.name + '.png'"
            />
          </q-avatar>
          <q-item-section>
            <div class="row no-wrap items-center">
              <a
                  :href="goToCourse(course, tool)"
              >
                {{ tool.nameToTranslate }}
              </a>
            </div>
          </q-item-section>
        </a>
      </div>
    </div>
  </div>
</template>

<script>
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import isEmpty from 'lodash/isEmpty';
import { useRoute } from 'vue-router'
import axios from "axios";
import { ENTRYPOINT } from '../../config/entrypoint';
import { reactive, toRefs} from 'vue'

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
    const state = reactive({course: [], tools: [], shortcuts:[]});
    const route = useRoute()
    let id = route.params.id;

    axios.get(ENTRYPOINT + '../course/' + id + '/home.json').then(response => {
      state.course = response.data.course;
      state.tools = response.data.tools;
      state.shortcuts = response.data.shortcuts;
    }).catch(function (error) {
      console.log(error);
    });

    return toRefs(state);
  },
  methods: {
    goToCourse(course, tool) {
      let sessionId = this.$route.query.sid ?? 0;
      let url = '/course/' + course.id + '/tool/' + tool.name + '?sid=' + sessionId;

      return url;
    }
  }
};
</script>
