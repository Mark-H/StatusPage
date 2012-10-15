<?php
/* Data available:
    'service' => nameofservice
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
*/

/* Load up our Config */
$config = include 'config.php';

require_once $config['libPath'].'status.class.php';
$status = new Status($config);

$status->getData();

foreach ($status->data as $check) {
    echo '<tr class="'.(($check['status']) ? '' : 'error ').'service-'.$check['service'].'">
    <td>'.(($check['status']) ? '<span class="icon-ok" title="Online at '.$check['lasttesttime'].'"></span>' : '<span class="icon-fire" title="Offline at '.$check['lasttesttime'].'"></span>').'</td>
    <td>'.$check['name'].'</td>
    <td>'.$check['lastresponsetime'].'</td>
    </tr>';
}
