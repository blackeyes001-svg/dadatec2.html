/* =========================================================
   BAYOGOAL ENTERPRISES
   JavaScript - Main Website Functions
   Tagline: Gear Up. Play Hard. Win Big.
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       1. MOBILE NAVIGATION MENU
       ===================================================== */

    const menuToggle = document.querySelector(".menu-toggle");
    const navLinks = document.querySelector(".nav-links");

    if (menuToggle && navLinks) {

        menuToggle.addEventListener("click", function () {

            const isOpen = navLinks.classList.toggle("show");

            menuToggle.setAttribute("aria-expanded", String(isOpen));
            menuToggle.textContent = isOpen ? "✕" : "☰";

        });

        navLinks.querySelectorAll("a").forEach(function (link) {

            link.addEventListener("click", function () {

                navLinks.classList.remove("show");

                menuToggle.setAttribute("aria-expanded", "false");
                menuToggle.textContent = "☰";

            });

        });

    }


    /* =====================================================
       2. SEARCH FORM
       ===================================================== */

    const searchForm = document.querySelector(".search-box");

    if (searchForm) {

        searchForm.addEventListener("submit", function () {

            const searchInput = searchForm.querySelector(
                'input[name="search"]'
            );

            if (searchInput) {
                searchInput.value = searchInput.value.trim();
            }

        });

    }


    /* =====================================================
       3. PRODUCT SORTING
       ===================================================== */

    const sortSelect = document.querySelector(
        'select[name="sort"]'
    );

    if (sortSelect) {

        sortSelect.addEventListener("change", function () {

            const sortForm = sortSelect.closest("form");

            if (sortForm) {
                sortForm.submit();
            }

        });

    }


    /* =====================================================
       4. PRODUCT CARD INTERACTIONS
       ===================================================== */

    const productCards = document.querySelectorAll(".product-card");

    productCards.forEach(function (card) {

        card.addEventListener("mouseenter", function () {
            card.classList.add("product-card-hover");
        });

        card.addEventListener("mouseleave", function () {
            card.classList.remove("product-card-hover");
        });

    });


    /* =====================================================
       5. CURRENT YEAR
       ===================================================== */

    const yearElements = document.querySelectorAll(".current-year");

    const currentYear = new Date().getFullYear();

    yearElements.forEach(function (element) {
        element.textContent = currentYear;
    });


    /* =====================================================
       6. BACK-TO-TOP BUTTON
       ===================================================== */

    const backToTop = document.getElementById("backToTop");

    if (backToTop) {

        window.addEventListener("scroll", function () {

            if (window.scrollY > 300) {
                backToTop.classList.add("visible");
            } else {
                backToTop.classList.remove("visible");
            }

        });

        backToTop.addEventListener("click", function () {

            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });

        });

    }


    /* =====================================================
       7. FORM INPUT FEEDBACK
       ===================================================== */

    const formInputs = document.querySelectorAll(
        "input, textarea, select"
    );

    formInputs.forEach(function (input) {

        input.addEventListener("focus", function () {
            input.classList.add("input-focused");
        });

        input.addEventListener("blur", function () {
            input.classList.remove("input-focused");
        });

    });


    /* =====================================================
       8. PASSWORD VISIBILITY
       ===================================================== */

    const passwordButtons = document.querySelectorAll(
        ".toggle-password"
    );

    passwordButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const passwordInput = button.parentElement.querySelector(
                ".password-input"
            );

            if (!passwordInput) {
                return;
            }

            const currentlyHidden =
                passwordInput.type === "password";

            passwordInput.type = currentlyHidden
                ? "text"
                : "password";

            button.textContent = currentlyHidden
                ? "Hide"
                : "Show";

            button.setAttribute(
                "aria-label",
                currentlyHidden ? "Hide password" : "Show password"
            );

        });

    });


    /* =====================================================
       9. ADD-TO-CART BUTTON FEEDBACK
       ===================================================== */

    const cartButtons = document.querySelectorAll(".add-to-cart");

    cartButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            if (button.disabled) {
                return;
            }

            const originalText = button.textContent;

            button.textContent = "Added!";
            button.classList.add("added");

            window.setTimeout(function () {

                button.textContent = originalText;
                button.classList.remove("added");

            }, 1500);

        });

    });


    /* =====================================================
       10. NEWSLETTER FORM FEEDBACK
       ===================================================== */

    const newsletterForm = document.getElementById("newsletterForm");

    if (newsletterForm) {

        newsletterForm.addEventListener("submit", function (event) {

            event.preventDefault();

            const emailInput = newsletterForm.querySelector(
                'input[type="email"]'
            );

            if (!emailInput || !emailInput.value.trim()) {

                alert("Please enter your email address.");
                return;

            }

            alert(
                "The newsletter feature is not yet connected. Thank you for your interest!"
            );

        });

    }


    /* =====================================================
       11. WEBSITE INITIALIZATION
       ===================================================== */

    console.log("Bayogoal Enterprises JavaScript loaded successfully.");

});