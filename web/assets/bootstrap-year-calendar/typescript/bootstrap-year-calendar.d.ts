// Type definitions for bootstrap-year-calendar v1.1.0
// Project: https://github.com/Paul-DS/bootstrap-year-calendar
// Definitions by: Paul David-Sivelle
// Definitions: https://github.com/borisyankov/DefinitelyTyped

/// <reference path="../jquery/jquery.d.ts"/>

/**
 * Represent a context menu item for the calendar.
 */
interface CalendarContextMenuItem<T> {
    /**
     * The text of the menu item.
     */
    text: string;

    /**
     * A function to be called when the item is clicked.
     */
    click?: (event: T) => void;

    /**
     * The list of sub menu items.
     */
    submenu?: CalendarContextMenuItem<T>[];
}

/**
 * Represent an element to display in the calendar.
 */
interface CalendarDataSourceElement {
	/**
     * The name of the element. Used for context menu or specific events.
     */
    name?: string;

    /**
     * The color of the element. This property will be computed automatically if not defined.
     */
    color?: string;
	
    /**
     * The date of the beginning of the element range.
     */
    startDate: Date;

    /**
     * The date of the end of the element range.
     */
    endDate: Date;
	
	/**
     * Indicates whether only the half of start day of the element range should be rendered.
     */
    startHalfDay?: boolean;

    /**
     * Indicates whether only the half of last day of the element range should be rendered.
     */
    endHalfDay?: boolean;
}

/**
 * Options used for calendar customization.
 */
interface CalendarOptions<T extends CalendarDataSourceElement> {

    /**
     * Specifies whether the user can select a range which overlapping an other element present in the datasource.
     */
    allowOverlap?: boolean;

	/**
     * Specifies whether the beginning and the end of each range should be displayed as half selected day.
     */
    alwaysHalfDay?: boolean;
	
    /**
     * Specifies the items of the default context menu.
     */
    contextMenuItems?: CalendarContextMenuItem<T>[];
	
	/**
     * Specify a custom renderer for days.
	 * This function is called during render for each day.
     */
    customDayRenderer?: (element: JQuery, currentDate: Date) => void;
	
	/**
     * Specify a custom renderer for data source. Works only with the style set to "custom".
	 * This function is called during render for each day containing at least one event.
     */
    customDataSourceRenderer?: (element: JQuery, currentDate: Date, events: T[]) => void;

    /**
     * The elements that must be displayed on the calendar.
     */
    dataSource?: T[];

    /**
     * The days that must be displayed as disabled.
     */
    disableDays?: Date[];

    /**
     * Specifies whether the weeks number are displayed.
     */
    displayWeekNumber?: boolean;

    /**
     * Specifies whether the default context menu must be displayed when right clicking on a day.
     */
    enableContextMenu?: boolean;

    /**
     * Specifies whether the range selection is enabled.
     */
    enableRangeSelection?: boolean;

    /**
     * The language/culture used for calendar rendering.
     */
    language?: string;

    /**
     * The date until which days are enabled.
     */
    maxDate?: Date;

    /**
     * The date from which days are enabled.
     */
    minDate?: Date;
	
	/**
     * Specifies whether the beginning and the end of each range should be displayed as rounded cells.
     */
    roundRangeLimits?: boolean;

    /**
     * The year on which the calendar should be opened.
     */
    startYear?: number;

    /**
     * Specifies the style used for displaying datasource ("background", "border" or "custom").
     */
    style?: string;
	
    /**
     * Function fired when a day is clicked.
     */
    clickDay?: (e: CalendarClickEventObject<T>) => void;

    /**
     * Function fired when a day is right clicked.
     */
    dayContextMenu?: (e: CalendarDayEventObject<T>) => void;

    /**
     * Function fired when the mouse enter on a day.
     */
    mouseOnDay?: (e: CalendarDayEventObject<T>) => void;

    /**
     * Function fired when the mouse leaves a day.
     */
    mouseOutDay?: (e: CalendarDayEventObject<T>) => void;

    /**
     * Function fired when the calendar rendering is ended.
     */
    renderEnd?: (e: CalendarRenderEndEventObject) => void;

    /**
     * Function fired when a date range is selected.
     */
    selectRange?: (e: CalendarRangeEventObject) => void;
}

interface CalendarDayEventObject<T extends CalendarDataSourceElement> {
    /**
     * The element that contain the fired day.
     */
    element: JQuery;

    /**
     * The fired date.
     */
    date: Date;
	
	/**
     * The data source elements present on the fired day.
     */
    events: T[];
}

