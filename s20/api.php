<?php
session_start();
define("ORVFMS_PATH","../lib/orvfms/");

require_once(ORVFMS_PATH."orvfms.php");

header("Content-type: application/json; charset=utf-8");

if(isset($_SESSION["s20Table"])) {
    $s20Table = $_SESSION["s20Table"];
}
else{
    $s20Table = readDataFile();
}

if(isset($s20Table) && (count($s20Table) > 0)){
    $s20Table = updateAllStatus($s20Table);  
    if(DEBUG)
        error_log("Session restarted; only status update\n");
}
else{
    $s20Table=initS20Data();

    $ndev = count($s20Table);
    if($ndev == 0){
        echo json_encode(array(
            'success' => false,
            'msg' => "No sockets found."
        ));
        exit(1);
    }
    if(DEBUG)
        error_log("New session: S20 data initialized\n");
}


$action = $_REQUEST['action'];
$device = $_REQUEST['device'];
if(isset($action) && isset($device)){

    $response = switchDevice($s20Table, $device, $action);

    echo $response;
}

function switchDevice($s20Table, $device, $action) {
    $mac = getMacFromDeviceName($s20Table, $device);
    if (!$mac) {
        echo json_encode(array(
            'success' => false,
            'msg' => "Device not found (by name)."
        ));
        exit(1);
    }
    $msg = null;
    $initialStatus = $s20Table[$mac]['st'];
    $finalStatus = getFinalStatus($initialStatus, $action, $msg);

    if (!is_null($finalStatus)) {
        $s20Table[$mac]['st'] = actionAndCheck($mac, $finalStatus, $s20Table);
        $success = ($s20Table[$mac]['st'] == $finalStatus)? true : false;
        $a = ($initialStatus)?"on":"off";
        $b = ($finalStatus)?"on":"off";
        $msg = "Switch {$a}->{$b}";
    } else {
        $msg = "Action not found";
        $success = false;
    }

    return json_encode(
        array(
            $mac => array(
                'success' => $success,
                'device' => $device,
                'status' => $s20Table[$mac]['st'],
                'msg' => $msg
            )
        )
    );
}

function getMacFromDeviceName($s20Table, $deviceName) {
    $mac = null;
    foreach ($s20Table as $key=>$deviceData) {
        if (strcasecmp($deviceData['name'],$deviceName) == 0) {
            $mac = $key;
            $_POST['toMainPage'] = "switch".$mac;
            break;
        }
    }
    return $mac;
}

function getFinalStatus($initialStatus, $action, &$msg) {
    switch ($action) {
        case 'switch':
            $finalStatus = !$initialStatus;
            $msg = "Switch.";
            break;
        case 'switchon':
        case 'switch-on':
            $finalStatus = 1;
            $msg = "Switch on.";
            break;
        case 'switchoff':
        case 'switch-off':
            $finalStatus = 0;
            $msg = "Switch off.";
            break;
        default:
            $finalStatus = null;
            $msg = "Action not found.";
    }

    return $finalStatus;
}

?>
