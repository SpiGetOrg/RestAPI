<?php
include("../internal/Slim.php");
include("../internal/Database.php");

$GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"] = array(
    "_id",
    "name",
    "tag",
    "contributors",
    "likes",
    "file",
    "testedVersions",
    "links",
    "version",
    "author",
    "category",
    "rating",
    "releaseDate",
    "updateDate",
    "downloads");
$GLOBALS["SPIGET_RESOURCE_ALL_FIELDS"] = array(
    "_id",
    "name",
    "tag",
    "contributors",
    "external",
    "file",
    "description",
    "likes",
    "testedVersions",
    "version",
    "versions",
    "updates",
    "links",
    "author",
    "category",
    "rating",
    "releaseDate",
    "updateDate",
    "downloads"
);
$GLOBALS["SPIGET_AUTHOR_LIST_FIELDS"] = array(
    "_id",
    "name"
);
$GLOBALS["SPIGET_AUTHOR_ALL_FIELDS"] = array(
    "_id",
    "name",
    "icon"
);


$app = new Slim\Slim();
$app->notFound(function () use ($app) {
    echoData(array("error" => "invalid route"));
});
$app->hook("slim.before", function () use ($app) {
    $app->response()->header("Access-Control-Allow-Origin", "*");
    $app->response()->header("X-API-Time", time());
});
$app->hook("slim.before.dispatch", function () use ($app) {
    if ($app->response()->isSuccessful()) {
        $ua = !empty ($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : "unknown";

        //StatusCake
        if (preg_match("/\\(StatusCake\\)/", $ua)) {
            $ua = "StatusCake";
        }
        if (strpos($ua, "Mozilla") !== false || strpos($ua, "Internet Explorer") !== false || strpos($ua, "AppleWebKit") !== false || strpos($ua, "Opera") !== false) {
            $ua = "default";
        }
        if (isset($_REQUEST["spiget__ua"])) {
            $ua = $_REQUEST["spiget__ua"];
        }

        $today = strtotime("today");
        $hour = intval(date("H"));

        $method = $app->request()->getMethod();
        $path = $app->router()->getCurrentRoute()->getName();
        $ip = $app->request()->getIp();

        requests()->update(array(
            "day" => $today,
            "hour" => $hour,
            "ua" => $ua,
            "path" => $path,
            "ip" => $ip
        ), array('$inc' => array("count" => 1)), array(
            "upsert" => true,
            "writeConcern" => 0
        ));
    }
});

$app->group("/resources", function () use ($app) {

    $app->get("/", function () use ($app) {
        $cursor = paginate($app->request(), resources()->find(array(), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor);

        echoData($resources);
    })->name("/resources");

    $app->get("/new", function () use ($app) {
        $cursor = paginate($app->request(), resources()->find(array('$where' => "this.releaseDate == this.updateDate"), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor);

        echoData($resources);
    })->name("/resources/new");

    $app->get("/for/:versions(/:method)", function ($versions = "", $method = "any") use ($app) {
        $versionArray = preg_split("/\\,/i", $versions);
        if ($method === "any") {
            $cursor = resources()->find(array("testedVersions" => array('$exists' => true, '$in' => $versionArray)), array("id", "name", "testedVersions"));
        } else if ($method === "all") {
            $cursor = resources()->find(array("testedVersions" => array('$exists' => true, '$all' => $versionArray)), array("id", "name", "testedVersions"));
        } else {
            echoData(array("error" => "Unknown method. Allowed: any, all"), 400);
            return;
        }
        $cursor = paginate($app->request(), $cursor);
        $resources = dbToJson($cursor);

        echoData(array("check" => $versionArray, "method" => $method, "match" => $resources));
    })->name("/resources/for");

    $app->get("/:resource/download", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id", "name", "file"));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id", "name", "file"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        $file = makeDownloadFile($resource["id"], $resource["file"]["type"]);
        if (!file_exists($file)) {
            echoData(array("error" => "file not found"), 404);
            return;
        }

        $file_name = $resource["name"] . "#" . $resource["id"] . $resource["file"]["type"];
        $file_type = mime_content_type($file);
        if (empty($file_type)) $file_type = "application/octet-stream";

        $fp = fopen($file, 'rb');
        header("Content-Type: $file_type");
        header("Content-Disposition: attachment; filename=$file_name");
        header("Content-Length: " . filesize($file));
        fpassthru($fp);
        fclose($fp);
    })->name("/resources/x/download");

    $app->get("/:resource", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), selectFields($GLOBALS["SPIGET_RESOURCE_ALL_FIELDS"], $app->request()));
        } else {
            $cursor = resources()->find(array("name" => $resource), selectFields($GLOBALS["SPIGET_RESOURCE_ALL_FIELDS"], $app->request()));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        echoData($resource);
    })->name("/resources/x");

});
$app->group("/authors", function () use ($app) {

    $app->get("/", function () use ($app) {
        $cursor = paginate($app->request(), authors()->find(array(), selectFields($GLOBALS["SPIGET_AUTHOR_LIST_FIELDS"], $app->request())));
        $authors = dbToJson($cursor);

        echoData($authors);
    })->name("/authors");

    $app->get("/:author", function ($author) use ($app) {
        if (is_numeric($author)) {
            $cursor = authors()->find(array("_id" => (int)$author), selectFields($GLOBALS["SPIGET_AUTHOR_ALL_FIELDS"], $app->request()));
        } else {
            $cursor = authors()->find(array("name" => $author), selectFields($GLOBALS["SPIGET_AUTHOR_ALL_FIELDS"], $app->request()));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "author not found"), 404);
            return;
        }
        $author = dbToJson($cursor);

        echoData($author);
    })->name("/authors/x");

});
$app->group("/categories", function () use ($app) {

    $app->get("/", function () use ($app) {
        $cursor = paginate($app->request, categories()->find());
        $categories = dbToJson($cursor);

        echoData($categories);
    })->name("/categories");

    $app->get("/:category/resources", function ($category) use ($app) {
        if (is_numeric($category)) {
            $cursor = categories()->find(array("_id" => (int)$category), array("_id"));
        } else {
            $cursor = categories()->find(array("name" => $category), array("_id"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "category not found"), 404);
            return;
        }
        $category = dbToJson($cursor);

        $cursor = paginate($app->request(), resources()->find(array('category.$id' => $category["id"]), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor);

        echoData($resources);
    });

    $app->get("/:category", function ($category) use ($app) {
        if (is_numeric($category)) {
            $cursor = categories()->find(array("_id" => (int)$category));
        } else {
            $cursor = categories()->find(array("name" => $category));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "category not found"), 404);
            return;
        }
        $category = dbToJson($cursor);

        echoData($category);
    })->name("/categories/x");

});


