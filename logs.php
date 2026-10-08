<?php require 'inc/lib.php';need('admin');$p=db();
$a=$_GET['a']??'';$u=$_GET['u']??'';
$ok=fn($d)=>is_string($d)&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)&&strtotime($d)!==false;
$from=$ok($_GET['from']??'')?$_GET['from']:'';$to=$ok($_GET['to']??'')?$_GET['to']:'';
if($from&&$to&&$from>$to)[$from,$to]=[$to,$from];
$all=!empty($_GET['all']);$pg=max(1,(int)($_GET['p']??1));$per=$all?5000:50;
$W=' WHERE (?="" OR l.action=?) AND (?="" OR l.username=?) AND (?="" OR l.created_at>=?) AND (?="" OR l.created_at<?)';
$args=[$a,$a,$u,$u,$from,$from?"$from 00:00:00":'',$to,$to?date('Y-m-d',strtotime("$to +1 day")):''];
$c=$p->prepare('SELECT COUNT(*) FROM audit_log l'.$W);$c->execute($args);$tot=(int)$c->fetchColumn();$pages=$all?1:max(1,(int)ceil($tot/$per));$pg=$all?1:min($pg,$pages);
$s=$p->prepare('SELECT l.*,h.household_code FROM audit_log l LEFT JOIN households h ON h.id=l.household_id'.$W.' ORDER BY l.id DESC LIMIT '.$per.' OFFSET '.(($pg-1)*$per));$s->execute($args);$L=$s->fetchAll();
$us=$p->query('SELECT DISTINCT username FROM audit_log ORDER BY username')->fetchAll(PDO::FETCH_COLUMN);
$cl=['ADD'=>'c0','EDIT'=>'c1','DELETE'=>'c2','MERGE'=>'c2'];
$qs=fn($x=[])=>'?'.http_build_query(array_merge(['a'=>$a,'u'=>$u,'from'=>$from,'to'=>$to],$x));
$today=date('Y-m-d');$chips=['Today'=>[$today,$today],'Last 7 days'=>[date('Y-m-d',strtotime('-6 days')),$today],'Last 30 days'=>[date('Y-m-d',strtotime('-29 days')),$today],'This month'=>[date('Y-m-01'),$today]];
$ft=[];if($a!=='')$ft[]="Action: $a";if($u!=='')$ft[]="User: $u";if($from)$ft[]="From: $from";if($to)$ft[]="To: $to";
head('Audit log',true);?><style media="print">@page{size:A4 landscape;margin:10mm 10mm 14mm;@bottom-right{content:"Page " counter(page) " of " counter(pages);font-size:9px;color:#555}}</style>
<h2 class="noprint">Audit log</h2>
<form class="filters noprint" method="get"><select name="a"><option value="">All actions</option><?php foreach(['ADD','EDIT','DELETE','MERGE','DISMISS','EXPORT','PRINT','LOGIN','USER'] as $x)echo '<option'.($x==$a?' selected':'').">$x</option>";?></select><select name="u"><option value="">All users</option><?php foreach($us as $x)echo '<option'.($x==$u?' selected':'').'>'.e($x).'</option>';?></select>
<label class="dt">From <input type="date" name="from" value="<?=e($from)?>"></label><label class="dt">To <input type="date" name="to" value="<?=e($to)?>"></label>
<button class="btn p">Filter</button><a class="btn" href="logs.php">Reset</a></form>
<p class="noprint chips">Quick range: <?php foreach($chips as $l=>[$f,$t])echo '<a class="btn sm" href="'.e($qs(['from'=>$f,'to'=>$t])).'">'.e($l).'</a> ';?></p>
<div class="printonly"><h2>Audit Log</h2><p>Printed <?=date('F j, Y g:i A')?> by <?=e(user()['fullname'])?> · <?=count($L)?> of <?=$tot?> entr<?=$tot==1?'y':'ies'?><?=$ft?' · '.e(implode(' · ',$ft)):''?></p></div>
<div class="card scroll plist"><table><thead><tr><th>When</th><th>User</th><th>Action</th><th>Household</th><th>Details</th><th>IP</th></tr></thead><tbody>
<?php foreach($L as $r):?><tr><td class="nowrap"><?=e($r['created_at'])?></td><td><?=e($r['username'])?></td><td><span class="tag <?=$cl[$r['action']]??'cg'?>"><?=e($r['action'])?></span></td><td class="nowrap"><?php if($r['household_id']){echo $r['household_code']?'<a href="sheet.php?id='.(int)$r['household_id'].'">'.e($r['household_code']).'</a>':'#'.(int)$r['household_id'].' (removed)';}?></td><td><?=e($r['details'])?></td><td><?=e($r['ip'])?></td></tr><?php endforeach;if(!$L)echo '<tr><td colspan="6" class="mut">No log entries match.</td></tr>';?></tbody></table></div>
<p class="noprint"><?=$tot?> entr<?=$tot==1?'y':'ies'?><?=$all?($tot>$per?' · showing the newest '.$per:' · showing all'):" · page $pg of $pages"?> <?php if(!$all){if($pg>1)echo '<a class="btn" href="'.e($qs(['p'=>$pg-1])).'">Previous</a> ';if($pg<$pages)echo '<a class="btn" href="'.e($qs(['p'=>$pg+1])).'">Next</a> ';}?>
<button type="button" class="btn" onclick="print()">Print this page</button> <a class="btn p" href="<?=e($qs(['all'=>1,'print'=>1]))?>">Print all <?=$tot?> matching</a></p>
<?php if(!empty($_GET['print'])):?><script>addEventListener('load',function(){setTimeout(function(){print()},300)})</script><?php endif;foot();
