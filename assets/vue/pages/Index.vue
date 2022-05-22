<template>
  <div class="flex gap-8">
    <div class="order-2 flex-none w-96">
      <Login />
    </div>
    <div class="order-1 flex-1 ">
      <div
        v-if="pages.length"
      >
        <PageCardList
          :pages="pages"
        />
      </div>
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
