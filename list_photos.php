<?php

$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
$db = mysqli_connect('tools.labsdb', $ts_mycnf['user'], $ts_mycnf['password']) or  d_die('Mysql connect failed: ' . mysqli_connect_error());
unset($ts_mycnf);
mysqli_select_db($db, 's51154_hkmphotos') or  d_die('Mysql select db failed: ' . mysqli_error($db));

function d_die($str)
{
        die("SQL error");
}



function get_tag_id($tagkey) {
        global $db;
	$tag_id=0;

	$query_tmp="SELECT * FROM tags WHERE title = '%s' LIMIT 1 ";
	$query =sprintf($query_tmp, mysqli_real_escape_string($db, $tagkey));
        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );
        while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) 
	{
		$tag_id=$line['id'];
	}
	return $tag_id;
}

function get_photos_from_db($searchkey, $coordfilter, $finna_id)
{
        global $db;
        $ret=array();
	$tag_id=get_tag_id("deleted");
        $deleted=0;

        $query_tmp=sprintf("SELECT photos.* FROM stats, photos LEFT JOIN (SELECT photo_id FROM taglinks WHERE tag_id=%d AND deleted=1 GROUP BY photo_id) AS t ON photos.id=t.photo_id ", $tag_id);
        $query_tmp.="WHERE stats.photo_id=photos.id";
	$rule_prefix=" AND ";

	if ($searchkey!="") {
		$query_tmp.=sprintf("$rule_prefix placeline LIKE '%s' ", "%" . mysqli_real_escape_string($db, $searchkey) ."%");
		$rule_prefix=" AND ";
	}

	if ($finna_id!="") {
		$query_tmp.=sprintf("$rule_prefix finna_id LIKE '%s' ", "%" . mysqli_real_escape_string($db, $finna_id) ."%");
		$rule_prefix=" AND ";
	}

	if ($coordfilter==2) {
		$query_tmp.="$rule_prefix lat>0 AND lon>0 ";
		$rule_prefix=" AND ";
	}

	if ($deleted != 1)
	{
		$query_tmp.="$rule_prefix t.photo_id IS NULL ";
	}

//        $query_tmp.=" GROUP BY photos.id ";
	$query_tmp.="ORDER BY viewcount,random ";
	$query_tmp.="LIMIT 15 ";

	$query=$query_tmp;
        $ids=array();
        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );
        while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC))
        {
		if (isset($line['dateline'])) $line['dateline']=preg_replace("/(\n|\s+)/ism", " ", $line['dateline']);
                if (isset($line['medium_url'])) $line['small_url']=preg_replace("/medium/ism", "small", $line['medium_url']);
                $line['like']=0;

                array_push($ret, $line);
                array_push($ids, $line['id']);
        }
	$query=sprintf("UPDATE stats SET viewcount=viewcount+1 WHERE photo_id IN (%s)", implode(",", $ids)) ;
        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );

        return $ret;
}

$searchkey="";
if (isset($_GET['searchkey'])) $searchkey=trim($_GET['searchkey']);

$finna_id="";
if (isset($_GET['finna_id'])) $searchkey=trim($_GET['finna_id']);

$coordfilter=1;
if (isset($_GET['coordfilter'])) $coordfilter=trim($_GET['coordfilter'])*1;
if ($coordfilter!=2) $coordfilter=1;

$photos=get_photos_from_db($searchkey, $coordfilter, $finna_id);

print json_encode($photos, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
?>
