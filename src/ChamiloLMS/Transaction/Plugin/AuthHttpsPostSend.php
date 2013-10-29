<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Transaction\Plugin;

use ChamiloLMS\Transaction\Envelope;
use Entity\BranchSync;

/**
 * Send using POST over https with http authentication.
 *
 * @todo This can be easily splitted in more plugins or be configurable to
 * support different scenarios.
 */
class AuthHttpsPostSend implements SendPluginInterface
{
    /**
     * The POST target.
     *
     * @var string
     */
    protected $uri;

    /**
     * The user for http authentication.
     *
     * @var string
     */
    protected $user;

    /**
     * The password for http authentication.
     *
     * @var string
     */
    protected $password;

    /**
     * {@inheritdoc}
     */
    public static function getMachineName()
    {
        return 'auth_https_post';
    }

    /**
     * Constructor.
     *
     * @param array $data
     *   An array containing the values of the three required data members
     *   using the data member name as key.
     *
     * @throws SendException
     *   When there is an error while instanciating the plugin.
     */
    public function __construct($data) {
        foreach (array('uri', 'user', 'password') as $data_member_name) {
            if (!isset($data[$data_member_name])) {
                throw new SendException(sprintf('auth_https_post: Cannot instanciate the plugin with data array: "%s".', print_r($data, 1)));
            }
            $this->$data_member_name = $data[$data_member_name];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope)
    {
        if (!$blob = $envelope->getBlob()) {
            throw new SendException('auth_https_post: Cannot retrieve blob from envelope.');
        }

        if (!$blob_file = $this->getTemporaryFileToSend('blob_file', $envelope)) {
            throw new SendException(sprintf('auth_https_post: Unable to create correctly the temporary blob file on "%s".', $blob_file));
        }
        if (file_put_contents($blob_file, $blob) === false) {
            throw new SendException(sprintf('auth_https_post: Unable to write correctly the temporary blob file on "%s".', $blob_file));
        }

        $curl_handle = curl_init();
        $post_data = array('file' => '@' . $blob_file);

        // @todo See if we need to manually add the CA certificate, or default
        // is enough.
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_URL, $this->uri);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl_handle, CURLOPT_USERPWD, sprintf('%s:%s', $this->user, $this->password));

        $response = curl_exec($curl_handle);
        if ($response === false) {
            $message = curl_error($curl_handle);
            curl_close($curl_handle);
            throw new SendException(sprintf('auth_https_post: Unable to make the POST request correctly with file "%s"', $blob_file));
        }

        if ($response != '0') {
            $message = curl_error($curl_handle);
            curl_close($curl_handle);
            throw new SendException(sprintf('auth_https_post: File sent, error code "%s" receiving the file "%s"', $response, $blob_file));
        }
        curl_close($curl_handle);
        unlink($blob_file);
    }

    /**
     * Retrieves a temporary filename.
     *
     * @see SslSignedJsonWrapper.
     * @todo Unify on a common base plugin parent.
     */
    protected function getTemporaryFileToSend($name, Envelope $envelope) {
      static $tmp_directory;
      if (!isset($tmp_directory)) {
          $tmp_directory = api_get_path(SYS_DATA_PATH) . 'transaction_tmp_files';
          if (!is_dir($tmp_directory)) {
              if (!mkdir($tmp_directory, api_get_permissions_for_new_directories())) {
                  return false;
              }
          }
      }
      return tempnam($tmp_directory, $name);
    }
}
