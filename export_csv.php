<?php require 'inc/lib.php';need();
$q=trim($_GET['q']??'');$b=$_GET['b']??'';$c=$_GET['c']??'';
[$H,$MM]=find_households($q,$b,$c);
$f=array_filter(['search'=>$q,'barangay'=>$b,'class'=>$c===''?'':['Not indigent','Vulnerable','Indigent'][(int)$c]],fn($x)=>$x!=='');
logact('EXPORT',null,'Exported '.count($H).' household(s) to CSV'.($f?' (filters: '.http_build_query($f).')':''));
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="households_'.date('Ymd_His').'.csv"');
$o=fopen('php://output','w');fwrite($o,"\xEF\xBB\xBF");
$safe=function($v){if(!is_string($v)||$v==='')return $v;return (preg_match('/^[=@\t\r]/',$v)||preg_match('/^[+-](?![0-9 ()-]*$)/',$v))?"'".$v:$v;};
$row=fn($a)=>fputcsv($o,array_map($safe,$a));
$hdr=['Household ID','Barangay','Date of interview','Last name','First name','Middle name','Age','Sex','Birthdate','Civil status','Educational attainment','Occupation','Head monthly income','Religion','Contact number','Household members','Families','Combined monthly income','House type','House ownership','Electricity','Chronic illness','Chronic illness specify','PhilHealth','PhilHealth #','PhilHealth category'];
foreach(IND as $k=>$l)$hdr[]='Score: '.$l;
array_push($hdr,'Total score (/16)','Classification','Interviewer','Family members');
$row($hdr);
foreach($H as $h){$M=$MM[$h['id']]??[];$t=total($h);
$mem=implode(' | ',array_map(fn($m)=>trim($m['name']).' ('.implode('; ',array_filter([$m['age']!==null&&$m['age']!==''?'age '.$m['age']:'',$m['sex'],$m['edu'],$m['occ'],$m['income']>0?'income '.$m['income']:'',$m['remarks']],fn($x)=>$x!==''&&$x!==null)).')',$M));
$r=[$h['household_code'],$h['brgy'],$h['interview_date'],$h['ln'],$h['fn'],$h['mn'],$h['age'],$h['sex'],$h['birthdate'],$h['civil'],$h['edu'],$h['occ'],$h['income'],$h['religion'],$h['contact'],$h['total_members'],$h['total_families'],number_format(famincome($h,$M),2,'.',''),$h['house_type'],$h['house_own'],$h['electricity'],$h['chronic'],$h['chronic_spec'],$h['philhealth'],$h['ph_no'],$h['ph_cat']];
foreach(IND as $k=>$_)$r[]=(int)$h['s_'.$k];
array_push($r,$t,klass($t)[0],$h['interviewer'],$mem);$row($r);}
fclose($o);
