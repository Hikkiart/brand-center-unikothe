// assets/js/user/bcek-user-filter.js
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    const filterSelect = document.getElementById('bcek-category-filter');
    const templateGroups = document.querySelectorAll('.bcek-template-group');

    // Se não houver filtro ou templates na página, o script não faz nada.
    if (!filterSelect || templateGroups.length === 0) {
        return;
    }

    // Adiciona um listener para o evento 'change' (quando uma nova opção é selecionada)
    filterSelect.addEventListener('change', function() {
        const filterValue = filterSelect.value;

        templateGroups.forEach(group => {
            // Verifica se a opção é "Todas" (*) ou se o grupo corresponde ao filtro
            if (filterValue === '*' || group.matches(filterValue)) {
                group.style.display = 'block'; // Mostra o grupo
            } else {
                group.style.display = 'none';  // Esconde o grupo
            }
        });
    });
});