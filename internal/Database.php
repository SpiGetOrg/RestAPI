<?php
function db()
{
    extract(json_decode(file_get_contents("../../internal/mongo-v2.json"), true));
    $auth = "";
    if (!is_null($login['user']) && !is_null($login['pass'])) {
        $auth = $login['user'] . ':' . $login['pass'] . '@';
    }
    try {
        $mongo = new MongoClient ('mongodb://' . $auth . $host . ':' . $port . '/' . $login['db']);
        $db = $mongo->selectDB($database);
    } catch (Exception $e) {
        exit("Database connection failed");
    }
    return $db;
}

//** Database **//

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