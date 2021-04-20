<template>
  <div class="flex">
    <!-- Backdrop -->
    <div
        :class="isOpen ? 'block' : 'hidden'"
        @click="isOpen = false"
        class="fixed z-20 inset-0 bg-white opacity-50 transition-opacity lg:hidden"
    ></div>
    <!-- End Backdrop -->

    <div
        :class="isOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
        class="fixed z-30 inset-y-0 left-0 w-64 transition duration-300 transform bg-white overflow-y-auto lg:translate-x-0 lg:static lg:inset-0"
    >
      <div class="flex items-center justify-center mt-4">
        <div class="flex items-center">
          <span class="text-white text-2xl mx-2 font-semibold">
            <img style="width:200px" src="/build/css/themes/chamilo/images/header-logo.png" />
          </span>
        </div>
      </div>

      <nav class="mt-2">
          <q-list v-if="isAuthenticated" padding class="text-grey-8">
            <q-item class="GNL__drawer-item" v-ripple v-for="link in links1" :key="link.text" :to="link.url" clickable>
              <q-item-section avatar>
<!--                <q-icon :name="link.icon" />-->
                  <span class="w-6 h-6 stroke-current">
                   <font-awesome-icon :icon="link.icon" size="lg" />
                  </span>

              </q-item-section>
              <q-item-section>
                <q-item-label>{{ link.text }}</q-item-label>
              </q-item-section>
            </q-item>

            <q-separator inset class="q-my-sm" />
            <span v-if="isAdmin">
              <q-item class="GNL__drawer-item" v-ripple v-for="link in links2" :key="link.text" :to="link.url"  clickable>
                <q-item-section avatar>
<!--                  <q-icon :name="link.icon" />-->
                  <span class="w-6 h-6 stroke-current">
                   <font-awesome-icon :icon="link.icon" size="lg" />
                  </span>

                </q-item-section>
                <q-item-section>
                  <q-item-label>{{ link.text }}</q-item-label>
                </q-item-section>
              </q-item>
            </span>

            <q-separator inset class="q-my-sm" />

            <q-item class="GNL__drawer-item" v-ripple v-for="link in links3" :key="link.text" clickable>
              <q-item-section>
                <q-item-label>{{ link.text }}
                  <q-icon v-if="link.icon" :name="link.icon" />
                </q-item-label>
              </q-item-section>
            </q-item>

            <div class="q-mt-md">
              <div class="flex flex-center q-gutter-xs">
                <a class="GNL__drawer-footer-link" href="javascript:void(0)" aria-label="About">Chamilo</a>
              </div>
            </div>
          </q-list>


          <q-list v-else padding class="text-grey-8">
            <q-item class="GNL__drawer-item" v-ripple v-for="link in linksAnon" :key="link.text" :to="link.url" clickable>
              <q-item-section avatar>
                <q-icon :name="link.icon" />
              </q-item-section>
              <q-item-section>
                <q-item-label>{{ link.text }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>


<!--        <router-link-->
<!--            class="flex items-center duration-200 mt-4 py-2 px-6 border-l-4"-->
<!--            :class="[$route.name === 'Dashboard' ? activeClass : inactiveClass]"-->
<!--            to="/dashboard"-->
<!--        >-->
<!--          <svg-->
<!--              class="h-5 w-5"-->
<!--              viewBox="0 0 20 20"-->
<!--              fill="none"-->
<!--              xmlns="http://www.w3.org/2000/svg"-->
<!--          >-->
<!--            <path-->
<!--                d="M2 10C2 5.58172 5.58172 2 10 2V10H18C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10Z"-->
<!--                fill="currentColor"-->
<!--            />-->
<!--            <path-->
<!--                d="M12 2.25195C14.8113 2.97552 17.0245 5.18877 17.748 8.00004H12V2.25195Z"-->
<!--                fill="currentColor"-->
<!--            />-->
<!--          </svg>-->
<!--          <span class="mx-4">Dashboard</span>-->
<!--        </router-link>-->

<!--        <span  v-if="!isAuthenticated">-->
<!--          <router-link-->
<!--              class="flex items-center duration-200 mt-4 py-2 px-6 border-l-4"-->
<!--              v-for="link in linksAnon" :to="link.url"-->
<!--          >-->
<!--              <span class="w-6 h-6 stroke-current">-->
<!--               <font-awesome-icon :icon="link.icon" size="lg" />-->
<!--              </span>-->
<!--            <span class="mx-4 ">-->
<!--                {{ link.text }}-->
<!--              </span>-->
<!--          </router-link>-->
<!--        </span>-->

<!--        <span  v-if="isAuthenticated">-->
<!--          <router-link-->
<!--              class="flex items-center duration-200 mt-4 py-2 px-6 border-l-4"-->
<!--              v-for="link in links1" :to="link.url"-->
<!--          >-->
<!--              <span class="w-6 h-6 stroke-current">-->
<!--               <font-awesome-icon :icon="link.icon" size="lg" />-->
<!--              </span>-->
<!--            <span class="mx-4 ">-->
<!--                {{ link.text }}-->
<!--              </span>-->
<!--          </router-link>-->

<!--          <router-link-->
<!--              class="flex items-center duration-200 mt-4 py-2 px-6 border-l-4"-->
<!--              v-for="link in links2" :to="link.url">-->
<!--            <span class="w-6 h-6 stroke-current">-->
<!--             <font-awesome-icon :icon="link.icon" size="lg" />-->
<!--            </span>-->

<!--            <span class="mx-4 ">-->
<!--              {{ link.text }}-->
<!--            </span>-->
<!--          </router-link>-->
<!--        </span>-->

      </nav>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, computed } from "vue";
import { useSidebar } from "../../hooks/useSidebar";
import {mapGetters} from "vuex";

export default defineComponent({
  setup() {
    const { isOpen } = useSidebar();
    const activeClass = ref(
        "bg-gray-600 bg-opacity-25 text-gray-100 border-gray-100"
    );
    const inactiveClass = ref(
        "border-gray-900 text-gray-500 hover:bg-gray-600 hover:bg-opacity-25 hover:text-gray-100"
    );

    const links1 = [
      // { icon: 'person', url: '/courses', text: 'My courses' },
      // { icon: 'star_border', url: '/sessions', text: 'Sessions' },
      //{ icon: 'star_border', url: '/calendar', text: 'My calendar' },
      { icon: 'compass', url: '/catalog', text: 'Explore' },
      // { icon: 'star_border', url: '/news', text: 'News' },
    ];

    const links2 = [
      { icon: 'users', url: '/main/admin/user_list.php', text: 'Users' },
      { icon: 'book', url: '/main/admin/course_list.php', text: 'Courses' },
      { icon: 'book-open',  url: '/main/session/session_list.php', text: 'Sessions' },
      //{ icon: fasFlask, url: '/main/admin/index.php', text: 'Administration' },
      { icon: 'cogs', url: '/main/admin/index.php', text: 'Administration' },
    ];

    const linksAnon = [
      { icon: 'home', url: '/', text: 'Home' },
    ];

    return {
      linksAnon,
      links1,
      links2,
      isOpen,
      activeClass,
      inactiveClass,
    };
  },
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
    }),
  },
});
</script>