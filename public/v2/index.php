<?php
include("../../internal/Slim.php");
include("../../internal/Database.php");

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
    "downloads",

    "hidden");
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
    "reviews",

    "hidden"
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

$GLOBALS["DEFAULT_ICON_DATA"] = "iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAYAAADimHc4AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyJpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NTIzMjhDRTRFNjA0MTFFMzg3NzM5NzIzMkUzMEY0NEEiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NTIzMjhDRTNFNjA0MTFFMzg3NzM5NzIzMkUzMEY0NEEiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoV2luZG93cykiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDowRDRBRDc0NTZFODExMUUzOEU1QThBN0IyQUE4MUIxOSIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDowRDRBRDc0NjZFODExMUUzOEU1QThBN0IyQUE4MUIxOSIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PqJNnrAAAATNSURBVHja7J1baBZHHMU3jdiIWh+8oBG8++ANrQ9Fq1hRi+iDoiJFSy1VqZdqxdKrmDeltIqCpsaWGlFREWnTIFYsiA9FBB+KQSs+KF5AG6p9aItYSiQ9f/YEgv1IdjbfujPrOXD4kuzO7M7/NzM7s7vzpaK1tTWS8tMLCoEACIAkAAIgCYAASAIgAJIACIAkAAIgCYAASAIgAJIACIAkAIVVt7xPYNykV9Ikq4YXwZvgSngX3ADfd83o18uX1AIcA78GvgDXwqPhEfz5ArdVqwsqv/rDm+Gz8H54WIl9hnHbWe7bXwC6riq4Bj7HbmZ8gjTjua+l2Qr30DXAXT3htfBKeGzKPCbQy+EDbB2P1AI6Vj94HWxXxp1dCH57jWFel5h3PwH4vwaytl+E9zkE/gadRGOZ90Uea6APBa/I891QDEH74mMJvIHdRVLdhI/AX/N3G/28BY90yOMKR0/fYSj6x3MFAIG3a896+G14skPSe/C38CH41lPbhjO/1fBghzx/gQ/DXwFES6EBIPA2adrI2uoS+PusrScTdDmj4KVsVdWOIKxV7QWIJ4UCgMD3xscy1vqJDkmbrWbC9SlmudXs699z7O+beK04DhB/Bw0AgR+Aj3nwx44jmjvwMauN8G9dPI1BbHU2HB3qkO4a/CV8BiB+DwoAAt+d3cBHjjXeupcTcB37+3JqMIehb7CbcmkRO6z7A4h/QwFgo5N3HZLchQ+yq7mbcasfwq7pHf6cVN8AwJpQANhoojLhqKaOtf5G9Gw1iq1hXcJR0xMAKPudg6wmYv90sK2F/foWeBq8PYfgt3V323kOW3hOLSnL5B2AjoZxe6zGoTZ9Dt/JeyZq52DnwlawJ2WZgroVMYS1zjdNc7wmeH0N+BMfL3Wy2/ecgTbmHPgFnEEv7mS/v9BS+hShBbTJCvwD/BMnac9ay3jsxgTBz0w+3A19nZOu81F8Yy5rLeGxjvHYucqn5wEz4aNR/CTLuoVeZcy7J/M8x2PM9KXQvj2QeRGexW7hNLywiyB6MY/TzHMWjxEJQOeawWtEA2uvyySoG9M0MI/XfC1kCG9FzGHtPQOvSLD/Cu7byLReK6T3giyY9iDmZ3hVie0rue1QCIFv31RD03ROmizgtfybPXyZavOa0AoTIoCIgX4VnhJgSy4EgCj0wBemAAIgCYAASAIgAJIACIAkAAIQq7KAsaoMCUBVAQFUhQTgQAEB1IcE4P0oXgPQVIDAN7EsG7LI3NfX031QuK+nlwCRdoFGXjW+GAs0SoBoW6Jkz21f9izwl6P4cWbxliiVANGdfeqbkdtasSxka8PsXaHaLBZgeAmgHYi0y1TLoed3mepTEOzDFtHNhz+L3JYPpZGtC7DX0X+Em/V1NbGaOc6eygv1tYxGNet5jHoes7C3ItLqYRQvWbJm8Ql8vQx5Wh6fMs86HsMb+Xoz7hHH4HaBrmF/naaPr2EeX0QeflOKzwDa9BjeBs+GP4CvJkhzlfvOZtrHPhcwlNvRD+Dd8Nwo/h6h2yX2uc1tc7nvgxAKFtqLWfZ1BbYG+VQUr2r5kH+3gJ+MUnxpX96q0L8yVBckAJIACIAkAAIgCYAASAIgAJIACIAkAAIgCYAASAIgAJIACICUjf4TYAAW6zkIYPlZrwAAAABJRU5ErkJggg==";
$GLOBALS["DEFAULT_ICON_URL"] = "https://static.spigotmc.org/styles/spigot/xenresource/resource_icon.png";
$GLOBALS["DEFAULT_AVATAR_DATA"] = "iVBORw0KGgoAAAANSUhEUgAAADAAAAAwCAYAAABXAvmHAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2ZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo4QUFGMTlEMTUxRkRFMjExQTVBMTlGQUQ3OTQ4QkQxMCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDowQTIyRjREMDdGMjIxMUUzQkE1NThGRDdDNTc5MkFBNiIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDowQTIyRjRDRjdGMjIxMUUzQkE1NThGRDdDNTc5MkFBNiIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M2IChXaW5kb3dzKSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjIxMDM1QkRGODM2M0UzMTE4Qjk5QUIyRjRFRjZDNzUxIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjhBQUYxOUQxNTFGREUyMTFBNUExOUZBRDc5NDhCRDEwIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+RfxUUgAAAbNJREFUeNpi/P//P8NQBkwMQxyMemDUA6MeGPXAqAdGPTCkAQspirUNzIhRJgjEmUDsC9ICFbsKxFuAeBoQv0dWfPXCKfp5gAjgAsRLgFgcTdwCinOBOAaI9wzGJARy4G4sjkcG4lA1FoPNA6CYXEOC+rXUin1qeSAAiKVJUC8F1TNoPKBPhh6DoV6M/h9MHvhLJz008wAjnfTQtBQiFTAPJg98IEPP58Hkge1k6Nk3mDxwBVrDEgvOAPHpwVaMpgDxbyLVJg7GttAjIHYF4u9E1NpXBmt/4CC0Vl4OxJ+QxL9C2z+g2nfjgPUHiAS3gTgKytaC0jeA+N+Ad2hIADLQpHQLyhcAYk4gfjqYu5Si0Iy8E4gfAvEbaKYG4bfQPLITqkaUWpYykjI6jaVLyQrEHkAcBu1C8hNp1Ecg3gzEq4B4B7Bb+ZveHjAH4lAgDgJiRQoD8T4QrwPi1UCPnKS1B0ChXAvEpjTKO6AKrgHoka20ysTrqdUIwwFMoMUsC60yMS0dT5YdI2tgCwgqgbichjEB6qV10iwTD0YwOrg76oFRD4x6YNQDox4Y9cBAAoAAAwCVnli0jNHTPgAAAABJRU5ErkJggg==";
$GLOBALS["DEFAULT_AVATAR_URL"] = "https://static.spigotmc.org/styles/spigot/xenforo/avatars/avatar_female_s.png";

