import { getField, updateField } from 'vuex-map-fields';
import remove from 'lodash/remove';
import map from 'lodash/map';
import SubmissionError from '../../error/SubmissionError';
import isEmpty from 'lodash/isEmpty';

const initialState = () => ({
  allIds: [],
  byId: {},
  created: null,
  deleted: null,
  error: "",
  isLoading: false,
  resetList: false,
  selectItems: null,
  totalItems: 0,
  updated: null,
  view: null,
  violations: null,
  resourceNode: null
});

const handleError = (commit, e) => {
  console.log('handleError');
  commit(ACTIONS.TOGGLE_LOADING);
  console.log(e);
  if (e instanceof SubmissionError) {
    console.log('SubmissionError');
    commit(ACTIONS.SET_VIOLATIONS, e.errors);
    // eslint-disable-next-line
    commit(ACTIONS.SET_ERROR, e.errors._error);

    return Promise.reject(e);
  }

  console.log('ACTIONS.SET_ERROR');
  // eslint-disable-next-line
  commit(ACTIONS.SET_ERROR, e.message);

  return Promise.reject(e);
};

export const ACTIONS = {
  ADD: 'ADD',
  RESET_CREATE: 'RESET_CREATE',
  RESET_DELETE: 'RESET_DELETE',
  RESET_LIST: 'RESET_LIST',
  RESET_SHOW: 'RESET_SHOW',
  RESET_UPDATE: 'RESET_UPDATE',
  SET_CREATED: 'SET_CREATED',
  SET_DELETED: 'SET_DELETED',
  SET_DELETED_MULTIPLE: 'SET_DELETED_MULTIPLE',
  SET_ERROR: 'SET_ERROR',
  SET_SELECT_ITEMS: 'SET_SELECT_ITEMS',
  SET_TOTAL_ITEMS: 'SET_TOTAL_ITEMS',
  SET_UPDATED: 'SET_UPDATED',
  SET_VIEW: 'SET_VIEW',
  SET_VIOLATIONS: 'SET_VIOLATIONS',
  TOGGLE_LOADING: 'TOGGLE_LOADING',
  ADD_RESOURCE_NODE: 'ADD_RESOURCE_NODE'
};

