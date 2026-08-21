/* ==========================================
   DADA'S TECH - PRODUCTS JAVASCRIPT
========================================== */

document.addEventListener("DOMContentLoaded", () => {

    const productsGrid =
        document.getElementById("productsGrid");

    const productCards =
        Array.from(document.querySelectorAll(".product-card"));

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

    let cart = [];


    /* ======================================
       MOBILE MENU
    ====================================== */

    hamburger.addEventListener("click", () => {

        navLinks.classList.toggle("active");

    });


    /* ======================================
       DARK MODE
    ====================================== */

    darkModeBtn.addEventListener("click", () => {

        document.body.classList.toggle("dark");

        if (document.body.classList.contains("dark")) {

            darkModeBtn.textContent = "☀️";

        } else {

            darkModeBtn.textContent = "🌙";

        }

    });


    /* ======================================
       CATEGORY FILTER
    ====================================== */

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


    /* ======================================
       SEARCH
    ====================================== */

    searchInput.addEventListener("input", () => {

        filterProducts();

    });


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


            if (categoryMatch && searchMatch) {

                card.style.display = "";

                visibleProducts++;

            } else {

                card.style.display = "none";

            }

        });


        if (visibleProducts === 0) {

            noProducts.style.display = "block";

        } else {

            noProducts.style.display = "none";

        }

    }


    /* ======================================
       SORT PRODUCTS
    ====================================== */

    sortProducts.addEventListener("change", () => {

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
                        .localeCompare(b.dataset.name)
            );

        }


        sortedProducts.forEach(card => {

            productsGrid.appendChild(card);

        });

        filterProducts();

    });


    /* ======================================
       WISHLIST
    ====================================== */

    document
        .querySelectorAll(".wishlist")
        .forEach(button => {

            button.addEventListener("click", () => {

                button.classList.toggle("liked");

                if (button.classList.contains("liked")) {

                    button.textContent = "♥";

                } else {

                    button.textContent = "♡";

                }

            });

        });


    /* ======================================
       ADD TO CART
    ====================================== */

    document
        .querySelectorAll(".add-cart")
        .forEach(button => {

            button.addEventListener("click", () => {

                const card =
                    button.closest(".product-card");


                const product = {

                    name: card.dataset.name,

                    price:
                        Number(card.dataset.price),

                    image:
                        card.querySelector("img").src

                };


                cart.push(product);

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


    /* ======================================
       UPDATE CART
    ====================================== */

    function updateCart() {

        cartCount.textContent =
            cart.length;


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

            cartTotal.textContent = "₦0";

            return;

        }


        cartItems.innerHTML = "";


        let total = 0;


        cart.forEach((product, index) => {

            total += product.price;


            const item =
                document.createElement("div");

            item.className = "cart-item";


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

                </div>

                <button
                    class="remove-item"
                    data-index="${index}"
                >
                    ✕
                </button>

            `;


            cartItems.appendChild(item);

        });


        cartTotal.textContent =
            formatPrice(total);


        document
            .querySelectorAll(".remove-item")
            .forEach(button => {

                button.addEventListener("click", () => {

                    const index =
                        Number(button.dataset.index);

                    cart.splice(index, 1);

                    updateCart();

                });

            });

    }


    /* ======================================
       FORMAT PRICE
    ====================================== */

    function formatPrice(price) {

        return "₦" +
            price.toLocaleString("en-NG");

    }


    /* ======================================
       CART OPEN / CLOSE
    ====================================== */

    cartBtn.addEventListener("click", openCart);


    function openCart() {

        cartSidebar.classList.add("active");

        cartOverlay.classList.add("active");

        document.body.style.overflow = "hidden";

    }


    function closeCartSidebar() {

        cartSidebar.classList.remove("active");

        cartOverlay.classList.remove("active");

        document.body.style.overflow = "";

    }


    closeCart.addEventListener(
        "click",
        closeCartSidebar
    );


    cartOverlay.addEventListener(
        "click",
        closeCartSidebar
    );


    /* ======================================
       CHECKOUT BUTTON
    ====================================== */

    document
        .querySelector(".checkout-btn")
        .addEventListener("click", () => {

            if (cart.length === 0) {

                alert(
                    "Your cart is empty. Please add a product first."
                );

                return;

            }


            alert(
                "Checkout system coming soon!"
            );

        });

});
