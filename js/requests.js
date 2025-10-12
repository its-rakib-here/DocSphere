// Import or declare the lucide variable before using it
const lucide = window.lucide || {} // Placeholder for lucide variable

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  lucide.createIcons()
  initializeRequestsPage()
})

// Requests page initialization
function initializeRequestsPage() {
  initializeFilters()
  initializeBloodTypeFilters()
  initializeHelpButtons()
  initializeContactButtons()
  loadRequests()
}

// Filter functionality
function initializeFilters() {
  document.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      console.log("[v0] Filter changed:", this.parentElement.textContent.trim())
      applyFilters()
    })
  })

  document.querySelector("select").addEventListener("change", function () {
    console.log("[v0] Distance filter changed:", this.value)
    applyFilters()
  })
}

// Blood type filter functionality
function initializeBloodTypeFilters() {
  document.querySelectorAll('[class*="blood-badge"]').forEach((button) => {
    button.addEventListener("click", function () {
      this.classList.toggle("ring-2")
      this.classList.toggle("ring-primary")
      console.log("[v0] Blood type filter toggled:", this.textContent)
      applyFilters()
    })
  })
}

// Help button functionality
function initializeHelpButtons() {
  document.querySelectorAll("button").forEach((button) => {
    if (button.textContent.includes("I Can Help")) {
      button.addEventListener("click", function () {
        const requestCard = this.closest(".bg-card")
        const patientName = requestCard.querySelector("h3").textContent
        const bloodType = requestCard.querySelector('[class*="rounded-full"] span').textContent

        respondToRequest(patientName, bloodType, this)
      })
    }
  })
}

// Contact button functionality
function initializeContactButtons() {
  document.querySelectorAll("button").forEach((button) => {
    const icon = button.querySelector("i")
    if (icon) {
      if (icon.getAttribute("data-lucide") === "phone") {
        button.addEventListener("click", function () {
          const requestCard = this.closest(".bg-card")
          const phoneNumber = requestCard.querySelector('p:contains("+1")').textContent
          window.open(`tel:${phoneNumber}`, "_self")
        })
      } else if (icon.getAttribute("data-lucide") === "share") {
        button.addEventListener("click", function () {
          shareRequest(this.closest(".bg-card"))
        })
      }
    }
  })
}

// Load blood requests from server
function loadRequests() {
  fetch("api/get-requests.php")
    .then((response) => response.json())
    .then((data) => {
      console.log("[v0] Loaded requests:", data)
      displayRequests(data.requests)
    })
    .catch((error) => {
      console.error("[v0] Error loading requests:", error)
      showNotification("Error loading blood requests", "error")
    })
}

// Apply current filters
function applyFilters() {
  const urgencyFilters = []
  const bloodTypeFilters = []

  // Get urgency filters
  document.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
    const urgencyText = checkbox.parentElement.querySelector("span").textContent
    urgencyFilters.push(urgencyText.toLowerCase())
  })

  // Get blood type filters
  document.querySelectorAll('[class*="blood-badge"].ring-2').forEach((button) => {
    bloodTypeFilters.push(button.textContent)
  })

  // Get distance filter
  const distance = document.querySelector("select").value

  const filters = {
    urgency: urgencyFilters,
    bloodTypes: bloodTypeFilters,
    distance: distance,
  }

  console.log("[v0] Applying filters:", filters)

  fetch("api/filter-requests.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(filters),
  })
    .then((response) => response.json())
    .then((data) => {
      displayRequests(data.requests)
      updateResultsCount(data.count)
    })
    .catch((error) => {
      console.error("[v0] Filter error:", error)
    })
}

// Respond to a blood request
function respondToRequest(patientName, bloodType, button) {
  const response = confirm(`Are you sure you want to help ${patientName} who needs ${bloodType} blood?`)

  if (response) {
    const requestId = button.closest(".bg-card").dataset.requestId

    fetch("api/respond-request.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        requestId: requestId,
        response: "willing_to_help",
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          showNotification("Thank you for offering to help! The requester will be notified.", "success")
          button.textContent = "Response Sent"
          button.disabled = true
          button.classList.remove("pulse-red")
        } else {
          showNotification(data.message || "Error sending response", "error")
        }
      })
      .catch((error) => {
        console.error("[v0] Response error:", error)
        showNotification("Error sending response", "error")
      })
  }
}

// Share request functionality
function shareRequest(requestCard) {
  const patientName = requestCard.querySelector("h3").textContent
  const bloodType = requestCard.querySelector('[class*="rounded-full"] span').textContent
  const hospital = requestCard.querySelector('p:contains("Hospital")').nextElementSibling.textContent

  const shareText = `Urgent: ${patientName} needs ${bloodType} blood at ${hospital}. Please help if you can donate.`

  if (navigator.share) {
    navigator.share({
      title: "Blood Donation Request",
      text: shareText,
      url: window.location.href,
    })
  } else {
    navigator.clipboard.writeText(shareText).then(() => {
      showNotification("Request details copied to clipboard", "success")
    })
  }
}

// Display requests in the UI
function displayRequests(requests) {
  const container = document.querySelector(".space-y-6")
  // Implementation would update the DOM with filtered requests
  console.log("[v0] Displaying requests:", requests)
}

// Update results count
function updateResultsCount(count) {
  const countElement = document.querySelector(".text-muted-foreground")
  if (countElement) {
    countElement.textContent = `${count} active requests`
  }
}

// Show notification to user
function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
    type === "success" ? "bg-green-600" : type === "error" ? "bg-red-600" : "bg-blue-600"
  } text-white`
  notification.textContent = message

  document.body.appendChild(notification)

  setTimeout(() => {
    notification.remove()
  }, 5000)
}
