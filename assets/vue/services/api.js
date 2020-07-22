import fetch from '../utils/fetch';

export default function makeService(endpoint) {
  return {
    find(id) {
      let options = {params: {getFile: true}};

      return fetch(`${id}`, options);
    },
    findAll(params) {
      //console.log('findAll');
      return fetch(endpoint, params);
    },
    create(payload) {
      return fetch(endpoint, { method: 'POST', body: payload });
      //return fetch(endpoint, { method: 'POST', body: JSON.stringify(payload) });
    },
    del(item) {
      return fetch(item['@id'], { method: 'DELETE' });
    },
    update(payload) {
      console.log('update');
      console.log(JSON.stringify(payload));

      return fetch(payload['@id'], {
        method: 'PUT',
        body: JSON.stringify(payload)
      });
    }
  };
}
