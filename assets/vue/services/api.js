import fetch from '../utils/fetch';

export default function makeService(endpoint) {
  return {
    find(id) {
      console.log('api.js find');
      console.log(id);
      let options = {params: {getFile: true}};
      return fetch(`${id}`, options);
    },
    async findAll(params) {
      console.log('api.js findAll');
      console.log(params);
      return await fetch(endpoint, params);
    },
    async createFile(payload) {
      console.log('api.js createFile');
      return fetch(endpoint, { method: 'POST', body: payload });
      //return fetch(endpoint, { method: 'POST', body: JSON.stringify(payload) });
    },
    create(payload) {
      console.log('api.js create');
      return fetch(endpoint, { method: 'POST', body: payload });
      //return fetch(endpoint, { method: 'POST', body: JSON.stringify(payload) });
    },
    del(item) {
      console.log('api.js del');
      console.log(item['@id']);
      return fetch(item['@id'], { method: 'DELETE' });
    },
    update(payload) {
      console.log('api.js - update');
      //console.log(JSON.stringify(payload));

      return fetch(payload['@id'], {
        method: 'PUT',
        body: JSON.stringify(payload)
      });
    }
  };
}
