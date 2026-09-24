/* =====================================================
   SOLAR EXPLORED
   3D SOLAR SYSTEM
===================================================== */


/* =====================================================
   PLANET DATA
===================================================== */

const planets = {

    sun: {
        name: "THE SUN",
        number: "01",

        distance: "0 km",
        diameter: "1,392,700 km",
        moons: "8 planets",

        description:
            "The Sun is the star at the heart of our solar system. Its gravity holds the planets and other objects in orbit.",

        texture:
            "assets/textures/sun.jpg",

        size: 7,

        position: {
            x: 0,
            y: 0,
            z: 0
        },

        color: 0xff7200
    },


    mercury: {
        name: "MERCURY",
        number: "02",

        distance: "57.9 million km",
        diameter: "4,879 km",
        moons: "0",

        description:
            "Mercury is the smallest planet in the solar system and the closest planet to the Sun.",

        texture:
            "assets/textures/mercury.jpg",

        size: 1.15,

        position: {
            x: 10,
            y: 0,
            z: 0
        }
    },


    venus: {
        name: "VENUS",
        number: "03",

        distance: "108.2 million km",
        diameter: "12,104 km",
        moons: "0",

        description:
            "Venus is the second planet from the Sun and is wrapped in a thick atmosphere of carbon dioxide and clouds.",

        texture:
            "assets/textures/venus.jpg",

        size: 1.65,

        position: {
            x: 15,
            y: 0,
            z: 0
        }
    },


    earth: {
        name: "EARTH",
        number: "04",

        distance: "149.6 million km",
        diameter: "12,756 km",
        moons: "1",

        description:
            "Earth is the third planet from the Sun and the only world currently known to support life.",

        texture:
            "assets/textures/earth.jpg",

        size: 1.8,

        position: {
            x: 21,
            y: 0,
            z: 0
        }
    },


    mars: {
        name: "MARS",
        number: "05",

        distance: "227.9 million km",
        diameter: "6,792 km",
        moons: "2",

        description:
            "Mars is the fourth planet from the Sun. Its reddish appearance comes from iron minerals in its surface rocks.",

        texture:
            "assets/textures/mars.jpg",

        size: 1.35,

        position: {
            x: 27,
            y: 0,
            z: 0
        }
    },


    jupiter: {
        name: "JUPITER",
        number: "06",

        distance: "778.5 million km",
        diameter: "142,984 km",
        moons: "95+",

        description:
            "Jupiter is the largest planet in the solar system. Its atmosphere contains enormous storms including the Great Red Spot.",

        texture:
            "assets/textures/jupiter.jpg",

        size: 4.2,

        position: {
            x: 38,
            y: 0,
            z: 0
        }
    },


    saturn: {
        name: "SATURN",
        number: "07",

        distance: "1.43 billion km",
        diameter: "120,536 km",
        moons: "140+",

        description:
            "Saturn is a gas giant famous for its spectacular system of rings made primarily of ice and rocky debris.",

        texture:
            "assets/textures/saturn.jpg",

        size: 3.6,

        position: {
            x: 51,
            y: 0,
            z: 0
        }
    },


    uranus: {
        name: "URANUS",
        number: "08",

        distance: "2.87 billion km",
        diameter: "51,118 km",
        moons: "28",

        description:
            "Uranus is an ice giant with a blue-green appearance caused by methane in its atmosphere.",

        texture:
            "assets/textures/uranus.jpg",

        size: 2.6,

        position: {
            x: 64,
            y: 0,
            z: 0
        }
    },


    neptune: {
        name: "NEPTUNE",
        number: "09",

        distance: "4.5 billion km",
        diameter: "49,528 km",
        moons: "16",

        description:
            "Neptune is the most distant major planet in our solar system and has extremely powerful winds.",

        texture:
            "assets/textures/neptune.jpg",

        size: 2.55,

        position: {
            x: 77,
            y: 0,
            z: 0
        }
    }

};


/* =====================================================
   TEXTURE PATHS
===================================================== */

const textureLoader = new THREE.TextureLoader();

const textures = {};


/* =====================================================
   THREE.JS SETUP
===================================================== */

const scene = new THREE.Scene();

scene.background = new THREE.Color(0x000000);


