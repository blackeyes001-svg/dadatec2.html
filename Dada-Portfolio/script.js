/* ==========================================
   MOBILE NAVIGATION
========================================== */

const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");
const navOverlay = document.getElementById("navOverlay");

function openMenu() {
    if (!menuToggle || !navLinks) return;

    navLinks.classList.add("open");
    menuToggle.classList.add("open");

    if (navOverlay) {
        navOverlay.classList.add("show");
    }

    menuToggle.setAttribute("aria-expanded", "true");
    menuToggle.setAttribute("aria-label", "Close navigation");

    document.body.classList.add("menu-open");
}

function closeMenu() {
    if (!menuToggle || !navLinks) return;

    navLinks.classList.remove("open");
    menuToggle.classList.remove("open");

    if (navOverlay) {
        navOverlay.classList.remove("show");
    }

    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Open navigation");

    document.body.classList.remove("menu-open");
}

if (menuToggle && navLinks) {

    menuToggle.addEventListener("click", () => {
        const isOpen = navLinks.classList.contains("open");

        if (isOpen) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    document.querySelectorAll(".nav-link").forEach(link => {
        link.addEventListener("click", closeMenu);
    });

    if (navOverlay) {
        navOverlay.addEventListener("click", closeMenu);
    }

    document.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            closeMenu();
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 700) {
            closeMenu();
        }
    });
}


/* ==========================================
   REVEAL SECTIONS
========================================== */

if ("IntersectionObserver" in window) {

    const revealObserver = new IntersectionObserver(entries => {

        entries.forEach(entry => {

            if (entry.isIntersecting) {
                entry.target.classList.add("visible");
                revealObserver.unobserve(entry.target);
            }

        });

    }, {
        threshold: 0.12
    });

    document.querySelectorAll(".reveal").forEach(element => {
        revealObserver.observe(element);
    });

}


/* ==========================================
   ROTATING HERO WORDS
========================================== */

const words = [
    "web",
    "ideas",
    "experiences",
    "solutions"
];

const typedText = document.getElementById("typedText");

let wordIndex = 0;
let letterIndex = words[0].length;
let deleting = true;

function typeLoop() {

    if (!typedText) return;

    const current = words[wordIndex];

    if (deleting) {

        letterIndex--;

        typedText.textContent = current.slice(
            0,
            Math.max(0, letterIndex)
        );

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

if (typedText) {
    setTimeout(typeLoop, 1800);
}


/* ==========================================
   READING PROGRESS BAR
========================================== */

const progress = document.getElementById("scrollProgress");

window.addEventListener("scroll", () => {

    if (!progress) return;

    const scrollable =
        document.documentElement.scrollHeight - window.innerHeight;

    const percent = scrollable > 0
        ? (window.scrollY / scrollable) * 100
        : 0;

    progress.style.width = `${percent}%`;

});


/* ==========================================
   ACTIVE NAVIGATION LINK
========================================== */

const sections = document.querySelectorAll("main section[id]");

if ("IntersectionObserver" in window) {

    const navObserver = new IntersectionObserver(entries => {

        entries.forEach(entry => {

            if (entry.isIntersecting) {

                document.querySelectorAll(".nav-link").forEach(link => {

                    link.classList.toggle(
                        "active",
                        link.getAttribute("href") === `#${entry.target.id}`
                    );

                });

            }

        });

    }, {
        rootMargin: "-35% 0px -55% 0px"
    });

    sections.forEach(section => {
        navObserver.observe(section);
    });

}


/* ==========================================
   COPY EMAIL ADDRESS
========================================== */

const copyButton = document.getElementById("copyEmail");
const toast = document.getElementById("toast");

if (copyButton && toast) {

    copyButton.addEventListener("click", async () => {

        try {

            await navigator.clipboard.writeText(
                "dadadavid097@gmail.com"
            );

            toast.textContent = "Email address copied!";

        } catch {

            toast.textContent = "Email: dadadavid097@gmail.com";

        }

        toast.classList.add("show");

        setTimeout(() => {
            toast.classList.remove("show");
        }, 2200);

    });

}


/* ==========================================
   AUTOMATIC FOOTER YEAR
========================================== */

const year = document.getElementById("year");

if (year) {
    year.textContent = new Date().getFullYear();
}


/* ==========================================
   CONTACT FORM
========================================== */

function sendContactMessage(event) {

    event.preventDefault();

    const nameField = document.getElementById("contactName");
    const emailField = document.getElementById("contactEmail");
    const messageField = document.getElementById("contactMessage");

    if (!nameField || !emailField || !messageField) {
        return;
    }

    const name = nameField.value.trim();
    const email = emailField.value.trim();
    const message = messageField.value.trim();

    const subject = encodeURIComponent(
        "Portfolio Contact Message"
    );

    const body = encodeURIComponent(
        "Name: " + name + "\n" +
        "Email: " + email + "\n\n" +
        "Message:\n" + message
    );

    window.location.href =
        "mailto:dadadavid097@gmail.com?subject=" +
        subject +
        "&body=" +
        body;

}