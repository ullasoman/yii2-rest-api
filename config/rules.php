<?php

/**
 * @SWG\Parameter(
 *      description="ID of data",
 *      format="int64",
 *      in="path",
 *      name="id",
 *      required=true,
 *      type="integer"
 * )
 */

$fitegg = "../routes/fitegg";

$routes = [];
foreach (glob("{$fitegg}/*.php") as $filename) {
    $route = require($filename);
    $routes = array_merge($routes, $route);
}

return $routes;
