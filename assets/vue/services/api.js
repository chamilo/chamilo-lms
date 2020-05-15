import fetch from '../utils/fetch';

export default function makeService(endpoint) {
  return {
    find(id) {
      return fetch(`${id}`);
    },
    findAll(params) {
      return fetch(endpoint, params);
    },
    create(payload) {
      return fetch(endpoint, { method: 'POST', body: JSON.stringify(payload) });
    },
    del(item) {
      return fetch(item['@id'], { method: 'DELETE' });
    },
    update(payload) {
      return fetch(payload['@id'], {
        method: 'PUT',
        body: JSON.stringify(payload)
      });
    }
  };
}
