const stavyKategorie = [
    { beforeStart: "Před startem", active: "Aktivní", inactive: "Neaktivní", finished: "Dokončeno" },
]

async function fetchStarts() {
    const apiUrl = 'http://localhost:8000/getStarts';

    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        var formattedData = data.map(item => {
            return {
                id: item.id,
                name: item.name,
                startTime: item.startTime,
                status: item.status
            };
        }
        );

        const element = document.getElementById('startsContainer');
        element.innerHTML = '';
        const table = document.createElement('table');
        table.className = 'table table-bordered table-striped table-hover text-center mx-auto';

        const headerRow = document.createElement('tr');
        headerRow.innerHTML = `
            <th>Start</th>
            <th>Čas startu</th>
            <th>Stav</th>
            <th>Akce</th>
        `;
        table.appendChild(headerRow);

        formattedData.forEach(item => {
            const row = document.createElement('tr');

            const nameCell = document.createElement('td');
            nameCell.textContent = item.name;
            row.appendChild(nameCell);

            if (item.startTime) {
                const startTimeCell = document.createElement('td');
                const startTime = new Date(item.startTime);
                startTimeCell.textContent = startTime.toLocaleString('cs-CZ', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                row.appendChild(startTimeCell);
            } else {
                const startTimeCell = document.createElement('td');
                startTimeCell.textContent = 'N/A';
                row.appendChild(startTimeCell);
            }

            const statusCell = document.createElement('td');
            if (item.status === 'active') {
                statusCell.textContent = stavyKategorie[0].active;
            }
            else if (item.status === 'inactive') {
                statusCell.textContent = stavyKategorie[0].inactive;
            } else if (item.status === 'finished') {
                statusCell.textContent = stavyKategorie[0].finished;
            } else if (item.status === 'beforeStart') {
                statusCell.textContent = stavyKategorie[0].beforeStart;
            } else {
                statusCell.textContent = 'Neznámý stav';
            }
            row.appendChild(statusCell);

            const actionsCell = document.createElement('td');

            const startButton = document.createElement('button');
            if (item.status === 'active') {
                startButton.textContent = 'Zastavit';
                startButton.onclick = () => {
                    updateStart(null, 'inactive', item.id);
                }
            } else if (item.status === 'inactive') {
                startButton.textContent = 'Spustit';
                startButton.onclick = () => {
                    updateStart(new Date(), 'active', item.id);
                }
            } else if (item.status === 'finished') {
                startButton.textContent = 'Dokončeno';
                startButton.disabled = true;

            } else if (item.status === 'beforeStart') {
                startButton.textContent = 'Spustit';
                startButton.onclick = () => {
                    updateStart(new Date(), 'start', item.id);
                }
                
            } else {
                startButton.textContent = ' Neznámý stav';
                startButton.disabled = true;
            }
            actionsCell.appendChild(startButton);

            const viewListButton = document.createElement('button');
            viewListButton.textContent = 'Startovka';
            viewListButton.onclick = () => {
                document.location.href = `http://localhost/startovka?category=${item.id}`;
            };
            actionsCell.appendChild(viewListButton);

            const viewResultsButton = document.createElement('button');
            viewResultsButton.textContent = 'Výsledky';
            viewResultsButton.onclick = () => {
                document.location.href = `http://localhost/vysledky?category=${item.id}`;
            };
            actionsCell.appendChild(viewResultsButton);

            row.appendChild(actionsCell);
            table.appendChild(row);
        });

        const wrapperDiv = document.createElement('div');
        wrapperDiv.className = 'd-flex justify-content-center';
        wrapperDiv.appendChild(table);
        element.appendChild(wrapperDiv);
        return data;
    } catch (error) {
        console.error('Error fetching starts:', error);
    }
}

async function updateStart($startDate, $status, $startId) {
    const apiUrl = 'http://localhost:8000/updateStart';
    const data = {
        startDate: $startDate,
        status: $status,
        startId: $startId
    };

    try {
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        fetchStarts();
    } catch (error) {
        console.error('Error updating start:', error);
    }
}