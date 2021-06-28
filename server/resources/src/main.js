import "bootstrap";
import "bootstrap/dist/css/bootstrap.min.css";
import "./main.css";
import "./font-awesome.scss";
import "bootstrap-datepicker";
import "../node_modules/bootstrap-toggle/css/bootstrap-toggle.css";
import "../node_modules/bootstrap-toggle/js/bootstrap-toggle.js";
import "../node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css";
import "time-input-polyfill/auto";
import "jquery-pjax";
import toastr from "expose-loader?exposes=toastr!toastr";
import "toastr/build/toastr.css";
 
window.toastr = toastr;
toastr.options = {
  closeButton: false,
  debug: false,
  newestOnTop: false,
  progressBar: true,
  positionClass: "toast-bottom-right",
  preventDuplicates: false,
  onclick: null,
  showDuration: "300",
  hideDuration: "1000",
  timeOut: "5000",
  extendedTimeOut: "1000",
  showEasing: "swing",
  hideEasing: "linear",
  showMethod: "fadeIn",
  hideMethod: "fadeOut"
};

if(!('fetch' in window)){
  import(`./polyfills.js`).then((_ ) => {
    window.Promise = _.Promise;
    window.fetch = _.fetch;
  });
}

function objectToURLParams(list){
  let params = new URLSearchParams();
  list.forEach((el) => {
    params.append(el[0], el[1]);
  });
  return params.toString();
}

function displayResponseMessage(r){
  console.log(r);
  let statusCode = r.status;
  r.json().then(r => {
    if(statusCode === 200){
      toastr.success(r.message);
    } else {
      toastr.error(r.message);
    }
  });
}

$.fn.loading = function (action = "start", options) {
  const opts = $.extend({}, $.fn.loading.defaults, options);
 
  if (action === "show") {
    this.addClass("loading_blur");
    let message_b = $("<b>").text(opts.message);
    let message = $("<div>", {id: "loading_div", "class": "loading_overlay"}).append(message_b);
    $("body").append(message);
  } else if (action === "hide") {
    this.removeClass("loading_blur");
    this.addClass("loading_no_blur");
    setTimeout(() => {
      this.removeClass("loading_no_blur");
    }, 1000);
    $("#loading_div").remove();
  }
};
 
$.fn.loading.defaults = {
  message: "Loading..."
};
 
console.log("Commit: " + process.env.GIT_VERSION);
console.log("Date: " + process.env.GIT_AUTHOR_DATE);
console.log("Bundle mode: " + process.env.BUNDLE_MODE);
console.log("Bundle date: " + new Date(process.env.BUNDLE_DATE).toISOString());
 
$(document).pjax("a:not(.pjax_disable)", "#content", { timeout: 100000 });
$(document).on("pjax:start", function () {
  if (document.getElementById("topNavBar") !== undefined) {
    document.getElementById("topNavBar").className = "topnav";
  }
  oldData = "null";
  fillTable = undefined;
  tableEngine = "datatables";
  if (loadTableInterval !== undefined) {
    clearInterval(loadTableInterval);
    loadTableInterval = undefined;
  }
});
 
