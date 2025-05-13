async function fetchResults(category) {
    const apiUrl = 'http://localhost:8000/getResults?category=' + category;

    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        const formattedData = data.map(item => {
            return {
                bib: item.bib,
                first_name: item.first_name,
                last_name: item.last_name,
                date_of_birth: item.date_of_birth,
                gender: item.gender,
                expected_laps: item.expected_laps,
                lap_times: item.lap_times,
                lap_durations: item.lap_durations,
                total_time_formatted: item.total_time_formatted,
                order: item.order
            };
        });

        const element = document.getElementById('resultsContainer');
        element.innerHTML = '';
        const table = document.createElement('table');
        table.className = 'table table-bordered table-striped table-hover text-center mx-auto';

        const headerRow = document.createElement('tr');
        headerRow.innerHTML = `
            <th>Pořadí</th>
            <th>Bib</th>
            <th>Jméno a příjmení</th>
            <th>Datum narození</th>
            <th>Pohlaví</th>
            <th>Mezičasy</th>
            <th>Průjezdy</th>
            <th>Celkový čas</th>
        `;
        table.appendChild(headerRow);

        formattedData.forEach(item => {
            const row = document.createElement('tr');

            const orderCell = document.createElement('td');
            orderCell.textContent = item.order;
            row.appendChild(orderCell);

            const bibCell = document.createElement('td');
            bibCell.textContent = item.bib !== null ? item.bib : '';
            row.appendChild(bibCell);

            if (item.lap_times && item.expected_laps && item.lap_times.length === item.expected_laps) {
                row.style.backgroundColor = 'lightgreen';
            } else if (item.first_name.trim().startsWith('!!!')) {
                row.style.backgroundColor = 'yellow';
                item.first_name = item.first_name.replace('!!!', '');
            }

            const nameCell = document.createElement('td');
            nameCell.textContent = item.first_name + ' ' + item.last_name;
            row.appendChild(nameCell);

            const dobCell = document.createElement('td');
            if (item.date_of_birth) {
                const dob = new Date(item.date_of_birth);
                dobCell.textContent = dob.toLocaleDateString('cs-CZ');
            } else {
                dobCell.textContent = 'N/A';
            }
            row.appendChild(dobCell);

            const genderCell = document.createElement('td');
            genderCell.textContent = item.gender;
            row.appendChild(genderCell);

            const lapTimesCell = document.createElement('td');
            if (item.lap_times && item.lap_times.length > 0) {
                lapTimesCell.innerHTML = item.lap_times.map(lap => `${lap.time} (${lap.id})`).join('<br>');
            } else {
                lapTimesCell.textContent = '-';
            }
            row.appendChild(lapTimesCell);

            const lapDurationsCell = document.createElement('td');
            if (item.lap_durations && item.lap_durations.length > 0) {
                const formattedLapDurations = item.lap_durations.map(duration => {
                    if (duration >= 86400) {
                        const days = Math.floor(duration / 86400);
                        const remainder = duration % 86400;
                        const timeStr = new Date(remainder * 1000).toISOString().substr(11, 8);
                        return days + 'd ' + timeStr;
                    } else {
                        return new Date(duration * 1000).toISOString().substr(11, 8);
                    }
                });
                lapDurationsCell.innerHTML = formattedLapDurations.join('<br>');
            } else {
                lapDurationsCell.textContent = '-';
            }
            row.appendChild(lapDurationsCell);

            const totalTimeCell = document.createElement('td');
            totalTimeCell.textContent = item.total_time_formatted;
            row.appendChild(totalTimeCell);

            table.appendChild(row);
        });

        const wrapperDiv = document.createElement('div');
        wrapperDiv.className = 'd-flex justify-content-center';
        wrapperDiv.appendChild(table);
        element.appendChild(wrapperDiv);
        return data;
    } catch (error) {
        console.error('Error fetching results:', error);
    }
}