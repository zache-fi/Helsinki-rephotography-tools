<?php

$ts_pw = posix_getpwuid(posix_getuid());
$ts_mycnf = parse_ini_file($ts_pw['dir'] . "/replica.my.cnf");
$db = mysqli_connect('wikidatawiki.labsdb', $ts_mycnf['user'], $ts_mycnf['password']) or  d_die('Mysql connect failed: ' . mysqli_connect_error());
unset($ts_mycnf);
mysqli_select_db($db, 'wikidatawiki_p') or  d_die('Mysql select db failed: ' . mysqli_error($db));

function d_die($str)
{
        die("SQL error" . $str);
}



function get_failback_title($wikidata_id, $lang) {
        global $db;
	$tag_id=0;

	$query_tmp="select term_text,term_language from wb_terms where term_full_entity_id='%s' and term_type='label' AND term_language IN ('fi', 'en', 'sv', 'et', 'se', 'no', 'de', 'es')";
	$query =sprintf($query_tmp, mysqli_real_escape_string($db, $wikidata_id));
        $result = mysqli_query($db, $query) or d_die('Query failed: ' . mysqli_error($db) );

        $failback=array();
        while ($line = mysqli_fetch_array($result, MYSQLI_ASSOC)) 
	{
		$failback[$line['term_language']]=$line['term_text'];
	}
        switch($lang)
        {
           case 'fi': $langs=array('fi', 'en', 'sv', 'et', 'se', 'no', 'de', 'es'); break;
           case 'et': $langs=array('et', 'en', 'sv', 'fi', 'no', 'de', 'es', 'se'); break;
           case 'no': $langs=array('no', 'en', 'sv', 'fi', 'de', 'es', 'et', 'se'); break;
           case 'en': $langs=array('en', 'de', 'es', 'sv', 'fi', 'no', 'et', 'se'); break;
           default  : $langs=array('en', 'de', 'es', 'sv', 'fi', 'no', 'et', 'se'); break;
        } 
	foreach ($langs as $l)
        {
           if (isset($failback[$l])) return $failback[$l] ."($lang : $l)";
        }
        return $wikidata_id;
}


function get_wikidata_nearest($lat, $lon, $search="", $lang="en", $limit=15) {
   $limit=50;
   if ($lat!="" && $lon!="" && $search=="")
   {
      $url="https://m.wikidata.org/w/api.php?action=query&format=json&formatversion=2&redirects=1&prop=coordinates|pageprops|pageterms|pageimages|description&colimit=max&generator=geosearch&ggsradius=10000&ggsnamespace=146|0&ggslimit=50&ggscoord=$lat|$lon&ppprop=displaytitle&wbptterms=label&pilicence=free&piprop=thumbnail&pithumbsize=250&pilimit=$limit&codistancefrompoint=$lat|$lon&uselang=$lang";
   }
   elseif ($lat!="" && $lon!="" && $search!="")
   {
      $url="https://m.wikidata.org/w/api.php?action=query&format=json&prop=coordinates|pageprops|pageterms|pageimages|description&generator=wbsearch&formatversion=2&colimit=max&codistancefrompoint=$lat|$lon&ppprop=displaytitle&wbptterms=label&piprop=thumbnail&pithumbsize=150&pilimit=50&gwbstype=item&gwbslimit=50&gwbssearch=" . urlencode($search);
   }
   elseif ($search!="")
   {
      $url="https://m.wikidata.org/w/api.php?action=query&format=json&prop=coordinates|pageprops|pageterms|pageimages|description&generator=wbsearch&formatversion=2&colimit=max&ppprop=displaytitle&wbptterms=label&piprop=thumbnail&pithumbsize=150&pilimit=50&gwbstype=item&gwbslimit=50&gwbssearch=" . urlencode($search);
   }
   else
   {
      return array();
   }

   $file=file_get_contents($url);
   $json=json_decode($file, true);

   $albums=array();
   foreach ($json["query"]["pages"] as $row)
   {
        $album=array();
        if (preg_match("|Q\d+\z|", $row['title'])) {
           $album['id']=$row['title'];
        }
        else
        {
           continue;
        }

        if (isset($row['terms']) && isset($row['terms']['label'])) {
           $album['title']=$row['terms']['label'][0];
        }
        else
        {
           $album['title']=get_failback_title($album['id'], $lang);
        }

        if (isset($row['thumbnail']) && isset($row['thumbnail']['source']))
        {
           $album['image']=$row['thumbnail']['source'];
        }
        else
        {
           $album['image']="";
           continue;
        }
        $album['search']=$search;
        $album['lang']=$lang;
        $album['stats']=array('total'=>'', 'rephoto'=>'');
        array_push($albums, $album);
   }
   return $albums;
}

$albums=array();
$lat=60.1956352;
$lon=24.9266176;
$search="";
$language="en";
if (isset($_GET['search'])) $search=trim($_GET['search']);
if (isset($_GET['lat'])) $lat=trim($_GET['lat'])*1;
if (isset($_GET['lon'])) $lon=trim($_GET['lon'])*1;
if (isset($_GET['language'])) $language=trim($_GET['language']);


$limit=50;
if (isset($_GET['limit'])) $limit=trim($_GET['limit'])*1;
if (!($limit>0 && $limit<101)) $limit=15;
$albums=get_wikidata_nearest($lat, $lon, $search, $language, $limit);
print json_encode($albums, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
?>
