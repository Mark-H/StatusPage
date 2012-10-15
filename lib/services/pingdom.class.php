<?php

require_once dirname(__FILE__).'/service.class.php';
/**
 * StatusPage implementation for Pingdom.com
 * @author Mark Hamstra
 * @date   2012-10-13
 */
class PingdomStatusService extends StatusService {
    /**
     * @var string
     */
    public $serviceKey = 'pingdom';
    /**
     * @var string
     */
    public $checkMeta = array();

    /**
     * Gets all (default) configuration options.
     * @return array
     */
    public function getDefaultOptions() {
        return array(
            'useAutoUpdate' => true,
            'cacheExpires' => 60,
            'apiUrl' => 'https://api.pingdom.com/api/2.0/',
            'responseTimeDecimals' => 2,
            'apiKey' => 'e8m2doedrm29kigfiiq08bzgmgaf9i57', // Does not need to be site specific
            'username' => '',
            'password' => '',
        );
    }

    /**
     * @inheritdoc
     */
    public function update($id = '') {
        $data = $this->_getCheckResults();
        $this->writeToCache('checks', $data);

        if (!empty($id) && isset($data[$id])) {
            return $data[$id];
        }
        return $data;
    }

    /**
     * Gets all check data
     */
    public function getChecks() {
        $data = $this->getFromCache('checks', $this->getOption('cacheExpires'), $this->getOption('useAutoUpdate'));
        $checks = $this->getOption('checks', array());
        if (empty($checks)) {
            $checks = array_keys($data);
        }

        /* Ability to exclude checks selectively */
        $excludeChecks = $this->getOption('excludeChecks');
        if (!empty($excludeChecks)) {
            foreach ($excludeChecks as $id) {
                $inArray = array_search($id, $checks);
                if ($inArray !== false) {
                    unset ($checks[$inArray]);
                }
            }
        }

        foreach ($checks as $id) {
            if (isset($data[$id]) && !empty($data[$id])) {
                $this->data[] = $data[$id];
            }
        }

        return $this->data;
    }


    /**
     * @return mixed
     */
    public function _getCheckResults() {
        $uri = 'checks';
        $rawData = $this->curlGetRequest($uri);
        $data = array();
        foreach ($rawData as $key => $value) {
            $data[$value[0]['id']] = $this->prepare($value[0]);
        }
        return $data;
    }

    /**
     * Standardizes data to:
        'id' => int 349961
        'created' => stftime(%c)
        'name' => string
        'target' => string
        'resolution' => int 1
        'type' => string 'http'
        'lasterrortime' => stftime(%c)
        'lasttesttime' => stftime(%c)
        'lastresponsetime' => int 199
        'status' => 1|0
     *
     * @param array $data
     *
     * @return array
     */
    public function prepare(array $data = array()) {
        $data['service'] = $this->serviceKey;
        $data['target'] = $data['hostname'];
        $data['status'] = ($data['status'] == 'up') ? 1 : 0;
        $data['created'] = strftime('%c', $data['created']);
        $data['lasterrortime'] = strftime('%c', $data['lasterrortime']);
        $data['lasttesttime'] = strftime('%c', $data['lasttesttime']);
        return $data;
    }

    /**
     * Overriden curlGetRequest to auto prepend the apiUrl and prepend the apiKey (token).
     * {@inheritdoc}
     * @param $uri
     *
     * @return mixed
     */
    public function curlGetRequest($uri, array $options = array()) {
        $uri = $this->getOption('apiUrl') . $uri;
        $options = array(
            'userpass' => $this->getOption('username').':'.$this->getOption('password'),
            'headers' => array('App-Key:'.$this->getOption('apiKey')),
        );
        $data = parent::curlGetRequest($uri, $options);
        return json_decode($data, true);
    }
}
