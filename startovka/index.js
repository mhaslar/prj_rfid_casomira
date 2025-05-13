async function fetchStartList($category) {
    try {
        const response = await fetch('http://localhost:8000/getStartList?category=' + $category);
        const data = await response.json();
        const element = document.getElementById('startListContainer');

        const categoryTitle = document.getElementById('categoryTitle');
        categoryTitle.textContent = "Startovka " + data.category;

        const table = document.createElement('table');
        table.className = 'table table-bordered table-striped table-hover text-center mx-auto';
        const headerRow = document.createElement('tr');
        headerRow.innerHTML = `
            <th>ID</th>
            <th>Číslo</th>
            <th>Jméno</th>
            <th>Příjmení</th>
            <th>TAG</th>
        `;
        table.appendChild(headerRow);
        data.startList.forEach(item => {
            const row = document.createElement('tr');

            const idCell = document.createElement('td');
            idCell.textContent = item.id;
            row.appendChild(idCell);

            const numberCell = document.createElement('td');
            numberCell.textContent = item.bib;
            row.appendChild(numberCell);

            const nameCell = document.createElement('td');
            nameCell.textContent = item.firstName;
            row.appendChild(nameCell);

            const surnameCell = document.createElement('td');
            surnameCell.textContent = item.lastName;
            row.appendChild(surnameCell);

            const tagCell = document.createElement('td');
            tagCell.textContent = item.tag;
            row.appendChild(tagCell);

            table.appendChild(row);
        });

        element.appendChild(table);
        
    }
    catch (error) {
        console.error('Error fetching start list:', error);
    }
}