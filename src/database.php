<?php

use Phalcon\Db\Column;
use Phalcon\Db\Adapter\Pdo\Sqlite;


if( !file_exists ("/tmp/$dbname.sqlite"))
{
    $connection = (new Sqlite([
        "dbname" => "/tmp/$dbname.sqlite",
    ]))->createTable($dbname, "", array(
        "columns" => array(
            new Column("id", array("type" => Column::TYPE_VARCHAR, "size" => 70,"notNull" => true)),
            new Column("author", array("type" => Column::TYPE_VARCHAR, "size" => 70,"notNull" => true)),
            new Column("books", array("type" => Column::TYPE_VARCHAR, "size" => 13, "notNull" => true,)),
        )
    ));
}

$connection = new Sqlite([
    "dbname" => "/tmp/$dbname.sqlite",
]);