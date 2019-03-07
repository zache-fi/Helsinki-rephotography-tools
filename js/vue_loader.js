
function init_vue() {
   function getSearchParams(k, d){
      var p={};
      location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi,function(s,k,v){p[k]=v})
      var ret= k?p[k]:p;
      if (typeof(ret)==="undefined") return d; 
      else return ret;
   }

   function init_map() {

      var map = L.map('mapid').setView([60.192059,24.945831], 13);


      var normalTiles = L.tileLayer('https://cdn.digitransit.fi/map/v1/{id}/{z}/{x}/{y}.png', {
         attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
            '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
         maxZoom: 18,
         tileSize: 512,
         zoomOffset: -1,
         id: 'hsl-map'}).addTo(map);

      var wms1909 = L.tileLayer.wms('https://geoserver.hel.fi/geoserver/historical/wms?', {
         layers:'historical:1909_opaskartta',
         format: 'image/png',
         transparent: true,
         attribution: 'Map data CC-BY; <a href="http://www.hel.fi/www/kv/fi/organisaatio/kaupunkimittausosasto/">Helsingin Kaupunki, Kaupunkimittausosasto</a>',
      });

      var wms1917 = L.tileLayer.wms('https://geoserver.hel.fi/geoserver/historical/wms?', {
         layers:'historical:1917-1918_opaskartta',
         format: 'image/png',
         transparent: true,
         attribution: 'Map data CC-BY <a href="http://www.hel.fi/www/kv/fi/organisaatio/kaupunkimittausosasto/">Helsingin Kaupunki, Kaupunkimittausosasto</a>',
      });

      var wms1940 = L.tileLayer.wms('https://geoserver.hel.fi/geoserver/historical/wms?', {
         layers:'historical:1940_opaskartta',
         format: 'image/png',
         transparent: true,
         attribution: 'Map data CC-BY <a href="http://www.hel.fi/www/kv/fi/organisaatio/kaupunkimittausosasto/">Helsingin Kaupunki, Kaupunkimittausosasto</a>',

      });

      var wms1977 = L.tileLayer.wms('https://geoserver.hel.fi/geoserver/historical/wms?', {
         layers:'historical:1977_opaskartta',
         format: 'image/png',
         transparent: true,
         attribution: 'Map data &copy; <a href="http://www.hel.fi/www/kv/fi/organisaatio/kaupunkimittausosasto/">Helsingin Kaupunki, Kaupunkimittausosasto</a>',
      });


      L.control.layers({
        "digiTransit": normalTiles
        }, {
        "1977": wms1977, 
        "1940": wms1940,
        "1917": wms1917,
        "1909": wms1909
        }, {
          collapsed: false
        }).addTo(map);

      return map;
   }

   function addMarkers(map, photos) {
      var markers=[];
      for (var k in photos) {
         if (photos.hasOwnProperty(k)) {
            var p = photos[k];
            if (p.lat>0 && p.lon>0) {
               var marker = L.marker([p.lat, p.lon], {title: p.title}).addTo(map);

               var popup_html="";
               popup_html="<img class='map_popup_image' src='https://www.finna.fi" + p.small_url +"'>";
               popup_html+=p.title;
               popup_html+=" <a href='https://www.finna.fi/Record/'" + p.finna_id + "'>" + p.finna_id + "</a>";

               marker.bindPopup(popup_html);
               markers.push(marker);
               photos[k].marker=marker;
            }
         }
      }
      var group = L.featureGroup(markers).addTo(map);
      map.fitBounds(group.getBounds());
      setTimeout(function () { map.fitBounds(group.getBounds()); }, 3000);
      return group;
   }

   var map=init_map();
   var app = new Vue({
      el: '#app',
      data: { 
         photos:[],
         params:{
            searchkey:"",
            coordfilter:"2"
         } 
      },
      methods: {
         deleteclick: function(index)
         {
            var p=this.photos[index];
            var url="tag_photos.php?tag=delete&finna_id=" + encodeURIComponent(p.finna_id);
            if (typeof(p.marker) !== "undefined") p.marker.remove();

            $.getJSON(url, function (json) {
            });
            this.photos.splice(index,1);

         },
         likeclick: function(index)
         {
            var p=this.photos[index];
            var url="tag_photos.php?tag=like&finna_id=" + encodeURIComponent(p.finna_id);
            p.like=1;
            $.getJSON(url, function (json) {
            });
         },
      }
   })
   app.params.searchkey=decodeURIComponent(getSearchParams('searchkey', "").replace(/\+/g, '%20'));
   app.params.coordfilter=decodeURIComponent(getSearchParams('coordfilter',2));


   var url="list_photos.php?";
   if (app.params.searchkey != "") {
      url+="&searchkey=" + encodeURIComponent(app.params.searchkey);
   }
   if (app.params.coordfilter != "") {
      url+="&coordfilter=" + encodeURIComponent(app.params.coordfilter);
   }
   

   app.params.searchurl=url;
   $.getJSON(url, function (json) {
      app.photos = json;
      addMarkers(map, app.photos);
   });

}


$(init_vue);


