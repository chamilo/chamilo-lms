<template>
  <span>
    <b-button
      v-b-toggle.collapse-1
      variant="primary"
    >
      <font-awesome-icon icon="search" />
      {{ $t('Search') }}
    </b-button>
    <b-collapse
      id="collapse-1"
      class="mt-2"
    >
      <b-card>
        <p class="card-text">
          <slot name="filter" />
          <br>
          <b-button
            variant="primary"
            @click="handleFilter"
          >
            {{ $t('Filter') }}
          </b-button>
          <b-button
            variant="secondary"
            class="ml-2"
            text
            @click="handleReset"
          >
            {{ $t('Reset') }}
          </b-button>
        </p>
      </b-card>
    </b-collapse>


    <!--    <v-expansion-panels v-model="filtersExpanded">-->
    <!--      <v-expansion-panel>-->
    <!--        <v-expansion-panel-header>-->
    <!--          {{ $t('Filters') }}-->

    <!--          <template slot="actions">-->
    <!--            <v-icon large>-->
    <!--              mdi-filter-variant-->
    <!--            </v-icon>-->
    <!--          </template>-->
    <!--        </v-expansion-panel-header>-->

    <!--        <v-expansion-panel-content>-->
    <!--          <slot name="filter" />-->

    <!--          <b-button-->
    <!--            variant="primary"-->
    <!--            @click="handleFilter"-->
    <!--          >-->
    <!--            {{ $t('Filter') }}-->
    <!--          </b-button>-->
    <!--          <b-button-->
    <!--            variant="secondary"-->
    <!--            class="ml-2"-->
    <!--            text-->
    <!--            @click="handleReset"-->
    <!--          >-->
    <!--            {{ $t('Reset') }}-->
    <!--          </b-button>-->
    <!--        </v-expansion-panel-content>-->
    <!--      </v-expansion-panel>-->
    <!--    </v-expansion-panels>-->
  </span>
</template>

<script>
export default {
  name: 'DataFilter',
  props: {
    handleReset: {
      type: Function,
      required: true
    },
    handleFilter: {
      type: Function,
      required: true
    }
  },
  data() {
    return {
      filtersExpanded: false
    };
  }
};
</script>
