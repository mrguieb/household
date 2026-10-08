<?php
// Run once on an existing install: http://localhost/household/migrate.php  then DELETE this file.
require_once __DIR__.'/config.php';
$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec(file_get_contents(__DIR__.'/upgrade.sql'));
echo '<p>Upgrade complete: household IDs, audit log and duplicate tracker are ready. <b>Delete migrate.php now.</b></p><a href="index.php">Open the system</a>';
