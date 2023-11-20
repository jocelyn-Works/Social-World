
// ----------  slider signup  & connection phone ----------//

// séléctionne la classe img_slider
let img_slider = document.getElementsByClassName('img_slider');

let etape = 0; // Initialise une variable a 0 

let nbr_img = img_slider.length; // Stocke le nombre total d'images


function enleverActiveImages() {  // enlever la class active 
    for (let i = 0 ; i < nbr_img ; i++){
    img_slider[i].classList.remove('active');
    }
}

setInterval(function() { // pour chager d'image toute les 3 sec
    etape++; // + 1 chaque etape
    if (etape >= nbr_img) {  // si etape depasse le nombre d'image on repasse a O
        etape = 0;
    }
    enleverActiveImages();
    img_slider[etape].classList.add('active'); // ajoute la class active a l'image actuelle 
},3000) // 3 sec



