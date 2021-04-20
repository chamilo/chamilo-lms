<template>
  <div class="card">
    Session catalog todo
  </div>
</template>

<script>

import {ENTRYPOINT} from '../../config/entrypoint';
import axios from "axios";
import Dropdown from "primevue/dropdown";
import DataView from 'primevue/dataview';
import DataViewLayoutOptions from 'primevue/dataviewlayoutoptions';

export default {
  name: 'Catalog',
  components: {
    DataView,
    Dropdown,
    DataViewLayoutOptions
  },
  data() {
    return {
      status: '',
      courses: [],
      layout: 'list',
      sortKey: null,
      sortOrder: null,
      sortField: null,
      sortOptions: [
        {label: 'A-z', value: 'title'},
        {label: 'Z-a', value: '!title'},
      ]
    };
  },
  created: function () {
    this.load();
  },
  mounted: function () {

  },
  methods: {
    load: function () {
      //this.status = 'Loading';
      //let user = this.$store.getters['security/getUser'];
        axios.get(ENTRYPOINT + 'courses.json').then(response => {
          this.status = '';
          if (Array.isArray(response.data)) {
            this.courses = response.data;
          }
        }).catch(function (error) {
          console.log(error);
        });
    },
    onSortChange(event) {
      const value = event.value.value;
      const sortValue = event.value;

      if (value.indexOf('!') === 0) {
        this.sortOrder = -1;
        this.sortField = value.substring(1, value.length);
        this.sortKey = sortValue;
      }
      else {
        this.sortOrder = 1;
        this.sortField = value;
        this.sortKey = sortValue;
      }
    }
  }
};
</script>
