<template>
  <header
      class="flex justify-between items-center py-4 px-6 bg-white border-b-2 border-gray-300"
  >

    <div class="flex items-center" v-if="isAuthenticated">
      <q-tabs align="center" dense inline-label no-caps>
        <q-route-tab to="/" label="Home" />
        <q-route-tab to="/courses" label="My courses" />
        <q-route-tab to="/main/calendar/agenda_js.php?type=personal" label="Agenda" />
      </q-tabs>
<!--      <router-link :to="{name: 'Home'}" tag="button" class="flex items-center justify-center h-10 px-4 ml-auto text-sm font-medium rounded hover:bg-gray-300">-->
<!--        Home-->
<!--      </router-link>-->

<!--      <router-link :to="{name: 'MyCourses'}" tag="button"  class="flex items-center justify-center h-10 px-4 ml-2 text-sm font-medium bg-gray-200 rounded hover:bg-gray-300">-->
<!--        My courses-->
<!--      </router-link>-->
    </div>

    <div class="flex items-center">
      <button
          @click="isOpen = true"
          class="text-gray-500 focus:outline-none lg:hidden"
      >
        <svg
            class="h-6 w-6"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
          <path
              d="M4 6H20M4 12H20M4 18H11"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
          />
        </svg>
      </button>

<!--      <div class="relative mx-4 lg:mx-0">-->
<!--        <span class="absolute inset-y-0 left-0 pl-3 flex items-center">-->
<!--          <svg class="h-5 w-5 text-gray-500" viewBox="0 0 24 24" fill="none">-->
<!--            <path-->
<!--                d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"-->
<!--                stroke="currentColor"-->
<!--                stroke-width="2"-->
<!--                stroke-linecap="round"-->
<!--                stroke-linejoin="round"-->
<!--            />-->
<!--          </svg>-->
<!--        </span>-->

<!--        <input-->
<!--            class="w-32 sm:w-64 rounded-md pl-10 pr-4 focus:border-indigo-600"-->
<!--            type="text"-->
<!--            placeholder="Search"-->
<!--        />-->
<!--      </div>-->
    </div>

    <div class="flex items-center">

      <button class="flex mx-4 text-gray-600 focus:outline-none">
        <svg
            class="h-6 w-6"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
          <path
              d="M15 17H20L18.5951 15.5951C18.2141 15.2141 18 14.6973 18 14.1585V11C18 8.38757 16.3304 6.16509 14 5.34142V5C14 3.89543 13.1046 3 12 3C10.8954 3 10 3.89543 10 5V5.34142C7.66962 6.16509 6 8.38757 6 11V14.1585C6 14.6973 5.78595 15.2141 5.40493 15.5951L4 17H9M15 17V18C15 19.6569 13.6569 21 12 21C10.3431 21 9 19.6569 9 18V17M15 17H9"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
          />
        </svg>
      </button>

      <div v-if="isAuthenticated" class="relative">
        <button
            @click="dropdownOpen = !dropdownOpen"
            class="relative z-10 block h-8 w-8 rounded-full overflow-hidden shadow focus:outline-none"
        >
          <img
              class="h-full w-full object-cover"
              :src="userAvatar + '?w=80&h=80&fit=crop'"
              alt="Your avatar"
          />
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
<!--          <a-->
<!--              href="/main/messages/inbox.php"-->
<!--              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 hover:text-white"-->
<!--          >Inbox</a>-->

<!--          <a-->
<!--              href="/account/edit"-->
<!--              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 hover:text-white"-->
<!--          >Settings</a>-->


<!--          <a-->
<!--              href="/logout"-->
<!--              class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-200 hover:text-white"-->
<!--          >Logout</a>-->
<!--          -->

              <q-list dense>
                <q-item class="GL__menu-link-signed-in">
                  <q-item-section>
                    <div>Signed in as <strong>{{ currentUser.username }}</strong></div>
                  </q-item-section>
                </q-item>
                <!--                <q-separator />-->
                <!--                <q-item clickable class="GL__menu-link-status">-->
                <!--                  <q-item-section>-->
                <!--                    <div>-->
                <!--                      <q-icon name="tag_faces" color="blue-9" size="18px" />-->
                <!--                      Set your status-->
                <!--                    </div>-->
                <!--                  </q-item-section>-->
                <!--                </q-item>-->
                <q-separator />
                <q-item replace :to="'/main/messages/index.php'" clickable class="">
                  <q-item-section>Inbox</q-item-section>
                </q-item>
                <q-item href="/account/home" tag="a" class="">
                  <q-item-section>
                    Your profile
                  </q-item-section>
                </q-item>
                <q-item href="/account/edit" tag="a"  class="">
                  <q-item-section>Settings</q-item-section>
                </q-item>
                <q-item href="/logout" tag="a" clickable class="">
                  <q-item-section>
                    Sign out
                  </q-item-section>
                </q-item>
              </q-list>
        </div>
      </div>

      <div v-else class="relative">
        <router-link :to="'/login'" tag="button"  class="flex items-center justify-center h-10 px-4 ml-2 text-sm font-medium bg-gray-200 rounded hover:bg-gray-300">
          Sign in
        </router-link>
      </div>
    </div>
  </header>
</template>

<script lang="ts">
import { defineComponent, ref, toRefs } from "vue";
import { useSidebar } from "../../hooks/useSidebar";
import isEmpty from "lodash";
import {mapGetters} from "vuex";

export default defineComponent({
  setup() {
    const dropdownOpen = ref(false);
    const { isOpen } = useSidebar();
    const userAvatar = ref(window.userAvatar);

    console.log('defineComponent window.user');
    console.log(window.user);
    console.log(window.userAvatar);
    if (!isEmpty(window.user)) {
      console.log('is logged in as ' + window.user.username);
      //this.user = window.user;
      userAvatar.value = window.userAvatar;
      //isAuthenticated = true;
    }

    console.log(userAvatar.value);

    return {
      userAvatar,
      isOpen,
      dropdownOpen,
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