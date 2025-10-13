// =======================
// Blood Donor Finder v3.0
// =======================

document.addEventListener("DOMContentLoaded", () => {
  lucide.createIcons();
  initDonorFinder();
});

function initDonorFinder() {
  initSearchForm();
  initFilters();
  loadDonors(); // initial load
}

/* --------------------------
 * SEARCH & FILTERS
 * -------------------------- */
function initSearchForm() {
  document.getElementById("searchBtn")?.addEventListener("click", loadDonors);
  document.getElementById("bloodType")?.addEventListener("change", loadDonors);
  document.getElementById("radius")?.addEventListener("change", loadDonors);
}

function initFilters() {
  document.querySelectorAll(".blood-badge").forEach((btn) => {
    btn.addEventListener("click", () => {
      document
        .querySelectorAll(".blood-badge")
        .forEach((b) => b.classList.remove("ring-2", "ring-primary"));
      btn.classList.add("ring-2", "ring-primary");
      document.getElementById("bloodType").value = btn.textContent.trim();
      loadDonors();
    });
  });

  document.querySelectorAll('input[type="checkbox"]').forEach((cb) =>
    cb.addEventListener("change", loadDonors)
  );

  const range = document.querySelector('input[type="range"]');
  if (range)
    range.addEventListener("input", () => {
      document.getElementById("radius").value = range.value;
      loadDonors();
    });
}

/* --------------------------
 * FETCH API (GET)
 * -------------------------- */
async function loadDonors() {
  const bloodType = document.getElementById("bloodType").value || "";
  const radius = document.getElementById("radius").value || "10";
  const availabilityFilters = Array.from(
    document.querySelectorAll('input[type="checkbox"]:checked')
  ).map((cb) => cb.nextElementSibling.textContent.trim());

  const params = new URLSearchParams({
    bloodType,
    radius,
    availability: availabilityFilters.join(","),
  });

  const apiUrl = `api/search-donors.php?${params}`;
  showLoader(true);

  try {
    const res = await fetch(apiUrl);
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text.trim());
    } catch (err) {
      console.error("Invalid JSON:", text);
      showNotification("Server returned invalid data", "error");
      return;
    }

    if (data.success && Array.isArray(data.donors)) {
      renderDonorList(data.donors);
      updateResultsCount(data.count);
    } else {
      renderDonorList([]);
      updateResultsCount(0);
    }
  } catch (err) {
    console.error("Fetch error:", err);
    showNotification("Error fetching donors.", "error");
  } finally {
    showLoader(false);
  }
}

/* --------------------------
 * RENDER DONOR CARDS
 * -------------------------- */
let donorsCache = [];

function renderDonorList(donors) {
  donorsCache = donors;
  const container = document.getElementById("donorResults");
  container.innerHTML = "";

  if (!donors.length) {
    container.innerHTML = `<div class="text-center text-muted-foreground py-6">No donors found in this area.</div>`;
    return;
  }

  donors.forEach((d) => container.appendChild(createDonorCard(d)));
  lucide.createIcons();
  initContactButtons();
}

