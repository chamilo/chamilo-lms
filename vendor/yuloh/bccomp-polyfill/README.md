# BCComp Polyfill

A polyfill for the `bcmath` function, `bccomp`.

## Why?

I maintain a library that only uses `bccomp`, and doesn't use the rest of `bcmath`.  I wanted to be able to work on it on machines that weren't compiled with `bcmath` support, so I wrote this polyfill.

## Install

```bash
composer require yuloh/bccomp-polyfill
```

## Usage

If the function `bccomp` does not exist in the global namespace, this library will declare it's own `bccomp` function.
