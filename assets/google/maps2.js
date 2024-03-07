    //<![CDATA[

    var icon_0 = new GIcon(); 
    icon_0.image = 'google/marker/marker_0.png';
    icon_0.shadow = '';
    icon_0.iconSize = new GSize(26, 33);
    icon_0.shadowSize = new GSize(0, 0);
    icon_0.iconAnchor = new GPoint(13, 33);
    icon_0.infoWindowAnchor = new GPoint(13, 10);
	
    var icon_1 = new GIcon(); 
    icon_1.image = 'google/marker/marker_1.png';
    icon_1.shadow = '';
    icon_1.iconSize = new GSize(26, 33);
    icon_1.shadowSize = new GSize(0, 0);
    icon_1.iconAnchor = new GPoint(13, 33);
    icon_1.infoWindowAnchor = new GPoint(13, 10);

    var icon_2 = new GIcon(); 
    icon_2.image = 'google/marker/marker_2.png';
    icon_2.shadow = '';
    icon_2.iconSize = new GSize(26, 33);
    icon_2.shadowSize = new GSize(0, 0);
    icon_2.iconAnchor = new GPoint(13, 33);
    icon_2.infoWindowAnchor = new GPoint(13, 10);

    var icon_3 = new GIcon(); 
    icon_3.image = 'google/marker/marker_3.png';
    icon_3.shadow = '';
    icon_3.iconSize = new GSize(26, 33);
    icon_3.shadowSize = new GSize(0, 0);
    icon_3.iconAnchor = new GPoint(13, 33);
    icon_3.infoWindowAnchor = new GPoint(13, 10);

    var icon_4 = new GIcon(); 
    icon_4.image = 'google/marker/marker_4.png';
    icon_4.shadow = '';
    icon_4.iconSize = new GSize(26, 33);
    icon_4.shadowSize = new GSize(0, 0);
    icon_4.iconAnchor = new GPoint(13, 33);
    icon_4.infoWindowAnchor = new GPoint(13, 10);

    var icon_5 = new GIcon(); 
    icon_5.image = 'google/marker/marker_5.png';
    icon_5.shadow = '';
    icon_5.iconSize = new GSize(26, 33);
    icon_5.shadowSize = new GSize(0, 0);
    icon_5.iconAnchor = new GPoint(13, 33);
    icon_5.infoWindowAnchor = new GPoint(13, 10);

    var icon_6 = new GIcon(); 
    icon_6.image = 'google/marker/marker_6.png';
    icon_6.shadow = '';
    icon_6.iconSize = new GSize(26, 33);
    icon_6.shadowSize = new GSize(0, 0);
    icon_6.iconAnchor = new GPoint(13, 33);
    icon_6.infoWindowAnchor = new GPoint(13, 10);

    var icon_7 = new GIcon(); 
    icon_7.image = 'google/marker/marker_7.png';
    icon_7.shadow = '';
    icon_7.iconSize = new GSize(26, 33);
    icon_7.shadowSize = new GSize(0, 0);
    icon_7.iconAnchor = new GPoint(13, 33);
    icon_7.infoWindowAnchor = new GPoint(13, 10);

    var icon_8 = new GIcon(); 
    icon_8.image = 'google/marker/marker_8.png';
    icon_8.shadow = '';
    icon_8.iconSize = new GSize(26, 33);
    icon_8.shadowSize = new GSize(0, 0);
    icon_8.iconAnchor = new GPoint(13, 33);
    icon_8.infoWindowAnchor = new GPoint(13, 10);

    var icon_9 = new GIcon(); 
    icon_9.image = 'google/marker/marker_9.png';
    icon_9.shadow = '';
    icon_9.iconSize = new GSize(26, 33);
    icon_9.shadowSize = new GSize(0, 0);
    icon_9.iconAnchor = new GPoint(13, 33);
    icon_9.infoWindowAnchor = new GPoint(13, 10);

    var icon_10 = new GIcon(); 
    icon_10.image = 'google/marker/marker_10.png';
    icon_10.shadow = '';
    icon_10.iconSize = new GSize(26, 33);
    icon_10.shadowSize = new GSize(0, 0);
    icon_10.iconAnchor = new GPoint(13, 33);
    icon_10.infoWindowAnchor = new GPoint(13, 10);
								
    var customIcons = [];
    customIcons["0"] = icon_0;
	customIcons["1"] = icon_1;
    customIcons["2"] = icon_2;
	customIcons["3"] = icon_3;
	customIcons["4"] = icon_4;
	customIcons["5"] = icon_5;
	customIcons["6"] = icon_6;
	customIcons["7"] = icon_7;
	customIcons["8"] = icon_8;
	customIcons["9"] = icon_9;
	customIcons["10"] = icon_10;

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
        map.setCenter(new GLatLng(57.9971695, 56.2352792), 7);
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

    function createMarker(point, clid, client, title, adres, content, categ, tip, kol, idcategory) {
      var marker = new GMarker(point, customIcons[idcategory]);
      var html = "<b style=color:#99CC00 font=14><u>"+ client +"</u></b>&nbsp;(<b>"+ title +"</b>)<br><br><strong>Описание: </strong>"+ content +"<br><strong>Адрес: </strong>"+ adres +"<br><strong>Тип: </strong>"+ tip +"<br><strong>Стоимость (руб.): </strong>"+kol +"<br><strong>Стадия: </strong>"+ categ;
      GEvent.addListener(marker, 'click', function() {
        marker.openInfoWindowHtml(html);
      });
      return marker;
    }
	

    //]]>