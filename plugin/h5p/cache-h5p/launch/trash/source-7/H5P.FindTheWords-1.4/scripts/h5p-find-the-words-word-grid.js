(function (FindTheWords, EventDispatcher, $) {

  /**
   * WordGrid - Handles the word grid part of the game.
   * @class H5P.FindTheWords.WordGrid
   * @extends H5P.EventDispatcher
   * @param {Object} params Description.
   */
  FindTheWords.WordGrid = function (params) {
    /** @alias H5P.FindTheWords.WordGrid# */
    // extending the default parameter set for the grid
    this.options = params;

    EventDispatcher.call(this);

    this.createWordGrid();
  };

  FindTheWords.WordGrid.prototype = Object.create(EventDispatcher.prototype);
  FindTheWords.WordGrid.prototype.constructor = FindTheWords.WordGrid;

  // get i th element position based on the current position for different orientations
  const orientations = {
    horizontal: function (x, y, i) {
      return {
        x: x + i,
        y: y
      };
    },
    horizontalBack: function (x, y, i) {
      return {
        x: x - i,
        y: y
      };
    },
    vertical: function (x, y, i) {
      return {
        x: x,
        y: y + i
      };
    },
    verticalUp: function (x, y, i) {
      return {
        x: x,
        y: y - i
      };
    },
    diagonal: function (x, y, i) {
      return {
        x: x + i,
        y: y + i
      };
    },
    diagonalBack: function (x, y, i) {
      return {
        x: x - i,
        y: y + i
      };
    },
    diagonalUp: function (x, y, i) {
      return {
        x: x + i,
        y: y - i
      };
    },
    diagonalUpBack: function (x, y, i) {
      return {
        x: x - i,
        y: y - i
      };
    }
  };

  /*
   * Determines if an orientation is possible given the starting square (x,y),
   * the height (h) and width (w) of the puzzle, and the length of the word (l).
   * Returns true if the word will fit starting at the square provided using
   * the specified orientation.
   */
  const checkOrientations = {
    horizontal: function (x, y, h, w, l) {
      return w >= x + l;
    },
    horizontalBack: function (x, y, h, w, l) {
      return x + 1 >= l;
    },
    vertical: function (x, y, h, w, l) {
      return h >= y + l;
    },
    verticalUp: function (x, y, h, w, l) {
      return y + 1 >= l;
    },
    diagonal: function (x, y, h, w, l) {
      return (w >= x + l) && (h >= y + l);
    },
    diagonalBack: function (x, y, h, w, l) {
      return (x + 1 >= l) && (h >= y + l);
    },
    diagonalUp: function (x, y, h, w, l) {
      return (w >= x + l) && (y + 1 >= l);
    },
    diagonalUpBack: function (x, y, h, w, l) {
      return (x + 1 >= l) && (y + 1 >= l);
    }
  };

  /*
   *  Determines the next possible valid square given the square (x,y) was ]
   *  invalid and a word lenght of (l).  This greatly reduces the number of
   *  squares that must be checked. Returning {x: x+1, y: y} will always work
   *  but will not be optimal.
   */
  const skipOrientations = {
    horizontal: function (x, y) {
      return {
        x: 0,
        y: y + 1
      };
    },
    horizontalBack: function (x, y, l) {
      return {
        x: l - 1,
        y: y
      };
    },
    vertical: function (x, y) {
      return {
        x: 0,
        y: y + 100
      };
    },
    verticalUp: function (x, y, l) {
      return {
        x: 0,
        y: l - 1
      };
    },
    diagonal: function (x, y) {
      return {
        x: 0,
        y: y + 1
      };
    },
    diagonalBack: function (x, y, l) {
      return {
        x: l - 1,
        y: x >= l - 1 ? y + 1 : y
      };
    },
    diagonalUp: function (x, y, l) {
      return {
        x: 0,
        y: y < l - 1 ? l - 1 : y + 1
      };
    },
    diagonalUpBack: function (x, y, l) {
      return {
        x: l - 1,
        y: x >= l - 1 ? y + 1 : y
      };
    }
  };

  /**
   * calcOverlap - returns the overlap if the word can be fitted with the grid parameters provided.
   * @param {string} word Word to be fitted.
   * @param {Object[]} wordGrid Grid to which word needs to be fitted.
   * @param {number} x Starting x cordinate.
   * @param {nuber} y Starting y cordinate.
   * @param {function} fnGetSquare Function to get the next grid pos as per the specified direction.
   * @return {number} Overlap value if it can be fitted , -1 otherwise.
   */
  const calcOverlap = function (word, wordGrid, x, y, fnGetSquare) {
    let overlap = 0;

    // traverse the squares to determine if the word fits
    for (let index = 0 ; index < word.length; index++) {
      const next = fnGetSquare(x, y, index);
      const square = wordGrid[next.y][next.x];
      if (square === word[index]) {
        overlap++;
      }
      else if (square !== '') {
        return -1;
      }
    }

    return overlap;
  };

  /**
   * findBestLocations - Find the best possible location for a word in the grid.
   * @param {Object[]} wordGrid
   * @param {Object} options
   * @param {string} word
   */
  const findBestLocations = function (wordGrid, options, word) {
    const locations = [];
    const height = options.height;
    const width = options.width;
    const wordLength = word.length;
    let maxOverlap = 0;

    options.orientations.forEach(function (orientation) {

      const check = checkOrientations[orientation];
      const next = orientations[orientation];
      const skipTo = skipOrientations[orientation];

      let x = 0;
      let y = 0;

      while (y < height) {
        if (check(x, y, height, width, wordLength)) {
          const overlap = calcOverlap(word, wordGrid, x, y, next);
          if (overlap >= maxOverlap || (!options.preferOverlap && overlap > -1 )) {
            maxOverlap = overlap;
            locations.push({
              x: x,
              y: y,
              orientation: orientation,
              overlap: overlap
            });
          }
          x++;
          if ( x >= width) {
            x = 0;
            y++;
          }
        }
        else {
          const nextPossible = skipTo(x, y, wordLength);
          x = nextPossible.x;
          y = nextPossible.y;
        }
      }
    });
    return locations;
  };

  /**
   * placeWordInGrid - find the best location and place the word.
   * @param {Object[]} wordGrid
   * @param {Object} options
   * @param {string} word
   */
  const placeWordInGrid = function (wordGrid, options, word) {
    const locations = findBestLocations(wordGrid, options, word);
    if (locations.length === 0) {
      return false;
    }

    const selectedLoc = locations[Math.floor(Math.random() * locations.length)];
    for (let index = 0; index < word.length; index++) {
      const next = orientations[selectedLoc.orientation](selectedLoc.x, selectedLoc.y, index);
      wordGrid[next.y][next.x] = word[index];
    }
    return true;
  };

  /**
   * fillGrid - Create an empty grid and fill it with words.
   * @param {Object[]} words Description.
   * @param {Object} options Description.
   * @return {Object[]|null} Grid array if all words can be fitted, else null.
   */
  const fillGrid = function (words, options) {
    const wordGrid = [];
    for (let i = 0; i < options.height; i++) {
      wordGrid[i] = [];
      for (let j = 0; j < options.width; j++) {
        wordGrid[i][j] = '';
      }
    }

    for (const i in words) {
      if (!placeWordInGrid(wordGrid, options, words[i])) {
        return null;
      }
    }
    return wordGrid;
  };

  /**
   * fillBlanks - fill the unoccupied spaces with blanks.
   * @param {Object[]} wordGrid
   * @param {string} fillPool
   * @return {Object[]} Resulting word grid.
   */
  const fillBlanks = function (wordGrid, fillPool) {
    for (let i = 0; i < wordGrid.length; i++) {
      for (let j = 0;j < wordGrid[0].length; j++) {
        if (!wordGrid[i][j]) {
          const randomLetter = Math.floor(Math.random() * fillPool.length);
          wordGrid[i][j] = fillPool[randomLetter];
        }
      }
    }
    return wordGrid;
  };

  /**
   * calculateCordinates - function to calculate the cordinates & grid postions at which the event occured.
   * @param {number} x X-cordinate of the event.
   * @param {number} y Y-cordinate of the event.
   * @param {number} elementSize Current element size.
   * @return {Object[]} [normalized x, normalized y, row ,col].
   */
  const calculateCordinates = function (x, y, elementSize) {
    const row1 = Math.floor(x / elementSize);
    const col1 = Math.floor(y / elementSize);
    const x_click = row1 * elementSize + (elementSize / 2);
    const y_click = col1 * elementSize + (elementSize / 2);
    return [x_click, y_click, row1, col1];
  };

  /*
   * function to  process the line drawn to find if it is a valid marking
   * in terms of possible grid directions
   * returns directional value if it is a valid marking
   * else return false
   */

  /**
   * getValidDirection - process the line drawn to find if it is a valid marking.
   * @param {number} x1 Starting x cordinate.
   * @param {number} y1 Starting y cordinate.
   * @param {number} x2 Ending x cordinate.
   * @param {number} y2 Ending y cordinate.
   * @return {Object[]|boolean} Direction array if a valid marking, false otherwise.
   */
  const getValidDirection = function (x1, y1, x2, y2) {
    const dirx = (x2 > x1) ? 1 : ((x2 < x1) ? -1 : 0);
    const diry = (y2 > y1) ? 1 : ((y2 < y1) ? -1 : 0);
    let y = y1;
    let x = x1;

    if (dirx !== 0) {
      while (x !== x2) {
        x = x + dirx;
        y = y + diry;
      }
    }
    else {
      while (y !== y2) {
        y = y + diry;
      }
    }

    if (y2 === y) {
      return [dirx, diry];
    }
    else {
      return false;
    }
  };

  // All event handlers are registered here

  /**
   * mouseDownEventHandler.
   * @param {Object} e Event Object.
   * @param {HTMLelement} canvas Html5 canvas element.
   * @param {number} elementSize Element size.
   * @return {Object[]}
   */
  const mouseDownEventHandler = function (e, canvas, elementSize) {
    const x = e.pageX - $(canvas).offset().left;
    const y = e.pageY - $(canvas).offset().top;
    return calculateCordinates(x, y, elementSize);
  };


  /*
   * event handler for handling mousemove events
   * @private
   */

  /**
   * mouseMoveEventHandler.
   * @param {Object} e Event Object.
   * @param {HTMLelement} canvas Html5 Canvas Element.
   * @param {Object[]} srcPos Position from which the movement started.
   * @param {number} eSize  Current element size.
   */
  const mouseMoveEventHandler = function (e, canvas, srcPos, eSize) {
    const offsetTop = ($(canvas).offset().top > eSize * 0.75) ? Math.floor(eSize * 0.75) : $(canvas).offset().top;
    const desX = e.pageX - $(canvas).offset().left;
    const desY = e.pageY - Math.abs(offsetTop);
    const context = canvas.getContext('2d');

    // Draw the current marking
    context.clearRect(0, 0, context.canvas.width, context.canvas.height);
    context.fillStyle = 'rgba(107,177,125,0.3)';
    context.beginPath();
    context.lineCap = 'round';
    context.moveTo(srcPos[0] - (eSize / 8), srcPos[1] + (offsetTop / 8));
    context.strokeStyle = 'rgba(107,177,125,0.4)';
    context.lineWidth = Math.floor(eSize / 2);
    context.lineTo(desX - (eSize / 8), desY + (offsetTop / 8));
    context.stroke();
    context.closePath();
  };

  /*
   * event handler for handling mouseup events
   * @private
   */

  /**
   * mouseUpEventHandler.
   * @param {Object} e Event Object.
   * @param {HTMLelement} canvas Html5 Canvas Element.
   * @param {number} elementSize Current element size.
   * @param {Object[]} clickStart Starting Event location.
   * @return {Object} return staring,ending and direction of the current marking.
   */
  const mouseUpEventHandler = function (e, canvas, elementSize, clickStart) {
    let wordObject = {};
    const offsetTop = ($(canvas).offset().top > elementSize * 0.75) ? Math.floor(elementSize * 0.75) * (-1) : $(canvas).offset().top;
    const x = e.pageX - $(canvas).offset().left;
    const y = e.pageY - Math.abs(offsetTop);
    const clickEnd = calculateCordinates(x, y, elementSize);
    const context = canvas.getContext('2d');

    if ((Math.abs(clickEnd[0] - x) < 20) && (Math.abs(clickEnd[1] - y) < 15)) {
      // Drag ended within permissible range
      wordObject = {
        'start': clickStart,
        'end': clickEnd,
        'dir': getValidDirection(clickStart[2], clickStart[3], clickEnd[2], clickEnd[3])
      };
    }

    // Clear if there any markings started
    context.closePath();
    context.clearRect(0, 0, canvas.width, canvas.height);
    return wordObject;
  };

  /**
   * touchHandler - Mapping touchevents to corresponding mouse events.
   * @param {Object} event Description.
   */
  const touchHandler = function (event) {
    const touches = event.changedTouches;
    const  first = touches[0];
    const simulatedEvent = document.createEvent('MouseEvent');

    let type = '';
    switch (event.type) {
      case 'touchstart':
        type = 'mousedown';
        break;
      case 'touchmove':
        type = 'mousemove';
        break;
      case 'touchend':
        type = 'mouseup';
        break;
      default:
        return;
    }

    // Created and fire a simulated mouse event
    simulatedEvent.initMouseEvent(type, true, true, window, 1,
      first.screenX, first.screenY,
      first.clientX, first.clientY, false,
      false, false, false, 0 /*left*/, null);
    first.target.dispatchEvent(simulatedEvent);
    event.preventDefault();
  };

  FindTheWords.WordGrid.prototype.createWordGrid = function () {
    let wordGrid = null ;
    let attempts = 0;

    // sorting the words by length speedup the word fitting algorithm
    const wordList = this.options.vocabulary.slice(0).sort(function (a, b) {
      return (a.length < b.length);
    });

    while (!wordGrid) {
      while (!wordGrid && attempts++ < this.options.maxAttempts) {
        wordGrid = fillGrid(wordList, this.options);
      }

      // if grid cannot be formed in the current dimensions
      if (!wordGrid) {
        this.options.height++;
        this.options.width++;
        attempts = 0;
      }
    }

    // fill in empty spaces with random letters
    if (this.options.fillBlanks) {
      wordGrid = fillBlanks(wordGrid, this.options.fillPool);
    }

    // set the output puzzle
    this.wordGrid = wordGrid;
  };

  /**
   * markWord - mark the word on the output canvas (permanent).
   * @param {Object} wordParams
   */
  FindTheWords.WordGrid.prototype.markWord = function (wordParams) {
    const dirKey = wordParams['directionKey'];
    const clickStart = wordParams['start'];
    const clickEnd = wordParams['end'];
    const context = this.$outputCanvas[0].getContext('2d');
    const offsetTop = (this.$container.offset().top > this.elementSize * 0.75) ? Math.floor(this.elementSize * 0.75) * (-1) : this.$container.offset().top;
    const topRadius = Math.floor(this.elementSize / 8);
    const bottomRadius = Math.abs(Math.floor(offsetTop / 8));
    const lineWidth = Math.floor(this.elementSize / 4);

    let startingAngle;

    // set the drawing property values
    context.lineWidth = 2;
    context.strokeStyle = 'rgba(107,177,125,0.9)';
    context.fillStyle = 'rgba(107,177,125,0.3)';

    if (!this.options.gridActive) {
      context.strokeStyle = 'rgba(51, 102, 255,0.9)';
      context.fillStyle = 'rgba(51, 102, 255,0.1)';
      context.setLineDash([8, 4]);
    }

    // find the arc starting angle depending on the direction
    switch (dirKey) {
      case 'horizontal': {
        startingAngle = (Math.PI / 2);
        break;
      }
      case 'horizontalBack': {
        startingAngle = -(Math.PI / 2);
        break;
      }
      case 'diagonal': {
        startingAngle = 3 * (Math.PI / 4);
        break;
      }
      case 'diagonalBack': {
        startingAngle = 5 * (Math.PI / 4);
        break;
      }
      case 'diagonalUp': {
        startingAngle = (Math.PI / 4);
        break;
      }
      case 'diagonalUpBack': {
        startingAngle = -(Math.PI / 4);
        break;
      }
      case 'vertical': {
        startingAngle = (Math.PI);
        break;
      }
      case 'verticalUp': {
        startingAngle = 0;
        break;
      }
    }

    // start drawing
    context.beginPath();
    context.arc(clickStart[0] - topRadius, clickStart[1] + bottomRadius, lineWidth, startingAngle, startingAngle + (Math.PI));
    context.arc(clickEnd[0] - topRadius, clickEnd[1] + bottomRadius, lineWidth, startingAngle + (Math.PI), startingAngle + (2 * Math.PI));
    context.closePath();
    context.stroke();
    context.fill();
  };

  /**
   * mark - mark the words if they are not found.
   * @param {Object[]} wordList
   */
  FindTheWords.WordGrid.prototype.mark = function (wordList) {
    const words = wordList;
    const that = this;
    const options = {
      height: this.wordGrid.length,
      width: this.wordGrid[0].length,
      orientations: this.options.orientations,
      preferOverlap: this.options.preferOverlap
    };
    const found = [];
    const notFound = [];

    words.forEach(function (word) {
      const locations = findBestLocations(that.wordGrid, options, word);
      if (locations.length > 0 && locations[0].overlap === word.length) {
        locations[0].word = word;
        found.push(locations[0]);
      }
      else {
        notFound.push(word);
      }
    });

    this.markSolution(found);
  };

  /**
   * markSolution.
   * @param {Object[]} solutions
   */
  FindTheWords.WordGrid.prototype.markSolution = function (solutions) {
    const that = this;

    solutions.forEach(function (solution) {
      const next = orientations[solution.orientation];
      const word = solution.word;
      const startX = solution.x;
      const startY = solution.y;
      const endPos = next(startX, startY, word.length - 1);
      const clickStartX = startX * that.elementSize + (that.elementSize / 2);
      const clickStartY = startY * that.elementSize + (that.elementSize / 2);
      const clickEndX = endPos.x * that.elementSize + (that.elementSize / 2);
      const clickEndY = endPos.y * that.elementSize + (that.elementSize / 2);
      const wordParams = {
        'start': [clickStartX, clickStartY, startX, startY],
        'end': [clickEndX, clickEndY, endPos.x, endPos.y],
        'directionKey': solution.orientation
      };
      that.markWord(wordParams);
    });
  };

  /**
   * disableGrid.
   */
  FindTheWords.WordGrid.prototype.disableGrid = function () {
    this.options.gridActive = false;
  };

  /**
   * enableGrid.
   */
  FindTheWords.WordGrid.prototype.enableGrid = function () {
    this.options.gridActive = true;
  };

  /**
   * appendTo - Placing the container for drawing the grid.
   * @param {H5P.jQuery} $container
   * @param {number} elementSize
   */
  FindTheWords.WordGrid.prototype.appendTo = function ($container, elementSize) {
    this.$container = $container;
    this.canvasWidth = elementSize * this.wordGrid[0].length;
    this.canvasHeight = elementSize * this.wordGrid.length;
    this.elementSize = elementSize;
    $container.css('height', this.canvasHeight);
    $container.css('width', this.canvasWidth);
  };

  /**
   * drawGrid - draw the letter on the canvas element provided.
   * @param {number} margin Description.
   */
  FindTheWords.WordGrid.prototype.drawGrid = function (margin) {
    const that = this;

    const marginResp = (Math.floor(that.elementSize / 8) < margin) ? (Math.floor(that.elementSize / 8)) : margin;
    const offsetTop = (that.$container.offset().top > that.elementSize * 0.75) ? Math.floor(that.elementSize * 0.75) : that.$container.offset().top;

    this.$gridCanvas = $('<canvas id="grid-canvas" class="canvas-element" height="' + that.canvasHeight + 'px" width="' + that.canvasWidth + 'px" />').appendTo(that.$container);
    this.$outputCanvas = $('<canvas class="canvas-element" height="' + that.canvasHeight + 'px" width="' + that.canvasWidth + 'px"/>').appendTo(that.$container);
    this.$drawingCanvas = $('<canvas id="drawing-canvas" class="canvas-element" height="' + that.canvasHeight + 'px" width="' + that.canvasWidth + 'px"/>').appendTo(that.$container);

    const ctx1 = this.$gridCanvas[0].getContext('2d');
    const offset = that.$container.offset();

    ctx1.clearRect(offset.left, offset.top, that.canvasWidth, that.canvasHeight);
    ctx1.font = (that.elementSize / 3 ) + 'px sans-serif';

    that.wordGrid.forEach(function (row, index1) {
      row.forEach(function (element, index2) {
        ctx1.fillText(element.toUpperCase(), index2 * that.elementSize + 2 * marginResp, index1 * that.elementSize + (offsetTop) );
      });
    });

    let clickStart = [];
    let isDragged = false;
    let clickMode = false;

    this.$container[0].addEventListener('keydown', function () {
      //TODO: need to implement for a11y
    }, false);

    this.$drawingCanvas[0].addEventListener('touchstart', function (event) {
      touchHandler(event);
    }, false);

    this.$drawingCanvas[0].addEventListener('touchmove', function (event) {
      touchHandler(event);
    }, false);

    this.$drawingCanvas[0].addEventListener('touchend', function (event) {
      touchHandler(event);
    }, false);

    this.$drawingCanvas.on('mousedown', function (event) {
      if (that.options.gridActive) {
        if (!clickMode) {
          that.enableDrawing = true;
          clickStart = mouseDownEventHandler(event, this, that.elementSize);
          that.trigger('drawStart');
        }
      }
    });

    this.$drawingCanvas.on('mouseup', function (event) {
      if (that.enableDrawing) {
        if (isDragged || clickMode) {
          if (clickMode) {
            clickMode = false;
          }
          let markedWord = '';
          const wordObject = mouseUpEventHandler(event, this, that.elementSize, clickStart);
          const dict = {
            'horizontal' : [1, 0],
            'horizontalBack' : [-1, 0],
            'diagonal' : [1, 1],
            'diagonalBack' : [-1, 1],
            'diagonalUp' : [1, -1],
            'diagonalUpBack' : [-1, -1],
            'vertical' : [0, 1],
            'verticalUp' : [0, -1]
          };

          if (!$.isEmptyObject(wordObject) && wordObject['dir'] !== false) {
            const dir = wordObject['dir'];
            let y1 = wordObject['start'][3];
            let x1 = wordObject['start'][2];
            let x2 = wordObject['end'][2];
            const y2 = wordObject['end'][3];

            do {
              markedWord += that.wordGrid[y1][x1];
              x1 = x1 + dir[0];
              y1 = y1 + dir[1];
            } while (!((y1 === y2) && (x1 === x2)));

            markedWord += that.wordGrid[y2][x2];
            for (const key in dict) {
              if (dict[key][0] === dir[0] && dict[key][1] === dir[1]) {
                wordObject['directionKey'] = key;
                break;
              }
            }
          }
          that.enableDrawing = false;
          isDragged = false;
          that.trigger('drawEnd', {'markedWord': markedWord, 'wordObject': wordObject});
        }
        else if (!clickMode) {
          clickMode = true;
          const offsetTop = (that.$container.offset().top > that.elementSize * 0.75) ? Math.floor(that.elementSize * 0.75) : that.$container.offset().top;
          const context = that.$drawingCanvas[0].getContext('2d');
          //drawing the dot on initial click
          context.clearRect(0, 0, context.canvas.width, context.canvas.height);
          context.lineWidth = Math.floor(that.elementSize / 2);
          context.strokeStyle = 'rgba(107,177,125,0.9)';
          context.fillStyle = 'rgba(107,177,125,0.3)';
          context.beginPath();
          context.arc(clickStart[0] - (that.elementSize / 8), clickStart[1] + Math.floor(offsetTop / 8), that.elementSize / 4, 0, 2 * Math.PI);
          context.fill();
          context.closePath();
        }
      }
    });

    this.$drawingCanvas.on('mousemove', function (event) {
      if (that.enableDrawing ) {
        isDragged = true;
        mouseMoveEventHandler(event, this, clickStart, that.elementSize);
      }
    });
  };

  return FindTheWords.WordGrid;

}) (H5P.FindTheWords, H5P.EventDispatcher, H5P.jQuery);
