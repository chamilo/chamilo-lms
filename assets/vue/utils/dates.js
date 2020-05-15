import moment from 'moment';

const formatDateTime = function(date) {
  if (!date) return null;

  return moment(date).format('DD/MM/YYYY');
};

export { formatDateTime };
