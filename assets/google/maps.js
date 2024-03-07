   var map;
	//создаем свой маркер
	var tinyIcon = new GIcon();
	tinyIcon.image = "google/marker/marker_green.png"; // путь к иконке
	tinyIcon.shadow = ""; // к тени (если она вам нужна)
	tinyIcon.iconSize = new GSize(26, 33); //размеры иконки
	tinyIcon.shadowSize = new GSize(0, 0); // размеры тени
	tinyIcon.iconAnchor = new GPoint(13, 33); // "центр" иконки
	tinyIcon.infoWindowAnchor = new GPoint(13, 10); // точка привязки инфоокна	
		
   function initialize(adres) {
    //создаем объект для работы с картой
	//document.getElementById("mapdiv").style.display = 'block';
	if (adres) getAdress(adres); 
      map = new GMap2(document.getElementById("map_canvas"));
	  if (GBrowserIsCompatible()) {
	  map.hideControls();
		GEvent.addListener(map, "mouseover", function(){
			map.showControls();
		});
		GEvent.addListener(map, "mouseout", function(){
			map.hideControls();
		});	  
      map.setCenter(new GLatLng(57.9971695, 56.2352792), 12);
	  map.enableScrollWheelZoom();	   
	  map.addControl(new GSmallMapControl());
	  
	  GEvent.addListener(map, "click", function(overlay, latlng) {
		if (latlng) {
	    map.clearOverlays();	
		marker = new GMarker(latlng, {draggable:false, icon:tinyIcon});
		document.getElementById('lat').value = latlng.lat();
        document.getElementById('lan').value = latlng.lng();
		GEvent.addListener(marker, "click", function() {		  
			geocoder.getLocations(latlng, function(addresses) {
			  if(addresses.Status.code != 200) {
				alert("reverse geocoder failed to find an address for " + latlng.toUrlValue());
			  } else { 
				var result = addresses.Placemark[0];
				map.openInfoWindow(latlng, result.address);
		        document.getElementById('adres').value = result.address;
				html = result.address;
			  }
			});

		  document.getElementById('adres').value = result.address;
		  document.getElementById('lat').value = latlng.lat();
          document.getElementById('lan').value = latlng.lng();
		});		
        //GEvent.addListener(marker, "dragstart", function() { map.closeInfoWindow();  });
	    //GEvent.addListener(marker, "dragend", function() {
	    //  document.getElementById('lat').value = latlng.lat();
        //  document.getElementById('lan').value = latlng.lng();  });		
		  map.addOverlay(marker);
		}
	});
	  
   }
   }

	function load() {
      if (GBrowserIsCompatible()) {
        var map = new GMap2(document.getElementById("map"));
	    map.hideControls();
		GEvent.addListener(map, "mouseover", function(){
			map.showControls();
		});
		GEvent.addListener(map, "mouseout", function(){
			map.hideControls();
		});	
        //map.addControl(new GMapTypeControl());
		map.enableScrollWheelZoom();
		//map.addControl(new GSmallMapControl());
		map.addControl(new GSmallMapControl(), new GControlPosition(G_ANCHOR_TOP_LEFT, new GSize(7,32)));
		map.addControl(new GScaleControl());
        map.addControl(new GOverviewMapControl());
        map.setCenter(new GLatLng(57.9971695, 56.2352792), 16);
            // add zoom control....
            var boxStyleOpts = { opacity: .2, border: "2px solid yellow" };
            var otherOpts = {
              buttonHTML: "<img src='google/zoom-control-inactive.png' alt='Увеличить' />",
              buttonZoomingHTML: "<img src='google/zoom-control-active.png' alt='Снять увеличение' />",
              buttonStartingStyle: {width: '17px', height: '17px'},
              overlayRemoveTime: 0 };
            map.addControl(new DragZoomControl(boxStyleOpts, otherOpts, {}),
            new GControlPosition(G_ANCHOR_TOP_LEFT, new GSize(17,7)));
        GDownloadUrl("google/megamap.php", function(data) {
          var xml = GXml.parse(data);
          var markers = xml.documentElement.getElementsByTagName("marker");
          for (var i = 0; i < markers.length; i++) {
            var title = markers[i].getAttribute("title");
			var clid = markers[i].getAttribute("clid");
			var client = markers[i].getAttribute("client");
			var content = markers[i].getAttribute("content");
			var categ = markers[i].getAttribute("categ");
			var tip = markers[i].getAttribute("tip");
			var kol = markers[i].getAttribute("kol");
            var adres = markers[i].getAttribute("adres");
            var idcategory = markers[i].getAttribute("idcategory");
            var point = new GLatLng(parseFloat(markers[i].getAttribute("lat")),
                                    parseFloat(markers[i].getAttribute("lan")));
            var marker = createMarker(point, clid, client, title, adres, content, categ, tip, kol, idcategory);
            //map.addOverlay(marker);
			var clusterer = new Clusterer(map); clusterer.AddMarker(marker, 'Группа объектов'); // это вместо map.addOverlay(marker);
          }
        });
      }
    }

	var geocoder = new GClientGeocoder();
	
	function getAdress(address) {
		if (!address) var address = document.getElementById("sityname").value;
		geocoder.getLatLng(
			address,
			function(point) {
				if (!point) {
					alert(address + " не найден\nВозможно отсутствует карта Гугл.");
				} else {
					geocoder.getLocations(address, addAdr);
				}
			}
		);
	}
	
	function addAdr(response) {
		//удаляем слои, если они есть
		map.clearOverlays();
		if (!response || response.Status.code != 200) {
			alert("\"" + address + "\" не найден");
		} else {
			//создаем объект типа GLatLng и надпись
			place = response.Placemark[0];
			point = new GLatLng(place.Point.coordinates[1],
		     	               place.Point.coordinates[0]);
			marker = new GMarker(point,{draggable:false, icon:tinyIcon});
			//размещаем надпись на карте
			map.addOverlay(marker);
			//добавляем текст на надпись
			/*marker.openInfoWindowHtml(place.address + '<br />' + 
				'Широта: ' + place.Point.coordinates[1] + '<br />' +
				'Долгота: ' + place.Point.coordinates[0]);
			map.setCenter(new GLatLng(place.Point.coordinates[1], place.Point.coordinates[0]), 16);
			document.getElementById('adress').value = place.address;
		    document.getElementById('lat').value = place.Point.coordinates[1];
            document.getElementById('lan').value = place.Point.coordinates[0];*/
			map.setCenter(new GLatLng(place.Point.coordinates[1], place.Point.coordinates[0]), 16);
		}
		
	} 
	
	function showAdr(lat,lan) {
		//удаляем слои, если они есть
		map.clearOverlays();
			//создаем объект типа GLatLng и надпись
			point = new GLatLng(lat, lan);
			marker = new GMarker(point,{draggable:false, icon:tinyIcon});
			//размещаем надпись на карте
			map.addOverlay(marker);
			map.setCenter(point, 16);
	} 

   var map;
	
   function initialize2(lat,lan, html, markerid) {
      if (!lat) {
		  lat = 57.9971695;
		  lan = 56.2352792;
	  }
	var tinyIcon = new GIcon();
	tinyIcon.image = "google/marker/marker_green.png"; // путь к иконке
	tinyIcon.shadow = ""; // к тени (если она вам нужна)
	tinyIcon.iconSize = new GSize(26, 33); //размеры иконки
	tinyIcon.shadowSize = new GSize(0, 0); // размеры тени
	tinyIcon.iconAnchor = new GPoint(13, 33); // "центр" иконки
	tinyIcon.infoWindowAnchor = new GPoint(13, 10); // точка привязки инфоокна	
    //создаем объект для работы с картой
      map = new GMap2(document.getElementById("map_canvas"));
	  if (GBrowserIsCompatible()) {
	  map.hideControls();
		GEvent.addListener(map, "mouseover", function(){
			map.showControls();
		});
		GEvent.addListener(map, "mouseout", function(){
			map.hideControls();
		});
      map.setCenter(new GLatLng(lat, lan), 12);
	  map.enableScrollWheelZoom();	  
	  //geocoder.getLatLng("Пермь",map.setCenter(point, 12));  
	  map.addControl(new GSmallMapControl());  
			point = new GLatLng(lat, lan);
			//marker = new GMarker(point);
			var marker = new GMarker(point, {draggable:false, icon:tinyIcon});
			//размещаем надпись на карте
			map.addOverlay(marker);
			map.setCenter(point, 16);	
			marker.openInfoWindowHtml(html);
	 GEvent.addListener(marker, "click", function() {
	 marker.openInfoWindowHtml(html);											  
	 });
	 
   }
   }