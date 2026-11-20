assets / js / snfw - admin.js;
function showTab(evt, tabId) {
  evt.preventDefault();
  document
    .querySelectorAll(".tab-content")
    .forEach((el) => (el.style.display = "none"));
  document
    .querySelectorAll(".nav-tab")
    .forEach((el) => el.classList.remove("nav-tab-active"));
  document.getElementById(tabId).style.display = "flex";
  evt.currentTarget.classList.add("nav-tab-active");

  // Hide Save button for custom-message and bulk-messaging
  var saveButton = document.getElementById("snfw-settings-submit");
  if (tabId === "custom-message" || tabId === "bulk-messaging") {
    saveButton.style.display = "none";
  } else {
    saveButton.style.display = "block";
  }
}

// Initialize tab visibility on page load (optional fallback)
document.addEventListener("DOMContentLoaded", function () {
  var saveButton = document.getElementById("snfw-settings-submit");
  if (saveButton) saveButton.style.display = "block";
});
