<?

require_once('LedSign.php');

$ledSign = new LedSign('192.168.0.106');
$success = $ledSign->initialize();

?>