const camera = new THREE.PerspectiveCamera(
    55,
    window.innerWidth / window.innerHeight,
    0.1,
    1000
);

camera.position.set(
    30,
    18,
    45
);


const renderer = new THREE.WebGLRenderer({
    antialias: true,
    powerPreference: "high-performance"
});

renderer.setPixelRatio(
    Math.min(window.devicePixelRatio, 2)
);

renderer.setSize(
    window.innerWidth,
    window.innerHeight
);

renderer.outputColorSpace =
    THREE.SRGBColorSpace;

document
    .getElementById("space")
    .appendChild(renderer.domElement);


/* =====================================================
   CAMERA CONTROLS
===================================================== */

const controls =
    new THREE.OrbitControls(
        camera,
        renderer.domElement
    );

controls.enableDamping = true;

controls.dampingFactor = 0.06;

controls.minDistance = 8;

controls.maxDistance = 160;

controls.enablePan = true;


/* =====================================================
   LIGHTING
===================================================== */

const ambientLight =
    new THREE.AmbientLight(
        0xffffff,
        0.25
    );

scene.add(ambientLight);


const sunLight =
    new THREE.PointLight(
        0xffaa55,
        5,
        300
    );

scene.add(sunLight);


/* =====================================================
   STAR FIELD
===================================================== */

function createStars() {

    const geometry =
        new THREE.BufferGeometry();

    const vertices = [];

    for (let i = 0; i < 7000; i++) {

        const radius =
            150 + Math.random() * 300;

        const theta =
            Math.random() * Math.PI * 2;

        const phi =
            Math.acos(
                (Math.random() * 2) - 1
            );

        const x =
            radius *
            Math.sin(phi) *
            Math.cos(theta);

        const y =
            radius *
            Math.cos(phi);

        const z =
            radius *
            Math.sin(phi) *
            Math.sin(theta);

        vertices.push(x, y, z);
    }

    geometry.setAttribute(
        "position",
        new THREE.Float32BufferAttribute(
            vertices,
            3
        )
    );


    const material =
        new THREE.PointsMaterial({
            color: 0xffffff,
            size: 0.25,
            transparent: true,
            opacity: 0.85
        });


    const stars =
        new THREE.Points(
            geometry,
            material
        );

    scene.add(stars);
}

createStars();


/* =====================================================
   PLANET GROUP
===================================================== */

const planetMeshes = {};

const planetGroups = {};


/* =====================================================
   CREATE PLANETS
===================================================== */

function createPlanet(key) {

    const data =
        planets[key];


    const group =
        new THREE.Group();

    group.position.set(
        data.position.x,
        data.position.y,
        data.position.z
    );


    const geometry =
        new THREE.SphereGeometry(
            data.size,
            64,
            64
        );


    let material;


    if (key === "sun") {

        material =
            new THREE.MeshBasicMaterial({
                color: 0xffa52c
            });

    } else {

        material =
            new THREE.MeshStandardMaterial({
                color: 0xffffff,
                roughness: 0.95,
                metalness: 0
            });

    }


    const mesh =
        new THREE.Mesh(
            geometry,
            material
        );


    mesh.userData.planet =
        key;


    group.add(mesh);


    /* =========================
       SATURN RINGS
    ========================= */

    if (key === "saturn") {

        const ringGeometry =
            new THREE.RingGeometry(
                data.size * 1.35,
                data.size * 2.2,
                96
            );


        const ringMaterial =
            new THREE.MeshBasicMaterial({
                color: 0xd4bd8b,
                side: THREE.DoubleSide,
                transparent: true,
                opacity: 0.85
            });


        const rings =
            new THREE.Mesh(
                ringGeometry,
                ringMaterial
            );


        rings.rotation.x =
            Math.PI / 2.8;


        group.add(rings);
    }


    scene.add(group);


    planetMeshes[key] =
        mesh;

    planetGroups[key] =
        group;


    /* =========================
       LOAD TEXTURE
    ========================= */

    textureLoader.load(
        data.texture,

        function(texture) {

            texture.colorSpace =
                THREE.SRGBColorSpace;

            mesh.material.map =
                texture;

            mesh.material.needsUpdate =
                true;

        },

        undefined,

        function(error) {

            console.warn(
                "Texture failed:",
                key,
                error
            );

        }
    );
}


