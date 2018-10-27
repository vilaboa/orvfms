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

    $mac = null;
    foreach ($s20Table as $key=>$deviceData) {
        if (strcasecmp($deviceData['name'],$device) == 0) {
            $mac = $key;
            $_POST['toMainPage'] = "switch".$mac;
            break;
        }
    }
    if (!$mac) {
        echo json_encode(array(
            'success' => false,
            'msg' => "Device not found (by name)."
        ));
        exit(1);
    }

    $subaction = null;
    $msg = null;
    switch ($action) {
        case 'switch':
            $subaction = !$s20Table[$mac]['st'];
            $msg = "Switch.";
            break;
        case 'switchon':
        case 'switch-on':
            $subaction = 1;
            $msg = "Switch on.";
            break;
        case 'switchoff':
        case 'switch-off':
            $subaction = 0;
            $msg = "Switch off.";
            break;
        default:
            $msg = "Action not found.";
    }

    if (!is_null($subaction)) {
        $s20Table[$mac]['st'] = actionAndCheck($mac, $subaction, $s20Table);
        $success = ($s20Table[$mac]['st'] == $subaction)? true : false;
    } else {
        $success = false;
    }
    echo json_encode(array(
        'success' => $success,
        'status' => $s20Table[$mac]['st'],
        'msg' => $msg
    ));
}

?>
