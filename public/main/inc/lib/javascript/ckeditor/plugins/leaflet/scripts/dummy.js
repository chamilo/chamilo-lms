window2 = window.frames[0];
console.log(window.frames[0]);

(function (window2) {
  var window = window2;
  var document = window.document;

  console.log('lamang-lupa2');
  console.log(window);
  //console.log(window);
  //console.log(window.document);
  console.log(document);
  console.log('lamang-labas');
})(window2)
