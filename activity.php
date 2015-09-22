<?php
require 'vendor/autoload.php';
use GeoIp2\Database\Reader;
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$reader = new Reader('GeoLite2-City.mmdb');

$user_id = $_REQUEST['user_id'];

$db = pg_connect($ENV['RAILGUN_DATABASE']) or die("Can't connect to database" . pg_last_error());
$data = array();
$result = pg_query_params($db, "SELECT started_at, ended_at, uplink_traffic + downlink_traffic AS traffic, 1 AS service, protocol, client_address FROM ports.traffic RIGHT JOIN network.zones using (server_id) INNER JOIN ports.ports ON ports.port = traffic.port AND ports.zone_id = zones.id WHERE user_id = $1 AND started_at > now() - '1 month' :: INTERVAL ORDER BY client_address, started_at", $user_id);
$last_row = NULL;
while ($row = pg_fetch_assoc($result)) {
#if $last_row and $last_row['client_address'] == $row['client_address'] and $last_row['ended_at'] + 
    var_dump($row);
}
exit;
$result = pg_query($db, "select acctstarttime as started_at, acctstoptime as ended_at, acctsessiontime as duration, acctinputoctets + acctoutputoctets as traffic, 3 as service, split_part(host(framedipaddress::inet), '.', 3)::int >> 4 as protocol, split_part(callingstationid, '=', 1)::inet as client_address from radius.radacct right join radius.radcheck using(username) where id = 1 and acctstarttime > now() - '1 month'::interval");
while ($row = pg_fetch_assoc($result)) {
    $record = $reader->city($row['client_address']);
    if (isset($row['duration'])) {
        $row['duration'] = intval($row['duration']);
    }
    if (isset($row['traffic'])) {
        $row['traffic'] = intval($row['traffic']);
    }
    if (isset($row['service'])) {
        $row['service'] = intval($row['service']);
    }
    if (isset($row['protocol'])) {
        $row['protocol'] = intval($row['protocol']);
    }
    $row['location'] = $record->city->names['zh-CN'] ?: $record->city->name ?: $record->mostSpecificSubdivision->names['zh-CN'] ?: $record->mostSpecificSubdivision->name ?: $record->country->names['zh-CN'] ?: $record->country->names['zh-CN'];
    $data[] = $row;
}
echo json_encode($data);
