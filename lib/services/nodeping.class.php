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
            'dataSpan' => 1,
            'useAutoUpdate' => true,
            'responseTimeDecimals' => 2,
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

        /* Get all check data and if not empty, add to the data list. */
        foreach ($checks as $id) {
            $data = $this->getFromCache($id, $this->getOption('cacheExpires'), $this->getOption('useAutoUpdate'));
            if (!empty($data)) {
                $this->data[] = $data;
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
        $data = $this->curlGetRequest($uri);
        return $data;
    }


    /**
     * @param string $id
     *
     * @return mixed
     */
    public function _getCheckResults($id) {
        $uri = 'results?id=' . urlencode($id) . '&clean=1&span='.$this->getOption('dataSpan');
        $data = $this->curlGetRequest($uri);
        return $this->prepare($data, $id);
    }

    /**
     * @param array $data
     * @param $id
     *
     * @return array
     */
    public function prepare(array $data = array(), $id = '') {
        if (empty($data)) {
            return array();
        }
        $returnData = array(
            'status' => $data[0]['su'],
            'status_since' => 0,
            'last_check_time' => ($data[0]['e'] / 1000),
            'last_response_time' => $data[0]['rt'],
            'interval' => $data[0]['i'],
            'target' => $data[0]['i'],
            'message' => $data[0]['m'],
        );
        $avgResponseTime = 0;
        $avgResponseTimeSample = 0;
        $status = $data[0]['su'];
        $checkStatus = true;

        foreach ($data as $result) {
            $avgResponseTime += $result['rt'];
            $avgResponseTimeSample++;

            if ($checkStatus && ($result['su'] != $status)) {
                $returnData['status_since'] = ($result['e'] / 1000);
                $checkStatus = false;
            }
        }

        $returnData['average_response_time'] = round($avgResponseTime / $avgResponseTimeSample, $this->getOption('responseTimeDecimals', 2));

        /* Add the check meta to the row */
        $returnData = array_merge($this->checkMeta[$id],$returnData);
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
