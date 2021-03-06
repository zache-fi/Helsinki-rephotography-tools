<?php

$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
$db = mysqli_connect('tools.labsdb', $ts_mycnf['user'], $ts_mycnf['password']) or  d_die('Mysql connect failed: ' . mysqli_connect_error($db));
unset($ts_mycnf);
mysqli_select_db($db, 's51154_hkmphotos') or  d_die('Mysql select db failed: ' . mysqli_error($db));


function d_die($str)
{
	die("SQL error");

}


function tag_photo($finna_id, $tagkey, $state, $userhash)
{
        global $db;
        $ret=array();
        $tag_id=0;
        $photo_id=0;

        $query_tmp="SELECT * FROM tags WHERE title = '%s' LIMIT 1 ";
        $query=sprintf($query_tmp, mysqli_real_escape_string($db, $tagkey));

        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );
        while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$tag_id=$line['id'];
	}
	if ($tag_id==0) return array('status' => 'ERROR', 'message' => 'unknown tag: ' . $tagkey . " " . $query);

        $query_tmp="SELECT * FROM photos WHERE finna_id = '%s' LIMIT 1 ";
        $query=sprintf($query_tmp, mysqli_real_escape_string($db, $finna_id));
        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );
        while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$photo_id=$line['id'];
	}
	if ($photo_id==0) return array('status' => 'ERROR', 'message' => 'unknown finna_id: ' . $finna_ld);


        $query_tmp="SELECT * FROM taglinks WHERE tag_id = %d AND photo_id = %d LIMIT 1 ";
        $query=sprintf($query_tmp, $tag_id, $photo_id);
        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );

        while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		$taglink_id=$line['id'];
	}

	if ($taglink_id==0) {
		$query_tmp="INSERT taglinks (photo_id, tag_id, deleted) VALUES (%d, %d, %d)";
	        $query=sprintf($query_tmp, $photo_id, $tag_id, $state);
	}
	else
	{
		$query_tmp="UPDATE taglinks SET deleted=%d WHERE id=%d";
	        $query=sprintf($query_tmp, $state, $taglink_id);
	}
        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );

	$userhash=substr(md5($_SERVER['HTTP_USER_AGENT']), 0, 8);
	$query_tmp="INSERT taghistory (photo_id, tag_id, action, user) VALUES (%d, %d, %d, '%s')";
	$query=sprintf($query_tmp, $tag_id, $photo_id, $state,  mysqli_real_escape_string($db, $userhash));

        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );

	return array('status' => 'OK');
}

$tagkey="";
if (isset($_GET['tag'])) $tagkey=trim($_GET['tag']);

$finna_id="";
if (isset($_GET['finna_id'])) $finna_id=trim($_GET['finna_id']);

if ($tagkey=="delete")
{
   $ret=tag_photo($finna_id, "deleted", 1, $userhash);
}
elseif ($tagkey=="undelete")
{
   $ret=tag_photo($finna_id, "deleted", 0, $userhash);
}
elseif ($tagkey=="like")
{
   $ret=tag_photo($finna_id, "like", 1, $userhash);
}
elseif ($tagkey=="unlike")
{
   $ret=tag_photo($finna_id, "like", 0, $userhash);
}
else
{
   $ret=array('status' => 'ERROR', 'message' => 'unknown tag: ' . $tagkey);
}

print json_encode($ret, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
?>
