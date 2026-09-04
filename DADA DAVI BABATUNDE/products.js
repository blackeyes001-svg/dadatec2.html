/* ==========================================
   DADA'S TECH - PRODUCTS JAVASCRIPT
   CART + WISHLIST + SEARCH + FILTER + SORT
========================================== */

document.addEventListener("DOMContentLoaded", () => {

    const productsGrid = document.getElementById("productsGrid");
    const productCards = Array.from(
        document.querySelectorAll(".product-card")
    );

    const categoryButtons =
        document.querySelectorAll(".category-btn");

    const searchInput =
        document.getElementById("searchInput");

    const sortProducts =
        document.getElementById("sortProducts");

    const noProducts =
        document.getElementById("noProducts");

    const hamburger =
        document.getElementById("hamburger");

    const navLinks =
        document.getElementById("navLinks");

    const darkModeBtn =
        document.getElementById("darkModeBtn");

    const cartBtn =
        document.getElementById("cartBtn");

    const cartSidebar =
        document.getElementById("cartSidebar");

    const cartOverlay =
        document.getElementById("cartOverlay");

    const closeCart =
        document.getElementById("closeCart");

    const cartItems =
        document.getElementById("cartItems");

    const cartCount =
        document.getElementById("cartCount");

    const cartTotal =
        document.getElementById("cartTotal");


    let selectedCategory = "all";


    /* ==========================================
       LOAD CART
    ========================================== */

    let cart =
        JSON.parse(
            localStorage.getItem("dadasTechCart")
        ) || [];


    /* ==========================================
       LOAD WISHLIST
    ========================================== */

    let wishlist =
        JSON.parse(
            localStorage.getItem("dadasTechWishlist")
        ) || [];


    /* ==========================================
       MOBILE MENU
    ========================================== */

    if (hamburger && navLinks) {

        hamburger.addEventListener("click", () => {

            navLinks.classList.toggle("active");

        });

    }


    /* ==========================================
       DARK MODE
    ========================================== */

    if (darkModeBtn) {

        darkModeBtn.addEventListener("click", () => {

            document.body.classList.toggle("dark");

            if (
                document.body.classList.contains("dark")
            ) {

                darkModeBtn.textContent = "☀️";

            } else {

                darkModeBtn.textContent = "🌙";

            }

        });

    }


    /* ==========================================
       CATEGORY FILTER
    ========================================== */

    categoryButtons.forEach(button => {

        button.addEventListener("click", () => {

            categoryButtons.forEach(btn => {

                btn.classList.remove("active");

            });

            button.classList.add("active");

            selectedCategory =
                button.dataset.category;

            filterProducts();

        });

    });


    /* ==========================================
       SEARCH
    ========================================== */

    if (searchInput) {

        searchInput.addEventListener(
            "input",
            filterProducts
        );

    }


    function filterProducts() {

        const searchTerm =
            searchInput
                ? searchInput.value
                    .toLowerCase()
                    .trim()
                : "";

        let visibleProducts = 0;


        productCards.forEach(card => {

            const category =
                card.dataset.category;

            const name =
                card.dataset.name.toLowerCase();


            const categoryMatch =
                selectedCategory === "all" ||
                category === selectedCategory;


            const searchMatch =
                name.includes(searchTerm);


            if (
                categoryMatch &&
                searchMatch
            ) {

                card.style.display = "";

                visibleProducts++;

            } else {

                card.style.display = "none";

            }

        });


        if (noProducts) {

            noProducts.style.display =
                visibleProducts === 0
                    ? "block"
                    : "none";

        }

    }


    /* ==========================================
       SORT PRODUCTS
    ========================================== */

    if (sortProducts) {

        sortProducts.addEventListener(
            "change",
            () => {

                const value =
                    sortProducts.value;

                const sortedProducts =
                    [...productCards];


                if (value === "low") {

                    sortedProducts.sort(
                        (a, b) =>
                            Number(a.dataset.price) -
                            Number(b.dataset.price)
                    );

                }


                if (value === "high") {

                    sortedProducts.sort(
                        (a, b) =>
                            Number(b.dataset.price) -
                            Number(a.dataset.price)
                    );

                }


                if (value === "name") {

                    sortedProducts.sort(
                        (a, b) =>
                            a.dataset.name
                                .localeCompare(
                                    b.dataset.name
                                )
                    );

                }


                sortedProducts.forEach(card => {

                    productsGrid.appendChild(card);

                });


                filterProducts();

            }
        );

    }


    /* ==========================================
       WISHLIST
    ========================================== */

    function saveWishlist() {

        localStorage.setItem(
            "dadasTechWishlist",
            JSON.stringify(wishlist)
        );

    }


    function isInWishlist(name) {

        return wishlist.some(
            product => product.name === name
        );

    }


    function updateWishlistButtons() {

        document
            .querySelectorAll(".product-card")
            .forEach(card => {

                const button =
                    card.querySelector(".wishlist");

                if (!button) return;

                const name =
                    card.dataset.name;


                if (isInWishlist(name)) {

                    button.classList.add("liked");

                    button.textContent = "♥";

                } else {

                    button.classList.remove("liked");

                    button.textContent = "♡";

                }

            });

    }


    document
        .querySelectorAll(".wishlist")
        .forEach(button => {

            button.addEventListener("click", () => {

                const card =
                    button.closest(".product-card");

                if (!card) return;


                const name =
                    card.dataset.name;

                const price =
                    Number(card.dataset.price);

                const image =
                    card.querySelector("img")
                        ? card.querySelector("img").src
                        : "";

                const category =
                    card.dataset.category || "";

                const ratingElement =
                    card.querySelector(".rating");

                const rating =
                    ratingElement
                        ? ratingElement.textContent.trim()
                        : "";


                /* =========================
                   REMOVE FROM WISHLIST
                ========================= */

                const existingIndex =
                    wishlist.findIndex(
                        product =>
                            product.name === name
                    );


                if (existingIndex !== -1) {

                    wishlist.splice(
                        existingIndex,
                        1
                    );

                    button.classList.remove(
                        "liked"
                    );

                    button.textContent = "♡";

                }


                /* =========================
                   ADD TO WISHLIST
                ========================= */

                else {

                    wishlist.push({

                        name: name,

                        price: price,

                        image: image,

                        category: category,

                        rating: rating

                    });


                    button.classList.add(
                        "liked"
                    );

                    button.textContent = "♥";

                }


                saveWishlist();

            });

        });


    /* ==========================================
       RESTORE WISHLIST BUTTONS
    ========================================== */

    updateWishlistButtons();


    /* ==========================================
       ADD TO CART
    ========================================== */

    document
        .querySelectorAll(".add-cart")
        .forEach(button => {

            button.addEventListener("click", () => {

                const card =
                    button.closest(".product-card");


                if (!card) return;


                const name =
                    card.dataset.name;

                const price =
                    Number(card.dataset.price);

                const image =
                    card.querySelector("img")
                        ? card.querySelector("img").src
                        : "";


                const existingProduct =
                    cart.find(
                        product =>
                            product.name === name
                    );


                if (existingProduct) {

                    existingProduct.quantity++;

                } else {

                    cart.push({

                        name: name,

                        price: price,

                        image: image,

                        quantity: 1

                    });

                }


                updateCart();

                openCart();


                button.textContent =
                    "✓ Added to Cart";


                setTimeout(() => {

                    button.textContent =
                        "🛒 Add to Cart";

                }, 1200);

            });

        });


    /* ==========================================
       UPDATE CART
    ========================================== */

    function updateCart() {

        localStorage.setItem(
            "dadasTechCart",
            JSON.stringify(cart)
        );


        const totalQuantity =
            cart.reduce(
                (total, product) =>
                    total +
                    (product.quantity || 1),
                0
            );


        if (cartCount) {

            cartCount.textContent =
                totalQuantity;

        }


        if (!cartItems) return;


        if (cart.length === 0) {

            cartItems.innerHTML = `

                <div class="empty-cart">

                    <span>🛒</span>

                    <h3>
                        Your cart is empty
                    </h3>

                    <p>
                        Add some products
                        to get started.
                    </p>

                </div>

            `;


            if (cartTotal) {

                cartTotal.textContent = "₦0";

            }

            return;

        }


        cartItems.innerHTML = "";


        let total = 0;


        cart.forEach((product, index) => {

            const quantity =
                product.quantity || 1;


            const itemTotal =
                product.price * quantity;


            total += itemTotal;


            const item =
                document.createElement("div");


            item.className =
                "cart-item";


            item.innerHTML = `

                <img
                    src="${product.image}"
                    alt="${product.name}"
                >

                <div class="cart-item-info">

                    <h4>
                        ${product.name}
                    </h4>

                    <p>
                        ${formatPrice(product.price)}
                    </p>

                    <div class="quantity-controls">

                        <button
                            class="quantity-btn decrease"
                            data-index="${index}"
                        >
                            −
                        </button>

                        <span class="quantity">
                            ${quantity}
                        </span>

                        <button
                            class="quantity-btn increase"
                            data-index="${index}"
                        >
                            +
                        </button>

                    </div>

                </div>

                <div class="cart-item-right">

                    <strong>
                        ${formatPrice(itemTotal)}
                    </strong>

                    <button
                        class="remove-item"
                        data-index="${index}"
                    >
                        ✕
                    </button>

                </div>

            `;


            cartItems.appendChild(item);

        });


        if (cartTotal) {

            cartTotal.textContent =
                formatPrice(total);

        }


        /* INCREASE */

        document
            .querySelectorAll(".increase")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    () => {

                        const index =
                            Number(
                                button.dataset.index
                            );


                        cart[index].quantity++;


                        updateCart();

                    }
                );

            });


        /* DECREASE */

        document
            .querySelectorAll(".decrease")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    () => {

                        const index =
                            Number(
                                button.dataset.index
                            );


                        if (
                            cart[index].quantity > 1
                        ) {

                            cart[index].quantity--;

                        } else {

                            cart.splice(index, 1);

                        }


                        updateCart();

                    }
                );

            });


        /* REMOVE */

        document
            .querySelectorAll(".remove-item")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    () => {

                        const index =
                            Number(
                                button.dataset.index
                            );


                        cart.splice(index, 1);


                        updateCart();

                    }
                );

            });

    }


    /* ==========================================
       FORMAT PRICE
    ========================================== */

    function formatPrice(price) {

        return "₦" +
            Number(price)
                .toLocaleString("en-NG");

    }


    /* ==========================================
       OPEN CART
    ========================================== */

    if (cartBtn) {

        cartBtn.addEventListener(
            "click",
            openCart
        );

    }


    function openCart() {

        if (!cartSidebar || !cartOverlay) return;

        cartSidebar.classList.add("active");

        cartOverlay.classList.add("active");

        document.body.style.overflow =
            "hidden";

    }


    /* ==========================================
       CLOSE CART
    ========================================== */

    function closeCartSidebar() {

        if (cartSidebar) {

            cartSidebar.classList.remove(
                "active"
            );

        }

        if (cartOverlay) {

            cartOverlay.classList.remove(
                "active"
            );

        }

        document.body.style.overflow = "";

    }


    if (closeCart) {

        closeCart.addEventListener(
            "click",
            closeCartSidebar
        );

    }


    if (cartOverlay) {

        cartOverlay.addEventListener(
            "click",
            closeCartSidebar
        );

    }


    /* ==========================================
       CHECKOUT
    ========================================== */

    const checkoutBtn =
        document.querySelector(
            ".checkout-btn"
        );


    if (checkoutBtn) {

        checkoutBtn.addEventListener(
            "click",
            () => {

                if (cart.length === 0) {

                    alert(
                        "Your cart is empty. Please add a product first."
                    );

                    return;

                }


                window.location.href =
                    "checkout.html";

            }
        );

    }


    /* ==========================================
       INITIAL CART
    ========================================== */

    updateCart();

    filterProducts();

});
/* ==========================================
   DADA'S TECH - PRODUCTS JAVASCRIPT
   CART + WISHLIST + SEARCH + FILTER + SORT
   + PRODUCT DETAILS
========================================== */

