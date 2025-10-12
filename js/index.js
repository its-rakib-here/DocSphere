// --------------------
// Blood Community App JS (Optimized)
// --------------------

document.addEventListener("DOMContentLoaded", () => {
  if (typeof lucide !== "undefined") lucide.createIcons();
  initializeHomePage();
});

// --------------------
// Page Initialization
// --------------------
function initializeHomePage() {
  initializeFormSubmission();
  initializeStatisticsAnimation();
  initializeLocationMap();
    initializeRecentRequests(); // ✅ add this

}

// --------------------
// Form Submission
// --------------------
function initializeFormSubmission() {
  const form = document.getElementById("bloodRequestForm");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    submitBloodRequest(form);
  });
}

function submitBloodRequest(form) {
  const formData = new FormData(form);
  formData.append("requester_id", 1); // Example user ID

  // Include coordinates if already detected
  const lat = form.querySelector("#latitude")?.value;
  const lng = form.querySelector("#longitude")?.value;
  if (lat && lng) {
    formData.append("latitude", lat);
    formData.append("longitude", lng);
  }

  fetch("api/submit-blood-request.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        showNotification("✅ Blood request submitted successfully!", "success");
        form.reset();
      } else {
        showNotification(data.message || "Error submitting request", "error");
      }
    })
    .catch(() => showNotification("Network error submitting request", "error"));
}

// --------------------
// Statistics Animation
// --------------------
function initializeStatisticsAnimation() {
  const statsSection = document.querySelector(".stat-card");
  if (!statsSection) return;

  const observer = new IntersectionObserver((entries) => {
    if (entries[0].isIntersecting) {
      animateStatistics();
      observer.disconnect();
    }
  });
  observer.observe(statsSection.parentElement);
}

function animateStatistics() {
  document.querySelectorAll(".stat-card .text-4xl").forEach((counter) => {
    const target = Number(counter.textContent.replace(/,/g, ""));
    let current = 0;
    const increment = target / 100;

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

// --------------------
// Notifications
// --------------------
function showNotification(message, type = "info") {
  const notification = document.createElement("div");
  notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 text-white ${
    type === "success" ? "bg-green-600" : type === "error" ? "bg-red-600" : "bg-blue-600"
  }`;
  notification.textContent = message;

  document.body.appendChild(notification);
  setTimeout(() => notification.remove(), 5000);
}

// --------------------
// Location + Map Integration
// --------------------
function initializeLocationMap() {
  const mapContainer = document.getElementById("map");
  if (!mapContainer) return;

  const districtSelect = document.getElementById("district");
  const detectBtn = document.getElementById("detectLocationBtn");
  const latitudeField = document.getElementById("latitude");
  const longitudeField = document.getElementById("longitude");

  // Add detected location text
  let locationText = document.getElementById("detectedLocationText");
  if (!locationText) {
    locationText = document.createElement("p");
    locationText.id = "detectedLocationText";
    locationText.className = "mt-3 text-sm text-gray-700 bg-gray-100 p-3 rounded-lg";
    mapContainer.insertAdjacentElement("afterend", locationText);
  }

  // Load all Bangladesh districts
  const districts = [
    "Barguna",
    "Barisal",
    "Bhola",
    "Jhalokati",
    "Patuakhali",
    "Pirojpur",
    "Bandarban",
    "Brahmanbaria",
    "Chandpur",
    "Chittagong",
    "Comilla",
    "Cox's Bazar",
    "Feni",
    "Khagrachhari",
    "Lakshmipur",
    "Noakhali",
    "Rangamati",
    "Dhaka",
    "Faridpur",
    "Gazipur",
    "Gopalganj",
    "Kishoreganj",
    "Madaripur",
    "Manikganj",
    "Munshiganj",
    "Narayanganj",
    "Narsingdi",
    "Rajbari",
    "Shariatpur",
    "Tangail",
    "Bagerhat",
    "Chuadanga",
    "Jessore",
    "Jhenaidah",
    "Khulna",
    "Kushtia",
    "Magura",
    "Meherpur",
    "Narail",
    "Satkhira",
    "Jamalpur",
    "Mymensingh",
    "Netrokona",
    "Sherpur",
    "Bogra",
    "Joypurhat",
    "Naogaon",
    "Natore",
    "Nawabganj",
    "Pabna",
    "Rajshahi",
    "Sirajganj",
    "Dinajpur",
    "Gaibandha",
    "Kurigram",
    "Lalmonirhat",
    "Nilphamari",
    "Panchagarh",
    "Rangpur",
    "Thakurgaon",
    "Habiganj",
    "Moulvibazar",
    "Sunamganj",
    "Sylhet",
  ];

  if (districtSelect && districtSelect.options.length === 1) {
    districts.forEach((d) => {
      const opt = document.createElement("option");
      opt.value = d;
      opt.textContent = d;
      districtSelect.appendChild(opt);
    });
  }

  // Initialize Leaflet Map
  const map = L.map("map").setView([23.685, 90.3563], 7);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 18,
  }).addTo(map);

  const marker = L.marker([23.685, 90.3563], { draggable: true }).addTo(map);

  const updateLatLng = (lat, lng) => {
    latitudeField.value = lat;
    longitudeField.value = lng;
  };

  async function getLocationDetails(lat, lng) {
    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`
      );
      const data = await res.json();

      const address = data.display_name || "Unknown location";
      const district =
        data.address.state_district || data.address.county || data.address.state || "";

      locationText.textContent = `📍 Current Location: ${address}`;

      // Auto-select district if found
      if (district) {
        for (let opt of districtSelect.options) {
          if (district.toLowerCase().includes(opt.value.toLowerCase())) {
            districtSelect.value = opt.value;
            break;
          }
        }
      }
    } catch {
      locationText.textContent = "⚠️ Unable to retrieve address";
    }
  }

  marker.on("dragend", async (e) => {
    const { lat, lng } = e.target.getLatLng();
    updateLatLng(lat, lng);
    await getLocationDetails(lat, lng);
  });

  if (detectBtn) {
    detectBtn.addEventListener("click", () => {
      if (!navigator.geolocation) {
        showNotification("Geolocation not supported", "error");
        return;
      }

      detectBtn.textContent = "Detecting...";

      navigator.geolocation.getCurrentPosition(
        async (pos) => {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;

          map.setView([lat, lng], 13);
          marker.setLatLng([lat, lng]);
          updateLatLng(lat, lng);
          await getLocationDetails(lat, lng);

          detectBtn.textContent = "📍 Detect Current Location";
        },
        () => {
          detectBtn.textContent = "📍 Detect Current Location";
          showNotification("Unable to detect location. Please enable GPS.", "error");
        }
      );
    });
  }
}


