import moment from 'moment';

const date = function(value) {
  return moment(value).isValid();
};

export { date };
