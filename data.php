<?php

/* Load up our Config */
$config = include 'config.php';

require_once $config['libPath'].'status.class.php';
$status = new Status($config);

$status->getData();

foreach ($status->data as $check) {
    echo '<tr '.(($check['status']) ? '' : 'class="error"').'>
    <td>'.(($check['status']) ? '<span class="icon-ok" title="Online at '.strftime('%c',$check['last_check_time']).'"></span>' : '<span class="icon-fire" title="Offline at '.strftime('%c',$check['last_check_time']).'"></span>').'</td>
    <td>'.$check['label'].'</td>
    <td>'.$check['average_response_time'].'</td>
    </tr>';
}
