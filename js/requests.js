/* js/requests.js */

// --------------------
// GLOBALS
// --------------------
let lastFilters = {};
let refreshTimer;

// --------------------
// BOOT
// --------------------
document.addEventListener("DOMContentLoaded", () => {
  if (typeof lucide !== "undefined") lucide.createIcons();
  initializeFilters();
  loadRequests();      // initial
  startAutoRefresh();  // auto refresh
});

// --------------------
// AUTO REFRESH
// --------------------
function startAutoRefresh() {
  if (refreshTimer) clearInterval(refreshTimer);
  refreshTimer = setInterval(() => loadRequests(lastFilters, true), 30000);
}

// --------------------
// FILTERS
// --------------------
function initializeFilters() {
  // urgency checkboxes
  document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    cb.addEventListener("change", applyFilters);
  });

  // blood type pills
  document.querySelectorAll(".blood-badge").forEach(btn => {
    btn.addEventListener("click", function () {
      this.classList.toggle("ring-2");
      this.classList.toggle("ring-primary");
      applyFilters();
    });
  });

  // radius dropdown (expects values: all | low | medium | high)
  const radiusSelect = document.getElementById("radiusFilter");
  if (radiusSelect) radiusSelect.addEventListener("change", applyFilters);
}

function applyFilters() {
  const urgencies = [];
  document.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
    urgencies.push(cb.parentElement.textContent.trim().toLowerCase());
  });

  const bloodTypes = [];
  document.querySelectorAll('.blood-badge.ring-2').forEach(btn => {
    bloodTypes.push(btn.textContent.trim());
  });

  const radius = document.getElementById("radiusFilter")?.value || "all";

  // Get user location only when radius != all
  if (radius !== "all" && navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      pos => {
        lastFilters = {
          urgency: urgencies,
          bloodTypes,
          radius,
          latitude: pos.coords.latitude,
          longitude: pos.coords.longitude,
        };
        loadRequests(lastFilters);
      },
      // Location blocked -> still query backend (it will ignore radius)
      () => {
        lastFilters = { urgency: urgencies, bloodTypes, radius };
        loadRequests(lastFilters);
      },
      { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
    );
  } else {
    lastFilters = { urgency: urgencies, bloodTypes, radius };
    loadRequests(lastFilters);
  }
}

