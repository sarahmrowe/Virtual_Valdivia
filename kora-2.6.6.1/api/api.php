<?php
$charset = "utf-8";
$mime    = (isset($_SERVER["HTTP_ACCEPT"]) && stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml")) ? "application/xhtml+xml" : "text/plain";
header("content-type:$mime;charset=$charset");
require_once('apiutils.php');

if(isGetSet('action') && isGetSet('target'))
{
    $ACTION = $_GET['action'];
    $TARGET = $_GET['target'];
} else XMLDumpErrorAndDie("action_or_target_invalid");

if(!isset($actions[$ACTION]) || !isset($actions[$ACTION][$TARGET]))
    XMLDumpErrorAndDie("action_or_target_invalid");

if(isGetSet('token'))
{
    $TOKEN = $_GET['token'];
} else XMLDumpErrorAndDie("token_invalid");

foreach($actions[$ACTION][$TARGET] as $key => $val)
{
    if($val != '' && !isGetSet($val))
    {
        XMLDumpErrorAndDie("missing_args");
    }
}

require_once('search.php');
$perform = "{$API_FUNCTION_PREFIX}_{$ACTION}_{$TARGET}";
if(!function_exists($perform))
    XMLDumpErrorAndDie("api_function_not_defined");
$perform();
?>