export default function makeCrudModule({
  normalizeRelations = x => x,
  resolveRelations = x => x,
  service
} = {}) {
  return {
    actions: {
      checkResponse(response) {
        if (200 === response.status) {
          return response.json();
        }

        return response;
      },
      createWithFormData: ({ commit }, values) => {
        console.log('createWithFormData');
        commit(ACTIONS.SET_ERROR, '');
        commit(ACTIONS.TOGGLE_LOADING);

        return service
            .createWithFormData(values)
            .then(response => response.json())
            /*.then(response => {
              if (200 === response.status) {
                return response.json();
              }
            })*/
            .then(data => {
              commit(ACTIONS.TOGGLE_LOADING);
              commit(ACTIONS.ADD, data);
              commit(ACTIONS.SET_CREATED, data);
            })
            .catch(e => handleError(commit, e));
      },
      create: ({ commit }, values) => {
        console.log('crud.js create');
        console.log(values);
        commit(ACTIONS.SET_ERROR, '');
        commit(ACTIONS.TOGGLE_LOADING);

        return service
          .create(values)
          .then(response => response.json())
          .then(data => {
            commit(ACTIONS.TOGGLE_LOADING);
            commit(ACTIONS.ADD, data);
            commit(ACTIONS.SET_CREATED, data);
          })
          .catch(e => handleError(commit, e));
      },
      del: ({ commit }, item) => {
        console.log('del');
        commit(ACTIONS.SET_ERROR, '');
        commit(ACTIONS.TOGGLE_LOADING);

        return service
          .del(item)
          .then(() => {
            commit(ACTIONS.TOGGLE_LOADING);
            commit(ACTIONS.SET_DELETED, item);
          })
          .catch(e => handleError(commit, e));
      },
      delMultiple: ({ commit }, items) => {
        commit(ACTIONS.TOGGLE_LOADING);
        const promises = items.map(async item => {
          const result = await service.del(item).then(() => {
            //commit(ACTIONS.TOGGLE_LOADING);
            commit(ACTIONS.SET_DELETED_MULTIPLE, item);
          });

          return result;
        });

        const result = Promise.all(promises);

        if (result) {
          commit(ACTIONS.TOGGLE_LOADING);
        }

        return result;
      },
      findAll: ({ commit, state }, params) => {
        if (!service) throw new Error('No service specified!');

        console.log('crud.js findAll');
        //commit(ACTIONS.TOGGLE_LOADING);

        return service
            .findAll({params})
            .then(response => response.json())
            .then(retrieved => {
              console.log('result of retrieved');
              //commit(ACTIONS.TOGGLE_LOADING);

              return retrieved['hydra:member'];
            })
            .catch(e => handleError(commit, e));
      },
      fetchAll: ({ commit, state }, params) => {
        if (!service) throw new Error('No service specified!');

        console.log('crud.js fetchAll');

        commit(ACTIONS.TOGGLE_LOADING);

        return service
          .findAll({params})
          .then(response => response.json())
          .then(retrieved => {
            console.log('result of retrieved');
            commit(ACTIONS.TOGGLE_LOADING);
            commit(ACTIONS.SET_TOTAL_ITEMS, retrieved['hydra:totalItems']);
            commit(ACTIONS.SET_VIEW, retrieved['hydra:view']);
            if (true === state.resetList) {
              commit(ACTIONS.RESET_LIST);
            }
            retrieved['hydra:member'].forEach(item => {
              commit(ACTIONS.ADD, normalizeRelations(item));
            });
          })
          .catch(e => handleError(commit, e));
      },
      fetchSelectItems: (
        { commit },
        { params = { properties: ['@id', 'name'] } } = {}
      ) => {
        console.log('fetchSelectItems');
        commit(ACTIONS.TOGGLE_LOADING);
        if (!service) throw new Error('No service specified!');

        return service
          .findAll({ params })
          .then(response => response.json())
          .then(retrieved => {
            commit(ACTIONS.TOGGLE_LOADING);
            commit(ACTIONS.SET_SELECT_ITEMS, retrieved['hydra:member']);
          })
          .catch(e => handleError(commit, e));
      },
      loadWithQuery: ({ commit }, params= {}) => {
        if (!service) throw new Error('No service specified!');

        console.log('crud loadWithQuery');
        const id = params['id'];
        delete params['id'];

        /*console.log(id, 'id');
        console.log(commit, 'commit');*/

        if (isEmpty(id)) {
          throw new Error('Incorrect id');
        }

        commit(ACTIONS.TOGGLE_LOADING);
        service
            .find(id, params)
            //.then(response => service.checkResponse(response))
            .then(response => {
              if (200 === response.status) {
                return response.json();
              }
            })
            .then(item => {
              commit(ACTIONS.TOGGLE_LOADING);
              commit(ACTIONS.ADD, normalizeRelations(item));
            })
            .catch(e => handleError(commit, e));
      },
      load: ({ commit }, id) => {
        if (!service) throw new Error('No service specified!');
        console.log('crud load');

        if (isEmpty(id)) {
          throw new Error('Incorrect id');
        }

        commit(ACTIONS.TOGGLE_LOADING);
        return service
          .find(id)
          //.then(response => service.checkResponse(response))
            .then(response => {
              if (200 === response.status) {
                return response.json();
              }
            })
          .then(item => {
            commit(ACTIONS.TOGGLE_LOADING);
            commit(ACTIONS.ADD, normalizeRelations(item));

            return item;
          })
          .catch(e => handleError(commit, e));
      },
      findResourceNode: ({ commit }, params) => {
        const id = params['id'];
        delete params['id'];
        console.log('findResourceNode', id);
        if (!service) throw new Error('No service specified!');

        commit(ACTIONS.TOGGLE_LOADING);

        return service
            .find(id, params)
            .then(response => {
              if (200 === response.status) {
                return response.json();
              }
            })

            .then(item => {
              commit(ACTIONS.TOGGLE_LOADING);
              commit(ACTIONS.ADD_RESOURCE_NODE, item);

              return item;
            })
            .catch(e => handleError(commit, e));
      },
      resetCreate: ({ commit }) => {
        commit(ACTIONS.RESET_CREATE);
      },
      resetDelete: ({ commit }) => {
        commit(ACTIONS.RESET_DELETE);
      },
      resetShow: ({ commit }) => {
        commit(ACTIONS.RESET_SHOW);
      },
      resetList: ({ commit }) => {
        commit(ACTIONS.RESET_LIST);
      },
      resetUpdate: ({ commit }) => {
        commit(ACTIONS.RESET_UPDATE);
      },
      update: ({ commit }, item) => {
        console.log('crud update');
        commit(ACTIONS.TOGGLE_LOADING);

        return service
          .update(item)
          .then(response => response.json())
          .then(data => {
            commit(ACTIONS.TOGGLE_LOADING);
            commit(ACTIONS.SET_UPDATED, data);
          })
          .catch(e => handleError(commit, e));
      },
      updateWithFormData: ({ commit }, item) => {
        console.log('crud updateWithFormData');
        commit(ACTIONS.TOGGLE_LOADING);

        return service
            .updateWithFormData(item)
            .then(response => response.json())
            .then(data => {
              commit(ACTIONS.TOGGLE_LOADING);
              commit(ACTIONS.SET_UPDATED, data);
            })
            .catch(e => handleError(commit, e));
      }
    },
    getters: {
      find: state => id => {
        return resolveRelations(state.byId[id]);
      },
      getField,
      list: (state, getters) => {
        return state.allIds.map(id => getters.find(id));
      },
      getResourceNode: (state) => {
        return state.resourceNode;
      },
    },
    mutations: {
      updateField,
      [ACTIONS.ADD_RESOURCE_NODE]: (state, item) => {
        state.resourceNode = item;
        state.isLoading = false;
        //this.$set(state, 'resourceNode', item);
        //this.$set(state, 'isLoading', false);
      },
      [ACTIONS.ADD]: (state, item) => {
        //this.$set(state.byId, item['@id'], item);
        state.byId[item['@id']] = item;
        state.isLoading = false;
        //this.$set(state, 'isLoading', false);
        if (state.allIds.includes(item['@id'])) {
          return;
        }
        state.allIds.push(item['@id']);
      },
      [ACTIONS.RESET_CREATE]: state => {
        Object.assign(state, {
          isLoading: false,
          error: '',
          created: null,
          violations: null
        });
      },
      [ACTIONS.RESET_DELETE]: state => {
        Object.assign(state, {
          isLoading: false,
          error: '',
          deleted: null
        });
      },
      [ACTIONS.RESET_LIST]: state => {
        Object.assign(state, {
          allIds: [],
          byId: {},
          error: '',
          isLoading: false,
          resetList: false
        });
      },
      [ACTIONS.RESET_SHOW]: state => {
        Object.assign(state, {
          error: '',
          isLoading: false
        });
      },
      [ACTIONS.RESET_UPDATE]: state => {
        Object.assign(state, {
          error: '',
          isLoading: false,
          updated: null,
          violations: null
        });
      },
      [ACTIONS.SET_CREATED]: (state, created) => {
        console.log('set _created');
        console.log(created);
        Object.assign(state, { created });
        state.created = created;
      },
      [ACTIONS.SET_DELETED]: (state, deleted) => {
        console.log('SET_DELETED');
        if (!state.allIds.includes(deleted['@id'])) {
          return;
        }
        Object.assign(state, {
          allIds: remove(state.allIds, item => item['@id'] === deleted['@id']),
          byId: remove(state.byId, id => id === deleted['@id']),
          deleted
        });
      },
      [ACTIONS.SET_DELETED_MULTIPLE]: (state, deleted) => {
        console.log('SET_DELETED_MULTIPLE');
        //console.log(deleted['@id']);
        /*if (!state.allIds.includes(deleted['@id'])) {
          return;
        }*/
        Object.assign(state, {
          allIds: remove(state.allIds, item => item['@id'] === deleted['@id']),
          byId: remove(state.byId, id => id === deleted['@id']),
          deleted
        });
      },
      [ACTIONS.SET_ERROR]: (state, error) => {
        state.error = error;
        state.isLoading = false;
        //Object.assign(state, { error, isLoading: false });
      },
      [ACTIONS.SET_SELECT_ITEMS]: (state, selectItems) => {
        Object.assign(state, {
          error: '',
          isLoading: false,
          selectItems
        });
      },
      [ACTIONS.SET_TOTAL_ITEMS]: (state, totalItems) => {
        Object.assign(state, { totalItems });
      },
      [ACTIONS.SET_UPDATED]: (state, updated) => {
        console.log('SET_UPDATED');
        console.log(updated);
        state.byId[updated['@id']] = updated;
        state.isLoading = false;
        state.updated = updated;
        /*Object.assign(state, {
          byId: {
            [updated['@id']]: updated
          },
          updated
        });*/
      },
      [ACTIONS.SET_VIEW]: (state, view) => {
        Object.assign(state, { view });
      },
      [ACTIONS.SET_VIOLATIONS]: (state, violations) => {
        Object.assign(state, { violations });
      },
      [ACTIONS.TOGGLE_LOADING]: state => {
        Object.assign(state, { error: '', isLoading: !state.isLoading });
      }
    },
    namespaced: true,
    state: initialState
  };
}
