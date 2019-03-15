var baseurl="https://wikidata.beta.wmflabs.org/w/api.php?";

$.postJSON = function(url, data, func)
{
    $.post(url, data, func, 'json');
}

function testing(response)
{
   alert("callback");
}

function mw_test()
{
   whoami();
}

function mw_whoami(user) {
   var url=baseurl + "action=query&meta=userinfo&uiprop=rights%7Chasmsg&format=json";
   $.getJSON(url, function (json) {
      if (json && json.query && json.query.userinfo)
         user.username=json.query.userinfo.name;
   });
}


function edittoken() {
   var url=baseurl + "action=query&meta=tokens&format=json";
   $.postJSON(url, {}, function (json) {
      alert(JSON.stringify(json));
   });
}

function upload_to_commons(photo) {

   var permission=photo.imagerights_copyright;
   if (permission.trim()=="CC BY 4.0") permission="{{CC-BY-4.0}}";
  
   var description="{{Information\n";
   description+="| Description    = {{fi|" + photo.title + " -- " + photo.summary +"}}\n";
   description+="| Date           =" + photo.dateline +"\n"; 
   description+="| Source         = Helsingin kaupunginmuseo: [https://www.finna.fi/Record/" + photo.finna_id + " " + photo.finna_id +"]\n";
   description+="| Author         =" +photo.author_name +"\n"; 
   description+="| Permission     =" + permission +"\n"; 
   description+="| Other_versions ="; 
   description+="}}\n";
   if (photo.lat && photo.lon) {
      description+="{{location|" + photo.lat +"|" +photo.lon +"}}\n";
   }
   description+="[[Category:Helsinki]]\n";

   var url="https://tools.wmflabs.org/url2commons/index.html?";
   url+="urls=https://www.finna.fi" + encodeURIComponent(photo.large_url.replace("large", "master"));
   url+=encodeURIComponent(" " + (photo.title + ".jpg").replace("..jpg", ".jpg"));
   url+="&desc=" + encodeURIComponent(description);
   window.open(url, '_blank');
}
