<?php
date_default_timezone_set("UTC");

// Config
$serverConfig = json_decode(file_get_contents("../internal/config.json"), true);

$ipWhitelist = $serverConfig["logDownload"]["ipWhitelist"];
$authToken = $serverConfig["logDownload"]["token"];
$authPass = $serverConfig["logDownload"]["pass"];

// Check IP
$connectingIp = getIpAddress();
if (!in_array($connectingIp, $ipWhitelist)) {
    http_response_code(403);
    die("Not authorized: IP is not whitelisted (" . $connectingIp . ")");
}

// Check authentication
$token = $_SERVER["HTTP_X_AUTH_TOKEN"];
if ($token !== $authToken) {
    http_response_code(403);
    die("Not authorized: invalid token");
}
$pass = $_SERVER["HTTP_X_AUTH_PASS"];
if ($pass !== $authPass) {
    http_response_code(403);
    die("Not authorized: invalid password");
}

// Check if file exists
$date = date("Ymd", time());
$file = sprintf($serverConfig["logDownload"]["fileFormat"], $date);

if (!file_exists($file) || !is_file($file)) {
    http_response_code(404);
    die("Log file not found for " . $date . "(" . $file . ")");
}

$fileName = "access.log-" . $serverConfig["server"]["name"] . "-" . $date . ".gz";

header("Cache-Control: private");
header("Content-Type: application/octet-stream");
header("Content-Length: " . filesize($file));
header("Content-Disposition: attachment; filename=" . $fileName);
readfile($file);
exit();

// http://stackoverflow.com/a/2031935/6257838
function getIpAddress()
{
    foreach (array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip); // just to be safe

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return "";
}