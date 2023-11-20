
// -----  icone active  ----- //

$(document).ready(function() {
    $('.menu a').click(function() {
        // Supprime la classe active de tous les éléments
        $('.menu a').removeClass('active');
        // Ajoute la classe active à l'élément cliqué
        $(this).addClass('active');
    });
});

// icone modifier // 
$(document).ready(function() {
    $('#settingsIcon').click(function(e) {
        e.preventDefault();
        e.stopPropagation(); // Empêche la propagation du clic à l'extérieur de l'icône
        $('.settings-menu').toggle();
    });

    // Ajoute un gestionnaire de clics à l'ensemble du document
    $(document).click(function(event) {
        var settingsMenu = $(".settings-menu");
        var settingsIcon = $("#settingsIcon");

        // Vérifie si l'élément cliqué n'est pas l'icône ou le menu contextuel
        if (!settingsMenu.is(event.target) && !settingsIcon.is(event.target) && settingsMenu.has(event.target).length === 0) {
            settingsMenu.hide(); // Cache le menu contextuel
        }
    });
});



// search bar //
        const searchInput = document.querySelector('.scroll-add input[type="search"]');
        const friends = document.querySelectorAll('.add-freinds .row-user');

        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();

            friends.forEach(friend => {
                const name = friend.querySelector('.row').textContent.toLowerCase();
                const isMatch = name.includes(searchTerm);
                friend.style.display = isMatch ? 'flex' : 'none';
            });
        });



 








       