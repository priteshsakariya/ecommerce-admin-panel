<?php
require_once 'config/app.php';

$auth = new Auth($db);
$auth->logout();

redirectTo('login.php');