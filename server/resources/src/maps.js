import L from "leaflet";
import "leaflet.locatecontrol";
import "../node_modules/leaflet.locatecontrol/dist/L.Control.Locate.min.css";
import "../node_modules/leaflet/dist/leaflet.css";
import "./maps.css";

const iconRetinaUrl = "resources/dist/marker-icon-2x.png";
const iconUrl = "resources/dist/marker-icon.png";
const shadowUrl = "resources/dist/marker-shadow.png";
const iconDefault = new L.Icon({
  iconRetinaUrl,
  iconUrl,
  shadowUrl,
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  tooltipAnchor: [16, -28],
  shadowSize: [41, 41]
});

let marker;
let feature;
let map;

function setMarker (LatLng) {
  if (marker) {
    console.log("Marker exists");
    // console.log(marker);
    marker.remove();
  }
  console.log(LatLng);
  if ($("input[name='place']").val() !== undefined) {
    $("input[name='place']").val(LatLng.lat + ";" + LatLng.lng);
  }
  marker = L.marker(LatLng, { icon: iconDefault }).addTo(map);
}

var mapsList = [];

export function loadMap (lat = undefined, lng = undefined, selectorId = undefined, select = true, removeMap = false) {
  console.log("Loading map...", [lat, lng, selectorId, select]);
  console.trace();
  if (lat === undefined && lng === undefined) {
    lat = 45.5285; // TODO: replace hard-coded into cookie reading
    lng = 10.2956;
  }
  if (selectorId === undefined) {
    selectorId = "map";
  }
  let container = L.DomUtil.get(selectorId);
  if(container._leaflet_id){
    console.log(mapsList);
    if(removeMap){
      mapsList[0].off();
      mapsList[0].remove();
      mapsList.splice(0, 1);
    } else {
      console.log("Skipping map loading because already loaded...");
      return true;
    }
  }
  const zoom = select ? 10 : 17;
  const latLng = new L.LatLng(lat, lng);
  L.Map.addInitHook(function () {
    mapsList.push(this); // Use whatever global scope variable you like.
  });
  map = new L.Map(selectorId, { zoomControl: true });

  const osmUrl = "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";
  const osmAttribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
  const osm = new L.TileLayer(osmUrl, { maxZoom: 20, attribution: osmAttribution });

  map.setView(latLng, zoom).addLayer(osm);

  if (select) {
    map.on("click", function (e) {
      setMarker(e.latlng);
    });

    L.Control.CustomLocate = L.Control.Locate.extend({
	    _drawMarker: function () {
	      setMarker(this._event.latlng);
      },
      _onDrag: function () {},
      _onZoom: function () {},
      _onZoomEnd: function () {}
    });

    const lc = new L.Control.CustomLocate({
    	icon: "fa fa-map-marker",
    	cacheLocation: false, // disabled for privacy reasons
      initialZoomLevel: 16
    }).addTo(map);

	  if ($("#addr").val() !== undefined) {
	    document.getElementById("addr").addEventListener("keydown", function (event) {
    	  if (event.key === "Enter") {
    		  event.preventDefault();
          $("#search > button").trigger("click");
        }
      });
    }
	  window.addEventListener("focus", function (event) {
	    if ($("#addr").val() === "") {
	    	console.log("Loading location from clipboard");
        try {
          navigator.clipboard.readText().then((text) => {
            $("#addr").val(text);
            if (!addrSearch()) {
              $("#addr").val("");
            }
          }).catch((err) => {
            console.error("Failed to read clipboard contents: ", err);
          });
        } catch(error) {
          console.error(error);
        }
	    }
	  });
  } else {
    setMarker(latLng);
  }
  map.invalidateSize();
}

