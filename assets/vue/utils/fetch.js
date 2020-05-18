import isObject from 'lodash/isObject';
import { ENTRYPOINT } from '../config/entrypoint';
import SubmissionError from '../error/SubmissionError';
import { normalize } from './hydra';

const MIME_TYPE = 'application/ld+json';

const makeParamArray = (key, arr) =>
  arr.map(val => `${key}[]=${val}`).join('&');

export default function(id, options = {}) {
  if ('undefined' === typeof options.headers) options.headers = new Headers();

  if (null === options.headers.get('Accept'))
    options.headers.set('Accept', MIME_TYPE);

  if (
    'undefined' !== options.body &&
    !(options.body instanceof FormData) &&
    null === options.headers.get('Content-Type')
  )
    options.headers.set('Content-Type', MIME_TYPE);

  if (options.params) {
    const params = normalize(options.params);
    let queryString = Object.keys(params)
      .map(key =>
        Array.isArray(params[key])
          ? makeParamArray(key, params[key])
          : `${key}=${params[key]}`
      )
      .join('&');
    id = `${id}?${queryString}`;
  }

  const entryPoint = ENTRYPOINT + (ENTRYPOINT.endsWith('/') ? '' : '/');

  const payload = options.body && JSON.parse(options.body);
  if (isObject(payload) && payload['@id'])
    options.body = JSON.stringify(normalize(payload));

  return global.fetch(new URL(id, entryPoint), options).then(response => {
    if (response.ok) return response;

    return response.json().then(
      json => {
        const error =
          json['hydra:description'] ||
          json['hydra:title'] ||
          'An error occurred.';

        if (!json.violations) throw Error(error);

        let errors = { _error: error };
        json.violations.forEach(violation =>
          errors[violation.propertyPath]
            ? (errors[violation.propertyPath] +=
            '\n' + errors[violation.propertyPath])
            : (errors[violation.propertyPath] = violation.message)
        );

        throw new SubmissionError(errors);
      },
      () => {
        throw new Error(response.statusText || 'An error occurred.');
      }
    );
  });
}
