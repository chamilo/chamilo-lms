<template>
  <div class="container mx-auto flex gap-8">
    <Login class="md:w-4/12 lg:order-1" />
    <div
      v-if="pages.length"
      class="flex-1 md:w-8/12 lg:order-0"
    >
      <PageCardList
        :pages="pages"
      />
    </div>
  </div>
</template>

<script setup>
import {ref} from 'vue'
import {useStore} from "vuex";
import {useI18n} from "vue-i18n";
import Login from '../components/Login';
import PageCardList from "../components/page/PageCardList";

const store = useStore();
const {locale} = useI18n();

const pages = ref([]);

store
  .dispatch(
    'page/findAll',
    {
      'category.title': 'index',
      'enabled': '1',
      'locale': locale.value
    }
  )
  .then(
    response => pages.value = response
  );
</script>
