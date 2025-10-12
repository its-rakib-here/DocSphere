// Donor Registration JavaScript
document.addEventListener("DOMContentLoaded", () => {
  initializeDonorRegistration()
})

function initializeDonorRegistration() {
  const form = document.getElementById("donorRegistrationForm")
  const getLocationBtn = document.getElementById("getLocationBtn")
  const locationStatus = document.getElementById("locationStatus")

  // Form submission handling
  form.addEventListener("submit", function (e) {
    e.preventDefault()
    if (validateForm()) {
      submitDonorRegistration(this)
    }
  })

  // Get location functionality
  getLocationBtn.addEventListener("click", () => {
    getCurrentLocation()
  })

  // Password confirmation validation
  const password = form.querySelector('input[name="password"]')
  const confirmPassword = form.querySelector('input[name="confirm_password"]')

  confirmPassword.addEventListener("input", () => {
    if (password.value !== confirmPassword.value) {
      confirmPassword.setCustomValidity("Passwords do not match")
    } else {
      confirmPassword.setCustomValidity("")
    }
  })
}

function validateForm() {
  const form = document.getElementById("donorRegistrationForm")
  const password = form.querySelector('input[name="password"]').value
  const confirmPassword = form.querySelector('input[name="confirm_password"]').value
  const weight = Number.parseFloat(form.querySelector('input[name="weight"]').value)
  const dateOfBirth = new Date(form.querySelector('input[name="date_of_birth"]').value)
  const today = new Date()
  const age = today.getFullYear() - dateOfBirth.getFullYear()

  // Password validation
  if (password !== confirmPassword) {
    showNotification("Passwords do not match", "error")
    return false
  }

  if (password.length < 6) {
    showNotification("Password must be at least 6 characters long", "error")
    return false
  }

  // Age validation (must be 18+)
  if (age < 18) {
    showNotification("You must be at least 18 years old to donate blood", "error")
    return false
  }

  // Weight validation (must be at least 45kg)
  if (weight < 45) {
    showNotification("Minimum weight requirement is 45kg for blood donation", "error")
    return false
  }

  return true
}

function submitDonorRegistration(form) {
  const formData = new FormData(form)

  // Convert checkbox to boolean
  formData.set("is_available_donor", form.querySelector('input[name="is_available_donor"]').checked ? "1" : "0")

  console.log("[v0] Submitting donor registration...")

  fetch("api/register-donor.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("[v0] Registration response:", data)

      if (data.success) {
        showNotification("Registration successful! Welcome to our blood donor community.", "success")

        // Redirect to dashboard or login page after 2 seconds
        setTimeout(() => {
          window.location.href = "index.html"
        }, 2000)
      } else {
        showNotification(data.message || "Registration failed. Please try again.", "error")
      }
    })
    .catch((error) => {
      console.error("[v0] Registration error:", error)
      showNotification("Network error. Please check your connection and try again.", "error")
    })
}

function getCurrentLocation() {
  const locationStatus = document.getElementById("locationStatus")
  const getLocationBtn = document.getElementById("getLocationBtn")

  if (!navigator.geolocation) {
    showNotification("Geolocation is not supported by this browser", "error")
    return
  }

  locationStatus.textContent = "Getting your location..."
  getLocationBtn.disabled = true

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const latitude = position.coords.latitude
      const longitude = position.coords.longitude

      console.log("[v0] Location obtained:", latitude, longitude)

      // Reverse geocoding to get address
      reverseGeocode(latitude, longitude)

      locationStatus.textContent = "Location obtained successfully!"
      getLocationBtn.disabled = false
    },
    (error) => {
      console.error("[v0] Location error:", error)
      locationStatus.textContent = "Unable to get location"
      getLocationBtn.disabled = false

      switch (error.code) {
        case error.PERMISSION_DENIED:
          showNotification("Location access denied. Please enable location services.", "error")
          break
        case error.POSITION_UNAVAILABLE:
          showNotification("Location information unavailable.", "error")
          break
        case error.TIMEOUT:
          showNotification("Location request timed out.", "error")
          break
        default:
          showNotification("An unknown error occurred while getting location.", "error")
          break
      }
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 60000,
    },
  )
}

function reverseGeocode(latitude, longitude) {
  // Store coordinates in hidden fields or form data
  const form = document.getElementById("donorRegistrationForm")

  // Create hidden inputs for coordinates
  let latInput = form.querySelector('input[name="latitude"]')
  let lngInput = form.querySelector('input[name="longitude"]')

  if (!latInput) {
    latInput = document.createElement("input")
    latInput.type = "hidden"
    latInput.name = "latitude"
    form.appendChild(latInput)
  }

  if (!lngInput) {
    lngInput = document.createElement("input")
    lngInput.type = "hidden"
    lngInput.name = "longitude"
    form.appendChild(lngInput)
  }

  latInput.value = latitude
  lngInput.value = longitude

  // Try to get address using a geocoding service (simplified version)
  // In production, you would use Google Maps API or similar service
  console.log("[v0] Coordinates stored:", latitude, longitude)
}

function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
    type === "success" ? "bg-green-600" : type === "error" ? "bg-red-600" : "bg-blue-600"
  } text-white max-w-md`

  notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fa-solid ${
              type === "success" ? "fa-check-circle" : type === "error" ? "fa-exclamation-circle" : "fa-info-circle"
            }"></i>
            <span>${message}</span>
        </div>
    `

  document.body.appendChild(notification)

  // Auto remove after 5 seconds
  setTimeout(() => {
    notification.remove()
  }, 5000)

  // Click to dismiss
  notification.addEventListener("click", () => {
    notification.remove()
  })
}

// Form field validation helpers
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function validatePhone(phone) {
  const phoneRegex = /^[+]?[1-9][\d]{0,15}$/
  return phoneRegex.test(phone.replace(/\s/g, ""))
}

// Real-time validation
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("donorRegistrationForm")

  // Email validation
  const emailInput = form.querySelector('input[name="email"]')
  emailInput.addEventListener("blur", function () {
    if (this.value && !validateEmail(this.value)) {
      this.setCustomValidity("Please enter a valid email address")
    } else {
      this.setCustomValidity("")
    }
  })

  // Phone validation
  const phoneInput = form.querySelector('input[name="phone"]')
  phoneInput.addEventListener("blur", function () {
    if (this.value && !validatePhone(this.value)) {
      this.setCustomValidity("Please enter a valid phone number")
    } else {
      this.setCustomValidity("")
    }
  })
})
