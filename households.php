<?php require 'inc/lib.php';need();$p=db();
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['del'])){need('admin');check();$d=(int)$_POST['del'];$s=$p->prepare('SELECT * FROM households WHERE id=?');$s->execute([$d]);if($o=$s->fetch()){$p->prepare('DELETE FROM households WHERE id=?')->execute([$d]);logact('DELETE',$d,"Deleted $o[household_code] $o[ln], $o[fn] ($o[brgy]), score ".total($o).'/16');}header('Location: households.php');exit;}
$q=trim($_GET['q']??'');$b=$_GET['b']??'';$c=$_GET['c']??'';
[$H,$MM]=find_households($q,$b,$c);
ob_start();?>
<div class="card scroll plist"><table><thead><tr><th>ID</th><th>Head of household</th><th>Family members</th><th>Barangay</th><th>Members</th><th>Monthly income</th><th>Score</th><th>Class</th><th class="noprint"></th></tr></thead><tbody>
<?php foreach($H as $h){$t=total($h);$k=klass($t);$m=$MM[$h['id']]??[];?>
<tr><td><?=e($h['household_code'])?></td><td><?=e("$h[ln], $h[fn] $h[mn]")?></td><td><?php $mn=member_names($h,$m);echo $mn?implode('<br>',array_map('e',$mn)):'<span class="mut">—</span>';?></td><td><?=e($h['brgy'])?></td><td><?=$h['total_members']?></td><td><?=peso(famincome($h,$m))?></td><td><?=$t?>/16</td><td><span class="tag <?=$k[1]?>"><?=$k[0]?></span></td><td class="noprint"><div class="acts"><a class="btn" href="sheet.php?id=<?=$h['id']?>">View / print</a> <?php if(can('edit')):?><a class="btn" href="form.php?id=<?=$h['id']?>">Edit</a><?php endif;if(can('admin')):?> <form method="post" style="display:inline" onsubmit="return confirm('Delete this record permanently?')"><input type="hidden" name="t" value="<?=csrf()?>"><button class="btn d" name="del" value="<?=$h['id']?>">Delete</button></form><?php endif;?></div></td></tr><?php }
if(!$H)echo '<tr><td colspan="9" class="mut">No households match your search.</td></tr>';?></tbody></table></div>
<?php $results=ob_get_clean();
if(isset($_GET['ajax'])){echo $results;exit;}
$brs=$p->query('SELECT DISTINCT brgy FROM households ORDER BY brgy')->fetchAll(PDO::FETCH_COLUMN);
head('Households',true);?><style media="print">@page{size:A4 landscape;margin:10mm 10mm 14mm;@bottom-right{content:"Page " counter(page) " of " counter(pages);font-size:9px;color:#555}}</style><h2 class="noprint">Households</h2>
<form id="flt" class="filters noprint" method="get">
<div class="srch"><input type="search" name="q" placeholder="Search anything: name, member, barangay, ID, PhilHealth, contact, occupation…" title="Several words must all match (in any field)" value="<?=e($q)?>" autocomplete="off" autofocus></div>
<select name="b"><option value="">All barangays</option><?php foreach($brs as $x)echo '<option'.($x==$b?' selected':'').'>'.e($x).'</option>';?></select>
<select name="c"><option value="">All classifications</option><?php foreach(['0'=>'Not indigent','1'=>'Vulnerable','2'=>'Indigent'] as $k=>$l)echo "<option value='$k'".($c!==''&&$c==$k?' selected':'').">$l</option>";?></select>
<button type="button" class="btn" onclick="print()">Print list</button><a id="exp" class="btn p" href="export_csv.php?<?=e(http_build_query(['q'=>$q,'b'=>$b,'c'=>$c]))?>">Export CSV</a></form>
<div id="results"><?=$results?></div>
<script>(function(){var f=document.getElementById('flt'),r=document.getElementById('results'),ex=document.getElementById('exp'),t,ac;
function run(){var qs=new URLSearchParams(new FormData(f)).toString();history.replaceState(null,'','households.php'+(qs?'?'+qs:''));ex.href='export_csv.php?'+qs;
if(ac)ac.abort();ac=new AbortController();r.style.opacity=.5;
fetch('households.php?ajax=1&'+qs,{signal:ac.signal,credentials:'same-origin'}).then(function(x){if(x.redirected){location.reload();return null}return x.text()}).then(function(h){if(h===null)return;r.innerHTML=h;r.style.opacity=1}).catch(function(e){if(e.name!=='AbortError')r.style.opacity=1})}
f.addEventListener('input',function(){clearTimeout(t);t=setTimeout(run,200)});f.addEventListener('submit',function(e){e.preventDefault();run()});})();</script><?php foot();
