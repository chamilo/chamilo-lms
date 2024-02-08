const { DateTime } = require("luxon");

const formatDateTime = function(date) {
  if (!date) return null;

  return DateTime(date).format('DD/MM/YYYY');
};

const formatDateTimeFromISO = function(dateStr) {
  if (!dateStr) return '';

  return DateTime.fromISO(dateStr).toFormat('dd/LL/yyyy HH:mm');
};

export { formatDateTime, formatDateTimeFromISO };
