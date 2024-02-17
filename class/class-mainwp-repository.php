<?php
/**
 * MainWP Utility
 *
 * @package MainWP/Child
 */

namespace MainWP\Child;

use phpseclib3\Crypt\PublicKeyLoader;

/**
 * Class MainWP_Repository
 *
 * This class represents the repository for the MainWP plugin.
 */
class MainWP_Repository
{

    /**
     * Public static variable to hold the single instance of the class.
     *
     * @var mixed Default null
     */
    public static $instance = null;

    /**
     * Public variable to hold the public key.
     *
     * @var string Default empty string
     */
    public string $publicKey = '';


    /**
     * Method get_class_name()
     *
     * Get class name.
     *
     * @return string __CLASS__ Class name.
     */
    public static function get_class_name()
    {
        return __CLASS__;
    }

    /**
     * Method instance()
     *
     * Create a public static instance.
     *
     * @return mixed Class instance.
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Boots up the plugin.
     *
     * Registers the request handler for the plugin on the 'plugins_loaded' action hook.
     *
     * @return void
     */
    public function init(): void
    {
        add_action('plugins_loaded', [&$this, 'register_request']);
    }

    /**
     * Autoloads the required files for the plugin.
     *
     * This method is responsible for loading the necessary files and dependencies needed
     * for the plugin to function properly.
     *
     * @return void
     */
    public static function autoload_files()
    {
        require_once MAINWP_CHILD_PLUGIN_DIR.'libs'.DIRECTORY_SEPARATOR.'phpseclib'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
    }


    /**
     * Registers a request if the 'function' parameter is set to 'register' and 'pubkey' and 'server' parameters are provided.
     *
     * @return void
     */
    public function register_request(): void
    {
        if (isset($_REQUEST['function']) && $_REQUEST['function'] === 'register') {
            if ($_REQUEST['pubkey'] && $_REQUEST['server']) {
                $this->saveKey();
            }
        }
    }

    /**
     * Saves the public key from a form submission.
     * If the 'pubkey' field is set in the $_REQUEST array, it will be base64 encoded and saved as the 'mainwp_child_sshkey' option.
     * The current date and time will also be saved as the 'mainwp_child_sshtime' option.
     *
     * @return void
     */
    public function saveKey(): void
    {
        $key = (isset($_REQUEST['pubkey']) ? base64_encode(wp_unslash($_REQUEST['pubkey'])) : '');
        $newKey = $this->convertFormat($key)->getKey();

        if (! empty($newKey)) {
            get_option('mainwp_child_sshkey', $newKey);
            get_option('mainwp_child_sshtime', wp_date('Y-m-d H:i:s', '', wp_timezone()));
        }
    }

    /**
     * Gets the key based on the given type.
     *
     * @param  string  $type  The type of the key to retrieve. Defaults to 'public'.
     * @return string The key value.
     */
    public function getKey(string $type = 'public'): string
    {
        return $this->{$type.'Key'};
    }

    /**
     * Retrieves the domain of the website.
     *
     * @return string The domain of the website.
     */
    public function getDomain(): string
    {
        return $this->clearDomain(get_option('siteurl', 'wordpress'));
    }

    /**
     * Converts the format of a given key and stores it as the public key.
     *
     * This method converts the format of a given key to the OpenSSH format and stores it as the public key.
     * If no key is provided, it retrieves the mainwp_child_pubkey option value from the WordPress database.
     * The converted public key is stored in the mainwp_child_sshkey option, and the current date and time is
     * stored in the mainwp_child_sshtime option.
     *
     * @param  string  $key  (Optional) The key to be converted. Defaults to an empty string.
     * @return self Returns an instance of the class with the converted public key set.
     */
    public function convertFormat(string $key = ''): self
    {
        if (empty($key)) {
            $key = get_option('mainwp_child_pubkey', '');
        }
        self::autoload_files();

        try {
            $this->publicKey = PublicKeyLoader::load(base64_decode($key))->toString('OpenSSH', ['comment' => $this->getDomain()]);

            update_option('mainwp_child_sshkey', base64_encode(wp_unslash($this->publicKey, true)));
            update_option('mainwp_child_sshtime', date('Y-m-d H:i:s'), true);
        } catch (\Exception $e) {

            new \Exception('Error: '.$e->getMessage());
        }

        return $this;
    }

    /**
     * Clears the domain by removing the protocol, www subdomain, and trailing slashes,
     * and prepends a fixed string 'wpdash@' to the resulting domain.
     *
     * @param  string  $domain  The domain to be cleared.
     *
     * @return string The cleared domain with 'wpdash@' prepended.
     */
    public function clearDomain(string $domain): string
    {
        $domain = preg_replace('/^https?:\/\/(www\.)?/', '', $domain);
        $domain = rtrim($domain, '/');

        return 'wpdash@'.$domain;
    }

    /**
     * Decode a given base64 encoded key.
     *
     * @param  string  $key  The base64 encoded key to decode.
     * @return string The decoded key. Returns an empty string if the input key is empty or if decoding fails.
     */
    public static function decodeKey(string $key = ''): string
    {
        if (! empty($key)) {
            $key = base64_decode($key, true);
        }

        return $key;
    }

}
