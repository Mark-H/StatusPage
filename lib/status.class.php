<?php

class Status {
    public $config = array();
    public $cachePath = '';
    public $data = array();
    public $services = array();

    public function __construct(array $config = array()) {
        $this->config = $config;
        $this->cachePath = dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR;
    }

    public function getData () {
        $this->loadServices($this->config['services']);
        foreach ($this->services as $key => $service) {
            /* @var StatusService $service */
            $data = $service->getChecks();
            if (!empty($data) && is_array($data)) {
                $this->data = array_merge($this->data, $data);
            }
        }
        return $this->data;
    }

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
}
