// Donor Registration JavaScript (Final + Tested Version)
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("donorRegistrationForm");
  form.setAttribute("novalidate", "novalidate"); // Disable browser's built-in validation
  initializeDonorRegistration();
});

function initializeDonorRegistration() {
  const form = document.getElementById("donorRegistrationForm");
  const getLocationBtn = document.getElementById("getLocationBtn");

  // Handle form submission
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (validateForm()) {
      submitDonorRegistration(form);
    }
  });

  // Handle Get Location button
  getLocationBtn.addEventListener("click", getCurrentLocation);

  // Live validation for email and phone
  const emailInput = form.querySelector('input[name="email"]');
  const phoneInput = form.querySelector('input[name="phone"]');

  emailInput.addEventListener("blur", function () {
    if (this.value && !validateEmail(this.value)) {
      showNotification("Please enter a valid email address.", "error");
      this.focus();
    }
  });

  phoneInput.addEventListener("blur", function () {
    const phoneVal = sanitizePhone(this.value);
    console.log("📞 Testing phone:", phoneVal);
    if (this.value && !validatePhone(phoneVal)) {
      showNotification(
        "Please enter a valid Bangladeshi phone number (e.g., 01602253765).",
        "error"
      );
      this.focus();
    }
  });
}

// -------------------------------
// VALIDATION LOGIC
// -------------------------------
function validateForm() {
  const form = document.getElementById("donorRegistrationForm");
  const dob = new Date(form.date_of_birth.value);
  const today = new Date();
  const age =
    today.getFullYear() -
    dob.getFullYear() -
    (today < new Date(today.getFullYear(), dob.getMonth(), dob.getDate()) ? 1 : 0);

  const weight = parseFloat(form.weight.value);
  const phone = sanitizePhone(form.phone.value.trim());
  const email = form.email.value.trim();

  // Email validation
  if (!validateEmail(email)) {
    showNotification("Invalid email address.", "error");
    return false;
  }

  // Phone validation
  if (!validatePhone(phone)) {
    showNotification(
      "Please enter a valid Bangladeshi phone number (e.g., 01602253765).",
      "error"
    );
    return false;
  }

  // Age validation
  if (isNaN(age) || age < 18) {
    showNotification("You must be at least 18 years old to register.", "error");
    return false;
  }

  // Weight validation
  if (isNaN(weight) || weight < 45) {
    showNotification("Minimum weight requirement is 45 kg.", "error");
    return false;
  }

  return true;
}

// ✅ Email validation (RFC-compliant pattern)
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// ✅ Sanitize phone input
function sanitizePhone(value) {
  return (value || "").replace(/[^\d+]/g, ""); // keep digits and leading +
}

// ✅ Correct Bangladeshi phone validation
function validatePhone(raw) {
  const phone = sanitizePhone(raw);
  // Accepts: 01602253765, +8801602253765, 8801602253765
  const re = /^(?:\+?88)?01[3-9]\d{8}$/;
  return re.test(phone);
}

// -------------------------------
// SUBMIT FORM DATA TO BACKEND
// -------------------------------
function submitDonorRegistration(form) {
  const formData = new FormData(form);
  formData.set(
    "is_available_donor",
    form.querySelector("#availableCheck").checked ? "1" : "0"
  );

  fetch("api/register-donor.php", {
    method: "POST",
    body: formData,
  })
    .then(async (res) => {
      const text = await res.text();
      console.log("📦 Raw response:", text);

      try {
        const data = JSON.parse(text);
        if (data.success) {
          showNotification("Registration successful! Redirecting...", "success");
          form.reset();
          setTimeout(() => (window.location.href = "index.html"), 2000);
        } else {
          showNotification(data.message || "Registration failed.", "error");
        }
      } catch {
        showNotification("Invalid server response.", "error");
      }
    })
    .catch((err) => {
      console.error("❌ Fetch Error:", err);
      showNotification("Network error. Please try again later.", "error");
    });
}

// -------------------------------
// LOCATION CAPTURE
// -------------------------------
function getCurrentLocation() {
  const locationStatus = document.getElementById("locationStatus");
  const getLocationBtn = document.getElementById("getLocationBtn");
  const form = document.getElementById("donorRegistrationForm");

  if (!navigator.geolocation) {
    showNotification("Geolocation not supported by your browser.", "error");
    return;
  }

  locationStatus.textContent = "Getting your location...";
  getLocationBtn.disabled = true;

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      const { latitude, longitude } = pos.coords;

      let latInput = form.querySelector('input[name="latitude"]');
      let lngInput = form.querySelector('input[name="longitude"]');

      if (!latInput) {
        latInput = document.createElement("input");
        latInput.type = "hidden";
        latInput.name = "latitude";
        form.appendChild(latInput);
      }

      if (!lngInput) {
        lngInput = document.createElement("input");
        lngInput.type = "hidden";
        lngInput.name = "longitude";
        form.appendChild(lngInput);
      }

      latInput.value = latitude;
      lngInput.value = longitude;

      locationStatus.textContent = "✅ Location captured successfully!";
      getLocationBtn.disabled = false;
    },
    (err) => {
      console.error(err);
      locationStatus.textContent = "Failed to get location.";
      getLocationBtn.disabled = false;
      showNotification("Unable to access your location.", "error");
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// -------------------------------
// NOTIFICATION SYSTEM
// -------------------------------
function showNotification(message, type = "info") {
  const existing = document.querySelector("#notification");
  if (existing) existing.remove();

  const note = document.createElement("div");
  note.id = "notification";
  note.className = `fixed top-5 right-5 px-4 py-3 rounded-lg shadow-lg text-white z-50 transition ${
    type === "success"
      ? "bg-green-600"
      : type === "error"
      ? "bg-red-600"
      : "bg-blue-600"
  }`;
  note.textContent = message;

  document.body.appendChild(note);
  setTimeout(() => note.remove(), 4000);
}
