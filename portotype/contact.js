"use strict";

const contactForm = document.getElementById("contactForm");
const messageInput = document.getElementById("message");
const messageCount = document.getElementById("messageCount");
const searchIcon = document.querySelector(".search-icon");
const searchBox = document.querySelector(".search-box");
const closeSearch = document.querySelector(".close-search");
const searchInput = document.getElementById("searchInput");

function openBookSearch() {
    if (!searchBox) {
        return;
    }

    searchBox.classList.add("active");
    searchInput?.focus();
}

function closeBookSearch() {
    if (!searchBox) {
        return;
    }

    searchBox.classList.remove("active");
    searchIcon?.focus();
}

function activateWithKeyboard(event, callback) {
    if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        callback();
    }
}

searchIcon?.addEventListener("click", openBookSearch);

searchIcon?.addEventListener("keydown", (event) => {
    activateWithKeyboard(event, openBookSearch);
});

closeSearch?.addEventListener("click", closeBookSearch);

closeSearch?.addEventListener("keydown", (event) => {
    activateWithKeyboard(event, closeBookSearch);
});

document.addEventListener("keydown", (event) => {
    if (
        event.key === "Escape" &&
        searchBox?.classList.contains("active")
    ) {
        closeBookSearch();
    }
});

function setFieldError(input, message) {
    const group = input.closest(".field-group");

    const errorId = input
        .getAttribute("aria-describedby")
        ?.split(" ")[0];

    const errorElement = errorId
        ? document.getElementById(errorId)
        : null;

    if (group) {
        group.classList.add("has-error");
    }

    if (errorElement) {
        errorElement.textContent = message;
    }

    input.setAttribute("aria-invalid", "true");
}

function clearFieldError(input) {
    const group = input.closest(".field-group");

    const errorId = input
        .getAttribute("aria-describedby")
        ?.split(" ")[0];

    const errorElement = errorId
        ? document.getElementById(errorId)
        : null;

    if (group) {
        group.classList.remove("has-error");
    }

    if (errorElement) {
        errorElement.textContent = "";
    }

    input.removeAttribute("aria-invalid");
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return (
        phone === "" ||
        /^[0-9+()\-\s]{7,20}$/.test(phone)
    );
}

function updateMessageCount() {
    if (!messageInput || !messageCount) {
        return;
    }

    messageCount.textContent =
        `${messageInput.value.length} / 2000`;
}

if (messageInput) {
    updateMessageCount();

    messageInput.addEventListener(
        "input",
        updateMessageCount
    );
}

if (contactForm) {
    const inputs = contactForm.querySelectorAll(
        ".field-group input, " +
        ".field-group select, " +
        ".field-group textarea"
    );

    inputs.forEach((input) => {
        const eventName =
            input.tagName === "SELECT"
                ? "change"
                : "input";

        input.addEventListener(eventName, () => {
            clearFieldError(input);
        });
    });

    contactForm.addEventListener("submit", (event) => {
        const fullName =
            document.getElementById("fullName");

        const email =
            document.getElementById("email");

        const phone =
            document.getElementById("phone");

        const subject =
            document.getElementById("subject");

        const message =
            document.getElementById("message");

        let valid = true;

        inputs.forEach(clearFieldError);

        fullName.value = fullName.value
            .trim()
            .replace(/\s+/g, " ");

        email.value = email.value
            .trim()
            .toLowerCase();

        phone.value = phone.value.trim();
        message.value = message.value.trim();

        updateMessageCount();

        if (fullName.value === "") {
            setFieldError(
                fullName,
                "Please enter your full name."
            );

            valid = false;
        } else if (
            fullName.value.length < 2 ||
            fullName.value.length > 100
        ) {
            setFieldError(
                fullName,
                "Name must be between 2 and 100 characters."
            );

            valid = false;
        }

        if (email.value === "") {
            setFieldError(
                email,
                "Please enter your email address."
            );

            valid = false;
        } else if (!isValidEmail(email.value)) {
            setFieldError(
                email,
                "Please enter a valid email address."
            );

            valid = false;
        }

        if (!isValidPhone(phone.value)) {
            setFieldError(
                phone,
                "Please enter a valid phone number."
            );

            valid = false;
        }

        if (subject.value === "") {
            setFieldError(
                subject,
                "Please select a subject."
            );

            valid = false;
        }

        if (message.value === "") {
            setFieldError(
                message,
                "Please enter your message."
            );

            valid = false;
        } else if (message.value.length < 10) {
            setFieldError(
                message,
                "Message must contain at least 10 characters."
            );

            valid = false;
        }

        if (!valid) {
            event.preventDefault();

            const firstInvalid =
                contactForm.querySelector(
                    '[aria-invalid="true"]'
                );

            if (firstInvalid) {
                firstInvalid.focus();
            }

            return;
        }

        const button =
            contactForm.querySelector(
                ".submit-button"
            );

        if (button) {
            button.disabled = true;

            button.querySelector(
                "span:first-child"
            ).textContent = "Sending...";
        }
    });
}