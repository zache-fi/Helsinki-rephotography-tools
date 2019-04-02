(function (L) {
    'use strict';
    /*global $*/
    /*global L*/

   L = 'default' in L ? L['default'] : L;

   var photoMarker =  L.Marker.extend({
      options: {
         point: { lat: 0, lng: 0 },
         azimuth: undefined,
         markerArrowIcon: new L.icon({
               iconUrl: 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Ic_navigation_48px.svg/200px-Ic_navigation_48px.svg.png',
               iconSize:     [48, 48], // size of the icon
               iconAnchor:   [24, 0], // point of the icon which will correspond to marker's location
               popupAnchor:  [-3, -26] // point from which the popup should open relative to the iconAnchor
            }),
         markerIcon: new L.icon({
               iconUrl: 'https://upload.wikimedia.org/wikipedia/commons/thumb/9/92/Ic_location_on_48px.svg/200px-Ic_location_on_48px.svg.png',
               iconSize:     [48, 48], // size of the icon
               iconAnchor:   [24, 48], // point of the icon which will correspond to marker's location
               popupAnchor:  [-3, -26] // point from which the popup should open relative to the iconAnchor
            })
      },
      initialize: function initialize(options) {
         L.setOptions(this, options);
      },
      onAdd: function (map) {
         this._map = map;
         this._marker =  this._getPhotoMarker().addTo(map);
         return this;
      },

      removeFrom: function removeFrom(map) {
         this._map = undefined;
         this._marker.removeFrom(map);
      },

      _getPhotoMarker: function() {
         if (this.options.azimuth) {
            return this._getMarkerArrowLine();
         }
         else
         {
            return L.marker(this.options.point, {icon: this.options.markerIcon });
         }
      },

      _getMarkerLine: function() {
         var startingPoint=this.options.point;
         var destinationPoint= L.GeometryUtil.destination(startingPoint, this.options.azimuth, 500000);
         var pointList = [startingPoint, destinationPoint];
         var markerline = new L.Polyline(
               pointList,
               {
                  color: 'red',
                  weight: 3,
                  opacity: 0.5,
                  smoothFactor: 1,
                  dashArray: '7,7'
               });
         return markerline;
      },

      _getMarkerArrowLine: function() {
         var markerlayer=L.layerGroup();

         var markerOptions={
            icon: this.options.markerArrowIcon,
            rotationAngle:this.options.azimuth
         }
         var marker = L.marker(this.options.point, markerOptions);
         markerlayer.addLayer(marker);

         var markerline=this._getMarkerLine();
         markerlayer.addLayer(markerline);
         return markerlayer;
       },
   });


   var crosshairMarker =  L.Marker.extend({
      options: {
         crosshairHTML: '<div><img style="margin-top:-52px" alt="Center of the map; crosshair location" title="Crosshair" src="css/ajapaik_photo_camera_arrow_drop_down_mashup.svg" width="38px" /></div>'
      },
      initialize: function initialize(options) {
         L.setOptions(this, options);
      },
      onAdd: function (map) {
         this._map = map;
         map.setView(map.state.cameraLatLng);
         this._crosshair = L.geotagPhoto.crosshair(this.options).addTo(map);
         return this;
      },
      removeFrom: function removeFrom(map) {
         this._map = undefined;
         map.state.cameraLatLng=this._crosshair.getCrosshairLatLng()
         this._crosshair.removeFrom(map);
      }
   });


   var cameraMarker =  L.Marker.extend({
      options: {
                cameraIcon: L.icon({
                    iconUrl: 'css/camera.svg',
                    iconSize: [38, 38],
                    iconAnchor: [19, 19]
                }),
                targetIcon: L.icon({
                    iconUrl: 'css/marker.svg',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                }),
                angleIcon: L.icon({
                    iconUrl: 'css/marker.svg',
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                }),
                control:false,
                angleMarker: false,
                minangle:10,
                controlCameraImg: 'css/camera-icon.svg',
                controlCrosshairImg: 'css/crosshair-icon.svg',
                draggable: true
      },
      initialize: function initialize(options) {
         L.setOptions(this, options);
      },
      onAdd: function (map) {
         var cameraLatLng = map.state.cameraLatLng;
         var destinationLatLng = map.state.destinationLatLng
         var azimuth = 0;

         if (destinationLatLng)
         {
            azimuth=L.GeometryUtil.bearing(cameraLatLng,destinationLatLng);
         }
         else if (map.state.azimuth)
         {
            azimuth=map.state.azimuth;
         }

         if (!(destinationLatLng && map.getBounds().contains(destinationLatLng)))
         {
            var northWest = map.getBounds().getNorthWest();
            var northEast = map.getBounds().getNorthEast();
            var southWest = map.getBounds().getSouthWest();

            var dist_nwne=northWest.distanceTo(northEast) / 3;
            var dist_nwsw=northWest.distanceTo(southWest) / 3;
	    var dist=(dist_nwne>dist_nwsw ? dist_nwsw : dist_nwne);

	    destinationLatLng= L.GeometryUtil.destination(cameraLatLng, azimuth, dist);
         }

         var cameraPoint = [cameraLatLng.lng, cameraLatLng.lat];
    	 var targetPoint = [destinationLatLng.lng, destinationLatLng.lat];

         var points = {
               type: 'Feature',
               properties: {
                  angle: 20
               },
               geometry: {
                  type: 'GeometryCollection',
                  geometries: [
                     {
                        type: 'Point',
                        coordinates: cameraPoint
                     },
                     {
                        type: 'Point',
                        coordinates: targetPoint
                     }
                  ]
               }
            };



         this._map = map;
         this._camera = L.geotagPhoto.camera(points, this.options).addTo(map);
         this._camera.setAngle(45);
         return this;
      },
      removeFrom: function removeFrom(map) {
         this._map = undefined;
         map.state.cameraLatLng=this._camera.getCameraLatLng()
         map.state.destinationLatLng=this._camera.getTargetLatLng()
         this._camera.removeFrom(map);
      }
   });



   var startGeotaggingButton =  L.Control.extend({
      options: {
         position: 'bottomright'
      },

      onAdd: function (map) {
         var container = L.DomUtil.create('div', 'geotagger-start-geotagging leaflet-bar leaflet-control material-icons notranslate');
         container.innerHTML="<a href='#'>add_location</a>";

         container.onclick = function(){
            console.log('buttonClicked');
            map.state.mode='crosshair';

            map.removeControl(map.buttons.startGeotaggingButton);
            map.removeControl(map.buttons.confirmGeotagButton);
            map.buttons.photoMarker.removeFrom(map);

            map.addControl(map.buttons.toggleCrosshairGeotagButton);
            map.addControl(map.buttons.cancelGeotaggingButton);
            map.addControl(map.buttons.submitGeotagButton);
            map.buttons.photoMarker=new crosshairMarker().addTo(map);

         }
         return container;
      }
   });

   var confirmGeotagButton =  L.Control.extend({
      options: {
         position: 'bottomleft'
      },

      onAdd: function (map) {
         var container = L.DomUtil.create('div', 'geotagger-confirm-geotag leaflet-bar leaflet-control material-icons notranslate');
         container.innerHTML="<a href='#'>beenhere</a>";

         container.onclick = function(){
            console.log('buttonClicked');
         }
         return container;
      }
   });

   var cancelGeotaggingButton = L.Control.extend({
      options: {
         position: 'bottomright'
      },

      onAdd: function (map) {
         var container = L.DomUtil.create('div', 'geotagger-cancel-geotagging leaflet-bar leaflet-control material-icons notranslate');
         container.innerHTML="<a href='#'>cancel</a>";

         container.onclick = function(){
            console.log('buttonClicked');
            map.removeControl(map.buttons.toggleCrosshairGeotagButton);
            map.removeControl(map.buttons.cancelGeotaggingButton);
            map.removeControl(map.buttons.submitGeotagButton);
            map.buttons.photoMarker.removeFrom(map);

            map.addControl(map.buttons.startGeotaggingButton);
            map.addControl(map.buttons.confirmGeotagButton);
            map.buttons.photoMarker=new photoMarker({
                point: map.state.initialCenterLatLng,
                azimuth:map.state.azimuth
             }).addTo(map);
//            map.setView(map.state.initialCenterPoint, 13);

         }
         return container;
      }
   });

   var submitGeotagButton =  L.Control.extend({
      options: {
         position: 'bottomleft'
      },

      onAdd: function (map) {
         var container = L.DomUtil.create('div', 'geotagger-submit-geotagging leaflet-bar leaflet-control material-icons notranslate');
         container.innerHTML="<a href='#'>done</a>";

         container.onclick = function(){
            console.log('buttonClicked');
         }
         return container;
      }
   });


   var toggleCrosshairGeotagButton =  L.Control.extend({
      options: {
         position: 'topleft'
      },

      onAdd: function (map) {
         var container = L.DomUtil.create('div', 'geotagger-toggle-crosshair-geotagging leaflet-bar leaflet-control material-icons notranslate');
         container.innerHTML="<a href='#'>rotate_right</a>";

         container.onclick = function(){
            if (map.state.mode=='crosshair')
            {
               map.state.mode='busy';
               map.buttons.photoMarker.removeFrom(map);
               container.innerHTML="<a href='#' style='background-color:lightgreen'>rotate_right</a>";
               map.buttons.photoMarker=new cameraMarker().addTo(map);
               map.state.mode='camera';
            }
            else if (map.state.mode=='camera')
            {
               map.state.mode='busy';
               map.buttons.photoMarker.removeFrom(map);
               container.innerHTML="<a href='#'>rotate_right</a>";
               map.buttons.photoMarker=new crosshairMarker().addTo(map);
               map.state.mode='crosshair';
            }
         }
         return container;
      }
   });

   var geotagui =  L.Class.extend({
      options: {
      },
      initialize: function initialize(map, options) {
         L.setOptions(this, options);

          map.state=this.options;
          map.buttons={};
          map.addControl(new L.Control.Fullscreen({
             title: {
                'false': 'View Fullscreen',
                'true': 'Exit Fullscreen'
             }
          }));

          var normalTiles = L.tileLayer('https://cdn.digitransit.fi/map/v1/{id}/{z}/{x}/{y}.png', {
             attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
             maxZoom: 18,
             tileSize: 512,
             zoomOffset: -1,
             id: 'hsl-map'}).addTo(map);


          L.control.layers({
             "digiTransit": normalTiles
             }, {}, {
                collapsed: false
             }).addTo(map);

          map.state.initialCenterLatLng = {
             lat: map.state.lat,
             lng: map.state.lon
          };
          map.state.cameraLatLng = map.state.initialCenterLatLng;

          map.buttons.photoMarker=new photoMarker({
                point: map.state.initialCenterLatLng,
                azimuth:map.state.azimuth
             }).addTo(map);

          map.setView(map.state.initialCenterLatLng, 13);

          map.buttons.startGeotaggingButton=new startGeotaggingButton();
          map.buttons.confirmGeotagButton=new confirmGeotagButton();
          map.buttons.cancelGeotaggingButton=new cancelGeotaggingButton();
          map.buttons.submitGeotagButton=new submitGeotagButton();
          map.buttons.toggleCrosshairGeotagButton=new toggleCrosshairGeotagButton();

          map.addControl(map.buttons.startGeotaggingButton);
          map.addControl(map.buttons.confirmGeotagButton);
          return this;
       }
    });

   L.geotagPhoto.geotagui=  function (map, options) {
          return new geotagui(map, options);
       };

   L.GeotagPhoto.geotagui=geotagui;



}(L));

$(function() {
   var options = {
      'lat':60.168683,
      'lon':24.940967,
      'azimuth':45
   }
   var map = L.map("map");
   var t= L.geotagPhoto.geotagui(map, options);

});
