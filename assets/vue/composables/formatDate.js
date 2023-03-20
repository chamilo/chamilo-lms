const { DateTime } = require('luxon');

export function useAbbreviatedDatetime (datetime) {
    return DateTime.fromISO(datetime).toLocaleString(
        {
            ...DateTime.DATETIME_MED,
            month: 'long'
        }
    );
}

export function useRelativeDatetime (datetime) {
    return DateTime.fromISO(datetime).toRelative();
}
