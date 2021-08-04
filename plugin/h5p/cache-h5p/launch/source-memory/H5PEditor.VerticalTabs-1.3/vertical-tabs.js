/** @namespace H5PEditor */
var H5PEditor = H5PEditor || {};

H5PEditor.VerticalTabs = (function ($) {

  /**
   * Draws the list.
   *
   * @class
   * @param {List} list
   */
  function VerticalTabs(list) {
    var self = this;
    var entity = list.getEntity();

    // Make first letter upper case.
    entity = entity.substr(0,1).toUpperCase() + entity.substr(1);

    // Create DOM elements
    var $wrapper = $('<div/>', {
      'class': 'h5p-vtab-wrapper'
    });
    var $inner = $('<div/>', {
      'class': 'h5p-vtabs'
    }).appendTo($wrapper);
    var $tabs = $('<ol/>', {
      id: list.getId(),
      'aria-describedby': list.getDescriptionId(),
      'role': 'tablist',
      'class': 'h5p-ul'
    }).appendTo($inner);
    H5PEditor.createButton('add-entity', H5PEditor.t('core', 'addEntity', {':entity': entity}), function () {
      if (list.addItem()) {
        $tabs.children(':last').trigger('open');
        toggleOrderButtonsState();
      }
    }, true).appendTo($inner);
    var $forms = $('<div/>', {
      'class': 'h5p-vtab-forms'
    }).appendTo($wrapper);

    // Once all items have been added we toggle the state of the order buttons
    list.once('changeWidget', function () {
      toggleOrderButtonsState();
    });

    // Used when dragging items around
    var adjustX, adjustY, marginTop, formOffset, $currentTab;

    /**
     * @private
     * @param {jQuery} $item
     * @param {jQuery} $placeholder
     * @param {Number} x
     * @param {Number} y
     */
    var moveItem = function ($item, $placeholder, x, y) {
      var currentIndex;

      // Adjust so the mouse is placed on top of the icon.
      x = x - adjustX;
      y = y - adjustY;

      $item.css({
        top: y - marginTop - formOffset.top,
        left: x - formOffset.left
      });

      // Try to move up.
      var $prev = $item.prev().prev();
      if ($prev.length && y < $prev.offset().top + ($prev.height() / 2)) {
        $prev.insertAfter($item);


        currentIndex = $item.index();
        list.moveItem(currentIndex, currentIndex - 1);

        return;
      }

      // Try to move down.
      var $next = $item.next();
      if ($next.length && y + $item.height() > $next.offset().top + ($next.height() / 2)) {
        $next.insertBefore($placeholder);

        currentIndex = $item.index() - 2;
        list.moveItem(currentIndex, currentIndex + 1);
      }
    };

    /**
     * Re-index labels. Necessary after tabs are sorted or removed.
     *
     * @private
     */
    var reindexLabels = function () {
      $tabs.find('.h5p-index-label').each(function (index, element) {
        $(element).text(index + 1);
      });
      toggleOrderButtonsState();
    };

    /**
     * Always run after reordering, adding or removing to ensure correct
     * state of the order buttons.
     *
     * @private
     */
    var toggleOrderButtonsState = function () {
      $tabs.children().each(function (index, element) {
        var $tab = $(element);
        var isTopTab = !$tab.prev().length;
        $tab.find('.order-up').attr('aria-disabled', isTopTab).attr('tabindex', isTopTab ? '-1' : '0');
        var isBottomTab = !$tab.next().length;
        $tab.find('.order-down').attr('aria-disabled', isBottomTab).attr('tabindex', isBottomTab ? '-1' : '0');
      });
    };

    /**
     * Opens the given tab.
     *
     * @private
     * @param {jQuery} $newTab
     */
    var openTab = function ($newTab) {
      if ($currentTab !== undefined) {
        H5PEditor.Html.removeWysiwyg();
        $currentTab.removeClass('h5p-current');
      }
      $newTab.addClass('h5p-current');
      $currentTab = $newTab;
    };

    /**
     * Adds UI items to the widget.
     *
     * @public
     * @param {Object} item
     */
    self.addItem = function (item) {

      var $placeholder;
      var $tab = $('<li/>', {
        'class': 'h5p-vtab-li'
      }).appendTo($tabs);

      /**
       * Mouse move callback
       *
       * @private
       * @param {Object} event
       */
      var move = function (event) {
        moveItem($tab, $placeholder, event.pageX, event.pageY);
      };

      /**
       * Mouse button release callback
       *
       * @private
       */
      var up = function () {
        H5P.$window
          .unbind('mousemove', move)
          .unbind('mouseup', up);

        H5P.$body
          .attr('unselectable', 'off')
          .css({
            '-moz-user-select': '',
            '-webkit-user-select': '',
            'user-select': '',
            '-ms-user-select': '',
            'overflow': '',
          })[0].onselectstart = H5P.$body[0].ondragstart = null;

        $tab.removeClass('h5p-moving').css({
          width: 'auto',
          height: 'auto',
          top: '',
          left: ''
        });
        $placeholder.remove();
        reindexLabels();
      };

      /**
       * Mouse button down callback
       *
       * @private
       */
      var down = function (event) {
        if (event.which !== 1) {
          return; // Only allow left mouse button
        }

        H5P.$window
          .mousemove(move)
          .mouseup(up);

        // Start tracking mouse
        H5P.$body
          .attr('unselectable', 'on')
          .css({
            '-moz-user-select': 'none',
            '-webkit-user-select': 'none',
            'user-select': 'none',
            '-ms-user-select': 'none',
            'overflow': 'hidden'
          })[0].onselectstart = H5P.$body[0].ondragstart = function () {
            return false;
          };

        var offset = $tab.offset();
        adjustX = event.pageX - offset.left;
        adjustY = event.pageY - offset.top;
        marginTop = parseInt($tab.css('marginTop'));
        formOffset = $tabs.offsetParent().offset();
        // TODO: Couldn't formOffset and margin be added?

        var width = $tab.width();
        var height = $tab.height();

        $tab.addClass('h5p-moving').css({
          width: width,
          height: height
        });
        $placeholder = $('<li/>', {
          'class': 'h5p-placeholder'
        }).insertBefore($tab);

        $('<div/>', {
          class: 'h5p-vtab-a',
        }).appendTo($placeholder);

        move(event);
      };

      /**
       * Order current list item up
       *
       * @private
       */
      var moveItemUp = function () {
        var $prev = $tab.prev();
        if (!$prev.length) {
          return; // Cannot move item further up
        }

        var currentIndex = $tab.index();
        $prev.insertAfter($tab);
        list.moveItem(currentIndex, currentIndex - 1);
        reindexLabels();
      };

      /**
       * Order current ist item down
       *
       * @private
       */
      var moveItemDown = function () {
        var $next = $tab.next();
        if (!$next.length) {
          return; // Cannot move item further down
        }

        var currentIndex = $tab.index();
        $next.insertBefore($tab);
        list.moveItem(currentIndex, currentIndex + 1);
        reindexLabels();
      };

      // Handle opening of the tab
      $tab.on('open', function () {
        openTab($tab.add($form));
      });

      var mouseDownPos;

      // Add clickable label
      $('<div/>', {
        'class' : 'h5p-vtab-a',
        html: '<span class="h5p-index-label">' + ($tab.index() + 1) + '</span>. <span class="h5p-label" title="' + entity + '">' + entity + '</span>',
        role: 'tab',
        tabIndex: 0,
        on: {
          mouseup: function (e) {

            if (!mouseDownPos) {
              return;
            }
            // Determine movement
            var xDiff = Math.abs(mouseDownPos.x - e.pageX);
            var yDiff = Math.abs(mouseDownPos.y - e.pageY);
            var moveThreshold = 20;

            // Open tab if moved less than threshold
            if (xDiff < moveThreshold && yDiff < moveThreshold) {
              $tab.trigger('open');
            }
          },
          mousedown: function (e) {
            // Start position
            mouseDownPos = {
              x: e.pageX,
              y: e.pageY
            };

            // Order element
            down(e);
          },
          keypress: function (e) {
            if (e.which === 32) {
              e.preventDefault();
              $tab.trigger('open');
            }
          }
        }
      }).appendTo($tab);

      var $tabLabel = $tab.find('.h5p-label');

      var setTabLabel = function (label) {
        $tabLabel.text(label).attr('title', label);
      };

      // Add buttons for ordering
      var $orderWrapper = $('<div/>', {
        'class': 'vtab-order-wrapper',
        appendTo: $tab
      });
      H5PEditor.createButton('order-up', H5PEditor.t('core', 'orderItemUp'), moveItemUp).appendTo($orderWrapper);
      H5PEditor.createButton('order-down', H5PEditor.t('core', 'orderItemDown'), moveItemDown).appendTo($orderWrapper);

      // Add remove button
      var $removeWrapper = $('<div/>', {
        'class': 'vtab-remove-wrapper',
        appendTo: $tab
      });
      H5PEditor.createButton('remove', H5PEditor.t('core', 'removeItem'), function () {
        confirmRemovalDialog.show($(this).offset().top);
      }).appendTo($removeWrapper);

      // Create confirmation dialog for removing list item
      var confirmRemovalDialog = new H5P.ConfirmationDialog({
        dialogText: H5PEditor.t('core', 'confirmRemoval', {':type': entity.toLocaleLowerCase()})
      }).appendTo(document.body);

      // Remove list item on confirmation
      confirmRemovalDialog.on('confirmed', function () {
        var $next, index = $tab.index();

        if ($tab.hasClass('h5p-current')) {
          if (index) {
            // Go to previous tab
            $next = $tab.prev().add($form.prev());
          }
          else {
            // Go to next tab
            $next = $tab.next().add($form.next());
          }

          if ($next.length) {
            // Open another tab
            $next.trigger('open');
          }
        }

        list.removeItem(index);
        $tab.remove();
        $form.remove();
        reindexLabels();
      });

      // Create form wrapper
      var $form = $('<fieldset/>', {
        'role': 'tabpanel',
        'class': 'h5p-vtab-form'
      });

      if (item instanceof H5PEditor.Group) {
        item.on('summary', function (event) {
          if (event.data) {
            // Update tab with summary
            setTabLabel(event.data.substr(0, 32));
          }
        });
      }

      // Append new field item to forms wrapper
      item.appendTo($form);

      // Append form wrapper to forms list
      $form.appendTo($forms);

      // Good UX: automatically expand groups
      if (item instanceof H5PEditor.Group) {
        item.expand();

        // Remove group title
        item.$group.children('.title').remove();
      }
      else if (item instanceof H5PEditor.Library) {
        $form.addClass('content');

        let summary = item.$select.children(':selected').text();
        if (item.params.metadata && item.params.metadata.title) {
          // The given title usually makes more sense than the type name
          summary = item.params.metadata.title + (!item.libraries || (item.libraries.length > 1 && item.params.metadata.title.indexOf(summary) === -1) ? ' (' +  summary + ')' : '');
        }
        setTabLabel(summary);

        // Use selected library as title
        item.changes.push(function (library) {
          setTabLabel(library ? library.title : '');
        });

        let lastLib;
        const setSummary = function () {
          if (item.params && item.params.metadata && item.params.metadata.title) {
            // The given title usually makes more sense than the type name
            setTabLabel(item.params.metadata.title + (item.libraries.length > 1 && item.params.metadata.title.indexOf(lastLib.title) === -1 ? ' (' +  lastLib.title + ')' : ''));
          }
          else {
            setTabLabel(lastLib ? lastLib.title : '');
          }
        };
        if (item.metadataForm) {
          item.metadataForm.on('titlechange', setSummary);
        }
        item.change(function (library) {
          lastLib = library;
          setSummary();

          if (item.metadataForm) {
            // Update summary when metadata title changes
            item.metadataForm.off('titlechange', setSummary);
            item.metadataForm.on('titlechange', setSummary);
          }
        });
      }
      else if (item instanceof H5PEditor.Select) {
        // Use selected value as title
        var change = function () {
          var value = item.$select.val();
          setTabLabel(value === '-' ?  entity : item.$select.children('option[value="' + value + '"]').text());
        };
        item.$select.change(change);
        change();
      }

      if ($currentTab === undefined) {
        // Open tab if there are none open
        $tab.trigger('open');
      }

      $tabs.find('.h5p-vtab-li .h5p-vtab-a').first().focus();
    };

    /**
     * Puts this widget at the end of the given container.
     *
     * @public
     * @param {jQuery} $container
     */
    self.appendTo = function ($container) {
      $wrapper.appendTo($container);
    };

    /**
     * Remove this widget from the editor DOM.
     *
     * @public
     */
    self.remove = function () {
      $wrapper.remove();
    };
  }

  return VerticalTabs;
})(H5P.jQuery);
