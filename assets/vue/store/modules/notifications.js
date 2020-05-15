import {getField, updateField} from 'vuex-map-fields';

export default {
  namespaced: true,
  state: {
    show: false,
    color: 'error',
    text: 'An error occurred',
    subText: '',
    timeout: 6000
  },
  getters: {
    getField
  },
  mutations: {
    updateField
  }
};