interface CalendarClickEventObject<T extends CalendarDataSourceElement> extends CalendarDayEventObject<T> {
    /**
     * The clicked button.
     */
    which: number;
}

interface CalendarRenderEndEventObject {
    /**
     * The rendered year.
     */
    currentYear: number;
}

interface CalendarRangeEventObject {
    /**
     * The beginning of the selected range.
     */
    startDate: Date;

    /**
     * The end of the selected range.
     */
    endDate: Date;
}

/**
 * Calendar instance.
 */
interface Calendar<T extends CalendarDataSourceElement> {
    /**
     * Add a new element to the data source. This method causes a refresh of the calendar.
     * 
     * @param element The element to add.
     */
    addEvent(element: T): void;

    /**
     * Gets a value indicating whether the user can select a range which overlapping an other element present in the datasource.
     */
    getAllowOverlap(): boolean;
	
	/**
     * Gets a value indicating whether the beginning and the end of each range should be displayed as half selected day.
     */
    getAlwaysHalfDay(): boolean;

    /**
     * Gets the context menu items.
     */
    getContextMenuItems(): CalendarContextMenuItem<T>[];
	
	/**
     * Gets the custom day renderer.
     */
    getCustomDayRenderer(): (element: JQuery, currentDate: Date) => void;
	
	/**
     * Gets the custom data source renderer.
     */
    getCustomDataSourceRenderer(): (element: JQuery, currentDate: Date, events: T[]) => void;

    /**
     * Gets the current data source.
     */
    getDataSource(): T[];

    /**
     * Gets the disabled days.
     */
    getDisableDays(): Date[];

    /**
     * Gets a value indicating whether the weeks number are displayed.
     */
    getDisplayWeekNumber(): boolean;

    /**
     * Gets a value indicating whether the default context menu must be displayed when right clicking on a day.
     */
    getEnableContextMenu(): boolean;

    /**
     * Gets a value indicating whether the user can make range selection.
     */
    getEnableRangeSelection(): boolean;

    /**
     * Gets the data source elements for a specified day.
     *
     * @param date The specified day.
     */
    getEvents(Date: Date): T[];

    /**
     * Gets the language used for calendar rendering.
     */
    getLanguage(): string;

    /**
     * Gets the maximum date of the calendar.
     */
    getMaxDate(): Date;

    /**
     * Gets the minimum date of the calendar.
     */
    getMinDate(): Date;
	
	/**
     * Gets a value indicating whether the beginning and the end of each range should be displayed as rounded cells.
     */
    getRoundRangeLimits(): void;

    /**
     * Gets the current style used for displaying data source.
     */
    getStyle(): string;

    /**
     * Gets the week number for a specified date.
     *
     * @param date The specified date.
     */
    getWeekNumber(Date: Date): number;

    /**
     * Gets the year displayed on the calendar.
     */
    getYear(): number;
	
    /**
     * Sets a value indicating whether the user can select a range which overlapping an other element present in the datasource.
     *
     * @param allowOverlap Indicates whether the user can select a range which overlapping an other element present in the datasource.
     */
    setAllowOverlap(allowOverlap: boolean): void;

	/**
     * Sets a value indicating whether the beginning and the end of each range should be displayed as half selected day.
	 * This method causes a refresh of the calendar.
     *
     * @param alwaysHalfDay Indicates whether the beginning and the end of each range should be displayed as half selected day.
     */
    setAlwaysHalfDay(alwaysHalfDay: boolean): void;
	
    /**
     * Sets new context menu items. This method causes a refresh of the calendar.
     *
     * @param contextMenuItems The new context menu items.
     */
    setContextMenuItems(contextMenuItems: CalendarContextMenuItem<T>[]): void;
	
	/**
     * Sets the custom day renderer.
	 *
	  * @param handler The function used to render the days. This function is called during render for each day.
     */
    setCustomDayRenderer(handler: (element: JQuery, currentDate: Date) => void): void;
	
	/**
     * Sets the custom data source renderer. Works only with the style set to "custom".
	 *
	  * @param handler The function used to render the data source. This function is called during render for each day containing at least one event.
     */
    setCustomDataSourceRenderer(handler: (element: JQuery, currentDate: Date, events: T[]) => void): void;

    /**
     * Sets a new data source. This method causes a refresh of the calendar.
     *
     * @param dataSource The new data source.
     */
    setDataSource(dataSource: T[]): void;

    /**
     * Sets the disabled days. This method causes a refresh of the calendar.
     *
     * @param disableDays The disabled days to set.
     */
    setDisableDays(disableDays: Date[]): void;

