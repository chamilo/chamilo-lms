/**
 * @class
 * @classdesc Keyboard navigation for accessibility support
 * @extends H5P.EventDispatcher
 */
H5P.KeyboardNav = (function (EventDispatcher) {
  /**
   * Construct a new KeyboardNav
   * @constructor
   */
  function KeyboardNav() {
    EventDispatcher.call(this);

    /** @member {boolean} */
    this.selectability = true;

    /** @member {HTMLElement[]|EventTarget[]} */
    this.elements = [];
  }

  KeyboardNav.prototype = Object.create(EventDispatcher.prototype);
  KeyboardNav.prototype.constructor = KeyboardNav;

  /**
   * Adds a new element to navigation
   *
   * @param {HTMLElement} el The element
   * @public
   */
  KeyboardNav.prototype.addElement = function(el){
    const keyDown = this.handleKeyDown.bind(this);
    const onClick = this.onClick.bind(this);
    el.addEventListener('keydown', keyDown);
    el.addEventListener('click', onClick);

    // add to array to navigate over
    this.elements.push({
      el: el,
      keyDown: keyDown,
      onClick: onClick,
    });

    if(this.elements.length === 1){ // if first
      this.setTabbableAt(0);
    }
  };

  /**
   * Select the previous element in the list. Select the last element,
   * if the current element is the first element in the list.
   *
   * @param {Number} index The index of currently selected element
   * @public
   * @fires KeyboardNav#previousOption
   */
  KeyboardNav.prototype.previousOption = function (index) {
    var isFirstElement = index === 0;
    this.focusOnElementAt(isFirstElement ? (this.elements.length - 1) : (index - 1));

    /**
     * Previous option event
     *
     * @event KeyboardNav#previousOption
     * @type KeyboardNavigationEventData
     */
    this.trigger('previousOption', this.createEventPayload(index));
  };


  /**
   * Select the next element in the list. Select the first element,
   * if the current element is the first element in the list.
   *
   * @param {Number} index The index of the currently selected element
   * @public
   * @fires KeyboardNav#previousOption
   */
  KeyboardNav.prototype.nextOption = function (index) {
    var isLastElement = index === this.elements.length - 1;
    this.focusOnElementAt(isLastElement ? 0 : (index + 1));

    /**
     * Previous option event
     *
     * @event KeyboardNav#nextOption
     * @type KeyboardNavigationEventData
     */
    this.trigger('nextOption', this.createEventPayload(index));
  };

  /**
   * Focus on an element by index
   *
   * @param {Number} index The index of the element to focus on
   * @public
   */
  KeyboardNav.prototype.focusOnElementAt = function (index) {
    this.setTabbableAt(index);
    this.getElements()[index].focus();
  };

  /**
   * Disable possibility to select a word trough click and space or enter
   *
   * @public
   */
  KeyboardNav.prototype.disableSelectability = function () {
    this.elements.forEach(function (el) {
      el.el.removeEventListener('keydown', el.keyDown);
      el.el.removeEventListener('click', el.onClick);
    }.bind(this));
    this.selectability = false;
  };

  /**
   * Enable possibility to select a word trough click and space or enter
   *
   * @public
   */
  KeyboardNav.prototype.enableSelectability = function () {
    this.elements.forEach(function (el) {
      el.el.addEventListener('keydown', el.keyDown);
      el.el.addEventListener('click', el.onClick);
    }.bind(this));
    this.selectability = true;
  };

  /**
   * Sets tabbable on a single element in the list, by index
   * Also removes tabbable from all other elements in the list
   *
   * @param {Number} index The index of the element to set tabbale on
   * @public
   */
  KeyboardNav.prototype.setTabbableAt = function (index) {
    this.removeAllTabbable();
    this.getElements()[index].setAttribute('tabindex', '0');
  };

  /**
   * Remove tabbable from all entries
   *
   * @public
   */
  KeyboardNav.prototype.removeAllTabbable = function () {
    this.elements.forEach(function(el){
      el.el.removeAttribute('tabindex');
    });
  };

  /**
   * Toggles 'aria-selected' on an element, if selectability == true
   *
   * @param {EventTarget|HTMLElement} el The element to select/unselect
   * @private
   * @fires KeyboardNav#select
   */
  KeyboardNav.prototype.toggleSelect = function(el){
    if(this.selectability) {

      // toggle selection
      el.setAttribute('aria-selected', !isElementSelected(el));

      // focus current
      el.setAttribute('tabindex', '0');
      el.focus();

      var index = this.getElements().indexOf(el);

      /**
       * Previous option event
       *
       * @event KeyboardNav#select
       * @type KeyboardNavigationEventData
       */
      this.trigger('select', this.createEventPayload(index));
    }
  };

  /**
   * Handles key down
   *
   * @param {KeyboardEvent} event Keyboard event
   * @private
   */
  KeyboardNav.prototype.handleKeyDown = function(event){
    var index;

    switch (event.which) {
      case 13: // Enter
      case 32: // Space
        // Select
        this.toggleSelect(event.target);
        event.preventDefault();
        break;

      case 37: // Left Arrow
      case 38: // Up Arrow
        // Go to previous Option
        index = this.getElements().indexOf(event.currentTarget);
        this.previousOption(index);
        event.preventDefault();
        break;

      case 39: // Right Arrow
      case 40: // Down Arrow
        // Go to next Option
        index = this.getElements().indexOf(event.currentTarget);
        this.nextOption(index);
        event.preventDefault();
        break;
    }
  };

  /**
   * Get only elements from elements array
   * @returns {Array}
   */
  KeyboardNav.prototype.getElements = function () {
    return this.elements.map(function (el) {
      return el.el;
    });
  };

  /**
   * Handles element click. Toggles 'aria-selected' on element
   *
   * @param {MouseEvent} event Mouse click event
   * @private
   */
  KeyboardNav.prototype.onClick = function(event){
    this.toggleSelect(event.currentTarget);
  };

  /**
   * Creates a paylod for event that is fired
   *
   * @param {Number} index
   * @return {KeyboardNavigationEventData}
   */
  KeyboardNav.prototype.createEventPayload = function(index){
    /**
     * Data that is passed along as the event parameter
     *
     * @typedef {Object} KeyboardNavigationEventData
     * @property {HTMLElement} element
     * @property {number} index
     * @property {boolean} selected
     */
    return {
      element: this.getElements()[index],
      index: index,
      selected: isElementSelected(this.getElements()[index])
    };
  };

  /**
   * Sets aria-selected="true" on an element
   *
   * @param {HTMLElement} el The element to set selected
   * @return {boolean}
   */
  var isElementSelected = function(el){
    return el.getAttribute('aria-selected') === 'true';
  };

  return KeyboardNav;
})(H5P.EventDispatcher);
