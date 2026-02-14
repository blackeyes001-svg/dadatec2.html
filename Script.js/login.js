function login(event) {
    event.preventDefault(); // prevent the form from refreshing

    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const error = document.getElementById('error');

    // Example: simple login validation
    if(username === "admin" && password === "1234") {
        // Redirect to home page
        window.location.href = "recipe.html"; // <-- Replace with your home page filename
    } else {
        error.textContent = "Invalid username or password";
        error.style.color = "red";
    }
}