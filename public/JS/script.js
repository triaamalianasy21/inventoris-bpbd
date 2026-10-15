// ===== DROPDOWN =====
const dropdownBtn = document.querySelector(".dropdown-btn");
const dropdownMenu = document.querySelector(".dropdown");

dropdownBtn.addEventListener("click", () => {
  dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
});

// ===== ANIMASI SAAT SCROLL =====
const animElements = document.querySelectorAll(".animate");

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) entry.target.classList.add("visible");
  });
}, { threshold: 0.3 });

animElements.forEach(el => observer.observe(el));

// ===== TAMPILKAN SLIDE 1 LANGSUNG =====
window.addEventListener("load", () => {
  document.querySelectorAll(".slide1 .animate").forEach(el => el.classList.add("visible"));
});
