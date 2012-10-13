<?php

/* Load up our Config */
$config = include 'config.php';

require_once $config['libPath'].'status.class.php';
$status = new Status($config);

$status->getData();


echo '<!DOCTYPE HTML>
<html lang="en-US">
<head>
  <meta charset="UTF-8">
  <title>Status Dashboard</title>
</head>
<body>

<h1>Status Dashboard</h1>

<ul class="checks">';

foreach ($status->data as $check) {
    echo '<li>' . $check['label'] . ' is currently ' . (($check['status']) ? 'online' : '<span style="color:red">offline</span>');
    if ($check['status_since'] > 0) echo ' (since '.strftime('%c', $check['status_since']).')';
    echo ' with an average response time of ' . $check['average_response_time'] . ' over the past hour.</li>';
}

echo '</ul>
</body>
</html>';
