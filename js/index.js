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
