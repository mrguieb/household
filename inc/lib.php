<?php
require_once __DIR__.'/../config.php';
session_start();
if(!function_exists('mb_strtolower')){function mb_strtolower($s){return strtolower($s);}function mb_substr($s,$a,$b=null){return substr($s,$a,$b);}}
function db(){static $p;if(!$p){$p=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}return $p;}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
const ROLES=['admin'=>'Administrator','worker'=>'Social Worker / Encoder','official'=>'Barangay Official (view only)'];
const IND=['inc'=>'Monthly family income','emp'=>'Employment','food'=>'Food security','hou'=>'Housing','hea'=>'Health access','edu'=>'Education','ast'=>'Assets','sup'=>'Support'];
const OPT=['inc'=>['Above poverty threshold','Below poverty threshold','Below food threshold'],'emp'=>['Regular','Irregular','None'],'food'=>['3 meals/day','2 meals/day','<2 meals/day'],'hou'=>['Concrete','Semi-concrete','Makeshift'],'hea'=>['Can afford care','Sometimes difficult','Cannot afford'],'edu'=>['All in school','Frequent absences','Out-of-school child'],'ast'=>['Adequate','Limited','Minimal'],'sup'=>['Self-sufficient','Occasional aid','Dependent on aid']];
function user(){return $_SESSION['u']??null;}
function can($a){$r=user()['role']??'';return match($a){'edit'=>in_array($r,['admin','worker']),'admin'=>$r==='admin',default=>(bool)$r};}
function need($a='view'){if(!user()){header('Location: login.php');exit;}if(!can($a)){http_response_code(403);exit('Access denied for your role.');}}
function csrf(){return $_SESSION['t']??=bin2hex(random_bytes(16));}
function check(){if(!hash_equals(csrf(),$_POST['t']??'')){http_response_code(400);exit('Invalid form token.');}}
function setting($k,$d){$s=db()->prepare('SELECT v FROM settings WHERE k=?');$s->execute([$k]);return $s->fetchColumn()?:$d;}
function total($h){$t=0;foreach(IND as $k=>$_)$t+=(int)$h['s_'.$k];return $t;}
function klass($t){return $t<=5?['Not indigent','c0']:($t<=10?['Low-income / Vulnerable','c1']:['Indigent','c2']);}
function peso($n){return '₱'.number_format((float)$n,2);}
function toks($s){return preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower((string)$s),-1,PREG_SPLIT_NO_EMPTY);}
function member_names($h,$m){$ht=array_merge(toks($h['fn']??''),toks($h['mn']??''),toks($h['ln']??''));$need=array_merge(toks($h['fn']??''),toks($h['ln']??''));$seen=false;$o=[];
foreach($m as $r){$n=trim($r['name']??'');if($n==='')continue;$t=toks($n);if(!$seen&&$t&&!array_diff($need,$t)&&!array_diff($t,$ht)){$seen=true;continue;}$o[]=$n;}
return $o;}
function famincome($h,$m){$hi=(float)($h['income']??0);$oth=0.0;$ht=array_merge(toks($h['fn']??''),toks($h['mn']??''),toks($h['ln']??''));$need=array_merge(toks($h['fn']??''),toks($h['ln']??''));$seen=false;
foreach($m as $r){$t=toks($r['name']??'');$inc=(float)($r['income']??0);
if(!$seen&&$t&&!array_diff($need,$t)&&!array_diff($t,$ht)){$seen=true;$hi=max($hi,$inc);}else $oth+=$inc;}
return $hi+$oth;}
function find_households($q='',$b='',$c=''){$p=db();$w=[];$a=[];
foreach(preg_split('/\s+/',trim($q),-1,PREG_SPLIT_NO_EMPTY) as $t){$l='%'.addcslashes($t,'%_\\').'%';
$w[]="(CONCAT_WS(' ',h.household_code,h.brgy,h.ln,h.fn,h.mn,h.civil,h.edu,h.occ,h.religion,h.contact,h.house_type,h.house_own,h.chronic_spec,h.ph_no,h.ph_cat,h.interviewer,h.birthdate,h.interview_date,h.age) LIKE ? OR EXISTS(SELECT 1 FROM members m WHERE m.household_id=h.id AND CONCAT_WS(' ',m.name,m.edu,m.occ,m.remarks) LIKE ?))";$a[]=$l;$a[]=$l;}
if($b!==''){$w[]='h.brgy=?';$a[]=$b;}
$s=$p->prepare('SELECT h.* FROM households h'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY h.ln,h.fn');$s->execute($a);
$rows=array_values(array_filter($s->fetchAll(),fn($h)=>$c===''||(int)klass(total($h))[1][1]===(int)$c));
$mem=[];if($rows){$ids=implode(',',array_map('intval',array_column($rows,'id')));foreach($p->query("SELECT * FROM members WHERE household_id IN ($ids) ORDER BY id")->fetchAll() as $m)$mem[$m['household_id']][]=$m;}
return [$rows,$mem];}
function logact($act,$hid=null,$details=''){try{$u=user();db()->prepare('INSERT INTO audit_log(user_id,username,action,household_id,details,ip) VALUES(?,?,?,?,?,?)')->execute([$u['id']??null,$u['username']??'-',$act,$hid,mb_substr((string)$details,0,1000),$_SERVER['REMOTE_ADDR']??'']);}catch(Throwable $x){}}
function head($title,$wide=false){$u=user();?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> – Household Profiling</title><link rel="stylesheet" href="assets/style.css?v=<?=@filemtime(__DIR__.'/../assets/style.css')?>"></head><body>
<?php if($u):?><header class="noprint"><h1>Household Profiling System</h1><nav><a href="index.php">Dashboard</a><a href="households.php">Households</a><?php if(can('edit')):?><a href="form.php">New household</a><?php endif;if(can('admin')):?><a href="duplicates.php">Duplicates</a><a href="logs.php">Audit log</a><a href="users.php">Users</a><?php endif;?></nav><span><?=e($u['fullname'])?> (<?=e(ROLES[$u['role']])?>)</span><a class="btn" href="logout.php">Sign out</a></header><?php endif;?><main<?=$wide?' class="wide"':''?>><?php }
function foot(){if(user())echo "<script>var _lp=0;addEventListener('beforeprint',function(){if(Date.now()-_lp<4000)return;_lp=Date.now();var d=new FormData();d.append('t','".csrf()."');d.append('page',location.pathname.split('/').pop());d.append('qs',location.search);navigator.sendBeacon('log_print.php',d)})</script>";echo '</main></body></html>';}
