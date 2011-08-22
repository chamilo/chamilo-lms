# This file can be used as a base to configure OpenERP's web service module
# to connect to a Chamilo portal and make use of its SOAP services.
# This file is useless here, and should be placed on the OpenERP side after 
# being correctly configured (change variables below) in order to be put
# to good use
# @author Gustavo Maggi - Latinux

## Configuration section - set the variables below to their right values
# Chamilo's root directory URL. Replace by your own URL without the trailing /
# Examples: http://campus.server.com or http://campus.server.com/chamilo
url_root = 'http://192.168.1.1/chamilo'
# Get the Chamilo secret API key
# can be found in main/inc/conf/configuration.php, around line 115
security_key= 'abcdef1234567890abcdef1234567890'

## Connexion preparation
# Connexion - do not change anything here
from SOAPpy import SOAPProxy
server = SOAPProxy(url_root+'/main/webservices/soap.php' )
# Import libraries to calculate the SHA1 hash
import hashlib
import urllib2 as wget
# Get the IP of the current host, as detected by Chamilo (testip.php)
my_ip = wget.urlopen(url_root+'/main/webservices/testip.php').readlines()[0][:-1]
# Get the secret key - now the Chamilo webservices can be called
secret_key = hashlib.sha1(my_ip+security_key).hexdigest()

