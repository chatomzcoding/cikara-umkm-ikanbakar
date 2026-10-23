<?php

// CRUD-GENERATOR:LOAD-ROUTES
foreach (glob(__DIR__.'/crud/*.php') ?: [] as $crudRouteFile) {
    require $crudRouteFile;
}
