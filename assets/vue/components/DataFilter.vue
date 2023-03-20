<template>
  <span>
    <q-btn
      v-b-toggle.collapse-1
      variant="primary"
    >
      <v-icon icon="mdi-magnify"/>

      {{ $t('Search') }}
    </q-btn>
<!--    <b-collapse-->
<!--      id="collapse-1"-->
<!--      class="mt-2"-->
<!--    >-->
      <q-card>
        <p class="card-text">
          <slot name="filter" />
          <br>
          <q-btn
            variant="primary"
            @click="handleFilter"
          >
            {{ $t('Filter') }}
          </q-btn>
          <q-btn
            variant="secondary"
            class="ml-2"
            text
            @click="handleReset"
          >
            {{ $t('Reset') }}
          </q-btn>
        </p>
      </q-card>
<!--    </b-collapse>-->


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
