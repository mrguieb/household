<?php require 'inc/lib.php';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){$s=db()->prepare('SELECT * FROM users WHERE username=?');$s->execute([trim($_POST['username'])]);$u=$s->fetch();
if($u&&password_verify($_POST['password'],$u['password_hash'])){session_regenerate_id(true);$_SESSION['u']=$u;logact('LOGIN',null,'Signed in');header('Location: index.php');exit;}$err='Wrong username or password.';}
head('Sign in');?><div class="card" style="max-width:380px;margin:10vh auto"><h2>Sign in</h2><form method="post"><label>Username<input name="username" required autofocus></label><br><label>Password<input type="password" name="password" required></label><p class="bad"><?=e($err)?></p><button class="btn p">Sign in</button></form></div><?php foot();
