<?php

require_once dirname(__FILE__).'/service.class.php';
/**
 * StatusPage implementation for NodePing.com
 * @author Mark Hamstra
 * @date   2012-10-13
 */
class NodepingStatusService extends StatusService {
    /**
     * @var string
     */
    public $serviceKey = 'nodeping';
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
            'checksCacheExpires' => 1800,
            'cacheExpires' => 60,
            'apiUrl' => 'https://api.nodeping.com/api/1/',
            'useAutoUpdate' => true,
            'responseTimeDecimals' => 2,
            'subAccount' => '',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function initialize() {
        /* Get checks meta data */
        if (!$this->isCacheValid('meta', $this->getOption('checksCacheExpires')) && $this->getOption('useAutoUpdate')) {
            $this->update('meta');
        }
        $checkMeta = $this->getFromCache('meta', $this->getOption('checksCacheExpires'));
        $this->checkMeta = $checkMeta;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function update($id = '') {
        if (!empty($id) && ($id == 'meta')) {
            $this->checkMeta = $this->_getCheckMeta();
            $this->writeToCache($id, $this->checkMeta);
            return $this->checkMeta;
        }

        elseif (!empty($id)) {
            $data = $this->_getCheckResults($id);
            $this->writeToCache($id, $data);
            return $data;
        }

        else {
            if (!$this->isCacheValid('meta', $this->getOption('checksCacheExpires'))) {
                $this->update('meta');
            }
            foreach ($this->checkMeta as $key => $data) {
                $this->update($key);
            }
            return null;
        }
    }

    /**
     * Gets all check data
     */
    public function getChecks() {
        $checks = $this->getOption('checks', array());
        if (empty($checks)) {
            $checks = array_keys($this->checkMeta);
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

        $subAccount = $this->getOption('subAccount', null, true);
        /* Get all check data and if not empty, add to the data list. */
        foreach ($checks as $id) {
            $data = $this->getFromCache($id, $this->getOption('cacheExpires'), $this->getOption('useAutoUpdate'));
            if (!empty($data)) {
                if (!empty($subAccount)) {
                    if ($data['subaccount'] == $subAccount) {
                        $this->data[] = $data;
                    }
                } else {
                    $this->data[] = $data;
                }
            }
        }

        return $this->data;
    }

    /**
     * Gets the check meta
     * @return mixed
     */
    public function _getCheckMeta() {
        $uri = 'checks';
        if ($this->getOption('subAccount', null, true)) {
            $uri .= '?customerid='.$this->getOption('subAccount');
        }
        $data = $this->curlGetRequest($uri);
        return $data;
    }


    /**
     * @param string $id
     *
     * @return mixed
     */
    public function _getCheckResults($id) {
        $uri = 'results?id=' . urlencode($id) . '&clean=1&limit=1';
        if ($this->getOption('subAccount', null, true)) {
            $uri .= '&customerid='.$this->getOption('subAccount');
        }
        $data = $this->curlGetRequest($uri);
        return $this->prepare($data[0], $id);
    }

    /**
     * Standardizes data to:
        'service' => 'nodeping'
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
     * @param $id
     *
     * @return array
     */
    public function prepare(array $data = array(), $id = '') {
        if (empty($data)) {
            return array();
        }

        $checkMeta = $this->checkMeta[$id];
        $returnData = array(
            'service' => $this->serviceKey,
            'id' => $data['_id'],
            'created' => strftime('%c', $checkMeta['created']),
            'name' => $checkMeta['label'],
            'target' => $data['i'],
            'resolution' => $data['i'],
            'type' => $data['t'],
            'lasterrortime' => 0,
            'lasttesttime' => strftime('%c',($data['e'] / 1000)),
            'lastresponsetime' => $data['rt'],
            'status' => intval($data['su']),
            'message' => $data['m'],
            'subaccount' => $data['ci'],
        );
        return $returnData;
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
        $uri = $uri . ((strpos($uri,'?')) ? '&' : '?') . 'token=' . urlencode($this->getOption('apiKey'));
        $data = parent::curlGetRequest($uri, $options);
        return json_decode($data, true);
    }
}
