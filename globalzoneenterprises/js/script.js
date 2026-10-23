/* =========================================================
   GLOBAL ZONE SPORT ENTERPRISE
   JavaScript - Main Website Functions
   Tagline: Gear Up. Play Hard. Win Big.
   ========================================================= */


/* =========================================================
   1. MOBILE NAVIGATION MENU
   Opens and closes the menu on smaller screens.
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.querySelector(".menu-toggle");
    const navLinks = document.querySelector(".nav-links");

    if (menuToggle && navLinks) {
        menuToggle.addEventListener("click", function () {
            const isOpen = navLinks.classList.toggle("show");

            menuToggle.setAttribute("aria-expanded", isOpen);
            menuToggle.textContent = isOpen ? "✕" : "☰";
        });

        // Close the menu after clicking a navigation link.
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
       Allows customers to submit product searches easily.
       ===================================================== */

    const searchForm = document.querySelector(".search-box");

    if (searchForm) {
        searchForm.addEventListener("submit", function (event) {
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
       Automatically submits the form when a sort option
       is selected.
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
       Adds a small visual response when a product is clicked.
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
       Updates footer elements that use the current-year class.
       ===================================================== */

    const yearElements = document.querySelectorAll(".current-year");
    const currentYear = new Date().getFullYear();

    yearElements.forEach(function (element) {
        element.textContent = currentYear;
    });


    /* =====================================================
       6. BACK-TO-TOP BUTTON
       Works when a button with id="backToTop" is added.
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
       Highlights form fields when customers interact with
       them. This does not replace server-side validation.
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
       Works with password inputs using the class
       "password-input" and buttons using "toggle-password".
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
       Provides temporary feedback for buttons using the
       "add-to-cart" class. Actual cart storage and order
       processing will be handled separately with PHP.
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
       Shows a message for a newsletter form if one exists.
       Real subscriptions will require PHP/backend processing.
       ===================================================== */

    const newsletterForm = document.getElementById(
        "newsletterForm"
    );

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
                "The newsletter feature will be connected when the backend is ready."
            );
        });
    }

    console.log("Global Zone Sport Enterprise JavaScript loaded.");
});