$GLOBALS["MAX_PAGE_SIZE"] = 15000;

$app = new Slim\Slim();
$app->notFound(function () use ($app) {
    echoData(array("error" => "invalid route"));
});
$app->hook("slim.before", function () use ($app) {
    header("Access-Control-Allow-Origin: *");
    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
        header("Access-Control-Request-Headers: X-Requested-With, Accept, Content-Type, Origin");
        exit;
    }
});
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
    }
});

$app->get("/", function () use ($app) {
    $app->redirect("/v2/status");
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
        $category = dbToJson($cursor);

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
        $cursor = paginate($app, resource_reviews()->find());
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
        $cursor = resource_reviews()->find(array("_id" => (int)$review));
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
$app->group("/metrics", function () use ($app) {

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
            "/^(.*)(\\/)([0-9]+)\\.([0-9]+)\\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\\.[0-9A-Za-z-]+)*))?(?:\\+[0-9A-Za-z-]+)?$/",// SemVer with slash
            "/^(.*)([0-9]+)\\.([0-9]+)\\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\\.[0-9A-Za-z-]+)*))?(?:\\+[0-9A-Za-z-]+)?$/",// SemVer without slash
            "/(.*)\\/([0-9]+)\\.([0-9]+)\\.([0-9]+)_([0-9]+)/"// Java
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

    $app->get("/map/:days", function ($days) use ($app) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

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

        $ips = array();
        foreach ($cursor as $doc) {
            if (!in_array($doc["ip"], $ips)) {
                $ips[] = $doc["ip"];
            }
        }

        try {
            $ch = curl_init("http://ip2nation.inventivetalent.org");
            curl_setopt($ch, CURLOPT_HEADER, "User-Agent: " . $_SERVER["HTTP_USER_AGENT"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 2);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "query=" . json_encode($ips) . "&associative=true");

            $result = curl_exec($ch);
            curl_close($ch);

            $countryMap = json_decode($result, true);
        } catch (Exception $e) {
            echoData(array("error" => "Failed to connect to IP database"));
            return;
        }

        $data = array();
        foreach ($cursor as $doc) {
            $ip = $doc["ip"];
            $count = $doc["count"];
            $country = $countryMap[$ip];
            if (is_null($country)) {
                continue;
            }

            if (!isset($data[$country])) {
                $data[$country] = array("code" => $country, "value" => $count);
            } else {
                $data[$country]["value"] += $count;
            }
        }

        $data = array_values($data);
        echoData($data);
    })->name("/metrics/map");

});


// Run!
$app->run();

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
    return $fields;
}

