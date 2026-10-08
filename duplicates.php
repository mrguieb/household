<?php require 'inc/lib.php';need('admin');$p=db();$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){check();$a=$_POST['a']??'';
if($a==='dismiss'){$p->prepare('INSERT IGNORE INTO dup_ignore(gkey) VALUES(?)')->execute([$_POST['gkey']]);$msg='Marked as not a duplicate.';logact('DISMISS',null,'Marked households '.preg_replace('/[^0-9,]/','',$_POST['ids']??'').' as not duplicates');}
if($a==='restore'){$p->exec('DELETE FROM dup_ignore');$msg='Dismissed groups restored.';}
if($a==='keep'){$keep=(int)$_POST['keep'];$ids=array_filter(array_map('intval',explode(',',$_POST['ids'])));$n=0;
foreach($ids as $d){if($d===$keep)continue;$s=$p->prepare('SELECT * FROM households WHERE id=?');$s->execute([$d]);if($o=$s->fetch()){$p->prepare('DELETE FROM households WHERE id=?')->execute([$d]);$n++;logact('MERGE',$d,"Removed duplicate $o[household_code] $o[ln], $o[fn] ($o[brgy]); kept household #$keep");}}
$msg="Kept the selected record and deleted $n duplicate(s).";}}
$norm=fn($s)=>preg_replace('/\s+/',' ',mb_strtolower(trim((string)$s)));
$H=$p->query('SELECT * FROM households')->fetchAll();$HM=[];foreach($H as $h)$HM[$h['id']]=$h;
$G=[];
$by=[];foreach($H as $h)$by[$norm($h['ln']).'|'.$norm($h['fn'])][]=$h;
foreach($by as $rows){if(count($rows)<2)continue;$sub=[];foreach($rows as $r)if($r['birthdate'])$sub[$r['birthdate']][]=$r;$nulls=array_filter($rows,fn($r)=>!$r['birthdate']);
if(!$sub)$sub=[$rows];else foreach($sub as $b=>$_)$sub[$b]=array_merge($sub[$b],$nulls);
foreach($sub as $g)if(count($g)>1)$G[]=['type'=>'Same head of household (name and birthdate)','ids'=>array_column($g,'id'),'merge'=>true,'note'=>''];}
$by=[];foreach($H as $h){$k=strtoupper(preg_replace('/[\s-]/','',$h['ph_no']??''));if($k!=='')$by[$k][]=$h['id'];}
foreach($by as $k=>$ids)if(count($ids)>1)$G[]=['type'=>'Same PhilHealth number','ids'=>$ids,'merge'=>false,'note'=>"PhilHealth # $k"];
$by=[];foreach($p->query('SELECT household_id,name,age FROM members')->fetchAll() as $m){if(trim($m['name'])==='')continue;$by[$norm($m['name']).'|'.$m['age']][$m['household_id']]=$m['name'];}
foreach($by as $ids)if(count($ids)>1)$G[]=['type'=>'Same family member (name and age) in different households','ids'=>array_keys($ids),'merge'=>false,'note'=>'Member: '.reset($ids)];
$ign=$p->query('SELECT gkey FROM dup_ignore')->fetchAll(PDO::FETCH_COLUMN);$nIgn=0;$S=[];
foreach($G as $g){sort($g['ids']);$g['key']=md5($g['type'].implode(',',$g['ids']));if(in_array($g['key'],$ign)){$nIgn++;continue;}$S[]=$g;}
head('Duplicates',true);?><h2>Duplicate tracker</h2><p><b><?=e($msg)?></b></p>
<p class="mut">Suspected duplicates are found by head-of-household name and birthdate, shared PhilHealth numbers, and the same family member (name and age) listed under different households. Review each group before deleting anything.</p>
<?php if(!$S):?><div class="card">No suspected duplicates found.</div><?php endif;
foreach($S as $g):$ids=implode(',',$g['ids']);?><div class="card scroll" style="margin-bottom:14px"><h3><?=e($g['type'])?><?=$g['note']?' – '.e($g['note']):''?></h3><table><tr><th>ID</th><th>Head of household</th><th>Barangay</th><th>Birthdate</th><th>Members</th><th>PhilHealth #</th><th>Entered</th><th></th></tr>
<?php foreach($g['ids'] as $i):$h=$HM[$i];?><tr><td><?=e($h['household_code'])?></td><td><?=e("$h[ln], $h[fn] $h[mn]")?></td><td><?=e($h['brgy'])?></td><td><?=e($h['birthdate'])?></td><td><?=$h['total_members']?></td><td><?=e($h['ph_no'])?></td><td><?=e(substr($h['created_at'],0,10))?></td><td class="nowrap"><a class="btn" target="_blank" href="sheet.php?id=<?=$h['id']?>">View</a> <?php if($g['merge']):?><form method="post" style="display:inline" onsubmit="return confirm('Keep <?=e($h['household_code'])?> and permanently delete the other record(s) in this group?')"><input type="hidden" name="t" value="<?=csrf()?>"><input type="hidden" name="a" value="keep"><input type="hidden" name="ids" value="<?=$ids?>"><input type="hidden" name="keep" value="<?=$h['id']?>"><button class="btn d">Keep this, delete others</button></form><?php endif;?></td></tr><?php endforeach;?></table>
<form method="post" style="margin-top:8px"><input type="hidden" name="t" value="<?=csrf()?>"><input type="hidden" name="a" value="dismiss"><input type="hidden" name="gkey" value="<?=$g['key']?>"><input type="hidden" name="ids" value="<?=$ids?>"><button class="btn">Not a duplicate</button></form></div><?php endforeach;
if($nIgn):?><form method="post"><input type="hidden" name="t" value="<?=csrf()?>"><input type="hidden" name="a" value="restore"><?=$nIgn?> group(s) dismissed. <button class="btn">Show them again</button></form><?php endif;foot();