/* =====================================================
   CREATE ALL PLANETS
===================================================== */

Object.keys(planets).forEach(
    createPlanet
);


/* =====================================================
   ORBITS
===================================================== */

function createOrbit(radius) {

    const points = [];

    for (
        let i = 0;
        i <= 128;
        i++
    ) {

        const angle =
            (i / 128) *
            Math.PI *
            2;

        points.push(
            new THREE.Vector3(
                Math.cos(angle) * radius,
                0,
                Math.sin(angle) * radius
            )
        );
    }


    const geometry =
        new THREE.BufferGeometry()
            .setFromPoints(points);


    const material =
        new THREE.LineBasicMaterial({
            color: 0xff7200,
            transparent: true,
            opacity: 0.12
        });


    const line =
        new THREE.Line(
            geometry,
            material
        );


    scene.add(line);
}


[
    10,
    15,
    21,
    27,
    38,
    51,
    64,
    77
].forEach(createOrbit);


/* =====================================================
   SUN GLOW
===================================================== */

const glowGeometry =
    new THREE.SphereGeometry(
        8.2,
        32,
        32
    );

const glowMaterial =
    new THREE.MeshBasicMaterial({
        color: 0xff7200,
        transparent: true,
        opacity: 0.08
    });

const glow =
    new THREE.Mesh(
        glowGeometry,
        glowMaterial
    );

scene.add(glow);


/* =====================================================
   PLANET INFORMATION
===================================================== */

const planetName =
    document.getElementById(
        "planetName"
    );

const planetDescription =
    document.getElementById(
        "planetDescription"
    );

const planetNumber =
    document.getElementById(
        "planetNumber"
    );

const planetDistance =
    document.getElementById(
        "planetDistance"
    );

const planetDiameter =
    document.getElementById(
        "planetDiameter"
    );

const planetMoons =
    document.getElementById(
        "planetMoons"
    );


let selectedPlanet =
    "sun";


function updateInformation(key) {

    const data =
        planets[key];

    selectedPlanet =
        key;


    planetName.textContent =
        data.name;

    planetNumber.textContent =
        data.number;

    planetDescription.textContent =
        data.description;

    planetDistance.textContent =
        data.distance;

    planetDiameter.textContent =
        data.diameter;

    planetMoons.textContent =
        data.moons;


    document
        .querySelectorAll(".planet-btn")
        .forEach(button => {

            button.classList.toggle(
                "active",
                button.dataset.planet === key
            );

        });
}


/* =====================================================
   CAMERA TARGET
===================================================== */

let cameraTarget =
    new THREE.Vector3();

let desiredCamera =
    new THREE.Vector3();


function focusPlanet(key) {

    const group =
        planetGroups[key];

    if (!group) return;


    selectedPlanet =
        key;


    cameraTarget.copy(
        group.position
    );


    const distance =
        planets[key].size * 5 + 8;


    desiredCamera.set(
        group.position.x + distance,
        group.position.y + distance * 0.3,
        group.position.z + distance
    );


    updateInformation(key);
}


/* =====================================================
   PLANET BUTTONS
===================================================== */

document
    .querySelectorAll(".planet-btn")
    .forEach(button => {

        button.addEventListener(
            "click",
            () => {

                const key =
                    button.dataset.planet;

                focusPlanet(key);
            }
        );

    });


/* =====================================================
   CLICK PLANET IN 3D
===================================================== */

const raycaster =
    new THREE.Raycaster();

const mouse =
    new THREE.Vector2();


renderer.domElement.addEventListener(
    "click",
    event => {

        mouse.x =
            (event.clientX /
                window.innerWidth) *
            2 - 1;

        mouse.y =
            -(event.clientY /
                window.innerHeight) *
            2 + 1;


        raycaster.setFromCamera(
            mouse,
            camera
        );


        const objects =
            Object.values(
                planetMeshes
            );


        const intersections =
            raycaster.intersectObjects(
                objects
            );


        if (
            intersections.length
        ) {

            const key =
                intersections[0]
                    .object
                    .userData
                    .planet;

            focusPlanet(key);
        }

    }
);


/* =====================================================
   MODAL
===================================================== */

const modal =
    document.getElementById(
        "planetModal"
    );

