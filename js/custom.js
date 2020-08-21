/* var map;

function initMap() {

    map = new google.maps.Map(document.getElementById('map'), {

    mapTypeControl: false,

    center: {lat: 51.256792, lng: -0.000815 },

    zoom: 13

  });
	
	//setMarkers( );

  new AutocompleteDirectionsHandler(map);

}




function AutocompleteDirectionsHandler(map) {

  this.map = map;

  this.originPlaceId = null;

  this.destinationPlaceId = null;

  this.travelMode = 'DRIVING';

  this.directionsService = new google.maps.DirectionsService;

  this.directionsDisplay = new google.maps.DirectionsRenderer;
  

  this.directionsDisplay.setMap(map);



  var originInput = document.getElementById('origin-input');

  var destinationInput = document.getElementById('destination-input');

  var modeSelector = document.getElementById('mode-selector');


  var originAutocomplete = new google.maps.places.Autocomplete(originInput  );

  // Specify just the place data fields that you need.

  originAutocomplete.setFields(['place_id']);



  var destinationAutocomplete =

      new google.maps.places.Autocomplete(destinationInput);

  // Specify just the place data fields that you need.

  destinationAutocomplete.setFields(['place_id']);



 // this.setupClickListener('changemode-walking', 'WALKING');

 // this.setupClickListener('changemode-transit', 'TRANSIT');

  this.setupClickListener('changemode-driving', 'DRIVING');



  this.setupPlaceChangedListener(originAutocomplete, 'ORIG');

  this.setupPlaceChangedListener(destinationAutocomplete, 'DEST');



  //this.map.controls[google.maps.ControlPosition.TOP_LEFT].push(originInput);

  //this.map.controls[google.maps.ControlPosition.TOP_LEFT].push( destinationInput);

  //this.map.controls[google.maps.ControlPosition.TOP_LEFT].push(modeSelector);





}



// Sets a listener on a radio button to change the filter type on Places

// Autocomplete.

AutocompleteDirectionsHandler.prototype.setupClickListener = function(

    id, mode) {

  var radioButton = document.getElementById(id);

  var me = this;



  radioButton.addEventListener('click', function() {

    me.travelMode = mode;

    me.route();

  });

};



AutocompleteDirectionsHandler.prototype.setupPlaceChangedListener = function(

    autocomplete, mode) {

  var me = this;

  autocomplete.bindTo('bounds', this.map);



  autocomplete.addListener('place_changed', function() {

    var place = autocomplete.getPlace();
	
    if (!place.place_id) {

      window.alert('Please select an option from the dropdown list.');

      return;

    }

    if (mode === 'ORIG') {

      me.originPlaceId = place.place_id;
	  
	  
	} else {

      me.destinationPlaceId = place.place_id;
	  
	}

    me.route();
	

  });

};



//end code

AutocompleteDirectionsHandler.prototype.route = function() {

  if (!this.originPlaceId || !this.destinationPlaceId) {
	  

    return;

  }

  var me = this;

	
  this.directionsService.route(

      {

        origin: {'placeId': this.originPlaceId},

        destination: {'placeId': this.destinationPlaceId},

        travelMode: this.travelMode

      },

      function(response, status) {
		
		if (status === 'OK') {

          me.directionsDisplay.setDirections(response);
		  
		  estimatetime();
		  
		 // setMarkers();
		  
		  
		} else {

          window.alert('Directions request failed due to ' + status);

        }

      });

};

function setmarkerput(){
	
	var service = new google.maps.places.PlacesService(map);
	service.getDetails({
		placeId: originPlaceId
	}, function (result, status) {
		var marker = new google.maps.Marker({
			map: map,
			place: {
				placeId: originPlaceId,
				location: result.geometry.location
			}
		});
	});
}
function setmarker(){
	
						
	var from_adrres = document.getElementById("from_location_addr_resv").value;					
	var drop_adrres = document.getElementById("drop_location_addr_resv").value;		
	
	var from_lat = document.getElementById("from_location_lat_resv").value;					
	var from_long = document.getElementById("from_location_long_resv").value;	
	
	var drop_lat = document.getElementById("destionation_loc_lat_resv").value;					
	var drop_long = document.getElementById("destionation_loc_long_resv").value;					
						
	console.log(from_lat);
	console.log(from_long);
	
	console.log(drop_lat);
	console.log(drop_long);
	
	var locations = [
	  [  from_adrres  , from_lat , from_long , 2],
	  [  drop_adrres  , drop_lat , drop_long , 1]
	];
	
	console.log(locations);
	
	var map = new google.maps.Map(document.getElementById('map'), {
	  zoom: 13,
	  center: new google.maps.LatLng( 51.256792 , -0.000815 ),
	  mapTypeId: google.maps.MapTypeId.ROADMAP
	});
	

	var infowindow = new google.maps.InfoWindow();

	var marker, i;

	for (i = 0; i < locations.length; i++) {  
		 
		var image = 'https://developers.google.com/maps/documentation/javascript/examples/full/images/beachflag.png';

	  marker = new google.maps.Marker({
		position: new google.maps.LatLng(locations[i][1], locations[i][2]),
		icon: image,
		map: map
	  });

	  google.maps.event.addListener(marker, 'click', (function(marker, i) {
		return function() {
		  infowindow.setContent(locations[i][0]);
		  infowindow.open(map, marker);
		}
	  })(marker, i));
	}
}

function validateNumber(num) {
	if(isNaN(num)) {
		return false;
	} else if(String(num).charAt(0) != 0 || String(num).charAt(1) != 7 || String(num).length != 11 ) {
		return false;
	} else {
		return true;
	}
}

jQuery( document ).ready(function() {

  jQuery("#select_hour").click(function(){

    jQuery("#content_hour").css("display","block");

  });
  
    jQuery("#mobile_numberhold").focusout(function(){
		var num = jQuery("#mobile_numberhold").val();
		var checknum = validateNumber(num);
		
		if(!checknum){
			jQuery("#show_validnum").show();
			jQuery("#submitbtnbook").attr("disabled","disabled");
		} else {
			jQuery("#show_validnum").hide();
			jQuery("#submitbtnbook").attr("disabled",false);
		}
		
		console.log(checknum);
	});

}); */


