const { DateTime } = require("luxon");

const formatDateTime = function(date) {
  if (!date) return null;

  return DateTime(date).format('DD/MM/YYYY');
};

export { formatDateTime };