// ----------------------------
// 🩸 Load & Render Recent Requests (POST)
// ----------------------------
function initializeRecentRequests() {
  loadRecentRequests();
  setInterval(loadRecentRequests, 30000); // refresh every 30s
}

async function loadRecentRequests() {
  const container = document.getElementById("recentRequests");
  if (!container) return;

  try {
    // fetch all results
    const formData = new FormData();
    formData.append("limit", 10); // backend still returns up to 10

    const res = await fetch("api/get-recent-requests.php", {
      method: "POST",
      body: formData,
    });

    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch (e) {
      container.innerHTML = `<p class="text-red-600 text-sm">Invalid JSON from server.</p>`;
      console.error("Response:", text);
      return;
    }

    if (!json.success) {
      container.innerHTML = `<p class="text-red-600 text-sm">${json.message || "Failed to load requests."}</p>`;
      return;
    }

    const data = json.data || [];
    if (data.length === 0) {
      container.innerHTML = `<p class="text-sm text-muted-foreground">No recent blood requests found.</p>`;
      return;
    }

    // ✅ Show only first 3 requests (frontend limit)
    const limitedData = data.slice(0, 3);

    container.innerHTML = limitedData.map(renderRequestCard).join("");
  } catch (err) {
    console.error("Fetch error:", err);
    container.innerHTML = `<p class="text-red-600 text-sm">Network error loading requests.</p>`;
  }
}

function renderRequestCard(req) {
  const urgencyColors = {
    low: "bg-emerald-100 text-emerald-700",
    medium: "bg-yellow-100 text-yellow-800",
    high: "bg-orange-100 text-orange-800",
    critical: "bg-red-100 text-red-700",
  };

  const urgency = req.urgency_level ? req.urgency_level.toLowerCase() : "medium";
  const urgencyClass = urgencyColors[urgency] || urgencyColors.medium;

  return `
    <div class="flex items-center space-x-4 p-4 bg-secondary rounded-lg">
      <div class="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center">
        <span class="text-primary font-bold">${req.blood_type || "?"}</span>
      </div>
      <div class="flex-1">
        <p class="font-medium">${req.patient_name || "Anonymous"}</p>
        <p class="text-sm text-muted-foreground">
          ${req.hospital_name || "Unknown Hospital"} • ${req.district || ""}
        </p>
      </div>
      <span class="px-2 py-1 rounded text-xs font-medium ${urgencyClass}">
        ${urgency.charAt(0).toUpperCase() + urgency.slice(1)}
      </span>
    </div>
  `;
}


