/* ==========================================
   DADA'S TECH - WISHLIST JAVASCRIPT
========================================== */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        const wishlistGrid =
            document.getElementById(
                "wishlistGrid"
            );

        const emptyWishlist =
            document.getElementById(
                "emptyWishlist"
            );

        const wishlistCount =
            document.getElementById(
                "wishlistCount"
            );

        const darkModeBtn =
            document.getElementById(
                "darkModeBtn"
            );


        /* ======================================
           LOAD WISHLIST
        ====================================== */

        let wishlist =
            JSON.parse(
                localStorage.getItem(
                    "dadasTechWishlist"
                )
            ) || [];


        /* ======================================
           DARK MODE
        ====================================== */

        if (darkModeBtn) {

            darkModeBtn.addEventListener(
                "click",
                () => {

                    document.body.classList.toggle(
                        "dark"
                    );


                    if (
                        document.body.classList.contains(
                            "dark"
                        )
                    ) {

                        darkModeBtn.textContent =
                            "☀️";

                    } else {

                        darkModeBtn.textContent =
                            "🌙";

                    }

                }
            );

        }


        /* ======================================
           FORMAT PRICE
        ====================================== */

        function formatPrice(price) {

            return "₦" +
                Number(price)
                    .toLocaleString("en-NG");

        }


        /* ======================================
           SAVE WISHLIST
        ====================================== */

        function saveWishlist() {

            localStorage.setItem(
                "dadasTechWishlist",
                JSON.stringify(wishlist)
            );

        }


        /* ======================================
           RENDER WISHLIST
        ====================================== */

        function renderWishlist() {

            wishlistGrid.innerHTML = "";


            if (wishlist.length === 0) {

                emptyWishlist.style.display =
                    "block";


                wishlistGrid.style.display =
                    "none";


                wishlistCount.textContent =
                    "0 products saved";


                return;

            }


            emptyWishlist.style.display =
                "none";


            wishlistGrid.style.display =
                "grid";


            wishlistCount.textContent =
                wishlist.length === 1
                    ? "1 product saved"
                    : `${wishlist.length} products saved`;


            wishlist.forEach(
                (product, index) => {

                    const card =
                        document.createElement(
                            "div"
                        );


                    card.className =
                        "wishlist-card";


                    card.innerHTML = `

                        <img
                            class="wishlist-image"
                            src="${product.image}"
                            alt="${product.name}"
                        >

                        <button
                            class="remove-wishlist"
                            data-index="${index}"
                            title="Remove from Wishlist"
                        >
                            ♥
                        </button>

                        <div class="wishlist-info">

                            <span class="wishlist-category">
                                ${product.category || "Technology"}
                            </span>

                            <h3>
                                ${product.name}
                            </h3>

                            <div class="wishlist-rating">
                                ${product.rating || "★★★★★"}
                            </div>

                            <div class="wishlist-price">
                                ${formatPrice(product.price)}
                            </div>

                            <div class="card-actions">

                                <button
                                    class="add-to-cart"
                                    data-index="${index}"
                                >
                                    🛒 Add to Cart
                                </button>

                            </div>

                        </div>

                    `;


                    wishlistGrid.appendChild(
                        card
                    );

                }
            );


            /* ==================================
               REMOVE BUTTONS
            ================================== */

            document
                .querySelectorAll(
                    ".remove-wishlist"
                )
                .forEach(button => {

                    button.addEventListener(
                        "click",
                        () => {

                            const index =
                                Number(
                                    button.dataset.index
                                );


                            wishlist.splice(
                                index,
                                1
                            );


                            saveWishlist();

                            renderWishlist();

                        }
                    );

                });


            /* ==================================
               ADD TO CART
            ================================== */

            document
                .querySelectorAll(
                    ".add-to-cart"
                )
                .forEach(button => {

                    button.addEventListener(
                        "click",
                        () => {

                            const index =
                                Number(
                                    button.dataset.index
                                );


                            const product =
                                wishlist[index];


                            let cart =
                                JSON.parse(
                                    localStorage.getItem(
                                        "dadasTechCart"
                                    )
                                ) || [];


                            const existingProduct =
                                cart.find(
                                    item =>
                                        item.name ===
                                        product.name
                                );


                            if (
                                existingProduct
                            ) {

                                existingProduct.quantity++;

                            } else {

                                cart.push({

                                    name:
                                        product.name,

                                    price:
                                        product.price,

                                    image:
                                        product.image,

                                    quantity: 1

                                });

                            }


                            localStorage.setItem(
                                "dadasTechCart",
                                JSON.stringify(cart)
                            );


                            button.textContent =
                                "✓ Added to Cart";


                            setTimeout(() => {

                                button.textContent =
                                    "🛒 Add to Cart";

                            }, 1200);

                        }
                    );

                });

        }


        /* ======================================
           START
        ====================================== */

        renderWishlist();

    }
);