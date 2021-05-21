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
            <img class="" style="width:200px" src="/build/css/themes/chamilo/images/header-logo.png" />
            <img class="hidden w-5" src="/build/css/themes/chamilo/images/avatar.svg" />
          </span>
        </div>
      </div>

      <div class="overflow-y-auto overflow-x-hidden flex-grow">
        <ul class="flex flex-col py-4 space-y-1">


          <li v-for="link in linksAnon" :key="link.text"  >
            <router-link
                class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6"
                :to="link.url">
                <span class="inline-flex justify-center items-center ml-4">
                <span class="w-5 h-5">
                 <FontAwesomeIcon :icon="link.icon" />
                </span>
                <span class="ml-2 text-sm tracking-wide truncate">{{ link.text }}</span>
                  </span>
            </router-link>
          </li>

<!--          <li class="px-5">-->
<!--            <div class="flex flex-row items-center h-8">-->
<!--              <div class="text-sm font-light tracking-wide text-gray-500">Menu</div>-->
<!--            </div>-->
<!--          </li>-->
<!--          -->
<!--          <li>-->
<!--            <a href="/"-->
<!--               class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6">-->
<!--            <span class="inline-flex justify-center items-center ml-4">-->
<!--              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"-->
<!--                   xmlns="http://www.w3.org/2000/svg">-->
<!--                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"-->
<!--                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>-->
<!--            </span>-->
<!--              <span class="ml-2 text-sm tracking-wide truncate">Home</span>-->
<!--            </a>-->
<!--          </li>-->
<!--          -->
<!--          <li>-->
<!--            <a href="#"-->
<!--               class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6">-->
<!--            <span class="inline-flex justify-center items-center ml-4">-->
<!--              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"-->
<!--                   xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round"-->
<!--                                                            stroke-width="2"-->
<!--                                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>-->
<!--            </span>-->
<!--              <span class="ml-2 text-sm tracking-wide truncate">Inbox</span>-->
<!--              <span-->
<!--                  class="px-2 py-0.5 ml-auto text-xs font-medium tracking-wide text-indigo-500 bg-indigo-50 rounded-full">New</span>-->
<!--            </a>-->
<!--          </li>-->
<!--          <li>-->
<!--            <a href="#"-->
<!--               class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6">-->
<!--            <span class="inline-flex justify-center items-center ml-4">-->
<!--              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"-->
<!--                   xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round"-->
<!--                                                            stroke-width="2"-->
<!--                                                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>-->
<!--            </span>-->
<!--              <span class="ml-2 text-sm tracking-wide truncate">Messages</span>-->
<!--            </a>-->
<!--          </li>-->
<!--          <li>-->
<!--            <a href="#"-->
<!--               class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6">-->
<!--            <span class="inline-flex justify-center items-center ml-4">-->
<!--              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"-->
<!--                   xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round"-->
<!--                                                            stroke-width="2"-->
<!--                                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>-->
<!--            </span>-->
<!--              <span class="ml-2 text-sm tracking-wide truncate">Notifications</span>-->
<!--              <span class="px-2 py-0.5 ml-auto text-xs font-medium tracking-wide text-red-500 bg-red-50 rounded-full">1.2k</span>-->
<!--            </a>-->
<!--          </li>-->
<!--          <li class="px-5">-->
<!--            <div class="flex flex-row items-center h-8">-->
<!--              <div class="text-sm font-light tracking-wide text-gray-500">Tasks</div>-->
<!--            </div>-->
<!--          </li>-->
<!--          <li>-->
<!--            <a href="#"-->
<!--               class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6">-->
<!--            <span class="inline-flex justify-center items-center ml-4">-->
<!--              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"-->
<!--                   xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round"-->
<!--                                                            stroke-width="2"-->
<!--                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>-->
<!--            </span>-->
<!--              <span class="ml-2 text-sm tracking-wide truncate">Available Tasks</span>-->
<!--            </a>-->
<!--          </li>-->
<!--          <li>-->
<!--            <a href="#"-->
<!--               class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6">-->
<!--            <span class="inline-flex justify-center items-center ml-4">-->
<!--              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"-->
<!--                   xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round"-->
<!--                                                            stroke-width="2"-->
<!--                                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>-->
<!--            </span>-->
<!--              <span class="ml-2 text-sm tracking-wide truncate">Clients</span>-->
<!--              <span-->
<!--                  class="px-2 py-0.5 ml-auto text-xs font-medium tracking-wide text-green-500 bg-green-50 rounded-full">15</span>-->
<!--            </a>-->
<!--          </li>-->


          <li v-for="link in topLinks" :key="link.text"  >
            <router-link
                class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6"
                :to="link.url">
                <span class="inline-flex justify-center items-center ml-4">
                <span class="w-5 h-5">
                 <FontAwesomeIcon :icon="link.icon" />
                </span>
                <span class="ml-2 text-sm tracking-wide truncate">{{ link.text }}</span>
                  </span>
            </router-link>
          </li>

          <li class="px-5">
            <div class="flex flex-row items-center h-8">
              <div class="text-sm font-light tracking-wide text-gray-500">Settings</div>
            </div>
          </li>

          <li v-for="link in settingsLink" :key="link.text"  >
              <router-link
                  class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-gray-50 text-gray-600 hover:text-gray-800 border-l-4 border-transparent hover:border-indigo-500 pr-6"
                  :to="link.url">
                <span class="inline-flex justify-center items-center ml-4">
                <span class="w-5 h-5">
                 <FontAwesomeIcon :icon="link.icon" />
                </span>
                <span class="ml-2 text-sm tracking-wide truncate">{{ link.text }}</span>
                  </span>
              </router-link>
          </li>
        </ul>
      </div>
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
    const selected = ref(null);

    const activeClass = ref(
        "bg-gray-600 bg-opacity-25 text-gray-100 border-gray-100"
    );
    const inactiveClass = ref(
        "border-gray-900 text-gray-500 hover:bg-gray-600 hover:bg-opacity-25 hover:text-gray-100"
    );

    const topLinks = [
      // { icon: 'person', url: '/courses', text: 'My courses' },
      // { icon: 'star_border', url: '/sessions', text: 'Sessions' },
      //{ icon: 'star_border', url: '/calendar', text: 'My calendar' },
      { icon: 'compass', url: '/catalog', text: 'Explore' },
      // { icon: 'star_border', url: '/news', text: 'News' },
    ];

    const settingsLink = [
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
      selected,
      linksAnon,
      topLinks,
      settingsLink,
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