"use strict";


/* ================= PRODUCT IMAGE ================= */

function changeImage(element) {
    const mainImage = document.getElementById("mainImage");

    if (!mainImage) {
        return;
    }

    mainImage.src = element.src;

    document.querySelectorAll(".thumb").forEach((thumbnail) => {
        thumbnail.classList.remove("active");
    });

    element.classList.add("active");
}


/* ================= SIZE SELECTION ================= */

function selectSize(element, size) {
    const selectedSize = document.getElementById("selectedSize");

    const cartSize =
        document.getElementById("cartSize");

    const buyNowSize =
        document.getElementById("buyNowSize");

    document.querySelectorAll(".size-btn").forEach((button) => {
        button.classList.remove("active");
    });

    element.classList.add("active");

    /*
     * Display the selected size on the product page.
     */
    if (selectedSize) {
        selectedSize.textContent = size;
    }

    /*
     * Send the size to cart.php.
     */
    if (cartSize) {
        cartSize.value = size;
    }

    /*
     * Send the size to buy-now.php.
     */
    if (buyNowSize) {
        buyNowSize.value = size;
    }
}


/* ================= PRODUCT QUANTITY ================= */

let quantity = 1;

function changeQty(change) {
    const quantityText =
        document.getElementById("qtyValue");

    const cartQuantity =
        document.getElementById("cartQuantity");

    const buyNowQuantity =
        document.getElementById("buyNowQuantity");

    if (!quantityText) {
        return;
    }

    /*
     * Minimum quantity: 1
     * Maximum quantity: 99
     */
    quantity = Math.min(
        99,
        Math.max(1, quantity + change)
    );

    quantityText.textContent = quantity;

    /*
     * Send quantity to cart.php.
     */
    if (cartQuantity) {
        cartQuantity.value = String(quantity);
    }

    /*
     * Send quantity to buy-now.php.
     */
    if (buyNowQuantity) {
        buyNowQuantity.value = String(quantity);
    }
}


/* ================= ACCORDION ================= */

document.querySelectorAll(".accordion-header").forEach((button) => {
    button.addEventListener("click", () => {
        button.parentElement.classList.toggle("open");
    });
});


/* ================= SEARCH BAR ================= */

const searchIcon =
    document.querySelector(".search-icon");

const searchBox =
    document.querySelector(".search-box");

const closeSearch =
    document.querySelector(".close-search");

const searchInput =
    document.getElementById("searchInput");

if (searchIcon && searchBox && searchInput) {
    searchIcon.addEventListener("click", () => {
        searchBox.classList.add("active");
        searchInput.focus();
    });
}

if (closeSearch && searchBox && searchInput) {
    closeSearch.addEventListener("click", () => {
        searchBox.classList.remove("active");
        searchInput.value = "";
    });
}


/* ================= CART PAGE ================= */

function continueShopping() {
    window.location.href = "product.php";
}