document.addEventListener("DOMContentLoaded", () => {

    const productsGrid =
        document.getElementById("productsGrid");

    const productCards =
        Array.from(
            document.querySelectorAll(".product-card")
        );

    const categoryButtons =
        document.querySelectorAll(".category-btn");

    const searchInput =
        document.getElementById("searchInput");

    const sortProducts =
        document.getElementById("sortProducts");

    const noProducts =
        document.getElementById("noProducts");

    const hamburger =
        document.getElementById("hamburger");

    const navLinks =
        document.getElementById("navLinks");

    const darkModeBtn =
        document.getElementById("darkModeBtn");

    const cartBtn =
        document.getElementById("cartBtn");

    const cartSidebar =
        document.getElementById("cartSidebar");

    const cartOverlay =
        document.getElementById("cartOverlay");

    const closeCart =
        document.getElementById("closeCart");

    const cartItems =
        document.getElementById("cartItems");

    const cartCount =
        document.getElementById("cartCount");

    const cartTotal =
        document.getElementById("cartTotal");


    /* ==========================================
       PRODUCT DETAILS ELEMENTS
    ========================================== */

    const productDetails =
        document.getElementById("productDetails");

    const backToProducts =
        document.getElementById("backToProducts");

    const detailsImage =
        document.getElementById("detailsImage");

    const detailsName =
        document.getElementById("detailsName");

    const detailsCategory =
        document.getElementById("detailsCategory");

    const detailsRating =
        document.getElementById("detailsRating");

    const detailsPrice =
        document.getElementById("detailsPrice");

    const detailsDescription =
        document.getElementById("detailsDescription");

    const detailsHighlights =
        document.getElementById("detailsHighlights");

    const detailsBadge =
        document.getElementById("detailsBadge");

    const detailsMinus =
        document.getElementById("detailsMinus");

    const detailsPlus =
        document.getElementById("detailsPlus");

    const detailsQuantity =
        document.getElementById("detailsQuantity");

    const detailsAddCart =
        document.getElementById("detailsAddCart");

    const detailsBuyNow =
        document.getElementById("detailsBuyNow");

    const detailsWishlist =
        document.getElementById("detailsWishlist");

    const specName =
        document.getElementById("specName");

    const specCategory =
        document.getElementById("specCategory");

    const specRating =
        document.getElementById("specRating");


    let selectedCategory = "all";

    let detailsProduct = null;

    let detailsQty = 1;


    /* ==========================================
       LOAD CART
    ========================================== */

    let cart =
        JSON.parse(
            localStorage.getItem("dadasTechCart")
        ) || [];


    /* ==========================================
       LOAD WISHLIST
    ========================================== */

    let wishlist =
        JSON.parse(
            localStorage.getItem("dadasTechWishlist")
        ) || [];


    /* ==========================================
       MOBILE MENU
    ========================================== */

    if (hamburger && navLinks) {

        hamburger.addEventListener("click", () => {

            navLinks.classList.toggle("active");

        });

    }


    /* ==========================================
       DARK MODE
    ========================================== */

    if (darkModeBtn) {

        darkModeBtn.addEventListener("click", () => {

            document.body.classList.toggle("dark");

            if (
                document.body.classList.contains("dark")
            ) {

                darkModeBtn.textContent = "☀️";

            } else {

                darkModeBtn.textContent = "🌙";

            }

        });

    }


    /* ==========================================
       CATEGORY FILTER
    ========================================== */

    categoryButtons.forEach(button => {

        button.addEventListener("click", () => {

            categoryButtons.forEach(btn => {

                btn.classList.remove("active");

            });

            button.classList.add("active");

            selectedCategory =
                button.dataset.category;

            filterProducts();

        });

    });


    /* ==========================================
       SEARCH
    ========================================== */

    if (searchInput) {

        searchInput.addEventListener(
            "input",
            filterProducts
        );

    }


    function filterProducts() {

        const searchTerm =
            searchInput
                ? searchInput.value
                    .toLowerCase()
                    .trim()
                : "";

        let visibleProducts = 0;


        productCards.forEach(card => {

            const category =
                card.dataset.category;

            const name =
                card.dataset.name
                    .toLowerCase();


            const categoryMatch =
                selectedCategory === "all" ||
                category === selectedCategory;


            const searchMatch =
                name.includes(searchTerm);


            if (
                categoryMatch &&
                searchMatch
            ) {

                card.style.display = "";

                visibleProducts++;

            } else {

                card.style.display = "none";

            }

        });


        if (noProducts) {

            noProducts.style.display =
                visibleProducts === 0
                    ? "block"
                    : "none";

        }

    }


    /* ==========================================
       SORT PRODUCTS
    ========================================== */

    if (sortProducts) {

        sortProducts.addEventListener(
            "change",
            () => {

                const value =
                    sortProducts.value;


                const sortedProducts =
                    [...productCards];


                if (value === "low") {

                    sortedProducts.sort(
                        (a, b) =>
                            Number(
                                a.dataset.price
                            ) -
                            Number(
                                b.dataset.price
                            )
                    );

                }


                if (value === "high") {

                    sortedProducts.sort(
                        (a, b) =>
                            Number(
                                b.dataset.price
                            ) -
                            Number(
                                a.dataset.price
                            )
                    );

                }


                if (value === "name") {

                    sortedProducts.sort(
                        (a, b) =>
                            a.dataset.name
                                .localeCompare(
                                    b.dataset.name
                                )
                    );

                }


                sortedProducts.forEach(card => {

                    productsGrid.appendChild(card);

                });


                filterProducts();

            }
        );

    }


    /* ==========================================
       PRODUCT DETAILS
    ========================================== */

    function getProductData(card) {

        const imageElement =
            card.querySelector("img");

        const ratingElement =
            card.querySelector(".rating");

        const categoryElement =
            card.querySelector(
                ".product-category"
            );


        return {

            name:
                card.dataset.name || "",

            price:
                Number(
                    card.dataset.price || 0
                ),

            category:
                card.dataset.category || "",

            categoryLabel:
                categoryElement
                    ? categoryElement.textContent.trim()
                    : card.dataset.category || "",

            image:
                imageElement
                    ? imageElement.src
                    : "",

            rating:
                ratingElement
                    ? ratingElement.textContent.trim()
                    : "⭐⭐⭐⭐⭐",

            badge:
                card.querySelector(".badge")
                    ? card.querySelector(".badge")
                        .textContent.trim()
                    : "Product"

        };

    }


    function openProductDetails(card) {

        detailsProduct =
            getProductData(card);


        localStorage.setItem(
            "dadaTechSelectedProduct",
            JSON.stringify(detailsProduct)
        );


        showProductDetails();

    }


    function showProductDetails() {

        if (
            !productDetails ||
            !detailsProduct
        ) {
            return;
        }


        detailsQty = 1;


        if (detailsQuantity) {

            detailsQuantity.textContent =
                detailsQty;

        }


        /* IMAGE */

        if (detailsImage) {

            detailsImage.src =
                detailsProduct.image;

            detailsImage.alt =
                detailsProduct.name;

        }


        /* NAME */

        if (detailsName) {

            detailsName.textContent =
                detailsProduct.name;

        }


        /* CATEGORY */

        if (detailsCategory) {

            detailsCategory.textContent =
                detailsProduct.categoryLabel;

        }


        /* RATING */

        if (detailsRating) {

            detailsRating.textContent =
                detailsProduct.rating;

        }


        /* PRICE */

        if (detailsPrice) {

            detailsPrice.textContent =
                formatPrice(
                    detailsProduct.price
                );

        }


        /* BADGE */

        if (detailsBadge) {

            detailsBadge.textContent =
                detailsProduct.badge ||
                "Product";

        }


        /* DESCRIPTION */

        if (detailsDescription) {

            detailsDescription.textContent =
                getProductDescription(
                    detailsProduct.category
                );

        }


        /* HIGHLIGHTS */

        if (detailsHighlights) {

            detailsHighlights.innerHTML = "";

            const highlights =
                getProductHighlights(
                    detailsProduct.category
                );


            highlights.forEach(highlight => {

                const li =
                    document.createElement("li");

                li.textContent =
                    highlight;

                detailsHighlights.appendChild(li);

            });

        }


        /* SPECIFICATIONS */

        if (specName) {

            specName.textContent =
                detailsProduct.name;

        }


        if (specCategory) {

            specCategory.textContent =
                detailsProduct.categoryLabel;

        }


        if (specRating) {

            specRating.textContent =
                detailsProduct.rating;

        }


        updateDetailsWishlistButton();


        /* SHOW DETAILS */

        productDetails.classList.add("active");


        /* HIDE PRODUCT AREA */

        if (productsGrid) {

            productsGrid.parentElement
                ?.classList.add("products-hidden");

        }


        window.scrollTo({
            top: productDetails.offsetTop - 80,
            behavior: "smooth"
        });

    }


    /* ==========================================
       MAKE PRODUCTS CLICKABLE
    ========================================== */

    productCards.forEach(card => {

        card.style.cursor = "pointer";


        card.addEventListener("click", event => {

            /*
              Don't open details when the user
              clicks Wishlist or Add to Cart.
            */

            if (
                event.target.closest(
                    ".wishlist, .add-cart"
                )
            ) {
                return;
            }


            openProductDetails(card);

        });

    });


    /* ==========================================
       BACK TO PRODUCTS
    ========================================== */

    if (backToProducts) {

        backToProducts.addEventListener(
            "click",
            () => {

                if (productDetails) {

                    productDetails.classList.remove(
                        "active"
                    );

                }


                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });

            }
        );

    }


    /* ==========================================
       PRODUCT DESCRIPTIONS
    ========================================== */

    function getProductDescription(category) {

        const descriptions = {

            computers:
                "A reliable computer designed for everyday productivity, work, school, browsing, entertainment and other daily technology needs.",

            phones:
                "A modern smartphone designed for communication, entertainment, photography, social media and everyday mobile use.",

            tv:
                "A quality entertainment display designed for movies, streaming, sports, gaming and an enjoyable viewing experience.",

            gaming:
                "Gaming equipment designed to improve your gaming setup and provide a more enjoyable and immersive gaming experience.",

            audio:
                "Audio equipment designed for music, movies, calls and everyday listening with an enjoyable sound experience.",

            accessories:
                "A useful technology accessory designed to complement your devices and make everyday technology more convenient.",

            networking:
                "Networking equipment designed to help keep your devices connected and support a reliable network setup.",

            smart:
                "A smart technology product designed to add convenience, connectivity and modern features to your everyday life."

        };


        return (
            descriptions[category] ||
            "A quality technology product available from Dada's Tech."
        );

    }


    /* ==========================================
       PRODUCT HIGHLIGHTS
    ========================================== */

    function getProductHighlights(category) {

        const highlights = {

            computers: [
                "Suitable for work and everyday productivity",
                "Great for school and browsing",
                "Designed for everyday computer use"
            ],

            phones: [
                "Designed for everyday communication",
                "Suitable for entertainment and social media",
                "Portable and convenient"
            ],

            tv: [
                "Great for home entertainment",
                "Suitable for movies and streaming",
                "Ideal for sports and gaming"
            ],

            gaming: [
                "Designed for gaming setups",
                "Great for gaming and entertainment",
                "Suitable for gamers"
            ],

            audio: [
                "Designed for everyday listening",
                "Suitable for music and entertainment",
                "Useful for calls and media"
            ],

            accessories: [
                "Useful everyday technology accessory",
                "Designed to complement your devices",
                "Convenient and practical"
            ],

            networking: [
                "Designed for connectivity",
                "Useful for home or office setups",
                "Helps keep devices connected"
            ],

            smart: [
                "Designed for modern smart living",
                "Convenient everyday technology",
                "Suitable for connected environments"
            ]

        };


        return (
            highlights[category] ||
            [
                "Quality technology product",
                "Suitable for everyday use",
                "Available from Dada's Tech"
            ]
        );

    }


    /* ==========================================
       QUANTITY
    ========================================== */

    if (detailsMinus) {

        detailsMinus.addEventListener(
            "click",
            () => {

                if (detailsQty > 1) {

                    detailsQty--;

                }


                if (detailsQuantity) {

                    detailsQuantity.textContent =
                        detailsQty;

                }

            }
        );

    }


    if (detailsPlus) {

        detailsPlus.addEventListener(
            "click",
            () => {

                detailsQty++;


                if (detailsQuantity) {

                    detailsQuantity.textContent =
                        detailsQty;

                }

            }
        );

    }


    /* ==========================================
       ADD DETAILS PRODUCT TO CART
    ========================================== */

    function addDetailsProductToCart() {

        if (!detailsProduct) return;


        const existingProduct =
            cart.find(
                product =>
                    product.name ===
                    detailsProduct.name
            );


        if (existingProduct) {

            existingProduct.quantity +=
                detailsQty;

        } else {

            cart.push({

                name:
                    detailsProduct.name,

                price:
                    detailsProduct.price,

                image:
                    detailsProduct.image,

                quantity:
                    detailsQty

            });

        }


        updateCart();


        showDetailsMessage(
            "✓ Product added to cart"
        );

    }


    if (detailsAddCart) {

        detailsAddCart.addEventListener(
            "click",
            addDetailsProductToCart
        );

    }


    /* ==========================================
       BUY NOW
    ========================================== */

    if (detailsBuyNow) {

        detailsBuyNow.addEventListener(
            "click",
            () => {

                if (!detailsProduct) {
                    return;
                }


                addDetailsProductToCart();


                setTimeout(() => {

                    window.location.href =
                        "checkout.html";

                }, 400);

            }
        );

    }


    /* ==========================================
       WISHLIST
    ========================================== */

    function isDetailsProductInWishlist() {

        if (!detailsProduct) {
            return false;
        }


        return wishlist.some(
            product =>
                product.name ===
                detailsProduct.name
        );

    }


    function updateDetailsWishlistButton() {

        if (!detailsWishlist) return;


        if (isDetailsProductInWishlist()) {

            detailsWishlist.textContent =
                "♥ In Wishlist";

            detailsWishlist.classList.add(
                "liked"
            );

        } else {

            detailsWishlist.textContent =
                "♡ Add to Wishlist";

            detailsWishlist.classList.remove(
                "liked"
            );

        }

    }


    if (detailsWishlist) {

        detailsWishlist.addEventListener(
            "click",
            () => {

                if (!detailsProduct) {
                    return;
                }


                const existingIndex =
                    wishlist.findIndex(
                        product =>
                            product.name ===
                            detailsProduct.name
                    );


                if (existingIndex !== -1) {

                    wishlist.splice(
                        existingIndex,
                        1
                    );

                } else {

                    wishlist.push({

                        name:
                            detailsProduct.name,

                        price:
                            detailsProduct.price,

                        image:
                            detailsProduct.image,

                        category:
                            detailsProduct.category,

                        rating:
                            detailsProduct.rating

                    });

                }


                saveWishlist();

                updateDetailsWishlistButton();

                updateWishlistButtons();

            }
        );

    }


    /* ==========================================
       WISHLIST STORAGE
    ========================================== */

    function saveWishlist() {

        localStorage.setItem(
            "dadasTechWishlist",
            JSON.stringify(wishlist)
        );

    }


    function isInWishlist(name) {

        return wishlist.some(
            product =>
                product.name === name
        );

    }


    function updateWishlistButtons() {

        document
            .querySelectorAll(".product-card")
            .forEach(card => {

                const button =
                    card.querySelector(
                        ".wishlist"
                    );


                if (!button) return;


                const name =
                    card.dataset.name;


                if (isInWishlist(name)) {

                    button.classList.add(
                        "liked"
                    );

                    button.textContent =
                        "♥";

                } else {

                    button.classList.remove(
                        "liked"
                    );

                    button.textContent =
                        "♡";

                }

            });

    }


    document
        .querySelectorAll(".wishlist")
        .forEach(button => {

            button.addEventListener(
                "click",
                () => {

                    const card =
                        button.closest(
                            ".product-card"
                        );


                    if (!card) return;


                    const name =
                        card.dataset.name;


                    const price =
                        Number(
                            card.dataset.price
                        );


                    const image =
                        card.querySelector("img")
                            ? card.querySelector("img").src
                            : "";


                    const category =
                        card.dataset.category ||
                        "";


                    const ratingElement =
                        card.querySelector(
                            ".rating"
                        );


                    const rating =
                        ratingElement
                            ? ratingElement.textContent.trim()
                            : "";


                    const existingIndex =
                        wishlist.findIndex(
                            product =>
                                product.name ===
                                name
                        );


                    if (existingIndex !== -1) {

                        wishlist.splice(
                            existingIndex,
                            1
                        );


                        button.classList.remove(
                            "liked"
                        );


                        button.textContent =
                            "♡";

                    } else {

                        wishlist.push({

                            name:
                                name,

                            price:
                                price,

                            image:
                                image,

                            category:
                                category,

                            rating:
                                rating

                        });


                        button.classList.add(
                            "liked"
                        );


                        button.textContent =
                            "♥";

                    }


                    saveWishlist();

                    updateDetailsWishlistButton();

                }
            );

        });


    updateWishlistButtons();


    /* ==========================================
       ADD TO CART
    ========================================== */

    document
        .querySelectorAll(".add-cart")
        .forEach(button => {

            button.addEventListener(
                "click",
                () => {

                    const card =
                        button.closest(
                            ".product-card"
                        );


                    if (!card) return;


                    const name =
                        card.dataset.name;


                    const price =
                        Number(
                            card.dataset.price
                        );


                    const image =
                        card.querySelector("img")
                            ? card.querySelector("img").src
                            : "";


                    const existingProduct =
                        cart.find(
                            product =>
                                product.name ===
                                name
                        );


                    if (existingProduct) {

                        existingProduct.quantity++;

                    } else {

                        cart.push({

                            name:
                                name,

                            price:
                                price,

                            image:
                                image,

                            quantity:
                                1

                        });

                    }


                    updateCart();

                    openCart();


                    button.textContent =
                        "✓ Added to Cart";


                    setTimeout(() => {

                        button.textContent =
                            "🛒 Add to Cart";

                    }, 1200);

                }
            );

        });


    /* ==========================================
       UPDATE CART
    ========================================== */

    function updateCart() {

        localStorage.setItem(
            "dadasTechCart",
            JSON.stringify(cart)
        );


        const totalQuantity =
            cart.reduce(
                (total, product) =>
                    total +
                    (product.quantity || 1),
                0
            );


        if (cartCount) {

            cartCount.textContent =
                totalQuantity;

        }


        if (!cartItems) return;


        if (cart.length === 0) {

            cartItems.innerHTML = `

                <div class="empty-cart">

                    <span>🛒</span>

                    <h3>
                        Your cart is empty
                    </h3>

                    <p>
                        Add some products
                        to get started.
                    </p>

                </div>

            `;


            if (cartTotal) {

                cartTotal.textContent =
                    "₦0";

            }


            return;

        }


        cartItems.innerHTML = "";


        let total = 0;


        cart.forEach(
            (product, index) => {

                const quantity =
                    product.quantity || 1;


                const itemTotal =
                    product.price *
                    quantity;


                total += itemTotal;


                const item =
                    document.createElement(
                        "div"
                    );


                item.className =
                    "cart-item";


                item.innerHTML = `

                    <img
                        src="${product.image}"
                        alt="${product.name}"
                    >

                    <div class="cart-item-info">

                        <h4>
                            ${product.name}
                        </h4>

                        <p>
                            ${formatPrice(
                                product.price
                            )}
                        </p>

                        <div class="quantity-controls">

                            <button
                                class="quantity-btn decrease"
                                data-index="${index}"
                            >
                                −
                            </button>

                            <span class="quantity">
                                ${quantity}
                            </span>

                            <button
                                class="quantity-btn increase"
                                data-index="${index}"
                            >
                                +
                            </button>

                        </div>

                    </div>

                    <div class="cart-item-right">

                        <strong>
                            ${formatPrice(
                                itemTotal
                            )}
                        </strong>

                        <button
                            class="remove-item"
                            data-index="${index}"
                        >
                            ✕
                        </button>

                    </div>

                `;


                cartItems.appendChild(item);

            }
        );


        if (cartTotal) {

            cartTotal.textContent =
                formatPrice(total);

        }


        document
            .querySelectorAll(".increase")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    () => {

                        const index =
                            Number(
                                button.dataset.index
                            );


                        cart[index].quantity++;


                        updateCart();

                    }
                );

            });


        document
            .querySelectorAll(".decrease")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    () => {

                        const index =
                            Number(
                                button.dataset.index
                            );


                        if (
                            cart[index].quantity > 1
                        ) {

                            cart[index].quantity--;

                        } else {

                            cart.splice(
                                index,
                                1
                            );

                        }


                        updateCart();

                    }
                );

            });


        document
            .querySelectorAll(".remove-item")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    () => {

                        const index =
                            Number(
                                button.dataset.index
                            );


                        cart.splice(
                            index,
                            1
                        );


                        updateCart();

                    }
                );

            });

    }


    /* ==========================================
       FORMAT PRICE
    ========================================== */

    function formatPrice(price) {

        return "₦" +
            Number(price)
                .toLocaleString("en-NG");

    }


    /* ==========================================
       OPEN CART
    ========================================== */

    if (cartBtn) {

        cartBtn.addEventListener(
            "click",
            openCart
        );

    }


    function openCart() {

        if (
            !cartSidebar ||
            !cartOverlay
        ) {
            return;
        }


        cartSidebar.classList.add(
            "active"
        );


        cartOverlay.classList.add(
            "active"
        );


        document.body.style.overflow =
            "hidden";

    }


    /* ==========================================
       CLOSE CART
    ========================================== */

    function closeCartSidebar() {

        if (cartSidebar) {

            cartSidebar.classList.remove(
                "active"
            );

        }


        if (cartOverlay) {

            cartOverlay.classList.remove(
                "active"
            );

        }


        document.body.style.overflow =
            "";

    }


    if (closeCart) {

        closeCart.addEventListener(
            "click",
            closeCartSidebar
        );

    }


    if (cartOverlay) {

        cartOverlay.addEventListener(
            "click",
            closeCartSidebar
        );

    }


    /* ==========================================
       CHECKOUT
    ========================================== */

    const checkoutBtn =
        document.querySelector(
            ".checkout-btn"
        );


    if (checkoutBtn) {

        checkoutBtn.addEventListener(
            "click",
            () => {

                if (cart.length === 0) {

                    alert(
                        "Your cart is empty. Please add a product first."
                    );

                    return;

                }


                window.location.href =
                    "checkout.html";

            }
        );

    }


    /* ==========================================
       MESSAGE
    ========================================== */

    function showDetailsMessage(message) {

        const messageBox =
            document.createElement("div");


        messageBox.textContent =
            message;


        messageBox.style.position =
            "fixed";

        messageBox.style.bottom =
            "25px";

        messageBox.style.right =
            "25px";

        messageBox.style.background =
            "#ff7200";

        messageBox.style.color =
            "white";

        messageBox.style.padding =
            "14px 22px";

        messageBox.style.borderRadius =
            "8px";

        messageBox.style.fontWeight =
            "600";

        messageBox.style.zIndex =
            "9999";


        document.body.appendChild(
            messageBox
        );


        setTimeout(() => {

            messageBox.remove();

        }, 2000);

    }


    /* ==========================================
       INITIAL CART
    ========================================== */

    updateCart();

    filterProducts();

});