function dbToJson($cursor, $forceArray = false)
{
    $isArray = $cursor->count() > 1;
    $json = array();
    foreach ($cursor as $k => $row) {
        if (isset($row["hidden"]) && $row["hidden"] === true) {
            // Satisfy the one person who feels like they need to hide their resources.
            continue;
        }

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
        if (empty($source["icon"]["data"])) {
            echo $defaultData;
        } else {
            echo $source["icon"]["data"];
        }
    } else if ($type === "go") {
        if (empty($source["icon"]["url"])) {
            header("Location: " . $defaultUrl);
        } else {
            header("Location: https://spigotmc.org/" . $source["icon"]["url"]);
        }
    } else if ($type === "url") {
        if (empty($source["icon"]["url"])) {
            echo $defaultUrl;
        } else {
            echo "https://spigotmc.org/" . $source["icon"]["url"];
        }
    } else {
        header("Content-Type: image/jpeg");
        if (empty($source["icon"]["data"])) {
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

//** Database **//

function resources()
{
    $db = db();
    return $db->resources;
}

function resource_versions()
{
    $db = db();
    return $db->resource_versions;
}

function resource_updates()
{
    $db = db();
    return $db->resource_updates;
}

function resource_reviews()
{
    $db = db();
    return $db->resource_reviews;
}

function authors()
{
    $db = db();
    return $db->authors;
}

function status()
{
    $db = db();
    return $db->status;
}

function categories()
{
    $db = db();
    return $db->categories;
}

function requests()
{
    $db = db();
    return $db->requests;
}

function requestsPrecise()
{
    $db = db();
    return $db->requests_precise;
}

function methods()
{
    $db = db();
    return $db->methods;
}

function webhooks()
{
    $db = db();
    return $db->webhooks;
}