// Run!
$app->run();

function paginate($request, $cursor) {
    $size = (int)$request->params("size", 10);
    $page = (int)$request->params("page", 1);
    $sort = $request->params("sort", "id");
    if ($sort == "id") $sort = "_id";
    return $cursor->skip($size * ($page - 1))->limit($size)->sort(array($sort));
}

function selectFields($allowed, $request, $default = null) {
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
    return $fields;
}

function dbToJson($cursor) {
    $isArray = $cursor->count() > 1;
    $json = array();
    foreach ($cursor as $k => $row) {
        // Replace ID
        if (isset($row["_id"])) {
            $row["id"] = $row["_id"];
            unset($row["_id"]);
        }
        if ($isArray) {
            $json [] = $row;
        } else {
            return $row;
        }
    }


    return $json;
}

function makeDownloadFile($resource, $type = ".jar") {
    $resource = (string)$resource;
    $split = str_split($resource);

    $finalFolder = "/home/spiget/resources/download/";
    for ($i = 0; $i < count($split) - 1; $i++) {
        $s = $split [$i];
        $finalFolder .= $s . "/";
    }

    return $finalFolder . $resource . $type;
}


function echoData($json, $status = 0) {
    $app = \Slim\Slim::getInstance();

    $app->response()->header("X-Api-Time", time());
    $app->response()->header("Cache-Control", "public, max-age=3600, s-maxage=3600" /* 2 Hours Cache */);
    $app->response()->header("Expires", gmdate('D, d M Y H:i:s', strtotime('+1 hour')) . " GMT");
    //$app->response()->header("Last-Modified", gmdate("D, d M Y H:i:s", getDBStats() ["fetcher"] ["start"]));
    $app->response()->header("Connection: close");

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

//** Database **//

function resources() {
    $db = db();
    return $db->resources;
}

function authors() {
    $db = db();
    return $db->authors;
}

function stats() {
    $db = db();
    return $db->stats;
}

function categories() {
    $db = db();
    return $db->categories;
}

function requests() {
    $db = db();
    return $db->requests;
}

function requestsPrecise() {
    $db = db();
    return $db->requests_precise;
}

function methods() {
    $db = db();
    return $db->methods;
}

function webhooks() {
    $db = db();
    return $db->webhooks;
}
