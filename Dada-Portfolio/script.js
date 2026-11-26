const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

menuToggle.addEventListener("click", () => {
  const isOpen = navLinks.classList.toggle("open");
  menuToggle.classList.toggle("open", isOpen);
  menuToggle.setAttribute("aria-expanded", String(isOpen));
  menuToggle.setAttribute("aria-label", isOpen ? "Close navigation" : "Open navigation");
});

document.querySelectorAll(".nav-link").forEach(link => {
  link.addEventListener("click", () => {
    navLinks.classList.remove("open");
    menuToggle.classList.remove("open");
    menuToggle.setAttribute("aria-expanded", "false");
  });
});

// Reveal sections as they enter the viewport.
const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add("visible");
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12 });
document.querySelectorAll(".reveal").forEach(element => revealObserver.observe(element));

// Rotating hero words.
const words = ["web", "ideas", "experiences", "solutions"];
const typedText = document.getElementById("typedText");
let wordIndex = 0;
let letterIndex = words[0].length;
let deleting = true;

function typeLoop() {
  const current = words[wordIndex];
  if (deleting) {
    letterIndex--;
    typedText.textContent = current.slice(0, Math.max(0, letterIndex));
    if (letterIndex <= 0) {
      deleting = false;
      wordIndex = (wordIndex + 1) % words.length;
      setTimeout(typeLoop, 300);
      return;
    }
  } else {
    const next = words[wordIndex];
    letterIndex++;
    typedText.textContent = next.slice(0, letterIndex);
    if (letterIndex >= next.length) {
      deleting = true;
      setTimeout(typeLoop, 1450);
      return;
    }
  }
  setTimeout(typeLoop, deleting ? 65 : 105);
}
setTimeout(typeLoop, 1800);

// Reading progress bar.
const progress = document.getElementById("scrollProgress");
window.addEventListener("scroll", () => {
  const scrollable = document.documentElement.scrollHeight - window.innerHeight;
  const percent = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
  progress.style.width = `${percent}%`;
});

// Highlight the navigation item for the visible section.
const sections = document.querySelectorAll("main section[id]");
const navObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      document.querySelectorAll(".nav-link").forEach(link => {
        link.classList.toggle("active", link.getAttribute("href") === `#${entry.target.id}`);
      });
    }
  });
}, { rootMargin: "-35% 0px -55% 0px" });
sections.forEach(section => navObserver.observe(section));

// Gmail is opened directly from the Message Me links. Copy email helper:
const copyButton = document.getElementById("copyEmail");
const toast = document.getElementById("toast");
copyButton.addEventListener("click", async () => {
  try {
    await navigator.clipboard.writeText("dadadavid097@gmail.com");
    toast.textContent = "Email address copied!";
  } catch {
    toast.textContent = "Email: dadadavid097@gmail.com";
  }
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 2200);
});

document.getElementById("year").textContent = new Date().getFullYear();
