<?php

$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
$db = mysql_connect('tools.labsdb', $ts_mycnf['user'], $ts_mycnf['password']) or  d_die('Mysql connect failed: ' . mysql_error());
unset($ts_mycnf);
mysql_select_db('s51154_hkmphotos', $db) or  d_die('Mysql select db failed: ' . mysql_error());


function d_die($str)
{
        die("SQL error");
}



function get_tag_id($tagkey) {
        global $db;
	$tag_id=0;

	$query_tmp="SELECT * FROM tags WHERE title = '%s' LIMIT 1 ";
	$query =sprintf($query_tmp, mysql_real_escape_string($tagkey));
        $result = mysql_query($query, $db) or d_die('Query failed: ' . mysql_error() );
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) 
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

        $query_tmp=sprintf("SELECT * FROM photos LEFT JOIN taglinks as t ON tag_id=%d AND photos.id=t.photo_id AND t.deleted=1 ", $tag_id);
	$rule_prefix="WHERE ";
	if ($searchkey!="") {
		$query_tmp.=sprintf("$rule_prefix placeline LIKE '%s' ", "%" . mysql_real_escape_string($searchkey) ."%");
		$rule_prefix=" AND ";
	}

	if ($finna_id!="") {
		$query_tmp.=sprintf("$rule_prefix finna_id LIKE '%s' ", "%" . mysql_real_escape_string($finna_id) ."%");
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

        $query_tmp.=" GROUP BY photos.id ";
	$query_tmp.="ORDER BY RAND() LIMIT 15 ";

	$query=$query_tmp;
        $result = mysql_query($query, $db) or d_die('Query failed: ' . mysql_error() );
        while ($line = mysql_fetch_array($result, MYSQL_ASSOC))
        {
		if (isset($line['dateline'])) $line['dateline']=preg_replace("/(\n|\s+)/ism", " ", $line['dateline']);
                if (isset($line['medium_url'])) $line['small_url']=preg_replace("/medium/ism", "small", $line['medium_url']);
                $line['like']=0;

                array_push($ret, $line);
        }
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
