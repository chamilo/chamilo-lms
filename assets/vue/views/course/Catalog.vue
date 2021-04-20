<template>
  <div class="card">
    <DataView :value="courses" :layout="layout" :paginator="true" :rows="9" :sortOrder="sortOrder" :sortField="sortField">
      <template #header>
        <div class="p-grid p-nogutter">
          <div class="p-col-3" style="text-align: left">
            <Dropdown
                v-model="sortKey"
                :options="sortOptions"
                optionLabel="label"
                placeholder="Sort By Title"
                @change="onSortChange($event)"
            />
          </div>
<!--          <div class="p-col-3" style="text-align: left">-->
<!--            <Dropdown-->
<!--                v-model="sortKey"-->
<!--                :options="sortOptions"-->
<!--                optionLabel="label"-->
<!--                placeholder="Categories"-->
<!--                @change="onSortChange($event)"-->
<!--            />-->
<!--          </div>-->
<!--          <div class="p-col-6" style="text-align: right">-->
<!--            <DataViewLayoutOptions v-model="layout" />-->
<!--          </div>-->
        </div>
      </template>

      <template #list="slotProps">
        <div class="p-col-12">
          <div class="course-list-item">

            <img src="/img/session_default.png" :alt="slotProps.data.title"/>

            <div class="course-list-detail">
              <div class="course-name">{{ slotProps.data.title }}</div>
              <div class="course-description">{{ slotProps.data.description }}</div>
<!--              <Rating :modelValue="slotProps.data.rating" :readonly="true" :cancel="false"></Rating>-->

              <span v-for="category in slotProps.data.categories">
                   <i class="pi pi-tag course-category-icon"></i>
                   <span class="course-category">{{ category.name }}</span>&nbsp;
              </span>
            </div>
            <div class="course-list-action">
<!--              <span class="course-price">${{slotProps.data.price}}</span>-->
<!--              <Button icon="pi pi-shopping-cart" label="Add to Cart" :disabled="slotProps.data.inventoryStatus === 'OUTOFSTOCK'"></Button>-->
<!--              <span :class="'course-badge status-'+slotProps.data.inventoryStatus.toLowerCase()">{{slotProps.data.inventoryStatus}}</span>-->
            </div>
          </div>
        </div>
      </template>

      <template #grid="slotProps">
        <div class="p-col-12 p-md-4">
          <div class="course-grid-item card">
            <div class="course-grid-item-top">
              <div>
                <i class="pi pi-tag course-category-icon"></i>
                <span class="course-category">{{ slotProps.data.title }}</span>
              </div>
<!--              <span :class="'course-badge status-'+slotProps.data.inventoryStatus.toLowerCase()">{{slotProps.data.inventoryStatus}}</span>-->
            </div>
            <div class="course-grid-item-content">
              <img src="/img/icons/64/course.png" :alt="slotProps.data.title"/>
              <div class="course-name">{{ slotProps.data.title }}</div>
              <div class="course-description">{{ slotProps.data.description }}</div>
<!--              <Rating :modelValue="slotProps.data.rating" :readonly="true" :cancel="false"></Rating>-->
            </div>
            <div class="course-grid-item-bottom">
<!--              <span class="course-price">${{slotProps.data.price}}</span>-->
<!--              <Button icon="pi pi-shopping-cart" :disabled="slotProps.data.inventoryStatus === 'OUTOFSTOCK'"></Button>-->
            </div>
          </div>
        </div>
      </template>
    </DataView>
  </div>
</template>

<style lang="scss" scoped>
.card {
  background: #ffffff;
  padding: 2rem;
  box-shadow: 0 2px 1px -1px rgba(0,0,0,.2), 0 1px 1px 0 rgba(0,0,0,.14), 0 1px 3px 0 rgba(0,0,0,.12);
  border-radius: 4px;
  margin-bottom: 2rem;
}
.p-dropdown {
  width: 14rem;
  font-weight: normal;
}

.course-name {
  font-size: 1.5rem;
  font-weight: 700;
}

.course-description {
  margin: 0 0 1rem 0;
}

.course-category-icon {
  vertical-align: middle;
  margin-right: .5rem;
}

.course-category {
  font-weight: 600;
  vertical-align: middle;
}

::v-deep(.course-list-item) {
  display: flex;
  align-items: center;
  padding: 1rem;
  width: 100%;

  img {
    width: 150px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
    margin-right: 2rem;
  }

  .course-list-detail {
    flex: 1 1 0;
  }

  .p-rating {
    margin: 0 0 .5rem 0;
  }

  .course-price {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: .5rem;
    align-self: flex-end;
  }

  .course-list-action {
    display: flex;
    flex-direction: column;
  }

  .p-button {
    margin-bottom: .5rem;
  }
}

::v-deep(.course-grid-item) {
  margin: .5rem;
  border: 1px solid #dee2e6;

  .course-grid-item-top,
  .course-grid-item-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  img {
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
    margin: 2rem 0;
  }

  .course-grid-item-content {
    text-align: center;
  }

  .course-price {
    font-size: 1.5rem;
    font-weight: 600;
  }
}

@media screen and (max-width: 576px) {
  .course-list-item {
    flex-direction: column;
    align-items: center;

    img {
      margin: 2rem 0;
    }

    .course-list-detail {
      text-align: center;
    }

    .course-price {
      align-self: center;
    }

    .course-list-action {
      display: flex;
      flex-direction: column;
    }

    .course-list-action {
      margin-top: 2rem;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
      width: 100%;
    }
  }
}
</style>
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
