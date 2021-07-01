import fetch from '../utils/fetch';

export default function makeService(endpoint) {
  return {
    find(id, params) {
      console.log('api.js find');
      if (params) {
        params['getFile'] = true;
      } else {
        params = {getFile: true};
      }

      let options = {params: params};
      return fetch(`${id}`, options);
    },
    findAll(params) {
      console.log('api.js findAll');
      console.log(params);
      return fetch(endpoint, params);
    },
    async createWithFormData(payload) {
      console.log('api.js createWithFormData');

      let formData = new FormData();
      console.log('body');
      console.log(payload);
      if (payload) {
        Object.keys(payload).forEach(function (key) {
          // key: the name of the object key
          // index: the ordinal position of the key within the object
          formData.append(key, payload[key]);
          console.log('options.key', key);
        });
        payload = formData;
      }

      return fetch(endpoint, { method: 'POST', body: payload});
    },
    async create(payload) {
      console.log('api.js create');
      return fetch(endpoint, { method: 'POST', body: JSON.stringify(payload) });
    },
    del(item) {
      console.log('api.js del');
      console.log(item['@id']);
      return fetch(item['@id'], { method: 'DELETE' });
    },
    updateWithFormData(payload) {
      console.log('api.js - update');

      return fetch(payload['@id'], {
        method: 'PUT',
        body: JSON.stringify(payload)
      });
    },
    update(payload) {
      console.log('api.js - update');

      return fetch(payload['@id'], {
        method: 'PUT',
        body: JSON.stringify(payload)
      });
    }
  };
}
