<?php
function paginate($app, $cursor)
{
    $request = $app->request();
    $response = $app->response();

    $size = max((int)$request->params("size", 10), 1);
    $size = min($size, $GLOBALS["MAX_PAGE_SIZE"]);

    $page = max((int)$request->params("page", 1), 1);
    $sort = $request->params("sort", "id");
    $sortMode = 1;
    if (strpos($sort, "-") === 0) {
        $sortMode = -1;
        $sort = substr($sort, 1);
    } else if (strpos($sort, "+") === 0) {
        $sortMode = 1;
        $sort = substr($sort, 1);
    }
    $sort = trim($sort);
    $response->headers->set("X-Page-Sort", $sort);
    $response->headers->set("X-Page-Order", $sortMode);
    if ($sort == "id") $sort = "_id";

    $response->headers->set("X-Page-Size", "$size");
    $response->headers->set("X-Max-Page-Size", $GLOBALS["MAX_PAGE_SIZE"]);
    $response->headers->set("X-Page-Index", "$page");
    $count = ceil($cursor->count() / $size);
    $response->headers->set("X-Page-Count", "$count");

    return $cursor->skip($size * ($page - 1))->limit($size)->sort(array($sort => $sortMode));
}


function selectFields($allowed, $request, $default = null)
{
    $paramFields = $request->params("fields");
    if (!isset($paramFields)) {
        if (!is_null($default)) {
            return $default;
        } else {
            return $allowed;
        }
    } else {
        $paramFields = preg_split("/\\,/i", $paramFields);
    }
    $fields = $allowed;
    foreach ($allowed as $field) {
        if ("_id" === $field) {
            continue;
        } else {
            if (!in_array($field, $paramFields)) {
                if (($key = array_search($field, $fields)) !== false) {
                    unset($fields[$key]);
                }
            }
        }
    }
    // Remove gaps in the array - http://stackoverflow.com/a/15763467/6257838
    $fields = array_values(array_filter($fields));
    return $fields;
}


function makeDownloadFile($resource, $type = ".jar")
{
    $resource = (string)$resource;
    $split = str_split($resource);

    $finalFolder = "/home/spiget/resources/download/";
    for ($i = 0; $i < count($split) - 1; $i++) {
        $s = $split [$i];
        $finalFolder .= $s . "/";
    }

    return $finalFolder . $resource . $type;
}


function echoImage($app, $source, $type, $defaultData, $defaultUrl)
{
    if ($type === "raw") {
        if (is_null($source) || empty($source["icon"]["data"])) {
            $app->response->setStatus(404);
            http_response_code(404);
            echo $defaultData;
        } else {
            echo $source["icon"]["data"];
        }
    } else if ($type === "go") {
        if (is_null($source) || empty($source["icon"]["url"])) {
            $app->response->setStatus(404);
            http_response_code(404);
            header("Location: " . $defaultUrl);
        } else {
            header("Location: https://spigotmc.org/" . $source["icon"]["url"]);
        }
    } else if ($type === "url") {
        if (is_null($source) || empty($source["icon"]["url"])) {
            $app->response->setStatus(404);
            http_response_code(404);
            echo $defaultUrl;
        } else {
            echo "https://spigotmc.org/" . $source["icon"]["url"];
        }
    } else {
        header("Content-Type: image/jpeg");
        if (is_null($source) || empty($source["icon"]["data"])) {
            $app->response->setStatus(404);
            http_response_code(404);
            echo base64_decode($defaultData);
        } else {
            echo base64_decode($source["icon"]["data"]);
        }
    }
}

function echoData($json, $status = 0)
{
    $app = \Slim\Slim::getInstance();

    $app->response()->header("X-Api-Time", time());
    $app->response()->header("X-Api-Server", getServerConfig()["server"]["name"]);
    $app->response()->header("Cache-Control", "public, max-age=3600, s-maxage=3600" /* 2 Hours Cache */);
    $app->response()->header("Expires", gmdate('D, d M Y H:i:s', strtotime('+1 hour')) . " GMT");
    $app->response()->header("Last-Modified", gmdate("D, d M Y H:i:s", (getStatus("fetch.start") / 1000)));
    $app->response()->header("Connection", "close");

    $paramPretty = $app->request()->params("pretty");
    $pretty = true;
    if (!is_null($paramPretty)) {
        $pretty = $paramPretty !== "false";
    }

    if ($status !== 0) {
        $app->response->setStatus($status);
        http_response_code($status);
    }

    $app->contentType("application/json; charset=utf-8");
    header("Content-Type: application/json; charset=utf-8");

    $serialized = "{}";
    if ($pretty) {
        $serialized = json_encode($json, JSON_PRETTY_PRINT, JSON_UNESCAPED_UNICODE);
    } else {
        $serialized = json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    $jsonpCallback = $app->request()->params("callback");
    if (!is_null($jsonpCallback)) {
        echo $jsonpCallback . "(" . $serialized . ")";
    } else {
        echo $serialized;
    }
}

function getStatus($key)
{
    $cursor = status()->find(array("key" => $key), array("value"))->limit(1);
    if ($cursor->count() <= 0) {
        return null;
    }
    $value = dbToJson($cursor)["value"];
    if ($value instanceof MongoInt64 || $value instanceof MongoInt32) {
        return $value->value;
    }
    return $value;
}

function getSpigotStatus()
{
    try {
        $config = json_decode(file_get_contents("../../internal/statuscake.json"), true);

        $ch = curl_init("https://www.statuscake.com/API/Tests/Details/?TestID=" . $config["testId"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Spiget");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "API: " . $config["apiKey"],
            "Username: " . $config["username"]
        ));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    } catch (Exception $e) {
        return false;
    }
}

function getServerConfig()
{
    $config = json_decode(file_get_contents("../../internal/config.json"), true);

    $config["isMaster"] = $config["server"]["mode"] === "master";
    $config["isSlave"] = $config["server"]["mode"] === "slave";

    return $config;
}