function GoogleMap_selected() {
    // Lat Long for starting Position of MAP
    var lattitude_value = "51.256792";
    var longitude_value = "-0.000815 ";

    // Initializing the map
    this.initialize = function() {
        // Creating MAP options
        var mapOptions = {
            zoom: 12,
            center: new google.maps.LatLng(lattitude_value, longitude_value),
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        //Drawing inital MAP
        var map = new google.maps.Map(document.getElementById("map"), mapOptions);
        
        //Setting Marker variable
        var marker = new google.maps.Marker({
            map: map,
            draggable: false,
        });
		
		 var options_search = {
		  types: ['(regions)'],
		  componentRestrictions: {country: "uk"}
		 };
		 
		 //via address autocomplete by mubeen
		 var via_addr = jQuery(".via_address_pick").val();
		 var via_addr = document.getElementById('origin-input');

		var autocomplete_via = new google.maps.places.Autocomplete(via_addr);
		
		var via_place;
        autocomplete_via.addListener('place_changed', function() {
            via_place = autocomplete_via.getPlace();
            if (!via_place.geometry) {
                window.alert("Autocomplete's returned place contains no geometry");
                return;
            }
           
        });
		
		 //end via address autocomplete by mubeen

        /* Input start_point start  */
        var s_input = document.getElementById('origin-input');
        var s_autocomplete = new google.maps.places.Autocomplete(s_input);
        s_autocomplete.bindTo('bounds', map);
        var s_infowindow = new google.maps.InfoWindow();
        var s_place;
        s_autocomplete.addListener('place_changed', function() {
            s_place = s_autocomplete.getPlace();
            if (!s_place.geometry) {
                window.alert("Autocomplete's returned place contains no geometry");
                return;
            }

            // If the place has a geometry, then present it on a map.
            if (s_place.geometry.viewport) {
                map.fitBounds(s_place.geometry.viewport);
            } else {
                map.setCenter(s_place.geometry.location);
                map.setZoom(17);
            }

            makeMarker(s_place.geometry.location, icons.start, "Start Point", map);
            
			jQuery("#from_location_lat").val( s_place.geometry.location.lat() );
			jQuery("#from_location_lat_resv").val( s_place.geometry.location.lat() );
					
			jQuery("#from_location_long").val( s_place.geometry.location.lng() );
			jQuery("#from_location_long_resv").val( s_place.geometry.location.lng() );

            createAndDisplayRoute();
        });
        /* Input start_point end  */

        /* Input end_point start  */
        var e_input = document.getElementById('destination-input');
        var e_autocomplete = new google.maps.places.Autocomplete(e_input,options_search);
        e_autocomplete.bindTo('bounds', map);
        e_autocomplete.addListener('place_changed', function() {
            var e_place = e_autocomplete.getPlace();
            if (!e_place.geometry) {
                window.alert("Autocomplete's returned place contains no geometry");
                return;
            }

            // If the place has a geometry, then present it on a map.
            if (e_place.geometry.viewport) {
                map.fitBounds(e_place.geometry.viewport);
            } else {
                map.setCenter(e_place.geometry.location);
                map.setZoom(17);
            }
            makeMarker(e_place.geometry.location, icons.end, "End Point", map);
            
            jQuery("#destionation_loc_lat").val( e_place.geometry.location.lat() );
			jQuery("#destionation_loc_lat_resv").val( e_place.geometry.location.lat() );
			
			jQuery("#destionation_loc_long").val( e_place.geometry.location.lng() );
			jQuery("#destionation_loc_long_resv").val( e_place.geometry.location.lng() );

            createAndDisplayRoute();
        });
        google.maps.event.addDomListener(window, 'load', initialize);
        /* Input end_point end  */

        function createAndDisplayRoute(){
            var from = new google.maps.LatLng(jQuery("#from_location_lat").val(), jQuery("#from_location_long").val());
            var to = new google.maps.LatLng(jQuery("#destionation_loc_lat").val(), jQuery("#destionation_loc_long").val());
            
            if(from == undefined || to == undefined) return false;
            
            if(from == "" || to == "") return false;
            
            if(from == "(0, 0)" || to == "(0, 0)") return false;

            map = new google.maps.Map(document.getElementById("map"), mapOptions);

            var directionsService = new google.maps.DirectionsService();
            var directionsRequest = {
                origin: from,
                destination: to,
                travelMode: google.maps.DirectionsTravelMode.DRIVING,
                unitSystem: google.maps.UnitSystem.METRIC
            };

            directionsService.route(directionsRequest,function(response, status) {
                if (status == google.maps.DirectionsStatus.OK) {
                    new google.maps.DirectionsRenderer({
                        map: map,
                        directions: response,
                        suppressMarkers: true
                    });
					
					estimatetime();
					
                    var leg = response.routes[0].legs[0];
                    makeMarker(leg.start_location, icons.start, "Start", map);
                    makeMarker(leg.end_location, icons.end, 'End', map);
				} else {
                    alert("Unable to retrive route");
                }
            });
        }
    }

    function makeMarker(position, icon, title, map) {
        new google.maps.Marker({
            position: position,
            map: map,
            icon: icon,
            title: title
        });
    }

    var icons = {
        start: new google.maps.MarkerImage(
            // URL
            //'http://maps.google.com/mapfiles/ms/micons/blue.png',
            'http://www.caterhamairporttaxi.co.uk/wp-content/uploads/2019/07/start-pin.png',
            // (width,height)
            new google.maps.Size(44, 59),
            // The origin point (x,y)
            new google.maps.Point(0, 0),
            // The anchor point (x,y)
            new google.maps.Point(22, 32)
        ),
        end: new google.maps.MarkerImage(
            // URL
            //'http://maps.google.com/mapfiles/ms/micons/green.png',
            'http://www.caterhamairporttaxi.co.uk/wp-content/uploads/2019/07/end-pin.png',
            // (width,height)
            new google.maps.Size(44, 59),
            // The origin point (x,y)
            new google.maps.Point(0, 0),
            // The anchor point (x,y)
            new google.maps.Point(22, 32)
        )
    };
}

function initialize() {
    var instance = new GoogleMap_selected();
    instance.initialize();
}

google.maps.event.addDomListener(window, 'load', initialize);

