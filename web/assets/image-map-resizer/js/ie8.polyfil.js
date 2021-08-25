//PolyFils from MDN.
(function() {
  if (!Array.prototype.map){
    Array.prototype.map = function(fun /*, thisArg */){
      "use strict";

      if (this === void 0 || this === null || typeof fun !== "function"){
        throw new TypeError();
      }

      var
          t = Object(this),
          len = t.length >>> 0,
          res = new Array(len),
          thisArg = arguments.length >= 2 ? arguments[1] : void 0;

      for (var i = 0; i < len; i++){
        if (i in t) {
          res[i] = fun.call(thisArg, t[i], i, t);
        }
      }

      return res;
    };
  }

  if (!Array.prototype.forEach){
    Array.prototype.forEach = function(fun /*, thisArg */){
      "use strict";

      if (this === void 0 || this === null || typeof fun !== "function"){
        throw new TypeError();
      }

      var
        t = Object(this),
        len = t.length >>> 0,
        thisArg = arguments.length >= 2 ? arguments[1] : void 0;

      for (var i = 0; i < len; i++){
        if (i in t){
          fun.call(thisArg, t[i], i, t);
        }
      }
    };
  }

})();