function createDonorCard(donor) {
  const available = donor.available;
  const availableColor = available ? "text-green-500" : "text-yellow-400";
  const dotColor = available ? "bg-green-500" : "bg-yellow-400";

  const card = document.createElement("div");
  card.className =
    "group bg-card border border-border hover:border-primary/40 rounded-2xl p-6 transition-all shadow-sm hover:shadow-md";

  card.innerHTML = `
    <div class="flex justify-between items-start">
      <div class="flex items-start gap-4">
        <div class="w-14 h-14 rounded-full flex items-center justify-center bg-primary/10 border border-primary/20">
          <span class="font-bold text-lg text-primary">${donor.blood_type}</span>
        </div>
        <div class="space-y-1">
          <h3 class="text-lg font-semibold">${donor.full_name}</h3>
          <p class="text-sm text-muted-foreground">${donor.city || "Unknown city"}</p>
          <div class="flex items-center text-sm text-muted-foreground">
            <i data-lucide="map-pin" class="w-4 h-4 mr-1"></i>
            <span>${donor.distance ? donor.distance + " km away" : "Distance unknown"}</span>
          </div>
          <div class="flex items-center text-sm ${availableColor}">
            <div class="w-2 h-2 ${dotColor} rounded-full mr-2"></div>
            ${donor.availability}
          </div>
          <p class="text-sm text-muted-foreground">Last donated: ${
            donor.last_donated || "Unknown"
          }</p>
        </div>
      </div>
      <div class="flex flex-col gap-2">
        <button class="contact-phone p-2 border border-border rounded-lg hover:bg-accent" data-id="${
          donor.id
        }"><i data-lucide="phone" class="w-4 h-4"></i></button>
        <button class="contact-msg p-2 border border-border rounded-lg hover:bg-accent" data-id="${
          donor.id
        }"><i data-lucide="message-circle" class="w-4 h-4"></i></button>
        <button class="contact-direct mt-1 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm hover:bg-primary/90 transition-all" data-id="${
          donor.id
        }">Contact</button>
      </div>
    </div>
  `;
  return card;
}

/* --------------------------
 * CONTACT HANDLING
 * -------------------------- */
function initContactButtons() {
  document.querySelectorAll(".contact-phone").forEach((btn) =>
    btn.addEventListener("click", () => {
      const donor = donorsCache.find((d) => d.id == btn.dataset.id);
      if (donor?.phone) window.open(`tel:${donor.phone}`, "_self");
      else showNotification("Phone number unavailable", "error");
    })
  );

  document.querySelectorAll(".contact-msg, .contact-direct").forEach((btn) =>
    btn.addEventListener("click", () => openContactModal(btn.dataset.id))
  );
}

function openContactModal(id) {
  const donor = donorsCache.find((d) => d.id == id);
  if (!donor) return;
  document.getElementById("contactDonorName").textContent = `Message to ${
    donor.full_name
  } (${donor.blood_type})`;
  document.getElementById("contactModal").classList.remove("hidden");

  document.getElementById("sendContact").onclick = async () => {
    const message = document.getElementById("contactMessage").value.trim();
    if (!message) {
      showNotification("Please write a message.", "error");
      return;
    }
    const res = await fetch("api/contact-donor.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ donorId: id, message }),
    });
    const data = await res.json();
    if (data.success) {
      showNotification("Message sent successfully!", "success");
      closeModal();
    } else {
      showNotification("Failed to send message.", "error");
    }
  };

  document.getElementById("cancelContact").onclick = closeModal;
  document.getElementById("closeModal").onclick = closeModal;
}

function closeModal() {
  document.getElementById("contactModal").classList.add("hidden");
  document.getElementById("contactMessage").value = "";
}

/* --------------------------
 * UTILITIES
 * -------------------------- */
function updateResultsCount(count) {
  const el = document.getElementById("resultsCount");
  if (el) el.textContent = `${count} donor${count !== 1 ? "s" : ""} found`;
}

function showNotification(msg, type = "info") {
  const box = document.createElement("div");
  box.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${
    type === "success"
      ? "bg-green-600"
      : type === "error"
      ? "bg-red-600"
      : "bg-blue-600"
  } text-white`;
  box.textContent = msg;
  document.body.appendChild(box);
  setTimeout(() => {
    box.style.opacity = "0";
    setTimeout(() => box.remove(), 300);
  }, 4000);
}

function showLoader(show) {
  let loader = document.getElementById("pageLoader");
  if (!loader) {
    loader = document.createElement("div");
    loader.id = "pageLoader";
    loader.className =
      "fixed inset-0 flex items-center justify-center bg-black/40 text-white text-lg z-50";
    loader.innerHTML = `<div class="bg-gray-800 px-6 py-4 rounded-lg animate-pulse">Loading...</div>`;
    document.body.appendChild(loader);
  }
  loader.style.display = show ? "flex" : "none";
}
