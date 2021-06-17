const cacheVersion = process.env.BUNDLE_DATE;
const cacheName = "static-" + cacheVersion;
const expectedCaches = [cacheName, "tables-1"];

const urls = ["offline", "manifest.webmanifest", "resources/images/favicon.ico", "resources/dist/marker-icon.png", "resources/dist/layers.png", "resources/dist/layers-2x.png", "resources/images/android-chrome-192x192.png", "resources/images/android-chrome-384x384.png", "resources/images/black_helmet.png", "resources/images/red_helmet.png", "resources/images/wheel.png", "resources/images/logo.png", "resources/images/owner.png", "resources/dist/fonts/fontawesome-webfont.woff2"];

function fetchHandler (event, contentType, notFoundMessage) {
  // TODO: refactoring
  console.log(event);

  // FROM https://googlechrome.github.io/samples/service-worker/custom-offline-page/
  // We only want to call event.respondWith() if this is a navigation request
  // for an HTML page.
  if (event.request.mode === "navigate") {
    event.respondWith((async () => {
      console.log("respond with");
      try {
        // First, try to use the navigation preload response if it's supported.
        const preloadResponse = await event.preloadResponse;
        if (preloadResponse) {
          return preloadResponse;
        }

        const networkResponse = await fetch(event.request);
        console.log("network response");
        return networkResponse;
      } catch (error) {
        // catch is only triggered if an exception is thrown, which is likely
        // due to a network error.
        // If fetch() returns a valid HTTP response with a response code in
        // the 4xx or 5xx range, the catch() will NOT be called.
        console.log("Fetch failed; returning offline page instead.", error);

        const cache = await caches.open(cacheName);
        if (event.request.headers.get("Accept").includes("text/html")) {
          cacheFileName = "offline";
        } else {
          cacheFileName = event.request.url;
        }
        const cachedResponse = await cache.match(cacheFileName);
        return cachedResponse;
      }
    })());
  }

  // If our if() condition is false, then this fetch handler won't intercept the
  // request. If there are any other fetch handlers registered, they will get a
  // chance to call event.respondWith(). If no fetch handlers call
  // event.respondWith(), the request will be handled by the browser as if there
  // were no service worker involvement.
}

self.addEventListener("fetch", function (event) {
  const request = event.request;

  // https://stackoverflow.com/a/49719964
  if (event.request.cache === "only-if-cached" && event.request.mode !== "same-origin") return;

  if (request.headers.get("Accept").includes("text/html")) {
    fetchHandler(event, null, "offline");
  } else if (request.destination === "script") {
    fetchHandler(event, "application/javascript", "console.error('Script " + event.request.url + " not found');");
  } else if (request.destination === "image") {
    fetchHandler(event, null, "resources/images/logo.png");
  } else if (request.destination === "font") {
    fetchHandler(event, null, null);
  } else if (request.destination === "manifest" || request.url.includes("manifest")) {
    fetchHandler(event, null, "manifest.webmanifest");
  } else {
    event.respondWith(fetch(request));
  }
});

self.addEventListener("install", (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(cacheName).then((cache) => {
      cache.addAll(urls);
      fetch("resources/dist/assets-manifest.json")
        .then((response) => response.json())
        .then((manifest) => {
          const scriptsRequired = ["main.js", "maps.js"];
          scriptsRequired.map((scriptName) => {
            console.log(manifest);
            console.log(scriptName);
            cache.add("resources/dist/" + manifest[scriptName]["src"]);
          });
        });
    })
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.map((key) => {
        if (!expectedCaches.includes(key)) {
          console.log("Deleting cache " + key);
          return caches.delete(key);
        }
      })
    )).then(() => {
      console.log("Service worker now ready to handle fetches!");
    })
  );
});
