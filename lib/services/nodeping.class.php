<?php

require_once dirname(__FILE__).'/service.class.php';
class NodepingStatusService extends StatusService {
    /**
     * @var string
     */
    public $serviceKey = 'nodeping';

    /**
     * @return bool|void
     */
    public function initialize() {
        parent::initialize();

    }

    /**
     *
     */
    public function getChecks() {
        $checks = $this->getFromCache('checks', $this->getOption('checksCacheExpires'));
        if (!$checks) {
            $checks = $this->_getAllChecks();
            $this->writeToCache('checks', $checks);
        }

        $fetchChecks = $this->getOption('checks');
        if (empty($fetchChecks)) $fetchChecks = array_keys($checks);
        foreach ($fetchChecks as $id) {
            $data = $this->getFromCache(md5($id), $this->getOption('cacheExpires'));
            if (!$data || empty($data)) {
                $data = $this->prepare($this->_getCheckResults($id));
                $data = array_merge($checks[$id],$data);
                $this->writeToCache(md5($id), $data);
            }
            $this->data[] = $data;
        }


        return $this->data;
    }

    /**
     * @return mixed
     */
    public function _getAllChecks() {
        $uri = 'checks';
        $data = $this->curlGetRequest($uri);
        return $data;
    }


    /**
     * @param string $id
     *
     * @return mixed
     */
    public function _getCheckResults($id = '') {
        $uri = 'results?id=' . urlencode($id) . '&clean=1&span='.$this->getOption('dataSpan');
        $data = $this->curlGetRequest($uri);
        return $data;
    }

    public function prepare(array $data = array()) {
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

        $returnData['average_response_time'] = round($avgResponseTime / $avgResponseTimeSample, $this->getOption('responseTimeDecimals', 3));

        return $returnData;
    }

    /**
     * @param $uri
     *
     * @return mixed
     */
    public function curlGetRequest($uri) {
        $uri = $this->getOption('apiUrl') . $uri;
        $uri = $uri . ((strpos($uri,'?')) ? '&' : '?') . 'token=' . urlencode($this->getOption('apiKey'));
        $data = parent::curlGetRequest($uri);
        return json_decode($data, true);
    }

    /**
     * @return array
     */
    public function getDefaultOptions() {
        return array(
            'checksCacheExpires' => 1800,
            'cacheExpires' => 60,
            'apiUrl' => 'https://api.nodeping.com/api/1/',
            'dataSpan' => 1,
        );
    }
}
