// Import Lucide icons library
import lucide from "lucide"

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  lucide.createIcons()
  initializeDonorsPage()
})

// Donors page initialization
function initializeDonorsPage() {
  initializeSearch()
  initializeFilters()
  initializeContactButtons()
  loadInitialDonors()
}

// Search functionality
function initializeSearch() {
  const searchButton = document.querySelector('button[onclick="searchDonors()"]')
  if (searchButton) {
    searchButton.removeAttribute("onclick")
    searchButton.addEventListener("click", performSearch)
  }

  // Auto-search when filters change
  document.getElementById("bloodType").addEventListener("change", performSearch)
  document.getElementById("radius").addEventListener("change", performSearch)
}

// Filter functionality
function initializeFilters() {
  // Blood type filter buttons
  document.querySelectorAll('[class*="blood-badge"]').forEach((button) => {
    button.addEventListener("click", function () {
      // Remove active class from all buttons
      document.querySelectorAll('[class*="blood-badge"]').forEach((btn) => {
        btn.classList.remove("ring-2", "ring-primary")
      })

      // Add active class to clicked button
      this.classList.add("ring-2", "ring-primary")

      // Update main search dropdown
      document.getElementById("bloodType").value = this.textContent

      // Perform search
      performSearch()
    })
  })

  // Availability checkboxes
  document.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
    checkbox.addEventListener("change", performSearch)
  })

  // Distance range slider
  const rangeSlider = document.querySelector('input[type="range"]')
  if (rangeSlider) {
    rangeSlider.addEventListener("input", function () {
      document.getElementById("radius").value = this.value
      performSearch()
    })
  }
}

// Contact button functionality
function initializeContactButtons() {
  document.addEventListener("click", (e) => {
    const button = e.target.closest("button")
    if (!button) return

    const icon = button.querySelector("i")
    if (!icon) return

    const donorCard = button.closest(".bg-card")
    const donorName = donorCard.querySelector("h3").textContent

    if (icon.getAttribute("data-lucide") === "phone") {
      handlePhoneContact(donorName)
    } else if (icon.getAttribute("data-lucide") === "message-circle") {
      handleMessageContact(donorName)
    } else if (button.textContent.includes("Contact")) {
      handleDirectContact(donorName, donorCard)
    }
  })
}

// Perform donor search
function performSearch() {
  const bloodType = document.getElementById("bloodType").value
  const radius = document.getElementById("radius").value

  // Get availability filters
  const availabilityFilters = []
  document.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
    availabilityFilters.push(checkbox.nextElementSibling.textContent.trim())
  })

  const searchParams = {
    bloodType: bloodType,
    radius: radius,
    availability: availabilityFilters,
  }

  console.log("[v0] Searching donors with params:", searchParams)

  fetch("api/search-donors.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(searchParams),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("[v0] Search results:", data)
      displayDonors(data.donors)
      updateResultsCount(data.count)
    })
    .catch((error) => {
      console.error("[v0] Search error:", error)
      showNotification("Error searching for donors", "error")
    })
}

// Load initial donors
function loadInitialDonors() {
  performSearch()
}

// Handle phone contact
function handlePhoneContact(donorName) {
  // In a real app, this would get the actual phone number
  const phoneNumber = "+1234567890" // Placeholder
  window.open(`tel:${phoneNumber}`, "_self")
  console.log("[v0] Calling donor:", donorName)
}

// Handle message contact
function handleMessageContact(donorName) {
  // In a real app, this would open a messaging interface
  showNotification(`Opening message to ${donorName}`, "info")
  console.log("[v0] Messaging donor:", donorName)
}

// Handle direct contact
function handleDirectContact(donorName, donorCard) {
  const bloodType = donorCard.querySelector('[class*="rounded-full"] span').textContent
  const distance = donorCard.querySelector('[data-lucide="map-pin"]').nextElementSibling.textContent

  const contactData = {
    donorName: donorName,
    bloodType: bloodType,
    distance: distance,
  }

  fetch("api/contact-donor.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(contactData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showNotification(`Contact request sent to ${donorName}`, "success")
      } else {
        showNotification(data.message || "Error contacting donor", "error")
      }
    })
    .catch((error) => {
      console.error("[v0] Contact error:", error)
      showNotification("Error contacting donor", "error")
    })
}

// Display donors in the UI
function displayDonors(donors) {
  const container = document.getElementById("donorResults")
  if (!container) return

  // Clear existing results
  container.innerHTML = ""

  donors.forEach((donor) => {
    const donorCard = createDonorCard(donor)
    container.appendChild(donorCard)
  })

  console.log("[v0] Displayed donors:", donors.length)
}

// Create donor card element
function createDonorCard(donor) {
  const card = document.createElement("div")
  card.className = "bg-card border border-border rounded-xl p-6 hover:border-primary/50 transition-colors"

  card.innerHTML = `
        <div class="flex items-start justify-between">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center">
                    <span class="text-primary font-bold">${donor.bloodType}</span>
                </div>
                <div class="space-y-1">
                    <h3 class="font-semibold">${donor.name}</h3>
                    <p class="text-sm text-muted-foreground">Last donated: ${donor.lastDonation}</p>
                    <div class="flex items-center space-x-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-muted-foreground"></i>
                        <span class="text-sm text-muted-foreground">${donor.distance}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 ${donor.available ? "bg-green-500" : "bg-yellow-500"} rounded-full"></div>
                        <span class="text-sm ${donor.available ? "text-green-400" : "text-yellow-400"}">${donor.availability}</span>
                    </div>
                </div>
            </div>
            <div class="flex space-x-2">
                <button class="p-2 bg-secondary hover:bg-accent rounded-lg transition-colors">
                    <i data-lucide="phone" class="w-4 h-4"></i>
                </button>
                <button class="p-2 bg-secondary hover:bg-accent rounded-lg transition-colors">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                </button>
                <button class="px-4 py-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-lg text-sm font-medium transition-colors">
                    Contact
                </button>
            </div>
        </div>
    `

  // Re-initialize Lucide icons for the new card
  lucide.createIcons()

  return card
}

// Update results count
function updateResultsCount(count) {
  const countElement = document.getElementById("resultsCount")
  if (countElement) {
    countElement.textContent = `${count} donors found`
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