    /**
     * Sets a value indicating whether the weeks number are displayed. This method causes a refresh of the calendar.
     *
     * @param  displayWeekNumber Indicates whether the weeks number are displayed.
     */
    setDisplayWeekNumber(displayWeekNumber: boolean): void;

    /**
     * Sets a value indicating whether the default context menu must be displayed when right clicking on a day. 
     * This method causes a refresh of the calendar.
     * 
     * @param enableContextMenu Indicates whether the default context menu must be displayed when right clicking on a day.
     */
    setEnableContextMenu(enableContextMenu: boolean): void;

    /**
     * Sets a value indicating whether the user can make range selection. This method causes a refresh of the calendar.
     *
     * @param enableRangeSelection Indicates whether the user can make range selection.
     */
    setEnableRangeSelection(enableRangeSelection: boolean): void;

    /**
     * Sets the language used for calendar rendering. This method causes a refresh of the calendar.
     *
     * @param language The language to use for calendar redering.
     */
    setLanguage(language: string): void;

    /**
     * Sets the maximum date of the calendar. This method causes a refresh of the calendar.
     *
     * @param maxDate The maximum date to set.
     */
    setMaxDate(maxDate: Date): void;

    /**
     * Sets the minimum date of the calendar. This method causes a refresh of the calendar.
     *
     * @param minDate The minimum date to set.
     */
    setMinDate(minDate: Date): void;
	
	/**
     * Sets a value indicating whether the beginning and the end of each range should be displayed as rounded cells.
	 * This method causes a refresh of the calendar.
     *
     * @param roundRangeLimits Indicates whether the beginning and the end of each range should be displayed as rounded cells. 
     */
    setRoundRangeLimits(roundRangeLimits: boolean): void;

    /**
     * Sets the style to use for displaying data source. This method causes a refresh of the calendar.
     *
     * @param style The style to use for displaying data source ("background", "border" or "custom").
     */
    setStyle(style: string): void;

    /**
     * Sets the year displayed on the calendar.
     *
     * @param year The year to displayed on the calendar.
     */
    setYear(year: number): void;
}

/**
 * Basic calendar instance.
 */
interface BaseCalendar extends Calendar<CalendarDataSourceElement> {

}

interface JQuery {

    /**
     * Create a new calendar.
     */
    calendar(): BaseCalendar;

    /**
     * Create a new calendar.
     *
     * @param options The customization options.
     */
    calendar(options: CalendarOptions<CalendarDataSourceElement>): BaseCalendar;

    /**
     * Create a new calendar.
     *
     * @param options The customization options.
     */
    calendar<T extends CalendarDataSourceElement>(options: CalendarOptions<T>): Calendar<T>;
	

    /**
     * Function fired when a day is clicked (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    clickDay(handler: (e: CalendarClickEventObject<CalendarDataSourceElement>) => void): JQuery;

    /**
     * Function fired when a day is clicked (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    clickDay<T extends CalendarDataSourceElement>(handler: (e: CalendarClickEventObject<T>) => void): JQuery;

    /**
     * Function fired when a day is right clicked (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    dayContextMenu(handler: (e: CalendarDayEventObject<CalendarDataSourceElement>) => void): JQuery;

    /**
     * Function fired when a day is right clicked (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    dayContextMenu<T extends CalendarDataSourceElement>(handler: (e: CalendarDayEventObject<T>) => void): JQuery;

    /**
     * Function fired when the mouse enter on a day (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    mouseOnDay(handler: (e: CalendarDayEventObject<CalendarDataSourceElement>) => void): JQuery;

    /**
     * Function fired when the mouse enter on a day (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    mouseOnDay<T extends CalendarDataSourceElement>(handler: (e: CalendarDayEventObject<T>) => void): JQuery;

    /**
     * Function fired when the mouse leaves a day (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    mouseOutDay(handler: (e: CalendarDayEventObject<CalendarDataSourceElement>) => void): JQuery;

    /**
     * Function fired when the mouse leaves a day (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    mouseOutDay<T extends CalendarDataSourceElement>(handler: (e: CalendarDayEventObject<T>) => void): JQuery;

    /**
     * Function fired when the calendar rendering is ended (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    renderEnd(handler: (e: CalendarRenderEndEventObject) => void): JQuery;

    /**
     * Function fired when a date range is selected (for bootstrap-year-calendar only).
     *
     * @param handler A function to execute each time the event is triggered.
     */
    selectRange(handler: (e: CalendarRangeEventObject) => void): JQuery;
}