import get from 'lodash/get';
import has from 'lodash/has';
import mapValues from 'lodash/mapValues';

export function normalize(data) {
  //console.log('normalize');
  if (has(data, 'hydra:member')) {
    console.log('Normalize items in collections');
    // Normalize items in collections
    data['hydra:member'] = data['hydra:member'].map(item => normalize(item));

    return data;
  }
  //console.log('data', data);

  // Flatten nested documents
  return mapValues(data, value =>
    Array.isArray(value)
      ? value.map(v => get(v, '@id', v))
      : get(value, '@id', value)
  );
}
