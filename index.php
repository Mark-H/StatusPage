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

<table class="checks">
<thead>
<tr>
<td>Check ID</td>
<td>Server/Service</td>
<td>Status</td>
<td>Avg Response Time (-1hr)</td>
<td>Last Checked On</td>
</tr>
</thead>';

foreach ($status->data as $check) {
    echo '<tr>
    <td style="font-size: 50%;">'.$check['_id'].'</td>
    <td>'.$check['label'].'</td>
    <td>'.(($check['status']) ? 'online' : '<span style="color:red">offline</span>').'</td>
    <td>'.$check['average_response_time'].'</td>
    <td>'.strftime('%c',$check['last_check_time']).'</td>
    </tr>';
}

echo '</table>
</body>
</html>';
