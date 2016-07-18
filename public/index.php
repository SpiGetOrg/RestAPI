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
    "downloads",
    "icon",
    "reviews"
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
        $path = "/v2" . $app->router()->getCurrentRoute()->getName();
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

$app->get("/status", function () use ($app) {
    $status = array(
        "fetch" => array(
            "start" => getStatus("fetch.start"),
            "end" => getStatus("fetch.end"),
            "page" => array(
                "amount" => getStatus("fetch.page.amount"),
                "index" => getStatus("fetch.page.index"),
                "item" => array(
                    "index" => getStatus("fetch.page.item.index")
                )
            )
        )
    );
    $status["fetch"]["active"] = $status["fetch"]["end"] === 0;

    $stats = array(
        "resources" => resources()->count(),
        "authors" => authors()->count(),
        "categories" => categories()->count(),
        "resource_updates" => resource_updates()->count(),
        "resource_versions" => resource_versions()->count()
    );

    echoData(array("status" => $status, "stats" => $stats));
});

$app->group("/resources", function () use ($app) {

    $app->get("/", function () use ($app) {
        $cursor = paginate($app, resources()->find(array(), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/resources");

    $app->get("/new", function () use ($app) {
        $cursor = paginate($app, resources()->find(array('$where' => "this.releaseDate == this.updateDate"), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/resources/new");

    $app->get("/for/:versions", function ($versions = "") use ($app) {
        $method = $app->request()->params("method", "any");
        $versionArray = preg_split("/\\,/i", $versions);
        if ($method === "any") {
            $cursor = resources()->find(array("testedVersions" => array('$exists' => true, '$in' => $versionArray)), array("id", "name", "testedVersions"));
        } else if ($method === "all") {
            $cursor = resources()->find(array("testedVersions" => array('$exists' => true, '$all' => $versionArray)), array("id", "name", "testedVersions"));
        } else {
            echoData(array("error" => "Unknown method. Allowed: any, all"), 400);
            return;
        }
        $cursor = paginate($app, $cursor);
        $resources = dbToJson($cursor, true);

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

    $app->get("/:resource/author", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id", 'author.$id'));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id", 'author.$id'));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        $cursor = authors()->find(array("_id" => $resource['author']['$id']));
        $author = dbToJson($cursor);

        echoData($author);
    })->name("/resources/x/author");

    $app->get("/:resource/versions", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id", "versions"));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id", "versions"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        $versionIds = array();
        foreach ($resource["versions"] as $ver) {
            $versionIds[] = $ver['$id'];
        }
        $cursor = paginate($app, resource_versions()->find(array('_id' => array('$in' => $versionIds))));
        $versions = dbToJson($cursor);

        echoData($versions, true);
    })->name("/resources/x/versions");

    $app->get("/:resource/updates", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id", "updates"));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id", "updates"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        $updateIds = array();
        foreach ($resource["updates"] as $up) {
            $updateIds[] = $up['$id'];
        }
        $cursor = paginate($app, resource_updates()->find(array('_id' => array('$in' => $updateIds))));
        $updates = dbToJson($cursor);

        echoData($updates, true);
    })->name("/resources/x/updates");

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
        $cursor = paginate($app, authors()->find(array(), selectFields($GLOBALS["SPIGET_AUTHOR_LIST_FIELDS"], $app->request())));
        $authors = dbToJson($cursor, true);

        echoData($authors);
    })->name("/authors");

    $app->get("/:author/resources", function ($author) use ($app) {
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

        $cursor = paginate($app, resources()->find(array('author.$id' => $author["id"]), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/authors/x/resources");

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
        $categories = dbToJson($cursor, true);

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

        $cursor = paginate($app, resources()->find(array('category.$id' => $category["id"]), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/categories/x/resources");

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
$app->group("/webhook", function () use ($app) {

    $app->get("/events", function () use ($app) {
        $webhookEvents = array(
            "new-resource",
            "resource-update",
            "new-author");
        echoData(array("events" => $webhookEvents));
    })->name("/webhook/events");

    $app->post("/register", function () use ($app) {
        $url = $app->request()->post("url");
        $events = $app->request()->post("events", "[]");
        $events = json_decode($events, true);
        $salt = $app->request()->post("salt", hash("sha256", uniqid(), false));

        $webhookEvents = array(
            "new-resource",
            "resource-update",
            "new-author");

        if (empty($url)) {
            echoData(array("error" => "missing url"), 400);
            return;
        }
        if (empty($events)) {
            echoData(array("error" => "no events specified"), 400);
            return;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            echoData(array("error" => "malformed url"), 400);
        }
        foreach ($events as $event) {
            if (!in_array($event, $webhookEvents)) {
                echoData(array("error" => "unknown event: $event"));
                return;
            }
        }

        $webhooks = webhooks();
        $check = $webhooks->find(array(
            "url" => $url,
            "events" => $events
        ));
        if ($check->count() > 0) {
            echoData(array("error" => "duplicate webhook"), 400);
            return;
        }

        $id = hash("sha256", ($url . "_" . json_encode($events) . "_" . $app->request()->getIp() . "_" . time()), false);
        $secret = hash("sha512", $salt . $id . $salt . uniqid() . time() . $salt);
        $webhooks->insert(array(
            "_id" => $id,
            "url" => $url,
            "events" => $events,
            "secret" => $secret,
            "createdAt" => time(),

            // Placeholders
            "failedConnections" => new MongoInt32(0),
            "failStatus" => new MongoInt32(0)
        ));

        echoData(array(
            "id" => $id,
            "url" => $url,
            "events" => $events,
            "secret" => $secret));
    })->name("/webhook/register");

    $app->get("/status/:id", function ($id) use ($app) {
        $webhooks = webhooks();

        $document = $webhooks->find(array("_id" => $id), array("_id", "failStatus", "failedConnections"));
        if ($document->count() <= 0) {
            echoData(array("error" => "webhook not found"), 404);
            return;
        }
        $webhook = dbToJson($document);

        echoData(array(
            "id" => $id,
            "status" => $webhook["failStatus"],
            "failedConnections" => $webhook["failedConnections"]
        ));
    })->name("/webhook/status");


    $app->delete("/delete/:id/:secret", function ($id, $secret) use ($app) {
        $webhooks = webhooks();

        // Check if the ID exists first
        $document = $webhooks->find(array("_id" => $id), array("id"));
        if ($document->count() <= 0) {
            echoData(array("error" => "webhook not found"), 404);
            return;
        }

        // Then check the secret
        $document = $webhooks->find(array("_id" => $id, "secret" => $secret), array("id"));
        if ($document->count() <= 0) {
            echoData(array("error" => "Invalid secret"), 403);
            return;
        }

        // Finally remove if everything matches
        $webhooks->remove(array(
            "_id" => $id,
            "secret" => $secret
        ));
        echoData(array("success" => true));
    })->name("/webhook/delete");

});


// Run!
$app->run();

function paginate($app, $cursor) {
    $request = $app->request();
    $response = $app->response();

    $size = max((int)$request->params("size", 10), 1);
    $page = max((int)$request->params("page", 1), 1);
    $sort = $request->params("sort", "id");
    if ($sort == "id") $sort = "_id";

    $response->headers->set("X-Page-Size", "$size");
    $response->headers->set("X-Page-Index", "$page");
    $count = $cursor->count() / $size;
    $response->headers->set("X-Page-Count", "$count");

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

function dbToJson($cursor, $forceArray = false) {
    $isArray = $cursor->count() > 1;
    $json = array();
    foreach ($cursor as $k => $row) {
        // Replace ID
        if (isset($row["_id"])) {
            $row["id"] = $row["_id"];
            unset($row["_id"]);
        }
        if ($isArray || $forceArray) {
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
    $app->response()->header("Last-Modified", gmdate("D, d M Y H:i:s", (getStatus("fetch.start") / 1000)));
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

function getStatus($key) {
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

//** Database **//

function resources() {
    $db = db();
    return $db->resources;
}

function resource_versions() {
    $db = db();
    return $db->resource_versions;
}

function resource_updates() {
    $db = db();
    return $db->resource_updates;
}

function authors() {
    $db = db();
    return $db->authors;
}

function status() {
    $db = db();
    return $db->status;
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
