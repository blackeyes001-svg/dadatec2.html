

const signupForm =
    document.getElementById("signupForm");


if (signupForm) {

    signupForm.addEventListener(
        "submit",
        function(event) {

            event.preventDefault();


            const name =
                document.getElementById("fullName").value;

            const email =
                document.getElementById("signupEmail").value;

            const password =
                document.getElementById("signupPassword").value;

            const confirmPassword =
                document.getElementById("confirmPassword").value;


            // Check passwords

            if (password !== confirmPassword) {

                alert("Passwords do not match!");

                return;

            }


            // Save account in browser

            const user = {

                name: name,

                email: email,

                password: password

            };


            localStorage.setItem(
                "dadasTechUser",
                JSON.stringify(user)
            );


            alert(
                "Account created successfully!"
            );


            // Send user to login

            window.location.href =
                "logindada.html";
 
        }
    );

}


const loginForm =
    document.getElementById("loginForm");


if (loginForm) {

    loginForm.addEventListener(
        "submit",
        function(event) {

            event.preventDefault();


            const email =
                document.getElementById("loginEmail").value;

            const password =
                document.getElementById("loginPassword").value;



            const savedUser =
                localStorage.getItem(
                    "dadasTechUser"
                );


            if (!savedUser) {

                alert(
                    "No account found. Please create an account first."
                );

                return;

            }


            const user =
                JSON.parse(savedUser);


            if (
                email === user.email &&
                password === user.password
            ) {

          

                localStorage.setItem(
                    "dadasTechLoggedIn",
                    "true"
                );


                alert(
                    "Login successful! Welcome " +
                    user.name
                );


                // Redirect to homepage

                window.location.href =
                    "index.html";

            }

            else {

                alert(
                    "Incorrect email or password."
                );

            }

        }
    );

}
window.location.href = "products.html";