<?php

## DATABASE
$dbname = "authors";
require __DIR__ . "/database.php";





## API
$app = new \Phalcon\Mvc\Micro();



// Get by id + Get all + Ping liveness readiness
$app->get("/authors/{id}", function ($id) use ($app, $connection, $dbname) {
    list($url, $limit, $offset) = array_values($app->request->getQuery());

    if($id === 'ping') {
        return $app->response->setStatusCode(200, 'Found')->setJsonContent(["code"=>"OK"])->send();
    }

    if($id === 'count') {
        $datas = $connection->fetchAll("SELECT COUNT(*) FROM $dbname");
        if(!$datas) {
            return $app->response->setStatusCode(404, 'Not Found')->send();
        }        
        return $app->response->setStatusCode(200, 'Found')->setJsonContent(["count" => current(array_shift($datas))])->send();
    }

    if($id === 'all') {
        $datas = $connection->fetchAll("SELECT * FROM $dbname LIMIT $limit, $offset");
        if(!$datas) {
            return $app->response->setStatusCode(404, 'Not Found')->send();
        }
        return $app->response->setStatusCode(200, 'Found')->setJsonContent($datas)->send();
    }

    if(!$id) {
        return $app->response->setStatusCode(404, 'Not Found')->send();
    }
    $data = $connection->fetchOne("SELECT * FROM $dbname WHERE books='$id'");
    if(!$data) {
        return $app->response->setStatusCode(404, 'Not Found')->send();
    }
    return $app->response->setStatusCode(200, 'Found')->setJsonContent($data)->send();
});



// Post 
$app->post("/authors", function () use ($app, $connection, $dbname) {
    $payload = json_decode($app->request->getRawBody(), true);
    if (!isset($payload['id'])) {
        return $app->response->setStatusCode(422, 'Unprocessable Entity')->send();
    }
    // Test if exists
    $data = $connection->fetchOne("SELECT * FROM $dbname WHERE id=" . $payload['id']);
    if ($data) {
        return $app->response->setStatusCode(304, 'Not Modified')->send();
    }
    $connection->insert($dbname, array_values($payload), array_keys($payload) );
    $insert_id = $connection->lastInsertId();
    return $app->response->setStatusCode(201, 'Created')->setJsonContent(['id' => $insert_id])->send();
});



// Patch
$app->patch("/authors/{id}", function ($id) use ($app, $connection, $dbname) {
    $payload = json_decode($app->request->getRawBody(), true);
    // Test if exists
    $data = $connection->fetchOne("SELECT * FROM $dbname WHERE id=" . $id);
    if(!$data) {
        return $app->response->setStatusCode(404, 'Not Found')->send();
    }
    $connection->update($dbname, array_keys($payload), array_values($payload), ["conditions" => "id = $id"]);
    return $app->response->setStatusCode(204, 'No Content')->setJsonContent(['id' => $id])->send();
});



// Delete
$app->delete("/authors/{id}", function ($id) use ($app, $connection, $dbname) {

    if($id === 'all') {
        $connection->delete($dbname, "");
        // Set Headers
        return $app->response->setStatusCode(204, 'Resource Deleted Successfully')->send();
    }    

    $connection->delete($dbname, "id = $id");
    // Set Headers
    return $app->response->setStatusCode(204, 'Resource Deleted Successfully')->send();
});



// Bulk - sync - without testing 
$app->post("/authors/{bulk}", function ($bulk) use ($app, $connection, $dbname) {
    if($bulk !== 'bulk') {
        return $app->response->setStatusCode(304, 'Not Modified')->send();
    }

    try {
        $payload = json_decode($app->request->getRawBody(), true);
        foreach ($payload['authors'] as $book) {
            $connection->insert($dbname, array_values($book), array_keys($book) );
        }
        return $app->response->setStatusCode(201, 'Created')->setJsonContent(['bulk' => 'OK'])->send();
    } catch (\Exception $e) {
        return $app->response->setStatusCode(304, 'Not Modified')->send();
    }
});



// Error 404
$app->notFound( function () use ($app) {
    return $app->response->setStatusCode(404, 'Not Found')->send();
});



$app->handle((new \Phalcon\Http\Request())->getURI());