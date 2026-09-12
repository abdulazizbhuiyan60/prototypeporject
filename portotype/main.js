/*const hero = document.querySelector(".hero");

const images = [
    "img/banner1.jpg",
    "img/banner2.jpg",
    "img/banner3.jpg",
    "img/banner4.jpg"
];

let currentImage = 0;

hero.style.backgroundImage = `url("${images[currentImage]}")`;

setInterval(() => {
    currentImage++;

    if (currentImage >= images.length) {
        currentImage = 0;
    }

    hero.style.backgroundImage = `url("${images[currentImage]}")`;

}, 2000);
*/ 

function changeImage(el) {
    document.getElementById('mainImage').src = el.src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

function selectSize(el, size) {
    document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('selectedSize').innerText = size;
}

let qty = 1;
function changeQty(delta) {
    qty = Math.max(1, qty + delta);
    document.getElementById('qtyValue').innerText = qty;
}

document.querySelectorAll('.accordion-header').forEach(btn => {
    btn.addEventListener('click', () => {
        btn.parentElement.classList.toggle('open');
    });
});
console.log("asdfawdfasdfasfasdfasfafasfasdfasfadsf")




/*                  search bar           */

const searchIcon = document.querySelector(".search-icon");
const searchBox = document.querySelector(".search-box");
const closeSearch = document.querySelector(".close-search");
const searchInput = document.querySelector("#searchInput");


// Click search icon
searchIcon.addEventListener("click", function () {

    searchBox.classList.add("active");

    // Automatically put cursor inside search box
    searchInput.focus();

});


// Click X
closeSearch.addEventListener("click", function () {

    searchBox.classList.remove("active");

    searchInput.value = "";

});




