/**
 *
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

/**
 * Manages the Page Editor
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
var Sonata = Sonata || {};

Sonata.Page = {

    /**
     * Enable/disable debug mode
     *
     * @var boolean
     */
    debug: false,

    /**
     * Collection of blocks found on the page
     *
     * @var array
     */
    blocks: [],

    /**
     * Collection of containers found on the page
     *
     * @var array
     */
    containers: [],

    /**
     * block data
     *
     * @var array
     */
    data: [],

    /**
     * Block DOM selector
     *
     * @var string
     */
    blockSelector: '.cms-block',

    /**
     * Container DOM selector
     *
     * @var string
     */
    containerSelector: '.cms-container',

    /**
     * Drop placeholder CSS class
     *
     * @var string
     */
    dropPlaceHolderClass: 'cms-block-placeholder',

    /**
     * Drop placeholder size
     *
     * @var integer
     */
    dropPlaceHolderSize: 100,

    /**
     * Drop zone container CSS class
     *
     * @var string
     */
    dropZoneClass: 'cms-container-drop-zone',

    /**
     * Block hover CSS class
     *
     * @var string
     */
    blockHoverClass: 'cms-block-hand-over',

    /**
     * URLs to use when performing ajax operations
     *
     * @var Object
     */
    url: {
        block_save_position: null,
        block_edit: null
    },

    /**
     * Initialize Page editor mode
     */
    init: function(options) {
        options = options || [];
        for (property in options) {
            this[property] = options[property];
        }

        this.initInterface();
        this.initBlocks();
        this.initContainers();
        this.initBlockData();
    },

    /**
     * Initialize Admin interface (buttons)
     */
    initInterface: function() {
        jQuery('#page-action-enabled-edit').change(jQuery.proxy(this.toggleEditMode, this));
        jQuery('#page-action-save-position').click(jQuery.proxy(this.saveBlockLayout, this));
    },

    /**
     * Initialize block elements and behaviors
     */
    initBlocks: function() {
        // cache blocks
        this.blocks = jQuery(this.blockSelector);

        this.blocks.mouseover(jQuery.proxy(this.handleBlockHover, this));
        this.blocks.dblclick(jQuery.proxy(this.handleBlockClick, this));
    },

    /**
     * Initialize container elements and behaviors
     */
    initContainers: function() {
        // cache containers
        this.containers = jQuery(this.containerSelector);

        this.containers.sortable({
            connectWith:          this.containerSelector,
            items:                this.blockSelector,
            placeholder:          this.dropPlaceHolderClass,
            helper:               'clone',
            dropOnEmpty:          true,
            forcePlaceholderSize: this.dropPlaceHolderSize,
            opacity:              1,
            cursor:               'move',
            start:                jQuery.proxy(this.startContainerSort, this),
            stop:                 jQuery.proxy(this.stopContainerSort, this)
        }).sortable('disable');
    },

    /**
     * Initialize the block data (used to perform a diff when changing position/hierarchy)
     */
    initBlockData: function() {
        this.data = this.buildBlockData();
    },

    /**
     * Starts the container sorting
     *
     * @param event
     * @param ui
     */
    startContainerSort: function(event, ui) {
        this.containers.addClass(this.dropZoneClass);
        this.containers.append(jQuery('<div class="cms-fake-block">&nbsp;</div>'));
    },

    /**
     * Stops the container sorting
     *
     * @param event
     * @param ui
     */
    stopContainerSort: function(event, ui) {
        this.containers.removeClass(this.dropZoneClass);
        jQuery('div.cms-fake-block').remove();
        this.refreshLayers();
    },

    /**
     * Handle a click on the block
     *
     * @param event
     */
    handleBlockClick: function(event) {
        var target = event.currentTarget,
            id = jQuery(target).attr('data-id');

        window.open(this.url.block_edit.replace(/BLOCK_ID/, id), '_newtab');

        event.preventDefault();
        event.stopPropagation();
    },

    /**
     * Handle a hover on the block
     *
     * @param event
     */
    handleBlockHover: function(event) {
        this.blocks.removeClass(this.blockHoverClass);
        jQuery(this).addClass(this.blockHoverClass);
        event.stopPropagation();
    },

    /**
     * Toggle edit mode
     *
     * @param event
     */
    toggleEditMode: function(event) {
        if (event && event.currentTarget.checked) {
            jQuery('body').addClass('cms-edit-mode');
            jQuery('.cms-container').sortable('enable');
            this.buildLayers();
        } else {
            jQuery('body').removeClass('cms-edit-mode');
            jQuery('div.cms-container').sortable('disable');
            this.removeLayers();
        }

        event.preventDefault();
        event.stopPropagation();
    },

    /**
     * Build block layers
     */
    buildLayers:function() {
        this.blocks.each(function(index) {
            var block   = jQuery(this),
                role    = block.attr('data-role') || 'block',
                name    = block.attr('data-name') || 'missing data-name',
                id      = block.attr('data-id') || 'missing data-id',
                classes = [],
                layer;

            classes.push('cms-layout-layer');
            classes.push('cms-layout-role-'+role);

            // build layer
            layer = jQuery('<div class="'+classes.join(' ')+'" ></div>');
            layer.css({
                position: "absolute",
                left: 0,
                top: 0,
                width: '100%',
                height: '100%',
                zIndex: 2
            });

            // build layer title
            title = jQuery('<div class="cms-layout-title"></div>');
            title.css({
                position: "absolute",
                left: 0,
                top: 0,
                zIndex: 2
            });
            title.html('<span>'+name+'</span>');
            layer.append(title);

            block.prepend(layer);
        });
    },

    /**
     * Remove all block layers
     */
    removeLayers: function() {
        jQuery('.cms-layout-layer').remove();
    },

    /**
     * Refreshes the block layers
     */
    refreshLayers: function() {
        jQuery('.cms-layout-layer').each(function(position) {
            var layer = jQuery(this),
                block = layer.parent();

            layer.css('width', block.width());
            layer.css('height', block.height());
        });
    },

    /**
     * Build block data used to perform a database update of block position and hierarchy
     *
     * @return {Array} An array of block information with id, position, and parent id
     */
    buildBlockData: function() {
        var data = [];

        this.blocks.each(jQuery.proxy(function(index, block) {
            var item = this.buildSingleBlockData(block)
            if (item) {
                data.push(item);
            }
        }, this));

        // sort items on page, parent and position
        data.sort(function(a, b) {
            if (a.page_id == b.page_id) {
                if (a.parent_id == b.parent_id) {
                    return a.position - b.position;
                }
                return a.parent_id - b.parent_id;
            }
            return a.page_id - b.page_id;
        })

        return data;
    },

    /**
     * Builds a single block data
     *
     * @param original
     */
    buildSingleBlockData: function(original) {
        var block, id, parent, parentId, pageId, previous, position;

        block = jQuery(original);

        // retrieve current block id
        id = block.attr('data-id');
        if (!id) {
            this.log('Block has no data-id, ignored !');
            return;
        }

        // retrieve parent block container
        parent = this.findParentContainer(block);
        if (!parent) {
            this.log('Block '+id+' has no parent, it must be a root container, ignored');
            return;
        }
        parentId = jQuery(parent).attr('data-id');

        // retrieve root's page (because a root container cannot be moved)
        root = this.findRootContainer(block);
        if (!root) {
            this.log('Block '+id+' has no root but has a parent, should never happen!');
            return;
        }
        pageId = jQuery(root).attr('data-page-id');

        // get previous siblings to count position
        previous = block.prevAll(this.blockSelector+'[data-id]');
        position = previous.length + 1;

        if (!id || !parentId) {
            return;
        }

        return {
            id:        id,
            position:  position,
            parent_id: parentId,
            page_id:   pageId
        };
    },

    /**
     * Returns an array with differences from 2 arrays
     *
     * @param previousData Previous data
     * @param newData      New data
     *
     * @return Array
     */
    buildDiffBlockData: function(previousData, newData) {
        var diff = [];

        jQuery.map(previousData, function(previousItem, index) {
            var found;

            found = jQuery.grep(newData, function(newItem, index) {
                if (previousItem.id != newItem.id) {
                    return false;
                }

                if (previousItem.position != newItem.position || previousItem.parent_id != newItem.parent_id || previousItem.page_id != newItem.page_id) {
                    return true;
                }
            });

            if (found && found[0]) {
                diff.push(found[0]);
            }
        });

        return diff;
    },

    /**
     * Returns the parent container of a block
     *
     * @param block
     *
     * @return {*}
     */
    findParentContainer: function(block) {
        var parents, parent;

        parents = jQuery(block).parents(this.containerSelector+'[data-id]');
        parent = parents.get(0);

        return parent;
    },

    /**
     * Returns the root container of a block
     *
     * @param block
     *
     * @return {*}
     */
    findRootContainer: function(block) {
        var parents, root;

        parents = jQuery(block).parents(this.containerSelector+'[data-id]');
        root = parents.get(-1);

        return root;
    },

    /**
     * Save block layout to server
     *
     * @param event
     */
    saveBlockLayout: function(event) {
        var diff;

        event.preventDefault();
        event.stopPropagation();

        diff = this.buildDiffBlockData(this.data, this.buildBlockData());

        if (diff.length == 0) {
            alert('No changes found.');
            return;
        }

        jQuery.each(diff, jQuery.proxy(function(item, block) {
            this.log('Update block '+block.id+ ' (Page '+block.page_id+'), parent '+block.parent_id+', at position '+block.position+')');
        }, this));

        jQuery.ajax({
            type: 'POST',
            url: this.url.block_save_position,
            data: { disposition: diff },
            dataType: 'json',
            success: jQuery.proxy(function(data, status, xhr) {
                if (data.result == 'ok') {
                    alert('Block ordering saved!');
                    // re-initialize block data to consider as the new "previous" values
                    this.initBlockData();
                } else {
                    this.log(data);
                    alert('Server could not save block ordering!');
                }
            }, this),
            error: jQuery.proxy(function(xhr, status, error) {
                this.log('Unable to save block ordering: '+ error);
                this.log(status);
                this.log(xhr);
            }, this)
        });

    },

    /**
     * Log messages
     */
    log: function() {
        if (!this.debug) {
            return;
        }

        try {
            console.log(arguments);
        } catch(e) {

        }
    }
}