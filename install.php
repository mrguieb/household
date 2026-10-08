<?php
// Run once: http://localhost/household/install.php  then DELETE this file.
require_once __DIR__.'/config.php';
$pdo=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec(file_get_contents(__DIR__.'/schema.sql'));
$pdo->exec('USE '.DB_NAME);
$s=$pdo->prepare('INSERT IGNORE INTO users(username,password_hash,fullname,role) VALUES(?,?,?,?)');
foreach([['admin','admin123','Administrator','admin'],['worker','worker123','Social Worker','worker'],['official','official123','Barangay Official','official']] as $u)$s->execute([$u[0],password_hash($u[1],PASSWORD_DEFAULT),$u[2],$u[3]]);
echo '<p>Database ready. Logins: admin/admin123, worker/worker123, official/official123. <b>Change these passwords, then delete install.php.</b></p><a href="login.php">Go to login</a>';
