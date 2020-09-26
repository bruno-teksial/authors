<?php

use Phalcon\Mvc\Micro;
use Phalcon\Http\Request;
use Phalcon\Http\Response;

## DATABASE
$dbname = "authors";
require __DIR__ . "/database.php";


## API
$app = new Micro();

// Get All
$app->get("/all/limit={limit}&offset={offset}", function ($limit, $offset) use ($app, $connection, $dbname) {
// echo $limit, $offset; exit();
    $datas = $connection->fetchAll("SELECT * FROM $dbname LIMIT $limit");
    if(!$datas) {
        return $app->response->setStatusCode(404, 'Not Found')->send();
    }
    return $app->response->setStatusCode(200, 'Found')->setJsonContent($datas)->send();
});



// Get by id
$app->get("/{id}", function ($id) use ($app, $connection, $dbname) {
    if(!$id) {
        return $app->response->setStatusCode(404, 'Not Found')->send();
    }
    $data = $connection->fetchOne("SELECT * FROM $dbname WHERE id='$id'");
    if(!$data) {
        return $app->response->setStatusCode(404, 'Not Found')->send();
    }
    return $app->response->setStatusCode(200, 'Found')->setJsonContent($data)->send();
});



// Post 
$app->post("/", function () use ($app, $connection, $dbname) {
    $payload = json_decode($app->request->getRawBody(), true);
    if (!isset($payload['id'])) {
        return $app->response->setStatusCode(422, 'Unprocessable Entity')->send();
    }
    // Test if exists
    $data = $connection->fetchOne("SELECT * FROM $dbname WHERE id='" . $payload['id'] . "'");
    if ($data) {
        return $app->response->setStatusCode(304, 'Not Modified')->send();
    }
    $connection->insert($dbname, array_values($payload), array_keys($payload) );
    $insert_id = $connection->lastInsertId();
    return $app->response->setStatusCode(201, 'Created')->setJsonContent(['id' => $insert_id])->send();
});


// Delete 
$app->delete("/{id}", function ($id) use ($app, $connection, $dbname) {
    $connection->delete($dbname, "id = '$id'");
    // Set Headers
    return $app->response->setStatusCode(204, 'Resource Deleted Successfully')->send();
});



// Error 404
$app->notFound( function () use ($app) {
    return $app->response->setStatusCode(404, 'Not Found')->send();
});

$app->handle((new Request())->getURI());