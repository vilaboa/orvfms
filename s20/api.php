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

$ndummy = 0;
if(isset($s20Table)){
    foreach($s20Table as $mac => $data){
        if(array_key_exists('off',$s20Table[$mac])) $ndummy++;
    }
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
    foreach ($s20Table as $key=>$device) {
        if (strcasecmp($device['name'],$device) == 0) {
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

    switch ($action) {
        case 'switch': {
            $st = $s20Table[$mac]['st'];
            $newSt = actionAndCheck($mac,($st==0 ? 1 : 0),$s20Table);
            $s20Table[$mac]['st']=$newSt;

            echo json_encode(array(
                'success' => true,
                'status' => $s20Table[$mac]['st']
            ));
            exit(1);
        }
        default: {
            echo json_encode(array(
                'success' => false,
                'msg' => "Action not found."
            ));
            exit(1);
        }
    }
}

?>
