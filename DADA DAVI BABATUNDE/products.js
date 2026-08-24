/* ==========================================
   DADA'S TECH - PRODUCTS JAVASCRIPT
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
       LOAD SAVED CART
    ========================================== */

    let cart =
        JSON.parse(
            localStorage.getItem("dadasTechCart")
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
            searchInput.value
                .toLowerCase()
                .trim();

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

    document
        .querySelectorAll(".wishlist")
        .forEach(button => {

            button.addEventListener("click", () => {

                button.classList.toggle("liked");


                if (
                    button.classList.contains("liked")
                ) {

                    button.textContent = "♥";

                } else {

                    button.textContent = "♡";

                }

            });

        });


    /* ==========================================
       ADD TO CART
    ========================================== */

    document
        .querySelectorAll(".add-cart")
        .forEach(button => {

            button.addEventListener("click", () => {

                const card =
                    button.closest(".product-card");


                const name =
                    card.dataset.name;

                const price =
                    Number(card.dataset.price);

                const image =
                    card.querySelector("img").src;


                /* Check if product already exists */

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

        /* Save cart */

        localStorage.setItem(
            "dadasTechCart",
            JSON.stringify(cart)
        );


        /* Calculate total quantity */

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


        /* Empty cart */

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


            cartTotal.textContent =
                "₦0";

            return;

        }


        cartItems.innerHTML = "";


        let total = 0;


        /* Display cart */

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


        cartTotal.textContent =
            formatPrice(total);


        /* ======================================
           INCREASE QUANTITY
        ====================================== */

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


        /* ======================================
           DECREASE QUANTITY
        ====================================== */

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


        /* ======================================
           REMOVE PRODUCT
        ====================================== */

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

        cartSidebar.classList.add("active");

        cartOverlay.classList.add("active");

        document.body.style.overflow =
            "hidden";

    }


    /* ==========================================
       CLOSE CART
    ========================================== */

    function closeCartSidebar() {

        cartSidebar.classList.remove(
            "active"
        );

        cartOverlay.classList.remove(
            "active"
        );

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
       INITIAL CART DISPLAY
    ========================================== */

    updateCart();

});