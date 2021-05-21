<template>
  <q-layout view="hHh LpR lff" class="bg-grey-1">
    <q-header bordered class="bg-white text-grey-8" height-hint="64">
      <q-toolbar>
        <q-toolbar-title v-if="$q.screen.gt.xs" shrink class="row items-center no-wrap">
          <img style="width:200px" src="/build/css/themes/chamilo/images/header-logo.png" />
        </q-toolbar-title>

        <q-space />

        <div class="q-gutter-sm row items-center no-wrap">

        </div>
      </q-toolbar>
    </q-header>
    <q-page-container>
      <q-page class="q-layout-padding">
        <div class="grid grid-cols-1 md:grid-cols-2">

          <!-- Form-->
          <div class="md:row-start-1 md:col-start-2 md:col-end-2 xl:p-12">
            <div class="md:mt-10 lg:mt-16 flex justify-center">
              <div class="max-w-sm">
                <div>
                  <h2 class="text-center text-3xl font-extrabold text-gray-900">
                    Sign in
                  </h2>
                </div>
                <Login />
              </div>
            </div>
          </div>

          <div class="md:row-start-1 md:col-start-1 md:col-end-1">
            <div class="flex justify-center lg:mt-16">
              <img src="/img/document/images/mr_chamilo/svg/teaching.svg" />
            </div>
          </div>
        </div>

      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script>
import Login from '../components/Login';
export default {
  components: {
    Login
  },
  name: "Index",
  data: () => ({
    showBreadcrumb : false
  })
}
</script>