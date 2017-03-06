<?php
include 'config.php';
include 'DBConnector.php';
include 'RequestHandler.php';
include 'QuestionHandler.php';

/**
 * This is the central endpoint of the API
 *
 * It loads all of the classes used by the API, processes the request, and returns the response
 * PHP only has the $_GET and $POST super-globals
 * For a correctly built API we also need to handle $_PUT and $_DELETE (even though this example will only use GET)
 * To keep things consistent we will just use $_REQUEST to get the data
 * And then use the method type to define our GET, POST, PUT, DELETE behavior
 */
$request = $_REQUEST;
/**
 * The uri is usually used to specify what the user wants from the API request.
 */
$uri = $_SERVER['REQUEST_URI'];
/**
 * In general, it could be one of: GET, POST, PUT, DELETE
 */
$method = $_SERVER['REQUEST_METHOD'];

// instantiate our handler with the received data
$db_conn = new DBConnector(Config::$db_host, Config::$db_user, Config::$db_password, Config::$db_database);
$request_handler = new RequestHandler($db_conn, $uri, $method, $request);

// handle the request
$request_handler->handleRequest();