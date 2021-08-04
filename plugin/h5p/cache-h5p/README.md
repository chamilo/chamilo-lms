# h5p-standalone 2.1.0
Display H5P content without using a webserver


## Install
```
yarn add h5p-standalone
```

## Basic Usage

```javascript
import { H5P } from 'h5p-standalone'; // ES6
// const { H5P } = require('h5p-standalone'); AMD
// <script src="node_modules/h5p-standalone/dist/main.bundle.js"> // Globals
// const { H5P } = 'H5PStandalone';

const el = document.getElementById('h5p-container');
const h5pLocation = './workspace';

const h5p = new H5P(el, h5pLocation);
```

## Advanced Usage

There are several options for configuring h5p-standalone, if you would like to do things after rendering the H5P be sure to use await or .then(), as it is asynchronous.
```javascript
import { H5P } from 'h5p-standalone';

const el = document.getElementById('h5p-container');
const h5pLocation = './workspace';

const options = {
  id: 'lesson-one', // Optional unique ID, by default will be randomly generated
  frameJs: './frame.bundle.js',
  frameCss: './styles/h5p.css'
};

const displayOptions = { // Customise the look of the H5P
    frame: true,
    copyright: true,
    embed: false,
    download: false,
    icon: true,
    export: false
  };


const librariesPath = "/path/to/shared/libaries"; // Optional path to h5p activity libraries outside of content directory
const h5p = await new H5P(el, h5pLocation, options, displayOptions, librariesPath);

// OR

const h5p = new H5P(el, h5pLocation, options, displayOptions);

h5p.then(() => {
  // do stuff
});
```

# Multiple H5Ps on the same page
To render multiple H5Ps make sure your code is async aware.

```javascript
import { H5P } from 'h5p-standalone';

await new H5P(document.getElementById('h5p-container-1'), './h5p-1');
await new H5P(document.getElementById('h5p-container-2'), './h5p-2');

// OR

const h5p1 = new H5P(document.getElementById('h5p-container-1'), './h5p-1');

h5p1.then(() => {
  return new H5P(document.getElementById('h5p-container-2'), './h5p-2');
}).then(( => {
  // do stuff
}));
```

# Testing

```
yarn test
```