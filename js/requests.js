// --------------------
// BLOOD REQUEST PAGE SCRIPT
// --------------------

document.addEventListener("DOMContentLoaded", () => {
  if (typeof lucide !== "undefined") lucide.createIcons();
  loadRequests(); // 🔥 load data immediately
});

// --------------------
// Load blood requests
// --------------------
async function loadRequests() {
  const container = document.getElementById("requestsContainer");
  if (!container) return;

  container.innerHTML = `<p class="text-muted-foreground">Loading blood requests...</p>`;

  try {
    const res = await fetch("api/get-requests.php", {
      method: "POST",
    });

    const text = await res.text(); // log if backend sends stray text
    console.log("Raw response:", text);

    let data;
    try {
      data = JSON.parse(text);
    } catch (err) {
      container.innerHTML = `<p class="text-red-600">Invalid JSON from server.</p>`;
      console.error("JSON parse error:", err);
      return;
    }

    if (!data.success) {
      container.innerHTML = `<p class="text-red-600">${data.message || "Failed to load requests."}</p>`;
      return;
    }

    displayRequests(data.requests);
    updateResultsCount(data.count);
  } catch (err) {
    console.error("Fetch error:", err);
    container.innerHTML = `<p class="text-red-600">Network error while loading requests.</p>`;
  }
}

// --------------------
// Render blood request cards dynamically
// --------------------
function displayRequests(requests) {
  const container = document.getElementById("requestsContainer");
  if (!container) return;

  if (!requests || requests.length === 0) {
    container.innerHTML = `<p class="text-muted-foreground">No active blood requests found.</p>`;
    return;
  }

  const urgencyColors = {
    low: "border-green-500/30",
    medium: "border-yellow-500/30",
    high: "border-orange-500/30",
    critical: "border-red-500/30",
  };

  const urgencySide = {
    low: "bg-green-500",
    medium: "bg-yellow-500",
    high: "bg-orange-500",
    critical: "bg-red-500",
  };

  container.innerHTML = requests
    .map((req) => {
      const urgency = req.urgency_level?.toLowerCase() || "medium";
      return `
      <div class="bg-card border ${urgencyColors[urgency]} rounded-xl p-6 relative overflow-hidden" data-request-id="${req.id}">
        <div class="absolute top-0 left-0 w-1 h-full ${urgencySide[urgency]}"></div>
        <div class="space-y-4">
          <div class="flex items-start justify-between">
            <div class="space-y-2">
              <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                  <span class="text-primary font-bold text-lg">${req.blood_type}</span>
                </div>
                <div>
                  <h3 class="text-lg font-semibold">${req.patient_name}</h3>
                  <p class="text-sm text-muted-foreground">Posted on ${formatDate(req.created_at)}</p>
                </div>
              </div>
              <span class="px-3 py-1 rounded-full text-sm font-medium bg-${urgencySide[urgency]} text-white capitalize">
                ${urgency}
              </span>
            </div>
          </div>
          
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-muted-foreground">Hospital</p>
              <p class="font-medium">${req.hospital_name}</p>
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Contact</p>
              <p class="font-medium">${req.contact_phone}</p>
            </div>
          </div>

          <p class="text-sm text-muted-foreground">${req.description || "No details provided."}</p>

          <div class="flex items-center justify-end pt-4 border-t border-border">
            <button class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg font-medium transition-colors" 
                    onclick="respondToRequest('${req.patient_name}', '${req.blood_type}', '${req.contact_phone}')">
              I Can Help
            </button>
          </div>
        </div>
      </div>
    `;
    })
    .join("");
}

// --------------------
// Simple utility functions
// --------------------
function updateResultsCount(count) {
  const el = document.querySelector(".text-muted-foreground");
  if (el) el.textContent = `${count} active requests`;
}

function formatDate(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleString("en-GB", { dateStyle: "medium", timeStyle: "short" });
}

function respondToRequest(patient, blood, phone) {
  alert(`✅ Thank you! Please contact ${patient} (${blood}) at ${phone}.`);
}
