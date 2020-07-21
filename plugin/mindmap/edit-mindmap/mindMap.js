var connect = require('connect');
var path = require('path');

connect.static.mime.define({
    'text/cache-manifest' : ['mf', 'manifest']
});

var app = connect()
    .use( connect.logger('dev') )
    .use( connect.static('client/') )
    .listen(8000);