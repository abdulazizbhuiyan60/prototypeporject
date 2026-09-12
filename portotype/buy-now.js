"use strict";

const checkoutForm = document.getElementById("checkoutForm");


/* ================= ERROR FUNCTIONS ================= */

function setFieldError(input, message) {
    const errorId = input.getAttribute("aria-describedby");

    const errorElement = errorId
        ? document.getElementById(errorId)
        : null;

    const group = input.closest(".field-group");

    if (errorElement) {
        errorElement.textContent = message;
    }

    if (group) {
        group.classList.add("has-error");
    }

    input.setAttribute("aria-invalid", "true");
}

function clearFieldError(input) {
    const errorId = input.getAttribute("aria-describedby");

    const errorElement = errorId
        ? document.getElementById(errorId)
        : null;

    const group = input.closest(".field-group");

    if (errorElement) {
        errorElement.textContent = "";
    }

    if (group) {
        group.classList.remove("has-error");
    }

    input.removeAttribute("aria-invalid");
}


/* ================= VALIDATION FUNCTIONS ================= */

function validEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validPhone(phone) {
    return /^[0-9+()\-\s]{7,20}$/.test(phone);
}

function validPostalCode(postalCode) {
    return /^[A-Za-z0-9\-\s]{3,12}$/.test(postalCode);
}


/* ================= CHECKOUT FORM ================= */

if (checkoutForm) {
    const inputs = checkoutForm.querySelectorAll(
        ".field-group input, .field-group textarea"
    );

    /*
     * Remove an error when the user starts
     * correcting the field.
     */
    inputs.forEach((input) => {
        input.addEventListener("input", () => {
            clearFieldError(input);
        });
    });

    checkoutForm.addEventListener("submit", (event) => {
        const fullName = document.getElementById("fullName");
        const email = document.getElementById("email");
        const phone = document.getElementById("phone");
        const address = document.getElementById("address");
        const city = document.getElementById("city");
        const postalCode = document.getElementById("postalCode");

        let valid = true;

        /*
         * Clear previous JavaScript errors.
         */
        inputs.forEach((input) => {
            clearFieldError(input);
        });

        /*
         * Remove unnecessary spaces.
         */
        fullName.value = fullName.value
            .trim()
            .replace(/\s+/g, " ");

        email.value = email.value.trim();
        phone.value = phone.value.trim();
        address.value = address.value.trim();

        city.value = city.value
            .trim()
            .replace(/\s+/g, " ");

        postalCode.value = postalCode.value.trim();


        /* ================= FULL NAME ================= */

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


        /* ================= EMAIL ================= */

        if (email.value === "") {
            setFieldError(
                email,
                "Please enter your email."
            );

            valid = false;
        } else if (!validEmail(email.value)) {
            setFieldError(
                email,
                "Please enter a valid email address."
            );

            valid = false;
        }


        /* ================= PHONE ================= */

        if (phone.value === "") {
            setFieldError(
                phone,
                "Please enter your phone number."
            );

            valid = false;
        } else if (!validPhone(phone.value)) {
            setFieldError(
                phone,
                "Please enter a valid phone number."
            );

            valid = false;
        }


        /* ================= ADDRESS ================= */

        if (address.value === "") {
            setFieldError(
                address,
                "Please enter your delivery address."
            );

            valid = false;
        } else if (
            address.value.length < 10 ||
            address.value.length > 200
        ) {
            setFieldError(
                address,
                "Address must be between 10 and 200 characters."
            );

            valid = false;
        }


        /* ================= CITY ================= */

        if (city.value === "") {
            setFieldError(
                city,
                "Please enter your city."
            );

            valid = false;
        } else if (
            city.value.length < 2 ||
            city.value.length > 80
        ) {
            setFieldError(
                city,
                "City must be between 2 and 80 characters."
            );

            valid = false;
        }


        /* ================= POSTAL CODE ================= */

        if (postalCode.value === "") {
            setFieldError(
                postalCode,
                "Please enter your postal code."
            );

            valid = false;
        } else if (!validPostalCode(postalCode.value)) {
            setFieldError(
                postalCode,
                "Please enter a valid postal code."
            );

            valid = false;
        }


        /* ================= FINAL RESULT ================= */

        if (!valid) {
            event.preventDefault();

            const firstInvalidInput =
                checkoutForm.querySelector(
                    '[aria-invalid="true"]'
                );

            if (firstInvalidInput) {
                firstInvalidInput.focus();
            }
        } else {
            /*
             * Allow the valid form to submit to PHP.
             */
            const submitButton =
                checkoutForm.querySelector(
                    ".place-order-button"
                );

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = "Placing order...";
            }
        }
    });
}