<?php
require "../../vendor/autoload.php";

include("../../internal/Config.php");
include("../../internal/Database.php");
include("../../internal/Utils.php");

date_default_timezone_set("UTC");

$app = new Slim\Slim();
$app->notFound(function () use ($app) {
    echoData(array("error" => "invalid route"));
});

// enforce allowable methods, middleware
$app->hook("slim.before", function () use ($app) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Expose-Headers: Content-Type, Content-Length, Location, X-Api-Time, X-Page-Sort, X-Page-Order, X-Page-Size, X-Max-Page-Size, X-Page-Index, X-Page-Count");
    if (!in_array($_SERVER["REQUEST_METHOD"], $GLOBALS["ALLOWED_METHODS"])) {
        header("Access-Control-Allow-Methods: " . implode(', ', $GLOBALS["ALLOWED_METHODS"]));
        header("Access-Control-Request-Headers: X-Requested-With, Accept, Content-Type, Origin");
        header("Access-Control-Allow-Headers: Spiget-User-Agent");
        exit;
    }
});

// request tracking middleware
$app->hook("slim.before.dispatch", function () use ($app) {
    $dontTrackMeHeader = $app->request()->headers("X-Do-Not-Track-Me", "false");
    if ($dontTrackMeHeader === "true") {
        $app->response()->header("X-Do-Not-Track-Me", "true");
    } else {
        if ($app->response()->isSuccessful()) {
            $ua = !empty ($_SERVER ['HTTP_USER_AGENT']) ? $_SERVER ['HTTP_USER_AGENT'] : "unknown";

            //StatusCake
            if (preg_match("/\\(StatusCake\\)/", $ua)) {
                $ua = "StatusCake";
            }
            if (strpos($ua, "Mozilla") !== false || strpos($ua, "Internet Explorer") !== false || strpos($ua, "AppleWebKit") !== false || strpos($ua, "Opera") !== false) {
                $ua = "default";
            }
            if (isset($_SERVER["HTTP_SPIGET_USER_AGENT"])) {
                $ua = $_SERVER["HTTP_SPIGET_USER_AGENT"];
            } else if (isset($_REQUEST["spiget___ua"])) {
                $ua = $_REQUEST["spiget___ua"];
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
    }
});

$app->get("/", function () use ($app) {
    $app->redirect("/v2/status");
});

$app->get("/status", function () use ($app) {
    $serverConfig = getServerConfig();
    $status = array(
        "server" => array(
            "name" => $serverConfig["server"]["name"],
            "mode" => $serverConfig["server"]["mode"]
        ),
        "fetch" => array(
            "start" => getStatus("fetch.start"),
            "end" => getStatus("fetch.end"),
            "page" => array(
                "amount" => getStatus("fetch.page.amount"),
                "index" => getStatus("fetch.page.index"),
                "item" => array(
                    "index" => getStatus("fetch.page.item.index"),
                    "state" => getStatus("fetch.page.item.state")
                )
            )
        ),
        "existence" => array(
            "start" => getStatus("existence.start"),
            "end" => getStatus("existence.end"),
            "document" => array(
                "amount" => getStatus("existence.document.amount"),
                "suspects" => getStatus("existence.document.suspects"),
                "index" => getStatus("existence.document.index"),
                "id" => getStatus("existence.document.id")
            )
        )
    );
    $status["fetch"]["active"] = $status["fetch"]["end"] === 0;
    $status["existence"]["active"] = $status["existence"]["end"] === 0;

    if (($app->request()->params("spigotStatus", "false") === "true") && ($spigotStatus = getSpigotStatus())) {
        $status["spigotmc"] = array(
            "status" => $spigotStatus["Status"],
            "online" => $spigotStatus["Status"] === "Up",
            "uptime" => $spigotStatus["Uptime"],
            "lastCheck" => $spigotStatus["LastTested"] . " GMT"
        );
    }

    $stats = array(
        "resources" => resources()->count(),
        "authors" => authors()->count(),
        "categories" => categories()->count(),
        "resource_updates" => resource_updates()->count(),
        "resource_versions" => resource_versions()->count()
    );

    echoData(array("status" => $status, "stats" => $stats));
})->name("/status");

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

    $app->get("/recentUpdates", function () use ($app) {
        $lastTime = time() - 7200;
        $cursor = paginate($app, resources()->find(array('$where' => "this.updateDate > $lastTime"), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/resources/recentUpdates");

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
        header("Connection: close");
        fpassthru($fp);
        fclose($fp);
        exit();
    })->name("/resources/x/download");

    $app->get("/:resource/icon(/:type)", function ($resource, $type = "image") use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id", "icon"));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id", "icon"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        echoImage($app, $resource, $type, $GLOBALS["DEFAULT_ICON_DATA"], $GLOBALS["DEFAULT_ICON_URL"]);
        exit();
    })->name("/resources/x/icon");

    $app->get("/:resource/author", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id", 'author.id'));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id", 'author.id'));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        $cursor = authors()->find(array("_id" => $resource['author']['id']));
        $author = dbToJson($cursor);

        echoData($author);
    })->name("/resources/x/author");

    $app->get("/:resource/versions/:version/download", function ($resource, $version) use ($app) {
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

        if ("latest" === $version) {
            $highest = 0;
            foreach ($resource["versions"] as $ver) {
                $id = $ver["id"];
                if ($id > $highest) {
                    $highest = $id;
                }
            }
            $cursor = resource_versions()->find(array('_id' => $highest));
        } else {
            $cursor = resource_versions()->find(array("_id" => (int)$version));
            if ($cursor->count() <= 0) {
                echoData(array("error" => "resource version not found"), 404);
                return;
            }
        }
        $version = dbToJson($cursor);

        header("Location: https://spigotmc.org/resources/" . $resource["id"] . "/download?version=" . $version["id"]);
    })->name("/resources/x/versions/x/download");

    $app->get("/:resource/versions/:version", function ($resource, $version) use ($app) {
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

        if ("latest" === $version) {
            $highest = 0;
            foreach ($resource["versions"] as $ver) {
                $id = $ver["id"];
                if ($id > $highest) {
                    $highest = $id;
                }
            }
            $cursor = resource_versions()->find(array('_id' => $highest));
        } else {
            $cursor = resource_versions()->find(array("_id" => (int)$version));
            if ($cursor->count() <= 0) {
                echoData(array("error" => "resource version not found"), 404);
                return;
            }
        }
        $version = dbToJson($cursor);

        echoData($version);
    })->name("/resources/x/versions/x");

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
            $versionIds[] = $ver['id'];
        }
        $cursor = paginate($app, resource_versions()->find(array('_id' => array('$in' => $versionIds))));
        $versions = dbToJson($cursor, true);

        echoData($versions);
    })->name("/resources/x/versions");

    $app->get("/:resource/updates/:update", function ($resource, $update) use ($app) {
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

        if ("latest" === $update) {
            $highest = 0;
            foreach ($resource["updates"] as $up) {
                $id = $up["id"];
                if ($id > $highest) {
                    $highest = $id;
                }
            }
            $cursor = resource_updates()->find(array('_id' => $highest));
        } else {
            $cursor = resource_updates()->find(array("_id" => (int)$update));
            if ($cursor->count() <= 0) {
                echoData(array("error" => "resource update not found"), 404);
                return;
            }
        }
        $update = dbToJson($cursor);

        echoData($update);
    })->name("/resources/x/updates/x");

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
            $updateIds[] = $up['id'];
        }
        $cursor = paginate($app, resource_updates()->find(array('_id' => array('$in' => $updateIds))));
        $updates = dbToJson($cursor, true);

        echoData($updates);
    })->name("/resources/x/updates");

    $app->get("/:resource/reviews", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id", "reviews"));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id", "reviews"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        $reviewIds = array();
        foreach ($resource["reviews"] as $up) {
            $reviewIds[] = $up['id'];
        }
        $cursor = paginate($app, resource_reviews()->find(array('_id' => array('$in' => $reviewIds))));
        $reviews = dbToJson($cursor, true);

        echoData($reviews);
    })->name("/resources/x/reviews");

    $app->get("/:resource/go", function ($resource) use ($app) {
        if (is_numeric($resource)) {
            $cursor = resources()->find(array("_id" => (int)$resource), array("_id"));
        } else {
            $cursor = resources()->find(array("name" => $resource), array("_id"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $resource = dbToJson($cursor);

        header("Location: https://spigotmc.org/resources/" . $resource["id"] . "?ref=spiget");
    })->name("/resources/x/go");

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

    $app->get("/recentUpdates", function () use ($app) {
        $lastTime = time() - 7200;
        // We don't really have an 'update time' field, so just guess by the fetch time
        $cursor = paginate($app, authors()->find(array('$where' => "this.fetch.latest > $lastTime"), selectFields($GLOBALS["SPIGET_AUTHOR_LIST_FIELDS"], $app->request())));
        $authors = dbToJson($cursor, true);

        echoData($authors);
    })->name("/authors/recentUpdates");

    $app->get("/:author/resources", function ($author) use ($app) {
        if (is_numeric($author)) {
            $cursor = authors()->find(array("_id" => (int)$author), array("_id"));
        } else {
            $cursor = authors()->find(array("name" => $author), array("_id"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "author not found"), 404);
            return;
        }
        $author = dbToJson($cursor);

        $cursor = paginate($app, resources()->find(array('author.id' => $author["id"]), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/authors/x/resources");

    $app->get("/:author/reviews", function ($author) use ($app) {
        if (is_numeric($author)) {
            $cursor = authors()->find(array("_id" => (int)$author), array("_id"));
        } else {
            $cursor = authors()->find(array("name" => $author), array("_id"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "author not found"), 404);
            return;
        }
        $author = dbToJson($cursor);

        $cursor = paginate($app, resource_reviews()->find(array('author.id' => $author["id"])));
        $reviews = dbToJson($cursor, true);

        echoData($reviews);
    })->name("/authors/x/reviews");

    $app->get("/:author/avatar(/:type)", function ($author, $type = "image") use ($app) {
        if (is_numeric($author)) {
            $cursor = authors()->find(array("_id" => (int)$author), array("_id", "icon"));
        } else {
            $cursor = authors()->find(array("name" => $author), array("_id", "icon"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "author not found"), 404);
            return;
        }
        $author = dbToJson($cursor);

        echoImage($app, $author, $type, $GLOBALS["DEFAULT_AVATAR_DATA"], $GLOBALS["DEFAULT_AVATAR_URL"]);
        exit();
    });

    $app->get("/:author/go", function ($author) use ($app) {
        if (is_numeric($author)) {
            $cursor = authors()->find(array("_id" => (int)$author), array("_id"));
        } else {
            $cursor = authors()->find(array("name" => $author), array("_id"));
        }
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "author not found"), 404);
            return;
        }
        $author = dbToJson($cursor);

        header("Location: https://spigotmc.org/members/" . $author["id"] . "?ref=spiget");
    })->name("/authors/x/go");

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
        $cursor = paginate($app, categories()->find());
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
        $category = dbToJson($cursor, true)[0];

        $cursor = paginate($app, resources()->find(array('category.id' => $category["id"]), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request())));
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/categories/x/resources");

    $app->get("/:category/go", function ($category) use ($app) {
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

        header("Location: https://spigotmc.org/categories/" . $category["id"] . "?ref=spiget");
    })->name("/category/x/go");

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

$app->group("/reviews", function () use ($app) {

    $app->get("/", function () use ($app) {
        $cursor = paginate($app, resource_reviews()->find(array(), selectFields($GLOBALS["SPIGET_REVIEW_LIST_FIELDS"], $app->request())));
        $reviews = dbToJson($cursor, true);

        echoData($reviews);
    })->name("/reviews");

    $app->get("/trends", function () use ($app) {
        $cursor = resource_reviews()->find();
        $data = array(
            "total" => $cursor->count(),
            "1" => 0,
            "2" => 0,
            "3" => 0,
            "4" => 0,
            "5" => 0
        );
        foreach ($cursor as $doc) {
            $rating = $doc["rating"]["average"];
            $data["$rating"]++;
        }

        echoData($data);
    })->name("/reviews/trends");

    $app->get("/:review", function ($review) use ($app) {
        $cursor = resource_reviews()->find(array("_id" => (int)$review), selectFields($GLOBALS["SPIGET_REVIEW_ALL_FIELDS"], $app->request()));
        $cursor->limit(1);
        if ($cursor->count() <= 0) {
            echoData(array("error" => "review not found"));
            return;
        }
        $review = dbToJson($cursor);

        // Resource ID
        $cursor = resources()->find(array("reviews" => array('$elemMatch' => array("id" => $review["id"]))));
        if ($cursor->count() > 0) {
            $review["resource"] = dbToJson($cursor)["id"];
        } else {
            $review["resource"] = -1;
        }

        echoData($review);
    })->name("/reviews/x");

});

$app->group("/search", function () use ($app) {

    $app->get("/resources/:query", function ($query) use ($app) {
        $field = $app->request()->params("field", "name");
        $searchFields = array(
            "name",
            "tag"
        );
        if (!in_array($field, $searchFields)) {
            echoData(array("error" => "invalid field"), 400);
            return;
        }

        $cursor = resources()->find(array($field => array('$regex' => new MongoRegex("/$query/i"))), selectFields($GLOBALS["SPIGET_RESOURCE_LIST_FIELDS"], $app->request(), array("_id", "name", "tag")));
        if ($cursor->count() <= 0) {
            echoData(array("error" => "resource not found"), 404);
            return;
        }
        $cursor = paginate($app, $cursor);
        $resources = dbToJson($cursor, true);

        echoData($resources);
    })->name("/search/resources");

    $app->get("/authors/:query", function ($query) use ($app) {
        $field = $app->request()->params("field", "name");
        $searchFields = array(
            "name"
        );
        if (!in_array($field, $searchFields)) {
            echoData(array("error" => "invalid field"), 400);
            return;
        }

        $cursor = authors()->find(array($field => array('$regex' => new MongoRegex("/$query/i"))), selectFields($GLOBALS["SPIGET_AUTHOR_LIST_FIELDS"], $app->request()));
        if ($cursor->count() <= 0) {
            echoData(array("error" => "author not found"), 404);
            return;
        }
        $cursor = paginate($app, $cursor);
        $authors = dbToJson($cursor, true);

        echoData($authors);
    })->name("/search/authors");

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
        if (getServerConfig()["isSlave"]) {
            echoData(array("error" => "Webhook modification is not supported on this API server"), 400);
            return;
        }

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
        if (getServerConfig()["isSlave"]) {
            echoData(array("error" => "Webhook modification is not supported on this API server"), 400);
            return;
        }

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
$app->group("/metrics", function () use ($app) {

    $app->get("/requests-new/:days", function ($days) use ($app) {
        $minTime = strtotime("00:00:00 GMT" . $days . " days ago");
        if ($minTime === false) {
            echoData(array("error" => "invalid time frame"), 400);
            exit ();
        }

        $cursor = metrics()->find(array("timestamp" => array('$gte' => $minTime)))->sort(array("timestamp" => 1));
        if ($cursor->count() <= 0) {
            echoData(array("error" => "could not find any data for the given time frame"), 400);
            return;
        }

        $data = array();

        foreach ($cursor as $item) {
            $global = $item["global"];
            $entry = array(
                "timestamp" => $item["timestamp"],
                "total" => $global["total"],
                "unique" => $global["unique"],
                "userAgents" => $global["userAgents"],
                "paths" => $global["paths"]
            );

            asort($entry["userAgents"]);
            asort($entry["paths"]);

            $data[] = $entry;
        }

        usort($data, function ($a, $b) {
            if ($a["timestamp"] === $b["timestamp"]) {
                return 0;
            };
            return ($a["timestamp"] < $b["timestamp"]) ? -1 : 1;
        });
        echoData($data);
    })->name("/metrics/requests");

    $app->get("/requests-new/:days", function ($days) use ($app) {
        $minTime = strtotime("00:00:00 GMT" . $days . " days ago");
        if ($minTime === false) {
            echoData(array("error" => "invalid time frame"), 400);
            exit ();
        }

        $cursor = metrics()->find(array("timestamp" => array('$gte' => $minTime)));
        if ($cursor->count() <= 0) {
            echoData(array("error" => "could not find any data for the given time frame"), 400);
            return;
        }

        $data = array();

        foreach ($cursor as $item) {
            $global = $item["global"];
            $data[] = array(
                "timestamp" => $item["timestamp"],
                "total" => $global["total"],
                "unique" => $global["unique"],
                "userAgents" => $global["userAgents"],
                "paths" => $global["paths"]
            );
        }

        echoData($data);
    })->name("/metrics/requests");

    $app->get("/requests/:days", function ($days) use ($app) {
        $minTime = strtotime("00:00:00 GMT" . $days . " days ago");
        if ($minTime === false) {
            echoData(array("error" => "invalid time frame"), 400);
            exit ();
        }

        $cursor = requests()->find(array("day" => array('$gt' => $minTime)));
        if ($cursor->count() <= 0) {
            echoData(array("error" => "invalid time frame"), 400);
            return;
        }

        $hourly = !is_null($app->request()->params("hourly"));
        $fullVersions = !is_null($app->request()->params("fullVersions"));


        $versionRegex = array(
            "/^(.*)(\\/)([0-9]+)\\.([0-9]+)(\\.([0-9]+))?(?:-([0-9A-Za-z-]+(?:\\.[0-9A-Za-z-]+)*))?(?:\\+[0-9A-Za-z-]+)?$/",// SemVer with slash
            "/^(.*)([0-9]+)\\.([0-9]+)(\\.([0-9]+))?(?:-([0-9A-Za-z-]+(?:\\.[0-9A-Za-z-]+)*))?(?:\\+[0-9A-Za-z-]+)?$/",// SemVer without slash
            "/^(Java)\\/(.*)$/",// Java
            "/^(ViaVersion) (.*)$/"// ViaVersion
        );

        $json = array();
        foreach ($cursor as $item) {
            $day = $item["day"];
            $ua = $item["ua"];
            $path = $item["path"];
            $count = $item["count"];

            if ($hourly) {
                $day = $day + (3600 * $item["hour"]);
            }

            if (!$fullVersions) {
                foreach ($versionRegex as $regex) {
                    if (preg_match($regex, $ua, $matches)) {
                        $ua = trim($matches[1]);
                    }
                }

                //TODO: remove this eventually (maybe)
                if (strpos($ua, "Java") === 0) {
                    $ua .= " #LazyDev";
                }
            }

            if (!isset($json["$day"])) {
                $json["$day"] = array(
                    "total" => 0,
                    "user_agents" => array(),
                    "methods" => array()
                );
            }
            if (!isset($json["$day"]["user_agents"]["$ua"])) {
                $json["$day"]["user_agents"]["$ua"] = $count;
            } else {
                $json["$day"]["user_agents"]["$ua"] += $count;
            }
            if (!isset($json["$day"]["methods"]["$path"])) {
                $json["$day"]["methods"]["$path"] = $count;
            } else {
                $json["$day"]["methods"]["$path"] += $count;
            }

            $json["$day"]["total"] += $count;
        }

        ksort($json);
        echoData($json);
    })->name("/metrics/requests");

});


// Run!
$app->run();