/* =========================================================
 * Bootstrap year calendar v1.1.0
 * Repo: https://github.com/Paul-DS/bootstrap-year-calendar
 * =========================================================
 * Created by Paul David-Sivelle
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================= */

(function($) {
    var Calendar = function(element, options) {
        this.element = element;
        this.element.addClass('calendar');

        this._initializeEvents(options);
        this._initializeOptions(options);
        this.setYear(this.options.startYear);
    };

    Calendar.prototype = {
        constructor: Calendar,
        _initializeOptions: function(opt) {
            if(opt == null) {
                opt = [];
            }

            this.options = {
                startYear: !isNaN(parseInt(opt.startYear)) ? parseInt(opt.startYear) : new Date().getFullYear(),
                minDate: opt.minDate instanceof Date ? opt.minDate : null,
                maxDate: opt.maxDate instanceof Date ? opt.maxDate : null,
                language: (opt.language != null && dates[opt.language] != null) ? opt.language : 'en',
                allowOverlap: opt.allowOverlap != null ? opt.allowOverlap : true,
                displayWeekNumber: opt.displayWeekNumber != null ? opt.displayWeekNumber : false,
                displayDisabledDataSource: opt.displayDisabledDataSource != null ? opt.displayDisabledDataSource : false,
                displayHeader: opt.displayHeader != null ? opt.displayHeader : true,
                alwaysHalfDay: opt.alwaysHalfDay != null ? opt.alwaysHalfDay : false,
                enableRangeSelection: opt.enableRangeSelection != null ? opt.enableRangeSelection : false,
                disabledDays: opt.disabledDays instanceof Array ? opt.disabledDays : [],
                disabledWeekDays: opt.disabledWeekDays instanceof Array ? opt.disabledWeekDays : [],
                hiddenWeekDays: opt.hiddenWeekDays instanceof Array ? opt.hiddenWeekDays : [],
                roundRangeLimits: opt.roundRangeLimits != null ? opt.roundRangeLimits : false,
                dataSource: opt.dataSource instanceof Array ? opt.dataSource : [],
                style: opt.style == 'background' || opt.style == 'border' || opt.style == 'custom' ? opt.style : 'border',
                enableContextMenu: opt.enableContextMenu != null ? opt.enableContextMenu : false,
                contextMenuItems: opt.contextMenuItems instanceof Array ? opt.contextMenuItems : [],
                customDayRenderer : $.isFunction(opt.customDayRenderer) ? opt.customDayRenderer : null,
                customDataSourceRenderer : $.isFunction(opt.customDataSourceRenderer) ? opt.customDataSourceRenderer : null,
                weekStart: !isNaN(parseInt(opt.weekStart)) ? parseInt(opt.weekStart) : null
            };

            this._initializeDatasourceColors();
        },
        _initializeEvents: function(opt) {
            if(opt == null) {
                opt = [];
            }

            if(opt.yearChanged) { this.element.bind('yearChanged', opt.yearChanged); }
            if(opt.renderEnd) { this.element.bind('renderEnd', opt.renderEnd); }
            if(opt.clickDay) { this.element.bind('clickDay', opt.clickDay); }
            if(opt.dayContextMenu) { this.element.bind('dayContextMenu', opt.dayContextMenu); }
            if(opt.selectRange) { this.element.bind('selectRange', opt.selectRange); }
            if(opt.mouseOnDay) { this.element.bind('mouseOnDay', opt.mouseOnDay); }
            if(opt.mouseOutDay) { this.element.bind('mouseOutDay', opt.mouseOutDay); }
        },
        _initializeDatasourceColors: function() {
            for(var i = 0; i < this.options.dataSource.length; i++) {
                if(this.options.dataSource[i].color == null) {
                    this.options.dataSource[i].color = colors[i % colors.length];
                }
            }
        },
        render: function() {
            this.element.empty();

            if(this.options.displayHeader) {
                this._renderHeader();
            }

            this._renderBody();
            this._renderDataSource();

            this._applyEvents();
            this.element.find('.months-container').fadeIn(500);

            this._triggerEvent('renderEnd', { currentYear: this.options.startYear });
        },
        _renderHeader: function() {
            var header = $(document.createElement('div'));
            header.addClass('calendar-header panel panel-default');

            var headerTable = $(document.createElement('table'));

            var prevDiv = $(document.createElement('th'));
            prevDiv.addClass('prev');

            if(this.options.minDate != null && this.options.minDate > new Date(this.options.startYear - 1, 11, 31)) {
                prevDiv.addClass('disabled');
            }

            var prevIcon = $(document.createElement('span'));
            prevIcon.addClass('glyphicon glyphicon-chevron-left');

            prevDiv.append(prevIcon);

            headerTable.append(prevDiv);

            var prev2YearDiv = $(document.createElement('th'));
            prev2YearDiv.addClass('year-title year-neighbor2 hidden-sm hidden-xs');
            prev2YearDiv.text(this.options.startYear - 2);

            if(this.options.minDate != null && this.options.minDate > new Date(this.options.startYear - 2, 11, 31)) {
                prev2YearDiv.addClass('disabled');
            }

            headerTable.append(prev2YearDiv);

            var prevYearDiv = $(document.createElement('th'));
            prevYearDiv.addClass('year-title year-neighbor hidden-xs');
            prevYearDiv.text(this.options.startYear - 1);

            if(this.options.minDate != null && this.options.minDate > new Date(this.options.startYear - 1, 11, 31)) {
                prevYearDiv.addClass('disabled');
            }

            headerTable.append(prevYearDiv);

            var yearDiv = $(document.createElement('th'));
            yearDiv.addClass('year-title');
            yearDiv.text(this.options.startYear);

            headerTable.append(yearDiv);

            var nextYearDiv = $(document.createElement('th'));
            nextYearDiv.addClass('year-title year-neighbor hidden-xs');
            nextYearDiv.text(this.options.startYear + 1);

            if(this.options.maxDate != null && this.options.maxDate < new Date(this.options.startYear + 1, 0, 1)) {
                nextYearDiv.addClass('disabled');
            }

            headerTable.append(nextYearDiv);

            var next2YearDiv = $(document.createElement('th'));
            next2YearDiv.addClass('year-title year-neighbor2 hidden-sm hidden-xs');
            next2YearDiv.text(this.options.startYear + 2);

            if(this.options.maxDate != null && this.options.maxDate < new Date(this.options.startYear + 2, 0, 1)) {
                next2YearDiv.addClass('disabled');
            }

            headerTable.append(next2YearDiv);

            var nextDiv = $(document.createElement('th'));
            nextDiv.addClass('next');

            if(this.options.maxDate != null && this.options.maxDate < new Date(this.options.startYear + 1, 0, 1)) {
                nextDiv.addClass('disabled');
            }

            var nextIcon = $(document.createElement('span'));
            nextIcon.addClass('glyphicon glyphicon-chevron-right');

            nextDiv.append(nextIcon);

            headerTable.append(nextDiv);

            header.append(headerTable);

            this.element.append(header);
        },
        _renderBody: function() {
            var monthsDiv = $(document.createElement('div'));
            monthsDiv.addClass('months-container');

            for(var m = 0; m < 12; m++) {
                /* Container */
                var monthDiv = $(document.createElement('div'));
                monthDiv.addClass('month-container');
                monthDiv.data('month-id', m);

                var firstDate = new Date(this.options.startYear, m, 1);

                var table = $(document.createElement('table'));
                table.addClass('month');

                /* Month header */
                var thead = $(document.createElement('thead'));

                var titleRow = $(document.createElement('tr'));

                var titleCell = $(document.createElement('th'));
                titleCell.addClass('month-title');
                titleCell.attr('colspan', this.options.displayWeekNumber ? 8 : 7);
                titleCell.text(dates[this.options.language].months[m]);

                titleRow.append(titleCell);
                thead.append(titleRow);

                var headerRow = $(document.createElement('tr'));

                if(this.options.displayWeekNumber) {
                    var weekNumberCell = $(document.createElement('th'));
                    weekNumberCell.addClass('week-number');
                    weekNumberCell.text(dates[this.options.language].weekShort);
                    headerRow.append(weekNumberCell);
                }

                var weekStart = this.options.weekStart ? this.options.weekStart : dates[this.options.language].weekStart;
                var d = weekStart;
                do
                {
                    var headerCell = $(document.createElement('th'));
                    headerCell.addClass('day-header');
                    headerCell.text(dates[this.options.language].daysMin[d]);

                    if(this._isHidden(d)) {
                        headerCell.addClass('hidden');
                    }

                    headerRow.append(headerCell);

                    d++;
                    if(d >= 7)
                        d = 0;
                }
                while(d != weekStart)

                thead.append(headerRow);
                table.append(thead);

                /* Days */
                var currentDate = new Date(firstDate.getTime());
                var lastDate = new Date(this.options.startYear, m + 1, 0);

                while(currentDate.getDay() != weekStart)
                {
                    currentDate.setDate(currentDate.getDate() - 1);
                }

                while(currentDate <= lastDate)
                {
                    var row = $(document.createElement('tr'));

                    if(this.options.displayWeekNumber) {
                        var weekNumberCell = $(document.createElement('td'));
                        weekNumberCell.addClass('week-number');
                        weekNumberCell.text(this.getWeekNumber(currentDate));
                        row.append(weekNumberCell);
                    }

                    do
                    {
                        var cell = $(document.createElement('td'));
                        cell.addClass('day');

                        if(this._isHidden(currentDate.getDay())) {
                            cell.addClass('hidden');
                        }

                        if(currentDate < firstDate) {
                            cell.addClass('old');
                        }
                        else if(currentDate > lastDate) {
                            cell.addClass('new');
                        }
                        else {
                            if(this._isDisabled(currentDate)) {
                                cell.addClass('disabled');
                            }

                            var cellContent = $(document.createElement('div'));
                            cellContent.addClass('day-content');
                            cellContent.text(currentDate.getDate());
                            cell.append(cellContent);

                            if(this.options.customDayRenderer) {
                                this.options.customDayRenderer(cellContent, currentDate);
                            }
                        }

                        row.append(cell);

                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    while(currentDate.getDay() != weekStart)

                    table.append(row);
                }

                monthDiv.append(table);

                monthsDiv.append(monthDiv);
            }

            this.element.append(monthsDiv);
        },
        _renderDataSource: function() {
            var _this = this;
            if(this.options.dataSource != null && this.options.dataSource.length > 0) {
                this.element.find('.month-container').each(function() {
                    var month = $(this).data('month-id');

                    var firstDate = new Date(_this.options.startYear, month, 1);
                    var lastDate = new Date(_this.options.startYear, month + 1, 1);

                    if((_this.options.minDate == null || lastDate > _this.options.minDate) && (_this.options.maxDate == null || firstDate <= _this.options.maxDate))
                    {
                        var monthData = [];

                        for(var i = 0; i < _this.options.dataSource.length; i++) {
                            if(!(_this.options.dataSource[i].startDate >= lastDate) || (_this.options.dataSource[i].endDate < firstDate)) {
                                monthData.push(_this.options.dataSource[i]);
                            }
                        }

                        if(monthData.length > 0) {
                            $(this).find('.day-content').each(function() {
                                var currentDate = new Date(_this.options.startYear, month, $(this).text());
                                var nextDate = new Date(_this.options.startYear, month, currentDate.getDate() + 1);

                                var dayData = [];

                                if((_this.options.minDate == null || currentDate >= _this.options.minDate) && (_this.options.maxDate == null || currentDate <= _this.options.maxDate))
                                {
                                    for(var i = 0; i < monthData.length; i++) {
                                        if(monthData[i].startDate < nextDate && monthData[i].endDate >= currentDate) {
                                            dayData.push(monthData[i]);
                                        }
                                    }

                                    if(dayData.length > 0 && (_this.options.displayDisabledDataSource || !_this._isDisabled(currentDate)))
                                    {
                                        _this._renderDataSourceDay($(this), currentDate, dayData);
                                    }
                                }
                            });
                        }
                    }
                });
            }
        },
        _renderDataSourceDay: function(elt, currentDate, events) {
            switch(this.options.style)
            {
                case 'border':
                    var weight = 0;

                    if(events.length == 1) {
                        weight = 4;
                    }
                    else if(events.length <= 3) {
                        weight = 2;
                    }
                    else {
                        elt.parent().css('box-shadow', 'inset 0 -4px 0 0 black');
                    }

                    if(weight > 0)
                    {
                        var boxShadow = '';

                        for (var i = 0; i < events.length; i++)
                        {
                            if(boxShadow != '') {
                                boxShadow += ",";
                            }

                            boxShadow += 'inset 0 -' + (parseInt(i) + 1) * weight + 'px 0 0 ' + events[i].color;
                        }

                        elt.parent().css('box-shadow', boxShadow);
                    }
                    break;

                case 'background':
                    elt.parent().css('background-color', events[events.length - 1].color);

                    var currentTime = currentDate.getTime();

                    if(events[events.length - 1].startDate.getTime() == currentTime)
                    {
                        elt.parent().addClass('day-start');

                        if(events[events.length - 1].startHalfDay || this.options.alwaysHalfDay) {
                            elt.parent().addClass('day-half');

                            // Find color for other half
                            var otherColor = 'transparent';
                            for(var i = events.length - 2; i >= 0; i--) {
                                if(events[i].startDate.getTime() != currentTime || (!events[i].startHalfDay && !this.options.alwaysHalfDay)) {
                                    otherColor = events[i].color;
                                    break;
                                }
                            }

                            elt.parent().css('background', 'linear-gradient(-45deg, ' + events[events.length - 1].color + ', ' + events[events.length - 1].color + ' 49%, ' + otherColor + ' 51%, ' + otherColor + ')');
                        }
                        else if(this.options.roundRangeLimits) {
                            elt.parent().addClass('round-left');
                        }
                    }
                    else if(events[events.length - 1].endDate.getTime() == currentTime)
                    {
                        elt.parent().addClass('day-end');

                        if(events[events.length - 1].endHalfDay || this.options.alwaysHalfDay) {
                            elt.parent().addClass('day-half');

                            // Find color for other half
                            var otherColor = 'transparent';
                            for(var i = events.length - 2; i >= 0; i--) {
                                if(events[i].endDate.getTime() != currentTime || (!events[i].endHalfDay &&  !this.options.alwaysHalfDay)) {
                                    otherColor = events[i].color;
                                    break;
                                }
                            }

                            elt.parent().css('background', 'linear-gradient(135deg, ' + events[events.length - 1].color + ', ' + events[events.length - 1].color + ' 49%, ' + otherColor + ' 51%, ' + otherColor + ')');
                        }
                        else if(this.options.roundRangeLimits) {
                            elt.parent().addClass('round-right');
                        }
                    }
                    break;

                case 'custom':
                    if(this.options.customDataSourceRenderer) {
                        this.options.customDataSourceRenderer.call(this, elt, currentDate, events);
                    }
                    break;
            }
        },
        _applyEvents: function () {
            var _this = this;

            /* Header buttons */
            this.element.find('.year-neighbor, .year-neighbor2').click(function() {
                if(!$(this).hasClass('disabled')) {
                    _this.setYear(parseInt($(this).text()));
                }
            });

            this.element.find('.calendar-header .prev').click(function() {
                if(!$(this).hasClass('disabled')) {
                    _this.element.find('.months-container').animate({'margin-left':'100%'},100, function() {
                        _this.element.find('.months-container').css('visibility', 'hidden');
                        _this.element.find('.months-container').css('margin-left', '0');

                        setTimeout(function() {
                            _this.setYear(_this.options.startYear - 1);
                        }, 50);
                    });
                }
            });

            this.element.find('.calendar-header .next').click(function() {
                if(!$(this).hasClass('disabled')) {
                    _this.element.find('.months-container').animate({'margin-left':'-100%'},100, function() {
                        _this.element.find('.months-container').css('visibility', 'hidden');
                        _this.element.find('.months-container').css('margin-left', '0');

                        setTimeout(function() {
                            _this.setYear(_this.options.startYear + 1);
                        }, 50);
                    });
                }
            });

            var cells = this.element.find('.day:not(.old, .new, .disabled)');

            /* Click on date */
            cells.click(function(e) {
                e.stopPropagation();
                var date = _this._getDate($(this));
                _this._triggerEvent('clickDay', {
                    element: $(this),
                    which: e.which,
                    date: date,
                    events: _this.getEvents(date)
                });
            });

            /* Click right on date */

            cells.bind('contextmenu', function(e) {
                if(_this.options.enableContextMenu)
                {
                    e.preventDefault();
                    if(_this.options.contextMenuItems.length > 0)
                    {
                        _this._openContextMenu($(this));
                    }
                }

                var date = _this._getDate($(this));
                _this._triggerEvent('dayContextMenu', {
                    element: $(this),
                    date: date,
                    events: _this.getEvents(date)
                });
            });

            /* Range selection */
            if(this.options.enableRangeSelection) {
                cells.mousedown(function (e) {
                    if(e.which == 1) {
                        var currentDate = _this._getDate($(this));

                        if(_this.options.allowOverlap || _this.getEvents(currentDate).length == 0)
                        {
                            _this._mouseDown = true;
                            _this._rangeStart = _this._rangeEnd = currentDate;
                            _this._refreshRange();
                        }
                    }
                });

                cells.mouseenter(function (e) {
                    if (_this._mouseDown) {
                        var currentDate = _this._getDate($(this));

                        if(!_this.options.allowOverlap)
                        {
                            var newDate =  new Date(_this._rangeStart.getTime());

                            if(newDate < currentDate) {
                                var nextDate = new Date(newDate.getFullYear(), newDate.getMonth(), newDate.getDate() + 1);
                                while(newDate < currentDate) {
                                    if(_this.getEvents(nextDate).length > 0)
                                    {
                                        break;
                                    }

                                    newDate.setDate(newDate.getDate() + 1);
                                    nextDate.setDate(nextDate.getDate() + 1);
                                }
                            }
                            else {
                                var nextDate = new Date(newDate.getFullYear(), newDate.getMonth(), newDate.getDate() - 1);
                                while(newDate > currentDate) {
                                    if(_this.getEvents(nextDate).length > 0)
                                    {
                                        break;
                                    }

                                    newDate.setDate(newDate.getDate() - 1);
                                    nextDate.setDate(nextDate.getDate() - 1);
                                }
                            }

                            currentDate = newDate;
                        }

                        var oldValue = _this._rangeEnd;
                        _this._rangeEnd = currentDate;

                        if (oldValue.getTime() != _this._rangeEnd.getTime()) {
                            _this._refreshRange();
                        }
                    }
                });

                $(window).mouseup(function (e) {
                    if (_this._mouseDown) {
                        _this._mouseDown = false;
                        _this._refreshRange();

                        var minDate = _this._rangeStart < _this._rangeEnd ? _this._rangeStart : _this._rangeEnd;
                        var maxDate = _this._rangeEnd > _this._rangeStart ? _this._rangeEnd : _this._rangeStart;

                        _this._triggerEvent('selectRange', {
                            startDate: minDate,
                            endDate: maxDate,
                            events: _this.getEventsOnRange(minDate, new Date(maxDate.getFullYear(), maxDate.getMonth(), maxDate.getDate() + 1))
                        });
                    }
                });
            }

            /* Hover date */
            cells.mouseenter(function(e) {
                if(!_this._mouseDown)
                {
                    var date = _this._getDate($(this));
                    _this._triggerEvent('mouseOnDay', {
                        element: $(this),
                        date: date,
                        events: _this.getEvents(date)
                    });
                }
            });

            cells.mouseleave(function(e) {
                var date = _this._getDate($(this));
                _this._triggerEvent('mouseOutDay', {
                    element: $(this),
                    date: date,
                    events: _this.getEvents(date)
                });
            });

            /* Responsive management */

            setInterval(function() {
                var calendarSize = $(_this.element).width();
                var monthSize = $(_this.element).find('.month').first().width() + 10;
                var monthContainerClass = 'month-container';

                if(monthSize * 6 < calendarSize) {
                    monthContainerClass += ' col-xs-2';
                }
                else if(monthSize * 4 < calendarSize) {
                    monthContainerClass += ' col-xs-3';
                }
                else if(monthSize * 3 < calendarSize) {
                    monthContainerClass += ' col-xs-4';
                }
                else if(monthSize * 2 < calendarSize) {
                    monthContainerClass += ' col-xs-6';
                }
                else {
                    monthContainerClass += ' col-xs-12';
                }

                $(_this.element).find('.month-container').attr('class', monthContainerClass);
            }, 300);
        },
        _refreshRange: function () {
            var _this = this;

            this.element.find('td.day.range').removeClass('range')
            this.element.find('td.day.range-start').removeClass('range-start');
            this.element.find('td.day.range-end').removeClass('range-end');

            if (this._mouseDown) {
                var beforeRange = true;
                var afterRange = false;
                var minDate = _this._rangeStart < _this._rangeEnd ? _this._rangeStart : _this._rangeEnd;
                var maxDate = _this._rangeEnd > _this._rangeStart ? _this._rangeEnd : _this._rangeStart;

                this.element.find('.month-container').each(function () {
                    var monthId = $(this).data('month-id');
                    if (minDate.getMonth() <= monthId && maxDate.getMonth() >= monthId) {
                        $(this).find('td.day:not(.old, .new)').each(function () {
                            var date = _this._getDate($(this));
                            if (date >= minDate && date <= maxDate) {
                                $(this).addClass('range');

                                if (date.getTime() == minDate.getTime()) {
                                    $(this).addClass('range-start');
                                }

                                if (date.getTime() == maxDate.getTime()) {
                                    $(this).addClass('range-end');
                                }
                            }
                        });
                    }
                });
            }
        },
        _openContextMenu: function(elt) {
            var contextMenu = $('.calendar-context-menu');

            if(contextMenu.length > 0) {
                contextMenu.hide();
                contextMenu.empty();
            }
            else {
                contextMenu = $(document.createElement('div'));
                contextMenu.addClass('calendar-context-menu');
                $('body').append(contextMenu);
            }

            var date = this._getDate(elt);
            var events = this.getEvents(date);

            for(var i = 0; i < events.length; i++) {
                var eventItem = $(document.createElement('div'));
                eventItem.addClass('item');
                eventItem.css('border-left', '4px solid ' + events[i].color);

                var eventItemContent = $(document.createElement('div'));
                eventItemContent.addClass('content');
                eventItemContent.text(events[i].name);

                eventItem.append(eventItemContent);

                var icon = $(document.createElement('span'));
                icon.addClass('glyphicon glyphicon-chevron-right');

                eventItem.append(icon);

                this._renderContextMenuItems(eventItem, this.options.contextMenuItems, events[i]);

                contextMenu.append(eventItem);
            }

            if(contextMenu.children().length > 0)
            {
                contextMenu.css('left', elt.offset().left + 25 + 'px');
                contextMenu.css('top', elt.offset().top + 25 + 'px');
                contextMenu.show();

                $(window).one('mouseup', function() {
                    contextMenu.hide();
                });
            }
        },
        _renderContextMenuItems: function(parent, items, evt) {
            var subMenu = $(document.createElement('div'));
            subMenu.addClass('submenu');

            for(var i = 0; i < items.length; i++) {
                if(!items[i].visible || items[i].visible(evt)) {
                    var menuItem = $(document.createElement('div'));
                    menuItem.addClass('item');

                    var menuItemContent = $(document.createElement('div'));
                    menuItemContent.addClass('content');
                    menuItemContent.text(items[i].text);

                    menuItem.append(menuItemContent);

                    if(items[i].click) {
                        (function(index) {
                            menuItem.click(function() {
                                items[index].click(evt);
                            });
                        })(i);
                    }

                    var icon = $(document.createElement('span'));
                    icon.addClass('glyphicon glyphicon-chevron-right');

                    menuItem.append(icon);

                    if(items[i].items && items[i].items.length > 0) {
                        this._renderContextMenuItems(menuItem, items[i].items, evt);
                    }

                    subMenu.append(menuItem);
                }
            }

            if(subMenu.children().length > 0)
            {
                parent.append(subMenu);
            }
        },
        _getColor: function(colorString) {
            var div = $('<div />');
            div.css('color', colorString);

        },
        _getDate: function(elt) {
            var day = elt.children('.day-content').text();
            var month = elt.closest('.month-container').data('month-id');
            var year = this.options.startYear;

            return new Date(year, month, day);
        },
        _triggerEvent: function(eventName, parameters) {
            var event = $.Event(eventName);

            for(var i in parameters) {
                event[i] = parameters[i];
            }

            this.element.trigger(event);

            return event;
        },
        _isDisabled: function(date) {
            if((this.options.minDate != null && date < this.options.minDate) || (this.options.maxDate != null && date > this.options.maxDate))
            {
                return true;
            }

            if(this.options.disabledWeekDays.length > 0) {
                for(var d = 0; d < this.options.disabledWeekDays.length; d++){
                    if(date.getDay() == this.options.disabledWeekDays[d]) {
                        return true;
                    }
                }
            }

            if(this.options.disabledDays.length > 0) {
                for(var d = 0; d < this.options.disabledDays.length; d++){
                    if(date.getTime() == this.options.disabledDays[d].getTime()) {
                        return true;
                    }
                }
            }

            return false;
        },
        _isHidden: function(day) {
            if(this.options.hiddenWeekDays.length > 0) {
                for(var d = 0; d < this.options.hiddenWeekDays.length; d++) {
                    if(day == this.options.hiddenWeekDays[d]) {
                        return true;
                    }
                }
            }

            return false;
        },
        getWeekNumber: function(date) {
            var tempDate = new Date(date.getTime());
            tempDate.setHours(0, 0, 0, 0);
            tempDate.setDate(tempDate.getDate() + 3 - (tempDate.getDay() + 6) % 7);
            var week1 = new Date(tempDate.getFullYear(), 0, 4);
            return 1 + Math.round(((tempDate.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
        },
        getEvents: function(date) {
            return this.getEventsOnRange(date, new Date(date.getFullYear(), date.getMonth(), date.getDate() + 1));
        },
        getEventsOnRange: function(startDate, endDate) {
            var events = [];

            if(this.options.dataSource && startDate && endDate) {
                for(var i = 0; i < this.options.dataSource.length; i++) {
                    if(this.options.dataSource[i].startDate < endDate && this.options.dataSource[i].endDate >= startDate) {
                        events.push(this.options.dataSource[i]);
                    }
                }
            }

            return events;
        },
        getYear: function() {
            return this.options.startYear;
        },
        setYear: function(year) {
            var parsedYear = parseInt(year);
            if(!isNaN(parsedYear)) {
                this.options.startYear = parsedYear;

                this.element.empty();

                if(this.options.displayHeader) {
                    this._renderHeader();
                }

                var eventResult = this._triggerEvent('yearChanged', { currentYear: this.options.startYear, preventRendering: false });

                if(!eventResult.preventRendering) {
                    this.render();
                }
            }
        },
        getMinDate: function() {
            return this.options.minDate;
        },
        setMinDate: function(date, preventRendering) {
            if(date instanceof Date) {
                this.options.minDate = date;

                if(!preventRendering) {
                    this.render();
                }
            }
        },
        getMaxDate: function() {
            return this.options.maxDate;
        },
        setMaxDate: function(date, preventRendering) {
            if(date instanceof Date) {
                this.options.maxDate = date;

                if(!preventRendering) {
                    this.render();
                }
            }
        },
        getStyle: function() {
            return this.options.style;
        },
        setStyle: function(style, preventRendering) {
            this.options.style = style == 'background' || style == 'border' || style == 'custom' ? style : 'border';

            if(!preventRendering) {
                this.render();
            }
        },
        getAllowOverlap: function() {
            return this.options.allowOverlap;
        },
        setAllowOverlap: function(allowOverlap) {
            this.options.allowOverlap = allowOverlap;
        },
        getDisplayWeekNumber: function() {
            return this.options.displayWeekNumber;
        },
        setDisplayWeekNumber: function(displayWeekNumber, preventRendering) {
            this.options.displayWeekNumber = displayWeekNumber;

            if(!preventRendering) {
                this.render();
            }
        },
        getDisplayHeader: function() {
            return this.options.displayHeader;
        },
        setDisplayHeader: function(displayHeader, preventRendering) {
            this.options.displayHeader = displayHeader;

            if(!preventRendering) {
                this.render();
            }
        },
        getDisplayDisabledDataSource: function() {
            return this.options.displayDisabledDataSource;
        },
        setDisplayDisabledDataSource: function(displayDisabledDataSource, preventRendering) {
            this.options.displayDisabledDataSource = displayDisabledDataSource;

            if(!preventRendering) {
                this.render();
            }
        },
        getAlwaysHalfDay: function() {
            return this.options.alwaysHalfDay;
        },
        setAlwaysHalfDay: function(alwaysHalfDay, preventRendering) {
            this.options.alwaysHalfDay = alwaysHalfDay;

            if(!preventRendering) {
                this.render();
            }
        },
        getEnableRangeSelection: function() {
            return this.options.enableRangeSelection;
        },
        setEnableRangeSelection: function(enableRangeSelection, preventRendering) {
            this.options.enableRangeSelection = enableRangeSelection;

            if(!preventRendering) {
                this.render();
            }
        },
        getDisabledDays: function() {
            return this.options.disabledDays;
        },
        setDisabledDays: function(disabledDays, preventRendering) {
            this.options.disabledDays = disabledDays instanceof Array ? disabledDays : [];

            if(!preventRendering) {
                this.render();
            }
        },
        getDisabledWeekDays: function() {
            return this.options.disabledWeekDays;
        },
        setDisabledWeekDays: function(disabledWeekDays, preventRendering) {
            this.options.disabledWeekDays = disabledWeekDays instanceof Array ? disabledWeekDays : [];

            if(!preventRendering) {
                this.render();
            }
        },
        getHiddenWeekDays: function() {
            return this.options.hiddenWeekDays;
        },
        setHiddenWeekDays: function(hiddenWeekDays, preventRendering) {
            this.options.hiddenWeekDays = hiddenWeekDays instanceof Array ? hiddenWeekDays : [];

            if(!preventRendering) {
                this.render();
            }
        },
        getRoundRangeLimits: function() {
            return this.options.roundRangeLimits;
        },
        setRoundRangeLimits: function(roundRangeLimits, preventRendering) {
            this.options.roundRangeLimits = roundRangeLimits;

            if(!preventRendering) {
                this.render();
            }
        },
        getEnableContextMenu: function() {
            return this.options.enableContextMenu;
        },
        setEnableContextMenu: function(enableContextMenu, preventRendering) {
            this.options.enableContextMenu = enableContextMenu;

            if(!preventRendering) {
                this.render();
            }
        },
        getContextMenuItems: function() {
            return this.options.contextMenuItems;
        },
        setContextMenuItems: function(contextMenuItems, preventRendering) {
            this.options.contextMenuItems = contextMenuItems instanceof Array ? contextMenuItems : [];

            if(!preventRendering) {
                this.render();
            }
        },
        getCustomDayRenderer: function() {
            return this.options.customDayRenderer;
        },
        setCustomDayRenderer: function(customDayRenderer, preventRendering) {
            this.options.customDayRenderer = $.isFunction(customDayRenderer) ? customDayRenderer : null;

            if(!preventRendering) {
                this.render();
            }
        },
        getCustomDataSourceRenderer: function() {
            return this.options.customDataSourceRenderer;
        },
        setCustomDataSourceRenderer: function(customDataSourceRenderer, preventRendering) {
            this.options.customDataSourceRenderer = $.isFunction(customDataSourceRenderer) ? customDataSourceRenderer : null;

            if(!preventRendering) {
                this.render();
            }
        },
        getLanguage: function() {
            return this.options.language;
        },
        setLanguage: function(language, preventRendering) {
            if(language != null && dates[language] != null) {
                this.options.language = language;

                if(!preventRendering) {
                    this.render();
                }
            }
        },
        getDataSource: function() {
            return this.options.dataSource;
        },
        setDataSource: function(dataSource, preventRendering) {
            this.options.dataSource = dataSource instanceof Array ? dataSource : [];
            this._initializeDatasourceColors();

            if(!preventRendering) {
                this.render();
            }
        },
        getWeekStart: function() {
            return this.options.weekStart ? this.options.weekStart : dates[this.options.language].weekStart;
        },
        setWeekStart: function(weekStart, preventRendering) {
            this.options.weekStart = !isNaN(parseInt(weekStart)) ? parseInt(weekStart) : null;

            if(!preventRendering) {
                this.render();
            }
        },
        addEvent: function(evt, preventRendering) {
            this.options.dataSource.push(evt);

            if(!preventRendering) {
                this.render();
            }
        }
    }

    $.fn.calendar = function (options) {
        var calendar = new Calendar($(this) ,options);
        $(this).data('calendar', calendar);
        return calendar;
    }

    /* Events binding management */
    $.fn.yearChanged = function(fct) { $(this).bind('yearChanged', fct); }
    $.fn.renderEnd = function(fct) { $(this).bind('renderEnd', fct); }
    $.fn.clickDay = function(fct) { $(this).bind('clickDay', fct); }
    $.fn.dayContextMenu = function(fct) { $(this).bind('dayContextMenu', fct); }
    $.fn.selectRange = function(fct) { $(this).bind('selectRange', fct); }
    $.fn.mouseOnDay = function(fct) { $(this).bind('mouseOnDay', fct); }
    $.fn.mouseOutDay = function(fct) { $(this).bind('mouseOutDay', fct); }

    var dates = $.fn.calendar.dates = {
        en: {
            days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
            daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
            daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"],
            months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
            monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            weekShort: 'W',
            weekStart:0
        }
    };

    var colors = $.fn.calendar.colors = ['#2C8FC9', '#9CB703', '#F5BB00', '#FF4A32', '#B56CE2', '#45A597'];

    $(function(){
        $('[data-provide="calendar"]').each(function() {
            $(this).calendar();
        });
    });
}(window.jQuery));