/* assets/scripts.js */

document.addEventListener("DOMContentLoaded", function () {
    const messages = document.querySelectorAll(".alert");
    
    messages.forEach((msg) => {
        setTimeout(() => {
            msg.style.display = "none";
        }, 3000);
    });
    
    const deleteButtons = document.querySelectorAll(".delete-btn");
    deleteButtons.forEach(button => {
        button.addEventListener("click", function (event) {
            if (!confirm("Are you sure you want to delete this record?")) {
                event.preventDefault();
            }
        });
    });

    const formInputs = document.querySelectorAll("input, select");
    formInputs.forEach(input => {
        input.addEventListener("input", function () {
            this.value = this.value.replace(/<[^>]*>?/gm, ""); // Prevent XSS
        });
    });
});
