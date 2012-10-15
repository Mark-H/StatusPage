<?php

/**
 * Abstract class for PageStatus Service Implementations.
 */
abstract class StatusService {
    /**
     * Contains rows of check data for ALL services. Should not be tampered with elsewhere.
     * @var array
     */
    protected $data = array();
    /**
     * Reference to the Status class
     * @var Status
     */
    public $status = null;
    /**
     * Service-specific options from the config and defaults.
     * @var array
     */
    public $options = array();
    /**
     * String to reference the service by in cache dir etc.
     * @var string
     */
    public $serviceKey = 'status';

    /**
     * Instantiates your StatusService implementation to set up options. Will call initialize().
     * @param Status $status
     * @param array $options
     * @final
     */
    final public function __construct(Status &$status, array $options = array()) {
        $this->status =& $status;
        $options = array_merge($this->getDefaultOptions(), $options);
        $options['cacheDir'] = $this->serviceKey;
        $this->setOptions($options);
        $this->initialize();
    }

    /**
     * Implement this to return an array of options and their possible values for the service.
     *
     * @return array
     * @abstract
     */
    abstract public function getDefaultOptions();

    /**
     * If you need to do any initialization, you may do so here.
     * @return bool
     */
    public function initialize() {
        return true;
    }

    /**
     * Implement this to fill the $this->data variable with data about your checks.
     * @return array
     */
    abstract public function getChecks();

    /**
     * Implement this method to call your update routine; allows crob jobs or other triggers
     * to update the status data.
     *
     * @param string $id Optionally, this method may be called with a specific ID to only update that.
     * @return boolean
     * @abstract
     */
    abstract public function update($id = '');

    /**
     * Checks to see if a cache file exists and is still valid.
     *
     * @param string $key   Key of the cache file
     * @param int $expires  Time in seconds the cache can live for.
     *                      Can be 0 to indicate the time does not matter as long as it exists.
     *
     * @return bool
     */
    public function isCacheValid($key, $expires = 60) {
        $valid = false;
        $file = $this->getCachePath($key);
        if (file_exists($file) && is_readable($file)) {
            $modified = filemtime($file);
            $expireTime = time() - $expires;
            if ($modified && ($modified > $expireTime)) {
                $valid = true;
            }
        }
        return $valid;
    }

    /**
     * @param $key
     *
     * @return string
     */
    public function getCachePath($key) {
        return $this->status->cachePath . $this->getOption('cacheDir') . DIRECTORY_SEPARATOR . $key . '.cache.php';
    }

    /**
     * @param $key
     * @param array $data
     *
     * @return bool
     */
    public function writeToCache($key, array $data = array()) {
        $file = $this->getCachePath($key);
        $data = serialize($data);
        $data = '<?php return \''.$data.'\';';

        $fh = fopen($file, 'w');
        if (!$fh) {
            echo 'Error opening cache file for writing.';
            return false;
        }
        fwrite($fh, $data);
        fclose($fh);
        return file_exists($file) && is_readable($file);
    }

    /**
     * @param $key
     * @param int $expires
     * @param bool $checkValid
     *
     * @return bool|mixed
     */
    public function getFromCache($key, $expires = 60, $checkValid = true) {
        $data = false;
        $file = $this->getCachePath($key);
        if (($checkValid && $this->isCacheValid($key, $expires)) ||
            (!$checkValid && file_exists($file) && is_readable($file)) ) {
            $data = include $file;
            if (!empty($data)) $data = unserialize($data);
        }
        if (empty($data) && $checkValid) {
            $data = $this->update($key);
        }
        return $data;

    }

    /**
     * @param $options
     */
    public function setOptions($options) {
        $this->options = $options;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value) {
        $this->options[$key] = $value;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * @param $key
     * @param string $default
     * @param bool $checkEmpty
     *
     * @return mixed
     */
    public function getOption($key, $default = '', $checkEmpty = true) {
        $value = $default;
        if (isset($this->options[$key]) && (!$checkEmpty || !empty($this->options[$key]))) {
            $value = $this->options[$key];
        }
        return $value;
    }

    /**
     * @param string $uri URI to request.
     * @param array $options Can include:
     *      - method [POST]
     *      - payload [array or string]
     *      - userpass [user:pass] for http basic auth
     *      - headers [string or array]
     * @return mixed
     */
    public function curlGetRequest($uri, array $options = array()) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        /* If curl_verifypeer is disabled in the config, disable it in the request */
        if (!$this->getOption('curl_verifypeer', true)) {
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, 0);
        }

        /* If the method is POST, set the right option. */
        if (isset($options['method']) && ($options['method'] == 'POST')) {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        /* If we have a payload, pass it on */
        if (isset($options['payload']) && !empty($options['payload'])) {
            $payload = $options['payload'];
            if (is_array($payload)) {
                $payload = http_build_query($payload);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        /* If asked to use HTTP basic auth, set the user:password */
        if (isset($options['userpass']) && !empty($options['userpass'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $options['userpass']);
        }

        /* If specific headers are present, send 'm */
        if (isset($options['headers']) && !empty($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        }

        /* Run the request and close the connection */
        $result = curl_exec($ch);
        curl_close($ch);

        /* Return the rawlasttesttime result - extended methods need to parse it and do error checking. */
        return $result;
    }
}
