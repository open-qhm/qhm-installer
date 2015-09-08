<?php
/**
* QHM インストーラー
*/
define('INSTALL_DIR', dirname(__FILE__));
define('ARCHIVE_URL', 'https://github.com/open-qhm/qhm/archive/master.zip');
define('DEVELOPMENT', true);

require 'src/func.php';

$data = array(
    'location' => $_SERVER['SCRIPT_NAME'],
);

require 'src/handling.php';


extract($data);
$data_json = json_encode($data);
include 'view/template.html';
