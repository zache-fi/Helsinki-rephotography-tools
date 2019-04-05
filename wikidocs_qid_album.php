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


function get_sparql_info($qid) {
   $ret=array(
      'lat' => "",
      'lon' => "",
      'country' => "",
      'commonscat' => "",
      'label' => "",
      'adm' => "",
   );
   $url="https://query.wikidata.org/sparql?query=%23Cats%0ASELECT%20%3Fitem%20%3FitemLabel%20%3Flat%20%3Flon%20%3FadmLabel%20%3FcountryLabel%0AWHERE%20%0A%7B%20%0A%20%20BIND%20(wd%3A".urlencode($qid) ."%20as%20%3Fitem)%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP17%20%3Fcountry%20%7D%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP131%20%3Fadm%20%7D%20%20%0A%20%20OPTIONAL%20%7B%0A%20%20%20%20%20%3Fitem%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20p%3AP625%20%20%20%20%20%20%20%20%20%3Fstatementnode.%0A%20%20%20%20%20%3Fstatementnode%20%20%20%20%20%20psv%3AP625%20%20%20%20%20%20%20%20%20%3Fvaluenode.%0A%20%20%20%20%20%3Fvaluenode%20%20%20%20%20wikibase%3AgeoLatitude%20%20%3Flat.%0A%20%20%20%20%20%3Fvaluenode%20%20%20%20%20wikibase%3AgeoLongitude%20%3Flon.%20%20%0A%20%20%7D%0A%20%20%0A%20%20SERVICE%20wikibase%3Alabel%20%7B%20bd%3AserviceParam%20wikibase%3Alanguage%20%22en%2Cen%22.%20%7D%0A%7D&format=json";
   $url="https://query.wikidata.org/sparql?query=%23Cats%0ASELECT%20%3Fitem%20%3FitemLabel%20%3Flat%20%3Flon%20%3FadmLabel%20%3FcountryLabel%20%3Fcommonscat%0AWHERE%20%0A%7B%20%0A%20%20BIND%20%28wd%3A".urlencode($qid)."%20as%20%3Fitem%29%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP17%20%3Fcountry%20%7D%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP131%20%3Fadm%20%7D%20%20%0A%20%20OPTIONAL%20%7B%20%3Fitem%20wdt%3AP373%20%3Fcommonscat%20%7D%0A%20%20OPTIONAL%20%7B%0A%20%20%20%20%20%3Fitem%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20p%3AP625%20%20%20%20%20%20%20%20%20%3Fstatementnode.%0A%20%20%20%20%20%3Fstatementnode%20%20%20%20%20%20psv%3AP625%20%20%20%20%20%20%20%20%20%3Fvaluenode.%0A%20%20%20%20%20%3Fvaluenode%20%20%20%20%20wikibase%3AgeoLatitude%20%20%3Flat.%0A%20%20%20%20%20%3Fvaluenode%20%20%20%20%20wikibase%3AgeoLongitude%20%3Flon.%20%20%0A%20%20%7D%0A%20%20%0A%20%20SERVICE%20wikibase%3Alabel%20%7B%20bd%3AserviceParam%20wikibase%3Alanguage%20%22%5BAUTO_LANGUAGE%5D%2Cen%22.%20%7D%0A%7D&format=json";
   $file=file_get_contents($url);
   $json=json_decode($file, true);

   if (isset($json["results"]) && isset($json["results"]["bindings"]))
   {
      $v=$json["results"]["bindings"][0];
      $ret["qid"]=str_replace("http://www.wikidata.org/entity/", "", $v['item']['value']);
      if (isset($v['lat'])) $ret["lat"]=$v['lat']['value'];
      if (isset($v['lon'])) $ret["lon"]=$v['lon']['value'];
      if (isset($v['country'])) $ret["country"]=$v['countryLabel']['value'];
      if (isset($v['admLabel'])) $ret["adm"]=$v['admLabel']['value'];
      if (isset($v['commonscat'])) $ret["commonscat"]=$v['commonscat']['value'];
      if (isset($v['itemLabel'])) $ret["label"]=$v['itemLabel']['value'];
   }
   return $ret;
}

function get_photos_url($item, $lang)
{
	$url="https://wikidocumentaries-api.wmflabs.org/images?";
	$url.="topic=" . urlencode($item["label"]);
        if (trim($item["adm"])!="") {
           $url.=urlencode(", " . $item["adm"]);
        }
        $url.="&language=". urlencode($lang);

        if (isset($item["commonscat"]) && trim($item["commonscat"])!="")
        {
           $url.="&commons_category=" . urlencode($item["commonscat"]);
        }
        if (trim($item["lat"])!="" && trim($item["lon"])!="")
        {
           $url.="&lat=" . urlencode($item["lat"]);
           $url.="&lon=" . urlencode($item["lon"]);
           $url.="&maxradius=2000";
        }
        return $url;
}

function get_photos($item, $lang)
{
    $url=get_photos_url($item, $lang);
    $file=file_get_contents($url);
    $json=json_decode($file, true);
    $ret=array();
    foreach ($json as $row)
    {
        $row["lat"]="";
        $row["lon"]="";
        $row["azimuth"]="";
	foreach($row["geoLocations"] as $g)
        {
           if (preg_match("|POINT\(([0-9.]+) ([0-9.]+)\)|ism", $g, $m))
           {
              $row['lat']=$m[2];
              $row['lon']=$m[1];
              break;
           }
        }
        unset($row["geoLocations"]);
        array_push($ret, $row);
    }
    return $ret;
}

$photos=array();
$qid="Q1636613";
$lat=60.1956352;
$lon=24.9266176;
$search="";
$language="en";
if (isset($_GET['search'])) $search=trim($_GET['search']);
if (isset($_GET['qid'])) $qid=trim($_GET['qid']);
if (isset($_GET['lat'])) $lat=trim($_GET['lat'])*1;
if (isset($_GET['lon'])) $lon=trim($_GET['lon'])*1;
if (isset($_GET['language'])) $language=trim($_GET['language']);

$limit=50;
if (isset($_GET['limit'])) $limit=trim($_GET['limit'])*1;
if (!($limit>0 && $limit<101)) $limit=15;
$item=get_sparql_info($qid);
if (count($item))
{
   $photos=get_photos($item, $language);
}
print json_encode($photos, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
?>
