<?php require 'inc/lib.php';
if(!user()||$_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals(csrf(),$_POST['t']??''))exit;
$pg=$_POST['page']??'';parse_str(ltrim($_POST['qs']??'','?'),$q);
if($pg==='sheet.php'){$id=(int)($q['id']??0);$s=db()->prepare('SELECT household_code,ln,fn,brgy FROM households WHERE id=?');$s->execute([$id]);if($h=$s->fetch())logact('PRINT',$id,"Printed data sheet $h[household_code] $h[ln], $h[fn] ($h[brgy])");}
elseif($pg==='households.php'){$f=array_filter(array_intersect_key($q,['q'=>1,'b'=>1,'c'=>1]),fn($x)=>$x!=='');logact('PRINT',null,'Printed household list'.($f?' (filters: '.http_build_query($f).')':''));}
elseif($pg==='logs.php'&&can('admin')){$f=array_filter(array_intersect_key($q,['a'=>1,'u'=>1,'from'=>1,'to'=>1]),fn($x)=>$x!=='');logact('PRINT',null,'Printed audit log'.($f?' (filters: '.http_build_query($f).')':''));}
