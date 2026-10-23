// Dada David Babatunde — Portfolio interactions
document.addEventListener("DOMContentLoaded", () => {
  const menuToggle = document.getElementById("menuToggle");
  const navLinks = document.getElementById("navLinks");
  const contactForm = document.getElementById("contactForm");
  const formMessage = document.getElementById("formMessage");

  // Mobile navigation
  if (menuToggle && navLinks) {
    menuToggle.addEventListener("click", () => {
      const isOpen = navLinks.classList.toggle("open");
      menuToggle.setAttribute("aria-expanded", String(isOpen));
      menuToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
    });

    navLinks.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        navLinks.classList.remove("open");
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.setAttribute("aria-label", "Open navigation");
      });
    });
  }

  // Automatically update footer year
  const year = document.getElementById("year");
  if (year) year.textContent = new Date().getFullYear();

  // Reveal sections as they enter the screen
  const revealItems = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver((entries, currentObserver) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          currentObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealItems.forEach((item) => observer.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add("visible"));
  }

  // Highlight the navigation item for the visible section
  const sections = document.querySelectorAll("main section[id]");
  if ("IntersectionObserver" in window) {
    const sectionObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        navLinks?.querySelectorAll("a").forEach((link) => {
          link.classList.toggle("active", link.getAttribute("href") === `#${entry.target.id}`);
        });
      });
    }, { rootMargin: "-35% 0px -55% 0px" });
    sections.forEach((section) => sectionObserver.observe(section));
  }

  // Contact form opens the visitor's email app. For automatic delivery,
  // connect this form to a backend or a form service.
  if (contactForm) {
    contactForm.addEventListener("submit", (event) => {
      event.preventDefault();
      const name = document.getElementById("name").value.trim();
      const email = document.getElementById("email").value.trim();
      const message = document.getElementById("message").value.trim();

      if (!name || !email || !message) {
        formMessage.textContent = "Please complete all fields before sending.";
        return;
      }

      // Replace this with your real email address.
      const recipient = "your@email.com";
      const subject = encodeURIComponent(`Portfolio enquiry from ${name}`);
      const body = encodeURIComponent(`Name: ${name}\nEmail: ${email}\n\nMessage:\n${message}`);
      formMessage.textContent = "Opening your email app to send the message…";
      window.location.href = `mailto:${recipient}?subject=${subject}&body=${body}`;
    });
  }

  // Keep the demo project link from jumping to the top of the page.
  document.querySelectorAll("[data-project-link]").forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      alert("Add your live Dada's Tech website URL to this project link in index.html.");
    });
  });
});
