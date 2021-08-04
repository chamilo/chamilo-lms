/* global ns */
H5PEditor.TableList = (function ($, EventDispatcher) {

  /**
   * Renders UI for the table list.
   *
   * @class
   * @extends H5P.EventDispatcher
   * @param {List} list
   * @param {string} [extraClass]
   */
  function TableList(list, extraClass) {
    var self = this;

    // Initialize inheritance
    EventDispatcher.call(self);

    // Grab entity and make first letter upper case
    var entity = list.getEntity();
    entity = entity.substr(0,1).toLocaleUpperCase() + entity.substr(1);

    // Create DOM structure elements for the table
    var $wrapper = $('<table/>', {
      'class': 'h5p-editor-table-list' + (extraClass ? ' ' + extraClass : '')
    });
    var $thead = $('<thead/>', {
      appendTo: $wrapper
    });
    var $headRow;
    var $tbody = $('<tbody/>', {
      appendTo: $wrapper
    });
    var $tfoot = $('<tfoot/>', {
      appendTo: $wrapper
    });

    /**
     * Adds UI items to the widget.
     *
     * @public
     * @param {Object} item
     */
    self.addItem = function (item) {
      if (!(item instanceof H5PEditor.Group)) {
        return; // Only support multiple fields
      }

      if (!$headRow) {
        var group = list.getField();
        addHeader(group.fields);
        addFooter(group.fields.length);

        self.trigger('tableprepared', {
          thead: $thead[0],
          tfoot: $tfoot[0],
          tbody: $tbody[0],
          fields: group.fields
        });
      }

      // Set default params in case item has no params
      if (item.params === undefined) {
        item.params = {};
        item.setValue(item.field, item.params);
      }

      addRow(item);
    };

    /**
     * Add table headers
     *
     * @private
     * @param {Array} fields
     */
    var addHeader = function (fields) {
      $headRow = $('<tr/>', {
        appendTo: $thead
      });
      for (var i = 0; i < fields.length; i++) {
        $('<th/>', {
          'class': 'h5peditor-type-' + fields[i].type,
          html: (fields[i].label ? fields[i].label : ''),
          appendTo: $headRow
        });
        fields[i].label = 0; // No labels inside table rows
      }
      $('<th/>', {
        'class': 'h5peditor-remove-header',
        appendTo: $headRow
      });

      self.trigger('headeradd', {
        element: $headRow[0],
        fields: fields
      });
    };

    /**
     * Add table footer
     *
     * @private
     * @param {number} length
     */
    var addFooter = function (length) {
      var $footRow = $('<tr/>', {
        appendTo: $tfoot
      });
      var $footCell = $('<td/>', {
        colspan: length,
        appendTo: $footRow
      });
      H5PEditor.createButton(list.getImportance(), H5PEditor.t('core', 'addEntity', {':entity': entity}), function () {
        list.addItem();
      }, true).appendTo($footCell);

      self.trigger('footeradd', {
        footerCell: $footCell[0],
        fields: list.getField().fields,
        tbody: $tbody[0]
      });
    };

    /**
     * Add a new table row with data using the given group as source
     *
     * @private
     * @param {H5PEditor.Group} item
     */
    var addRow = function (item) {
      // Keep track of field instances
      item.children = [];

      // Create row element
      var $tableRow = $('<tr/>', {
        appendTo: $tbody
      });

      // Process semantics to create row fields
      var fields = item.getFields();
      for (var i = 0; i < fields.length; i++) {
        fields[i].label = 0;

        var fieldInstance = processSemanticsField(item, fields[i]);

        if (fieldInstance) {
          var $cell = $('<td/>', {
            appendTo: $tableRow
          });
          fieldInstance.appendTo($cell);
          item.children.push(fieldInstance);
        }
      }

      // Add remove button
      var $removeButtonCell = $('<td/>', {
        'class': 'h5peditor-remove-button',
        appendTo: $tableRow
      });

      H5PEditor.createButton('remove', H5PEditor.t('core', 'removeItem'), function () {
        if (this.getAttribute('aria-disabled') !== 'true') {
          confirmRemovalDialog.show($(this).offset().top);
        }
      }).appendTo($removeButtonCell);

      // Create confirmation dialog for removing list item
      var confirmRemovalDialog = new H5P.ConfirmationDialog({
        dialogText: H5PEditor.t('core', 'confirmRemoval', {':type': entity.toLocaleLowerCase()})
      }).appendTo(document.body);
      confirmRemovalDialog.on('confirmed', function () {
        // Remove him!
        self.trigger('rowremove', {
          element: $tableRow[0],
          fields: fields
        });
        var index = $tableRow.index();
        list.removeItem(index);
        $tableRow.remove(); // Bye, bye
        self.trigger('rowremoved');
      });

      // Allow overriding / customization
      self.trigger('rowadd', {
        element: $tableRow[0],
        fields: fields,
        instances: item.children
      });
    };

    /**
     * Convert semantics into widgets.
     *
     * @private
     * @param {H5PEditor.Group} parent
     * @param {Object} field
     */
    var processSemanticsField = function (parent, field) {
      // Check required field properties
      if (field.name === undefined || field.type === undefined) {
        return;
      }

      // Set default value
      if (parent.params[field.name] === undefined && field['default'] !== undefined) {
        parent.params[field.name] = field['default'];
      }

      // Locate widget
      var widget = ns.getWidgetName(field);

      // Create new field instance
      return new ns.widgets[widget](parent, field, parent.params[field.name], function (field, value) {
        if (value === undefined) {
          delete parent.params[field.name];
        }
        else {
          parent.params[field.name] = value;
        }
      });
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

  // Extend the prototype
  TableList.prototype = Object.create(EventDispatcher.prototype);
  TableList.prototype.constructor = TableList;

  return TableList;
})(H5P.jQuery, H5P.EventDispatcher);
