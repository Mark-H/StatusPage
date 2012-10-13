<?php

abstract class StatusService {
    /**
     * @var array
     */
    protected  $data = array();
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
     * StatusService instantiation.
     * @param Status $status
     * @param array $options
     */
    public function __construct(Status &$status, array $options = array()) {
        $this->status =& $status;
        $options = array_merge($this->getDefaultOptions(), $options);
        $this->setOptions($options);
        $this->initialize();
    }

    /**
     * @return array
     */
    public function getDefaultOptions() {
        return array();
    }

    /**
     * @return bool
     */
    public function initialize() {
        $this->setOption('cacheDir',$this->serviceKey);
        return true;
    }

    /**
     * @return array
     */
    public function getChecks() {
        return $this->data;
    }

    /**
     * @param $key
     * @param int $expires
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
            (!$checkValid && file_exists($file) && is_readable($file))
        ) {
            $data = include $file;
        }
        if (!empty($data)) {
            $data = unserialize($data);
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
     * @param $uri
     *
     * @return mixed
     */
    public function curlGetRequest($uri) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