// --------------------
// FETCH HELPER (with timeout)
// --------------------
async function fetchJSON(url, options = {}, timeoutMs = 15000) {
  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const res = await fetch(url, { ...options, signal: controller.signal, cache: "no-store" });
    const text = await res.text();

    // Log everything once for debugging
    console.log(`📡 ${url} [${res.status}]`, text);

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${text || "No response body"}`);
    }

    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      throw new Error("Invalid JSON from server");
    }
    return json;
  } finally {
    clearTimeout(id);
  }
}

// --------------------
// LOAD REQUESTS
// --------------------
async function loadRequests(filters = {}, isAutoRefresh = false) {
  const container = document.getElementById("requestsContainer");
  if (!container) return;

  if (!isAutoRefresh) {
    container.innerHTML = `
      <div class="flex justify-center items-center py-8">
        <div class="animate-spin rounded-full h-6 w-6 border-t-2 border-primary mr-2"></div>
        <p class="text-muted-foreground">Loading requests...</p>
      </div>`;
  }

  try {
    const data = await fetchJSON("api/get-requests.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(filters),
    });

    if (!data.success) {
      container.innerHTML = `
        <div class="text-center py-8">
          <p class="text-red-600 font-medium">⚠️ ${data.message || "Server error."}</p>
          <button class="mt-3 px-4 py-2 border border-border rounded-lg hover:bg-accent transition"
                  onclick="loadRequests(lastFilters)">🔄 Retry</button>
        </div>`;
      return;
    }

    if (!data.requests || data.requests.length === 0) {
      container.innerHTML = `
        <div class="text-center py-8">
          <p class="text-muted-foreground">🚫 No matching requests found within your selected filters or radius.</p>
        </div>`;
      updateResultsCount(0);
      updateLastUpdated();
      return;
    }

    displayRequests(data.requests);
    updateResultsCount(data.count ?? data.requests.length);
    updateLastUpdated();
  } catch (err) {
    console.error("❌ Network/Parse Error:", err);
    container.innerHTML = `
      <div class="text-center py-8">
        <p class="text-red-600 font-medium">Network error loading requests.</p>
        <p class="text-xs text-muted-foreground mt-1">${err.message || ""}</p>
        <button class="mt-3 px-4 py-2 border border-border rounded-lg hover:bg-accent transition"
                onclick="loadRequests(lastFilters)">🔄 Retry</button>
      </div>`;
  }
}

// --------------------
// RENDER
// --------------------
function displayRequests(requests) {
  const container = document.getElementById("requestsContainer");
  if (!container) return;

  const urgencyBg = {
    low: "border-green-500/30 bg-green-50",
    medium: "border-yellow-500/30 bg-yellow-50",
    high: "border-orange-500/30 bg-orange-50",
    critical: "border-red-600 bg-red-100",
  };

  const urgencyBar = {
    low: "bg-green-500",
    medium: "bg-yellow-500",
    high: "bg-orange-500",
    critical: "bg-red-600",
  };

  container.innerHTML = requests.map(req => {
    const level = (req.urgency_level || "medium").toLowerCase();
    const bgClass = urgencyBg[level] || urgencyBg.medium;
    const barClass = urgencyBar[level] || urgencyBar.medium;

    const distanceText = typeof req.distance_km === "number"
      ? `${req.distance_km.toFixed(1)} km away`
      : "";

    const gmaps = (req.latitude && req.longitude)
      ? `https://www.google.com/maps?q=${req.latitude},${req.longitude}`
      : "#";

    const bloodColor = level === "critical" ? "text-red-600" : "text-primary-600";

    return `
      <div class="border ${bgClass} rounded-xl p-6 relative overflow-hidden shadow-lg transition-all duration-200 hover:scale-[1.02] hover:shadow-2xl group" data-id="${req.id}">
        <div class="absolute top-0 left-0 w-1 h-full ${barClass}"></div>

        <div class="flex items-start justify-between mb-3">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center border border-border group-hover:scale-110 transition-transform">
              <span class="text-lg font-bold ${bloodColor}">${req.blood_type || "?"}</span>
            </div>
            <div>
              <h3 class="text-lg font-semibold">${escapeHTML(req.patient_name || "Patient")}</h3>
              <p class="text-sm text-muted-foreground">${escapeHTML(req.hospital_name || "Hospital")}</p>
              ${distanceText ? `<p class="text-xs text-gray-600">${distanceText}</p>` : ""}
            </div>
          </div>
          <span class="px-3 py-1 rounded-full text-xs font-medium capitalize ${barClass} text-white">
            ${escapeHTML(level)}
          </span>
        </div>

        <p class="text-sm text-muted-foreground mb-3 leading-relaxed">
          ${escapeHTML(req.description || "No details provided.")}
        </p>

        <div class="flex gap-3 mt-4">
          <a href="tel:${encodeURI(req.contact_phone || "")}"
             class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-2 rounded-lg transition">📞 Call</a>

          <a href="${gmaps}" target="_blank"
             class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-2 rounded-lg transition"
             ${gmaps === "#" ? 'aria-disabled="true" tabindex="-1"' : ""}>📍 Location</a>

          <button class="ml-auto px-3 py-2 bg-primary hover:bg-primary/90 text-white text-sm rounded-lg transition"
                  onclick="respondToRequest('${escapeJS(req.patient_name || "Patient")}', '${escapeJS(req.blood_type || "?")}', '${escapeJS(req.contact_phone || "")}')">
            ❤️ I Can Help
          </button>
        </div>
      </div>`;
  }).join("");
}

// --------------------
// HELPERS
// --------------------
function updateResultsCount(count) {
  const el = document.querySelector(".text-muted-foreground");
  if (el) el.textContent = `${count} active requests`;
}

function respondToRequest(name, blood, phone) {
  const msg = `Are you sure you want to help ${name} (needs ${blood})?\nYou can contact them at ${phone}.`;
  if (confirm(msg) && phone) window.open(`tel:${phone}`, "_self");
}

function updateLastUpdated() {
  let footer = document.getElementById("lastUpdated");
  if (!footer) {
    footer = document.createElement("p");
    footer.id = "lastUpdated";
    footer.className = "text-xs text-gray-500 text-center mt-3";
    document.getElementById("requestsContainer").insertAdjacentElement("afterend", footer);
  }
  footer.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
}

// Basic XSS safety for dynamic strings
function escapeHTML(str) {
  return String(str).replace(/[&<>"']/g, s => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[s]));
}
function escapeJS(str) {
  return String(str).replace(/['"\\]/g, s => "\\" + s);
}
