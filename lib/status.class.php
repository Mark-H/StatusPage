<?php

/**
 *
 */
class Status {
    public $config = array();
    public $cachePath = '';
    public $data = array();
    public $services = array();

    /**
     * Constructs the Status object by making config available and calculating the cache path.
     * @param array $config
     */
    public function __construct(array $config = array()) {
        $this->config = $config;
        $this->cachePath = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    }

    /**
     * Returns an associative array with data from all checks.
     * @return array
     */
    public function getData () {
        $this->loadServices($this->config['services']);
        foreach ($this->services as $service) {
            /* @var StatusService $service */
            $data = $service->getChecks();
            if (!empty($data) && is_array($data)) {
                $this->data = array_merge($this->data, $data);
            }
        }

        if ($this->getOption('sortByName', true, false)) {
            usort($this->data,array($this,'sortByName'));
        }

        if ($this->getOption('sortOfflineFirst', true, false)) {
            usort($this->data,array($this,'sortOfflineFirst'));
        }

        return $this->data;
    }

    /**
     * Loads & instantiates all services from the config file.
     * @param array $services
     */
    public function loadServices(array $services = array()) {
        foreach ($services as $key => $config) {
            $className = ucfirst($key).'StatusService';
            if (!class_exists($className)) {
                $classFile = $this->config['libPath'].'services'.DIRECTORY_SEPARATOR.strtolower($key).'.class.php';
                if (file_exists($classFile)) {
                    require_once $classFile;
                }
            }

            if (!class_exists($className)) {
                echo 'Unable to find service class for service: '.$key;
                continue;
            }

            $this->services[$key] = new $className($this, $config);
            if (!$this->services[$key]) {
                echo 'Error instantiating service class for service: '.$key;
                unset($this->services[$key]);
                continue;
            }
        }
    }

    /**
     * Custom sort function for usort() that sorts by label.
     *
     * @param $v1
     * @param $v2
     *
     * @return bool
     */
    public function sortByName ($v1, $v2) {
        return ($v1['name'] > $v2['name']);
    }

    /**
     * Custom sort function for usort() that puts offline services before online ones.
     *
     * @param $v1
     * @param $v2
     *
     * @return bool
     */
    public function sortOfflineFirst ($v1, $v2) {
        if ($v1['status'] < 1) return -1;
        elseif ($v2['status'] < 1) return 1;
        elseif ($this->getOption('sortByName', true, false)) return $this->sortByName($v1, $v2);
        return 0;
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
        if (isset($this->config[$key]) && (!$checkEmpty || !empty($this->config[$key]))) {
            $value = $this->config[$key];
        }
        return $value;
    }
}