// Cookie functions from w3schools
function setCookie (cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  const expires = "expires=" + d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}
function getCookie (cname) {
  const name = cname + "=";
  const decodedCookie = decodeURIComponent(document.cookie);
  const ca = decodedCookie.split(";");
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === " ") {
      c = c.substring(1);
    }
    if (c.indexOf(name) === 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
 
$(document).ajaxError(function (event, xhr, settings, error) {
  console.error("Error requesting content: " + error + " - status code " + xhr.status);
  console.log(event);
  console.log(xhr);
  console.log(settings);
});
 
if (getCookie("authenticated")) {
  var installServiceWorker = true;
  if (window.skipServiceWorkerInstallation !== undefined) { // if you want to disable SW for example via GreasyFork userscript
    installServiceWorker = false;
  }
  if (getCookie("disableServiceWorkerInstallation")) {
    console.log("Skipping ServiceWorker installation because cookie 'disableServiceWorkerInstallation' exists");
    installServiceWorker = false;
  }
  if ("serviceWorker" in navigator) {
    if ("connection" in navigator && navigator.connection.saveData && !getCookie("forceServiceWorkerInstallation")) {
      console.log("Skipping ServiceWorker installation because saveData is enabled");
      installServiceWorker = false;
    }
    if ("storage" in navigator && "estimate" in navigator.storage && !getCookie("forceServiceWorkerInstallation")) {
      navigator.storage.estimate().then((quota) => {
        const requiredMemory = 3 * 1e+6;
        if (quota < requiredMemory) {
          console.log("Skipping ServiceWorker installation because memory is low. memory=" + quota);
          installServiceWorker = false;
        }
      });
    }
  } else {
    installServiceWorker = false;
  }
}
if (installServiceWorker) {
  window.addEventListener("load", () => {
    navigator.serviceWorker.register("sw.js").then((registration) => {
      console.log("SW registered: ", registration);
    }).catch((registrationError) => {
      console.log("SW registration failed: ", registrationError);
    });
  });
}
 
var offline = false;
var loadTableInterval = undefined;
var oldData = "null";
var tableEngine = "datatables";
var fillTable = undefined;
var fillTableLoaded = undefined;
 
window.addEventListener("securitypolicyviolation", console.error.bind(console));
 
$(function() {
  if(getCookie("JSless")){
    location.href="?JSless=0";
  }
  $("#topNavBar").show();
  $("#content").show();
  $("#footer").show();
  if ($("#frontend_version") !== undefined) {
    $("#frontend_version").append(process.env.GIT_VERSION + " aggiornamento " + new Date(process.env.BUNDLE_DATE).toLocaleString());
  }
  $("#menuButton").on("click", function() {
    const topNavBar = document.getElementById("topNavBar");
    if (topNavBar.className === "topnav") {
      topNavBar.className += " responsive";
      document.getElementById("navbar_right").style.display = "flex";
      document.getElementById("navbar_right").style.justifyContent = "flex-end";
    } else {
      topNavBar.className = "topnav";
      document.getElementById("navbar_right").style.display = "none";
      document.getElementById("navbar_right").style.justifyContent = null;
    }
  });
  window.addEventListener('resize', () => {
    console.log(window.innerWidth);
    if(window.innerWidth > 650) {
      document.getElementById("navbar_right").style.display = "flex";
      document.getElementById("navbar_right").style.justifyContent = "flex-end";
    } else {
      document.getElementById("navbar_right").style.display = null;
      document.getElementById("navbar_right").style.justifyContent = null;
      document.getElementById("topNavBar").className = "topnav";
    }
  });
  $("#logout-text").on("click", function(){ location.href='logout.php'; });
});
 
export var lastTableLoadConfig = {
    tablePage: undefined,
    setTableRefreshInterval: true,
    interval: 10000,
    onlineReload: false, 
    useCustomTableEngine: false,
    callback: false
}
 
export async function loadTable ({ tablePage, setTableRefreshInterval = true, interval = 10000, onlineReload = false, useCustomTableEngine = false, callbackRepeat = false, callback = false, saveFuncParam = true }) {
  if(loadTableInterval !== undefined) {
    clearInterval(loadTableInterval);
    loadTableInterval = undefined;
  }
  if (typeof fillTable === "undefined") {
    if (useCustomTableEngine !== false) {
      tableEngine = useCustomTableEngine;
    } else {
      tableEngine = "datatables";
    }
    fillTableLoaded = await import(/* webpackChunkName: `[request]` */ `./table_engine_${tableEngine}.js`)
      .then(({ default: _ }) => {
        return _;
      });
  }
  if ("getBattery" in navigator) {
    navigator.getBattery().then((level, charging) => {
      if (!charging && level < 0.2) {
        return;
      }
    });
  }
  if ("deviceMemory" in navigator && navigator.deviceMemory < 0.2) {
    return;
  }
  if(saveFuncParam){
      lastTableLoadConfig = {
        tablePage: tablePage,
        setTableRefreshInterval: setTableRefreshInterval,
        interval: interval,
        onlineReload: onlineReload, 
        useCustomTableEngine: useCustomTableEngine,
        callback: callback
      }
  }
  const replaceLatLngWithMap = tablePage === "services" || tablePage === "trainings";
  $.getJSON({
    url: "resources/ajax/ajax_" + tablePage + ".php",
    data: { oldData: oldData },
    success: function (data, status, xhr) {
      oldData = xhr.getResponseHeader("data"); // TODO: refactoring and adding comments
      console.log(data);
      if (data.length > 0) {
        fillTableLoaded({ data, replaceLatLngWithMap, callback });
        if(typeof(Headers) == "function"){
          const headers = new Headers();
          headers.append("date", Date.now());
          caches.open("tables-1").then((cache) => {
            cache.put("/table_" + tablePage + ".json", new Response(xhr.responseText, { headers: headers }));
          });
        }
      }
      if (offline) { // if xhr request successful, client is online
        console.log(onlineReload);
        if (onlineReload) {
          location.reload(); // for offline page
        } else {
          $("#offline_alert").hide(400);
          offline = false;
        }
      }
    }
  }).fail(function (data, status) {
    if (status === "parsererror") {
      if ($("#table_body").children().length === 0) { // this is a server-side authentication error on some cheap hosting providers
        loadTable(tablePage, setTableRefreshInterval, interval); // retry
      } // else nothing
    } else {
      caches.open("tables-1").then((cache) => {
        cache.match("/table_" + tablePage + ".json").then((response) => {
          response.json().then((data) => {
            fillTableLoaded({ data, replaceLatLngWithMap, callback });
            console.log("Table loaded from cache");
            $("#offline_update").text(new Date(parseInt(response.headers.get("date"), 10)).toLocaleString());
          });
        });
      });
      if (!offline) { // if xhr request fails, client is offline
        $("#offline_alert").show(400);
        offline = true;
      }
    }
  });
  if (setTableRefreshInterval) {
    if ("connection" in navigator && navigator.connection.saveData) {
      interval += 5000;
    }
    console.log("table_load interval " + interval);
    loadTableInterval = setInterval(function () {
      loadTable({ tablePage, setTableRefreshInterval: false, interval, onlineReload, useCustomTableEngine, callback: callbackRepeat ? callback : false, saveFuncParam: false });
    }, interval);
  }
}

export function reloadTable(){
  allertaJS.main.loadTable({
    tablePage: lastTableLoadConfig.tablePage,
    setTableRefreshInterval: lastTableLoadConfig.setTableRefreshInterval,
    interval: lastTableLoadConfig.interval,
    onlineReload: lastTableLoadConfig.onlineReload,
    useCustomTableEngine: lastTableLoadConfig.useCustomTableEngine,
    callback: lastTableLoadConfig.callback,
  });
  if (loadTableInterval !== undefined) {
    clearInterval(loadTableInterval);
    loadTableInterval = undefined;
  }
}

export function activate(id, token_list) {
  fetch("resources/ajax/ajax_change_availability.php", {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: objectToURLParams([
      ["change_id", id],
      ["dispo", 1],
      ["token_list", token_list]
    ])
  }).then(r => {
    displayResponseMessage(r);
    allertaJS.main.reloadTable();
  });
}
 
export function deactivate(id, token_list) {
  fetch("resources/ajax/ajax_change_availability.php", {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: objectToURLParams([
      ["change_id", id],
      ["dispo", 0],
      ["token_list", token_list]
    ])
  }).then(r => {
    displayResponseMessage(r);
    allertaJS.main.reloadTable();
  });
}

export function loadListListCallback(){
  $("tbody tr").on("click", function(event) {
    let targetElem = event.target;
    let userInfoElem = event.currentTarget.querySelector("[data-user]");
    let userId = userInfoElem.dataset.user;
    if(
      targetElem.hasAttribute("data-clickable") ||
      targetElem.parentElement.hasAttribute("data-clickable") ||
      (targetElem.childElementCount > 0 && targetElem.firstElementChild.hasAttribute("data-clickable"))
    ){
      if(userInfoElem.dataset.userAvailable === "1") {
        allertaJS.main.deactivate(userId,0);
      } else {
        allertaJS.main.activate(userId,0);
      }
    }
  });
}
