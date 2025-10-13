// ======================================================
// Blood Community JS – Stable Version
// ======================================================

document.addEventListener("DOMContentLoaded", () => {
  if (typeof lucide !== "undefined") lucide.createIcons();
  initializeHomePage();
});

function initializeHomePage() {
  initializeFormSubmission();
  initializeRecentRequests();
  initializeLocationMap();
  fetchStatistics(); // load stats dynamically
}

// -----------------------------
// 🩸 Fetch & Animate Statistics
// -----------------------------
async function fetchStatistics() {
  console.log("📢 Fetching statistics...");

  try {
    const res = await fetch("api/get-statistics.php");
    const data = await res.json();
    console.log("📊 Stats API Response:", data);

    if (!data.success) {
      console.error("❌ API returned error:", data.error || "Unknown");
      return;
    }

    await waitForStats();

    const cards = document.querySelectorAll(".stat-card .text-4xl");
    if (cards.length < 3) {
      console.warn("⚠️ No .stat-card elements found in DOM.");
      return;
    }

    cards[0].textContent = Number(data.donors || 0).toLocaleString();
    cards[1].textContent = Number(data.lives_saved || 0).toLocaleString();
    cards[2].textContent = Number(data.requests || 0).toLocaleString();

    console.log("✅ Statistics updated in UI");
    animateStatistics();
  } catch (err) {
    console.error("🔥 Fetch failed:", err);
  }
}

// Helper: Wait until stats section exists
function waitForStats(timeout = 5000) {
  return new Promise((resolve) => {
    const start = Date.now();
    const check = () => {
      const cards = document.querySelectorAll(".stat-card .text-4xl");
      if (cards.length >= 3 || Date.now() - start > timeout) return resolve();
      requestAnimationFrame(check);
    };
    check();
  });
}

// Animate numbers smoothly
function animateStatistics() {
  document.querySelectorAll(".stat-card .text-4xl").forEach((counter) => {
    const target = Number(counter.textContent.replace(/,/g, "")) || 0;
    let current = 0;
    const increment = Math.max(1, target / 100);
    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        counter.textContent = target.toLocaleString();
        clearInterval(timer);
      } else {
        counter.textContent = Math.floor(current).toLocaleString();
      }
    }, 20);
  });
}

// -----------------------------
// 🔔 Show Notifications
// -----------------------------
function showNotification(msg, type = "info") {
  const div = document.createElement("div");
  div.className = `fixed top-4 right-4 p-4 rounded-lg text-white shadow-lg z-50 ${
    type === "success"
      ? "bg-green-600"
      : type === "error"
      ? "bg-red-600"
      : "bg-blue-600"
  }`;
  div.textContent = msg;
  document.body.appendChild(div);
  setTimeout(() => {
    div.style.opacity = "0";
    setTimeout(() => div.remove(), 400);
  }, 4000);
}

// -----------------------------
// 🌍 Recent Blood Requests
// -----------------------------
async function initializeRecentRequests() {
  await loadRecentRequests();
  setInterval(loadRecentRequests, 30000);
}

async function loadRecentRequests() {
  const container = document.getElementById("recentRequests");
  if (!container) return;

  try {
    const res = await fetch("api/get-recent-requests.php", { method: "POST" });
    const text = await res.text();
    let json;

    try {
      json = JSON.parse(text);
    } catch (e) {
      console.error("Invalid JSON:", text);
      container.innerHTML =
        "<p class='text-red-600 text-sm'>Invalid response from server.</p>";
      return;
    }

    if (!json.success) {
      container.innerHTML =
        "<p class='text-red-600 text-sm'>Failed to load requests.</p>";
      return;
    }

    const data = json.data || [];
    if (!data.length) {
      container.innerHTML =
        "<p class='text-muted-foreground text-sm'>No recent blood requests found.</p>";
      return;
    }

    container.innerHTML = data
      .slice(0, 3)
      .map(
        (req) => `
        <div class="flex items-center space-x-4 p-4 bg-secondary rounded-lg">
          <div class="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center">
            <span class="text-primary font-bold">${req.blood_type || "?"}</span>
          </div>
          <div class="flex-1">
            <p class="font-medium">${req.patient_name || "Anonymous"}</p>
            <p class="text-sm text-muted-foreground">${
              req.hospital_name || "Unknown Hospital"
            } • ${req.district || ""}</p>
          </div>
        </div>
      `
      )
      .join("");
  } catch (err) {
    console.error("Fetch error:", err);
  }
}

// -----------------------------
// 🧭 Map (Leaflet Integration)
// -----------------------------
function initializeLocationMap() {
  const mapEl = document.getElementById("map");
  if (!mapEl || typeof L === "undefined") return;

  const map = L.map("map").setView([23.685, 90.3563], 7);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18,
  }).addTo(map);

  const marker = L.marker([23.685, 90.3563], { draggable: true }).addTo(map);
  marker.on("dragend", (e) => {
    const { lat, lng } = e.target.getLatLng();
    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;
  });
}