// from unknown source in the Internet
export function chooseAddr (addrLat, addrLng, zoom = undefined, lat1 = undefined, lng1 = undefined, lat2 = undefined, lng2 = undefined, osmType = undefined) {
  addrLat = addrLat.replace(",", ".");
  addrLng = addrLng.replace(",", ".");
  if (lat1 !== undefined && lng1 !== undefined && lat2 !== undefined && lng2 !== undefined && osmType !== undefined) {
    const loc1 = new L.LatLng(lat1, lng1);
    const loc2 = new L.LatLng(lat2, lng2);
    const bounds = new L.LatLngBounds(loc1, loc2);
    console.log(lat1, lng1, lat2, lng2, osmType);
    setMarker(new L.LatLng(addrLat, addrLng));
    if (feature) {
      map.removeLayer(feature);
    }
    if (osmType === "node") {
      map.fitBounds(bounds);
      map.setZoom(16);
    } else {
      const loc3 = new L.LatLng(lat1, lng2);
      const loc4 = new L.LatLng(lat2, lng1);
      feature = L.polyline([loc1, loc4, loc2, loc3, loc1], { color: "red" }).addTo(map);
      map.fitBounds(bounds);
    }
  } else if (addrLat !== undefined && addrLng !== undefined) {
    const loc = new L.LatLng(addrLat, addrLng);
    console.log(loc);
    setMarker(loc);
    if (zoom !== undefined) {
      map.setView(loc, zoom);
    } else {
      map.setView(loc);
    }
  }
}

// started from https://derickrethans.nl/leaflet-and-nominatim.html
export function addrSearch (stringResultsFound= undefined, stringResultsNotFound = undefined) {
  function searchError (error, checkClipboard) {
    if (!checkClipboard) {
      $("#results").empty();
      $("<p>", { html: stringResultsNotFound }).appendTo("#results");
      console.error(error);
    }
    return false;
  }
  let inp = document.getElementById("addr").value;
  // if translation strings are not defined, skip the nominatim step and don't log errors (no console.error)
  const checkClipboard = stringResultsFound=== undefined && stringResultsNotFound === undefined;
  $("#results").empty();

  if (inp.match("\@(-?[\d\.]*)")) { // Google Maps
    try {
      inp = inp.split("@")[1].split(",");
      chooseAddr(inp[0], inp[1]);
      return true;
    } catch (error) {
      searchError(error, checkClipboard);
    }
  } else if (inp.includes("#map=")) { // OpenStreetMap website
    try {
      inp = inp.split("#map=")[1].split("/");
      chooseAddr(inp[1], inp[2], inp[0]);
      return true;
    } catch (error) {
      searchError(error, checkClipboard);
    }
  } else if (inp.match(/[0-9]+,\s[0-9]+/)) { // Bing
    try {
      inp = inp.split(", ");
      chooseAddr(inp[0], inp[1]);
      return true;
    } catch (error) {
      searchError(error, checkClipboard);
    }
  } else if (inp.match(/[0-9]+;[0-9]+/)) { // DB dump
    try {
      inp = inp.split(";");
      chooseAddr(inp[0], inp[1]);
      return true;
    } catch (error) {
      searchError(error, checkClipboard);
    }
  } else if (!checkClipboard) {
    $.getJSON("https://nominatim.openstreetmap.org/search?format=json&limit=5&q=" + inp, function (data) {
      const items = [];

      $.each(data, function (key, val) {
        items.push("<li><a href='' onclick='allertaJS.maps.chooseAddr(\"" + val.lat + "\", \"" + val.lon + "\", undefined, " + val.boundingbox[0] + ", " + val.boundingbox[2] + ", " + val.boundingbox[1] + ", " + val.boundingbox[3] + ", \"" + val.osm_type + "\"); return false;'>" + val.display_name + "</a></li>");
      });

      if (items.length !== 0) {
        $("#results").empty();
        $("<p>", { html: stringResultsFound+ ":" }).appendTo("#results");
        $("<ul/>", {
          class: "results-list",
          html: items.join("")
        }).appendTo("#results");
      } else {
        $("#results").empty();
        $("<p>", { html: stringResultsNotFound }).appendTo("#results");
      }
    });
  } else {
    return false;
  }
}
