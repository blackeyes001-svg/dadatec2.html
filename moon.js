// =====================================
// MOON ANIMATION JAVASCRIPT
// =====================================


// Get HTML elements
const moon = document.querySelector(".moon");
const moonShadow = document.querySelector(".moon-shadow");
const sky = document.querySelector(".sky");


// =====================================
// 1. MOON MOVEMENT
// =====================================

let moonPosition = -200;

let moonSpeed = 0.15;


function moveMoon() {

    moonPosition += moonSpeed;


    // Reset moon when it leaves screen

    if (moonPosition > window.innerWidth + 200) {

        moonPosition = -200;

    }


    moon.style.left =
        moonPosition + "px";


    requestAnimationFrame(moveMoon);
}


// Start moon movement
moveMoon();


// =====================================
// 2. CREATE STARS
// =====================================

function createStars(number) {

    for (let i = 0; i < number; i++) {

        const star =
            document.createElement("div");


        star.classList.add("star");


        // Random position

        star.style.left =
            Math.random() * 100 + "%";


        star.style.top =
            Math.random() * 80 + "%";


        // Random size

        const size =
            Math.random() * 4 + 1;


        star.style.width =
            size + "px";


        star.style.height =
            size + "px";


        // Random animation speed

        star.style.animationDuration =
            Math.random() * 3 + 1 + "s";


        // Random animation delay

        star.style.animationDelay =
            Math.random() * 3 + "s";


        sky.appendChild(star);

    }

}


// Create 120 stars

createStars(120);


// =====================================
// 3. SHOOTING STARS
// =====================================

function createShootingStar() {

    const shootingStar =
        document.createElement("div");


    shootingStar.classList.add(
        "shooting-star"
    );


    // Random starting position

    shootingStar.style.left =
        Math.random() * 100 + "%";


    shootingStar.style.top =
        Math.random() * 50 + "%";


    sky.appendChild(
        shootingStar
    );


    // Remove after animation

    setTimeout(() => {

        shootingStar.remove();

    }, 1500);

}


// Create shooting star every 4 seconds

setInterval(() => {

    createShootingStar();

}, 4000);


// =====================================
// 4. MOON PHASES
// =====================================

const phases = [

    "new",
    "crescent",
    "half",
    "gibbous",
    "full"

];


let currentPhase = 0;


function changeMoonPhase() {

    const phase =
        phases[currentPhase];


    if (phase === "new") {

        moonShadow.style.left = "0px";

        moonShadow.style.opacity = "1";

    }


    else if (phase === "crescent") {

        moonShadow.style.left = "35px";

        moonShadow.style.opacity = "1";

    }


    else if (phase === "half") {

        moonShadow.style.left = "75px";

        moonShadow.style.opacity = "1";

    }


    else if (phase === "gibbous") {

        moonShadow.style.left = "115px";

        moonShadow.style.opacity = "0.8";

    }


    else if (phase === "full") {

        moonShadow.style.opacity = "0";

    }


    currentPhase++;


    if (currentPhase >= phases.length) {

        currentPhase = 0;

    }

}


// Change moon phase every 8 seconds

setInterval(

    changeMoonPhase,

    8000

);


// =====================================
// 5. MOUSE INTERACTION
// =====================================

document.addEventListener(
    "mousemove",
    function(event) {

        const mouseX =
            event.clientX;

        const mouseY =
            event.clientY;


        const moveX =
            (mouseX /
                window.innerWidth -
                0.5) * 20;


        const moveY =
            (mouseY /
                window.innerHeight -
                0.5) * 20;


        moon.style.transform =
            `translate(${moveX}px, ${moveY}px)`;

    }
);


// =====================================
// 6. DAY/NIGHT EFFECT
// =====================================

let nightBrightness = 1;


function nightCycle() {

    nightBrightness -= 0.0005;


    if (nightBrightness <= 0.3) {

        nightBrightness = 1;

    }


    sky.style.filter =
        `brightness(${nightBrightness})`;


    requestAnimationFrame(
        nightCycle
    );

}


nightCycle();


// =====================================
// 7. CONSOLE MESSAGE
// =====================================

console.log(
    "🌙 Moon Animation Started!"
);

console.log(
    "⭐ 120 Stars Created!"
);

console.log(
    "🌠 Shooting Stars Enabled!"
);

console.log(
    "🌗 Moon Phases Enabled!"
);

console.log(
    "☁️ Clouds Enabled!"
);

console.log(
    "🏔️ Mountains Enabled!"
);