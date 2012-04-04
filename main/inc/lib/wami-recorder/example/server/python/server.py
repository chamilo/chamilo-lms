# Run from the commandline:
#
# python server.py
# POST audio to   http://localhost:9000
# GET audio from  http://localhost:9000
#
# A simple server to collect audio using python.  To be more secure,
# you might want to check the file names and place size restrictions
# on the incoming data.

import cgi
from BaseHTTPServer import BaseHTTPRequestHandler, HTTPServer

class WamiHandler(BaseHTTPRequestHandler):
    dirname = "/tmp/"
    
    def do_GET(self):
        f = open(self.get_name())
        self.send_response(200)
        self.send_header('content-type','audio/x-wav')
        self.end_headers()
        self.wfile.write(f.read())
        f.close()

    def do_POST(self):
        f = open(self.get_name(), "wb")
        # Note that python's HTTPServer doesn't support chunked transfer.
        # Thus, it requires a content-length.
        length = int(self.headers.getheader('content-length'))
        print "POST of length " + str(length)
        f.write(self.rfile.read(length))
        f.close();

    def get_name(self):
        filename = 'output.wav';
        qs = self.path.split('?',1);
        if len(qs) == 2:
            params = cgi.parse_qs(qs[1])
            if params['name']:
                filename = params['name'][0];
        return WamiHandler.dirname + filename

def main():
    try:
        server = HTTPServer(('', 9000), WamiHandler)
        print 'Started server...'
        server.serve_forever()
    except KeyboardInterrupt:
        print 'Stopping server'
        server.socket.close()

if __name__ == '__main__':
    main()
