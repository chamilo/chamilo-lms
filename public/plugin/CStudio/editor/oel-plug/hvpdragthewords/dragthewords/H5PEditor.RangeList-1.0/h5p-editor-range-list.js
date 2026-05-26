H5PEditor.RangeList = (function ($, TableList) {

  /**
   * Renders UI for the table list.
   *
   * @class
   * @extends H5PEditor.TableList
   * @param {List} list
   */
  function RangeList(list) {
    var self = this;

    // Initialize inheritance
    TableList.call(self, list, 'h5p-editor-range-list');

    // Keep track of the widget state
    var initialized = false;
    list.once('changeWidget', function () {
      initialized = true;
      validateSequence();
    });

    // Global elements
    var distributeButton;
    var tbody;

    // Customize table header and footer
    self.once('tableprepared', function (event) {
      var headRow = event.data.thead.firstElementChild;
      var footCell = event.data.tfoot.firstElementChild.firstElementChild;
      tbody = event.data.tbody;
      var fields = event.data.fields;

      // Add dash between 'from' and 'to' values
      addDashCol(headRow, 'th');

      // Mark score range label as required
      headRow.children[0].classList.add('h5peditor-required');

      // Create button to evenly distribute ranges
      distributeButton = createDistributeButton(
        H5PEditor.t('H5PEditor.RangeList', 'distributeButtonLabel'),
        H5PEditor.t('H5PEditor.RangeList', 'distributeButtonWarning'),
        'h5peditor-range-distribute',
        distributeEvenlyHandler(fields[0].min, fields[1].max)
      );

      // Increase footer size and insert button
      footCell.colSpan += 2;
      footCell.appendChild(distributeButton);

      // Create message area and insert before buttons
      self.messageArea = document.createElement('div');
      self.messageArea.className = 'h5p-editor-range-list-message-area';
      footCell.insertBefore(self.messageArea, footCell.firstElementChild);
    });

    // Customize rows as they're added
    self.on('rowadd', function (event) {
      var row = event.data.element;
      var fields = event.data.fields;
      var instances = event.data.instances;

      // Customize the 'from' input part
      var fromInput = getFirst('input', row);
      makeReadOnly(fromInput);

      // Customize each row by adding a separation dash between 'from' and 'to'
      addDashCol(row, 'td', '–');

      // Customize the 'to' input part
      var toInput = getSecond('input', row);

      // Create textual representation to display if this is the last row
      addInputText(toInput);

      // Set min value of 'to' field to equal the 'from' field value
      linkPropertyValue('min', instances[1], fromInput);

      // Update the next row's 'from' input when this row's 'to' input changes
      toInput.addEventListener('change', updateInputHandler(fields[0]));

      var isFirstRow = !row.previousElementSibling;
      if (isFirstRow) {
        // This is the first row, disable buttons
        toggleButtons(false, row);
      }
      else {
        // Show the preivous field's second input when adding a new row
        makeEditable(row.previousElementSibling);

        // More than one row, enable buttons
        toggleButtons(true, row.previousElementSibling);
      }

      if (initialized) {
        validateSequence();
      }
    });

    // Handle row being removed from the table
    self.on('rowremove', function (event) {
      var row = event.data.element;
      var fields = event.data.fields;

      if (!row.nextElementSibling) {
        // This was the last row
        if (row.previousElementSibling) {
          getSecond('.h5peditor-input-text', row.previousElementSibling).style.display = '';
          var prevToInput = getSecond('input', row.previousElementSibling);
          prevToInput.style.display = 'none';
          setValue(prevToInput, fields[1].max);

          if (!row.previousElementSibling.previousElementSibling) {
            // Only one row left, disable buttons
            toggleButtons(false, row.previousElementSibling);
          }
        }
      }
      else if (!row.previousElementSibling) {
        // This was the first row
        setValue(getFirst('input', row.nextElementSibling), fields[0].min);
        if (!row.nextElementSibling.nextElementSibling) {
          // Only one row left, disable buttons
          toggleButtons(false, row.nextElementSibling);
        }
      }
      else {
        // Set first input of next row to match the second input of previous row.
        setValue(getFirst('input', row.nextElementSibling), getSecond('input', row.previousElementSibling).value);
      }
    });

    // When row is removed we check for overlapping sequences
    self.on('rowremoved', function () {
      validateSequence();
    });

    /**
     * Convert the given input field into a read-only type field that is
     * updated programmatically.
     *
     * @private
     * @param {HTMLInputElement} input
     */
    var makeReadOnly = function (input) {
      // Default value for newly added row is set to blank when this
      // is a row added by the user
      var isFirstRow = !input.parentElement.parentElement.parentElement.previousElementSibling;
      if (!isFirstRow && initialized) {
        setValue(input, '');
      }

      // Add textual representation of input
      addInputText(input);

      // Hide all errors since the input is updated programmatically
      input.parentElement.querySelector('.h5p-errors').style.display = 'none';
    };

    /**
     * The given row is no longer the last row and the 'to' input should
     * now be editable.
     *
     * @private
     * @param {HTMLTableRowElement} row
     */
    var makeEditable = function (row) {
      getSecond('.h5peditor-input-text', row).style.display = 'none';
      var prevToInput = getSecond('input', row);
      prevToInput.style.display = '';

      if (initialized) {
        // User action, use no value as default
        setValue(prevToInput, '');

        // Override / clear 'field is mandatory' error messages
        prevToInput.parentNode.querySelector('.h5p-errors').innerHTML = '';
        prevToInput.classList.remove('error');
      }
    };

    /**
     * Set the given field property to equal the value the given input field
     *
     * @private
     * @param {string} property
     * @param {Object} fieldInstance
     * @param {HTMLInputElement} input
     */
    var linkPropertyValue = function (property, fieldInstance, input) {
      // Update the current value to equal that of the input
      fieldInstance.field[property] = parseInt(input.value);

      // Update the value if the value of the field changes
      input.addEventListener('change', function () {
        fieldInstance.field[property] = parseInt(input.value);
        fieldInstance.$input[0].dispatchEvent(createNewEvent('change'));
      });
    };

    /**
     * Update the next row's 'from' input when this 'to' input change.
     *
     * @private
     * @param {Object} field
     * @return {function} Event handler
     */
    var updateInputHandler = function (field) {
      return function () {
        var nextRow = this.parentElement.parentElement.parentElement.nextElementSibling;
        if (!nextRow) {
          // This is the last row, nothing to update
          return;
        }

        var targetInput = getFirst('input', nextRow);
        if (this.value === '') {
          // No value has been set
          setValue(targetInput, '');
          return;
        }

        var value = parseInt(this.value);
        if (!isNaN(value)) {
          // Increment next from value
          value += 1;
          if (field.max && value >= field.max) {
            value = field.max; // Respect max limit
          }
          setValue(targetInput, value);
        }

        validateSequence();
      };
    };

    /**
     * Add dash column to the given row.
     *
     * @private
     * @param {HTMLTableRowElement} row
     * @param {string} type 'td' or 'th'
     * @param {string} [symbol] The 'text' to display
     */
    var addDashCol = function (row, type, symbol) {
      var dash = document.createElement(type);
      dash.classList.add('h5peditor-dash');
      if (symbol) {
        dash.innerText = '–';
      }
      row.insertBefore(dash, row.children[1]);
    };

    /**
     * Add text element displaying input value and hide input.
     *
     * @private
     * @param {HTMLInputElement} input
     */
    var addInputText = function (input) {
      // Add static text
      var text = document.createElement('div');
      text.classList.add('h5peditor-input-text');
      text.innerHTML = input.value;
      input.parentElement.insertBefore(text, input);

      // Hide input
      input.style.display = 'none';

      // Update static on changes
      input.addEventListener('change', function () {
        text.innerHTML = input.value;
      });
    };

    /**
     * Look for the given selector/type in the first cell of the given row.
     *
     * @private
     * @param {string} type selector
     * @param {HTMLTableRowElement} row to look in
     */
    var getFirst = function (type, row) {
      return row.children[0].querySelector(type);
    };

    /**
     * Look for the given selector/type in the second cell of the given row.
     *
     * @private
     * @param {string} type selector
     * @param {HTMLTableRowElement} row to look in
     */
    var getSecond = function (type, row) {
      return row.children[2].querySelector(type);
    };

    /**
     * Set the given value for the given input and trigger the change event.
     *
     * @private
     * @param {HTMLInputElement} input
     * @param {string} value
     */
    var setValue = function (input, value) {
      input.value = value;
      input.dispatchEvent(createNewEvent('change'));
    };

    /**
     * Create a new event, using a fallback for older browsers (IE11)
     *
     * @param {string} type
     * @return {Event}
     */
    var createNewEvent = function (type) {
      if (typeof Event !== 'function') {
        var event = document.createEvent('Event');
        event.initEvent(type, true, true);
        return event;
      }
      else {
        return new Event(type);
      }
    };

    /**
     * Identify any overlapping ranges and provide an error message.
     *
     * @private
     */
    var validateSequence = function () {
      var prevTo, error;
      for (var i = 0; i < tbody.children.length; i++) {
        var row = tbody.children[i];
        var to = parseInt(getSecond('input', row).value);

        if (prevTo !== undefined && !isNaN(to) && to <= prevTo) {
          error = true;
          row.classList.add('h5p-error-range-overlap');
        }
        else {
          row.classList.remove('h5p-error-range-overlap');
        }
        prevTo = to;
      }

      // Display a message
      self.messageArea.innerText = error ? H5PEditor.t('H5PEditor.RangeList', 'rangeOutOfSequenceWarning') : '';
      self.messageArea.classList[error ? 'add' : 'remove']('problem-found');
    };

    /**
     * Create distribute button
     *
     * @private
     * @param {string} label
     * @param {string} warning
     * @param {string} classname
     * @param {function} action
     * @return {HTMLElement}
     */
    var createDistributeButton = function (label, warning, classname, action) {

      // Create confirmation dialog
      var confirmDialog = new H5P.ConfirmationDialog({
        dialogText: warning
      }).appendTo(document.body);

      confirmDialog.on('confirmed', action);

      // Create and return button element
      return H5PEditor.createButton(classname, label, function () {
        if (this.getAttribute('aria-disabled') !== 'true') {
          // The button has been clicked, activate confirmation dialog if
          // the author has defined any ranges
          if (authorHasDefinedRanges()) {
            confirmDialog.show(this.getBoundingClientRect().top);
          }
          else {
            action();
          }
        }
      }, true)[0];
    };

    /**
     * Check if any input fields have gotten values by the author
     *
     * @private
     * @return {boolean}
     */
    var authorHasDefinedRanges = function () {
      for (var i = 0; i < tbody.children.length - 1; i++) {
        var to = parseInt(getSecond('input', tbody.children[i]).value);
        if (!isNaN(to)) {
          return true;
        }
      }
      return false;
    };

    /**
     * Generate an event handler for distributing ranges equally.
     *
     * @private
     * @param {number} start The minimum value
     * @param {number} end The maximum value
     * @return {function} Event handler
     */
    var distributeEvenlyHandler = function (start, end) {
      return function () {
        // Distribute percentages evenly
        var rowRange = (end - start) / tbody.children.length;

        // Go though all the rows
        for (var i = 0; i < tbody.children.length; i++) {
          var row = tbody.children[i];
          var from = start + (rowRange * i);
          setValue(getFirst('input', row), Math.floor(from) + (i === 0 ? 0 : 1));
          var secondInput = getSecond('input', row);
          setValue(secondInput, Math.floor(from + rowRange));
          secondInput.dispatchEvent(createNewEvent('keyup')); // Workaround to remove error messages
        }

        validateSequence();
      };
    };

    /**
     * Toggle buttons disabled / enabled
     *
     * @private
     * @param {boolean} state true to enable buttons, false to disable
     * @param {HTMLTableRowElement} row to look in
     */
    var toggleButtons = function (state, row) {
      var removeButton = row.children[row.children.length - 1].children[0];
      if (state) {
        enableButton(distributeButton);
        enableButton(removeButton);
      }
      else {
        disableButton(distributeButton);
        disableButton(removeButton);
      }
    };

    /**
     * Disables the given button
     *
     * @private
     * @param {HTMLElement} button to look in
     */
    var disableButton = function (button) {
      button.setAttribute('aria-disabled', 'true');
      button.removeAttribute('tabindex');
    };

    /**
     * Enables the given button
     *
     * @private
     * @param {HTMLElement} button to look in
     */
    var enableButton = function (button) {
      button.removeAttribute('aria-disabled');
      button.setAttribute('tabindex', '0');
    };
  }

  // Extend TableList prototype
  RangeList.prototype = Object.create(TableList.prototype);
  RangeList.prototype.constructor = RangeList;

  return RangeList;
})(H5P.jQuery, H5PEditor.TableList);
