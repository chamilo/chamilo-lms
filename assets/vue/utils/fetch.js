import { isArray, isObject, isUndefined, forEach } from 'lodash';
import { ENTRYPOINT } from '../config/entrypoint';
import SubmissionError from '../error/SubmissionError';
import { normalize } from './hydra';

const MIME_TYPE = 'application/ld+json';

const makeParamArray = (key, arr) =>
  arr.map(val => `${key}[]=${val}`).join('&');

export default function(id, options = {}, formData = false) {
    console.log('fetch');
    console.log(options);

    if ("undefined" === typeof options.headers) {
        options.headers = {};
    }

    if (!options.headers.hasOwnProperty("Accept")) {
        options.headers = { ...options.headers, Accept: MIME_TYPE };
    }

    /*if (
        undefined !== options.body &&
        !(options.body instanceof FormData) &&
        !options.headers.hasOwnProperty("Content-Type")
    ) {
        options.headers = { ...options.headers, "Content-Type": MIME_TYPE };
    }*/

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
        console.log('URL', id);
    }

    const entryPoint = ENTRYPOINT + (ENTRYPOINT.endsWith('/') ? '' : '/');

    console.log('entryPoint', entryPoint);

    if (formData) {
        //options.headers = { ...options.headers, Accept: MIME_TYPE };
        //options.headers = { ...options.headers, "Content-Type": 'multipart/form-data' };
        let formData = new FormData();
        console.log('body');
        console.log(options.body);
        if (options.body) {
            Object.keys(options.body).forEach(function (key) {
                // key: the name of the object key
                // index: the ordinal position of the key within the object
                formData.append(key, options.body[key]);
                console.log('options.key', key);
            });
            options.body = formData;
        }
    }

    if ('PUT' === options.method) {
        const payload = options.body && JSON.parse(options.body);
        if (isObject(payload) && payload['@id']) {
            options.body = JSON.stringify(normalize(payload));
        }
    }

    /*const payload = options.body && JSON.parse(options.body);
    if (isObject(payload) && payload["@id"]) {
        options.body = JSON.stringify(normalize(payload));
    }*/

    /*if (useAxios) {
        console.log('axios');
        let url = new URL(id, entryPoint);
        console.log(formData);
        return axios({
            url: url.toString(),
            method: 'POST',
            //headers: options.headers,
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            onUploadProgress: function (progressEvent) {
                console.log('progress');
                //console.log(progressEvent);
                console.log(options.body);

                let uploadPercentage = parseInt(Math.round((progressEvent.loaded / progressEvent.total) * 100));
                options.body['__progress'] = uploadPercentage;
                options.body['__progressLabel'] = uploadPercentage;
                this.uploadPercentage = uploadPercentage;
                console.log(options.body);
                //options.body.set('__progressLabel', uploadPercentage);
            }.bind(this)
        }
        ).then(response => {
            options.body['__uploaded'] = 1;
            options.body['uploadFile']['__uploaded'] = 1

            console.log(response);
            console.log('SUCCESS!!');

            return response.data;
        })
            .catch(function (response) {
                console.log(response);
                console.log('FAILURE!!');
            });
    }*/

  console.log('ready to fetch');

  return global.fetch(new URL(id, entryPoint), options).then(response => {
    console.log(response, 'global.fetch');

    if (response.ok) {
        return response;
    }

    return response.json().then(json => {
        let error =
          json['hydra:description'] ||
          json['hydra:title'] ||
          'An error occurred.';

        if (json['code'] && 401 === json['code']) {
            error = 'Not allowed';
        }

        if (json['error']) {
            error = json['error'];
        }

        console.log(error, 'fetch error');

        if (!json.violations) {
            console.log('violations');
            throw Error(error);
        }

        let errors = { _error: error };
        json.violations.map(
            violation => (errors[violation.propertyPath] = violation.message)
        );

        throw new SubmissionError(errors);
      },
      () => {
        console.log('error3');
        throw new Error(response.statusText || 'An error occurred.');
      }
    );
  });
}