const modalPlanet =
    document.getElementById(
        "modalPlanet"
    );

const modalTitle =
    document.getElementById(
        "modalTitle"
    );

const modalDescription =
    document.getElementById(
        "modalDescription"
    );

const modalDistance =
    document.getElementById(
        "modalDistance"
    );

const modalDiameter =
    document.getElementById(
        "modalDiameter"
    );

const modalMoons =
    document.getElementById(
        "modalMoons"
    );


function openPlanetModal() {

    const data =
        planets[selectedPlanet];


    modal.classList.add("show");


    modalTitle.textContent =
        data.name;


    modalDescription.textContent =
        data.description;


    modalDistance.textContent =
        data.distance;


    modalDiameter.textContent =
        data.diameter;


    modalMoons.textContent =
        data.moons;


    modalPlanet.style.backgroundImage =
        `url("${data.texture}")`;
}


document
    .getElementById("exploreButton")
    .addEventListener(
        "click",
        openPlanetModal
    );


document
    .getElementById("closeModal")
    .addEventListener(
        "click",
        () => {

            modal.classList.remove(
                "show"
            );

        }
    );


modal.addEventListener(
    "click",
    event => {

        if (
            event.target === modal
        ) {

            modal.classList.remove(
                "show"
            );

        }

    }
);


/* =====================================================
   INFO MODAL
===================================================== */

const infoModal =
    document.getElementById(
        "infoModal"
    );


document
    .getElementById("infoButton")
    .addEventListener(
        "click",
        () => {

            infoModal.classList.add(
                "show"
            );

        }
    );


document
    .getElementById("closeInfo")
    .addEventListener(
        "click",
        () => {

            infoModal.classList.remove(
                "show"
            );

        }
    );


/* =====================================================
   RESET CAMERA
===================================================== */

document
    .getElementById("resetView")
    .addEventListener(
        "click",
        () => {

            desiredCamera.set(
                30,
                18,
                45
            );

            cameraTarget.set(
                30,
                0,
                0
            );

            updateInformation(
                "sun"
            );

        }
    );


/* =====================================================
   ANIMATION
===================================================== */

function animate() {

    requestAnimationFrame(
        animate
    );


    /* Planet rotation */

    Object.keys(
        planetMeshes
    ).forEach(key => {

        const mesh =
            planetMeshes[key];

        mesh.rotation.y +=
            key === "sun"
                ? 0.002
                : 0.004;

    });


    /* Slowly move planets */

    const time =
        Date.now() * 0.0001;


    Object.keys(
        planetGroups
    ).forEach(key => {

        if (key === "sun") return;


        const group =
            planetGroups[key];

        const baseX =
            planets[key].position.x;


        const radius =
            baseX;


        const angle =
            time /
            (baseX * 0.7);


        group.position.x =
            Math.cos(angle) *
            radius;

        group.position.z =
            Math.sin(angle) *
            radius;

    });


    /* Camera smoothing */

    camera.position.lerp(
        desiredCamera,
        0.025
    );


    controls.target.lerp(
        cameraTarget,
        0.04
    );


    controls.update();


    renderer.render(
        scene,
        camera
    );
}


animate();


/* =====================================================
   INITIAL CAMERA
===================================================== */

desiredCamera.set(
    30,
    18,
    45
);

cameraTarget.set(
    25,
    0,
    0
);


/* =====================================================
   WINDOW RESIZE
===================================================== */

window.addEventListener(
    "resize",
    () => {

        camera.aspect =
            window.innerWidth /
            window.innerHeight;

        camera.updateProjectionMatrix();


        renderer.setSize(
            window.innerWidth,
            window.innerHeight
        );

    }
);


/* =====================================================
   LOADING
===================================================== */

window.addEventListener(
    "load",
    () => {

        setTimeout(
            () => {

                document
                    .getElementById(
                        "loader"
                    )
                    .classList.add(
                        "hidden"
                    );

            },
            1200
        );

    }
);


/* =====================================================
   ESCAPE KEY
===================================================== */

document.addEventListener(
    "keydown",
    event => {

        if (
            event.key === "Escape"
        ) {

            modal.classList.remove(
                "show"
            );

            infoModal.classList.remove(
                "show"
            );

        }

    }
);