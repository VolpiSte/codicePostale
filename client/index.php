<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client REST</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #94d2bd;
            overflow: hidden;
        }

        #container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #output {
            margin-top: 20px;
            padding: 20px;
            background-color: #005f73;
            color: #fff;
            border-radius: 10px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #0a9396;
        }

        th {
            background-color: #001219;
        }

        button {
            margin: 5px;
            padding: 10px 20px;
            background-color: #ee9b00;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #ffcc00;
        }

        @keyframes move {
            0% { transform: translateY(-50px) rotate(0deg); }
            50% { transform: translateY(50px) rotate(180deg); }
            100% { transform: translateY(-50px) rotate(360deg); }
        }

        #background {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: transparent;
            animation: move 10s infinite alternate;
        }
    </style>
</head>
<body>
    <div id="background"></div>
    <div id="container">
        <div>
            <h1>Client REST</h1>
            <button onclick="getAll()">Get All</button>
            <button onclick="getByPostalCode()">Get by Postal Code</button>
            <button onclick="getByCity()">Get by City</button>
            <button onclick="addPostalCode()">Add Postal Code</button>
            <button onclick="updatePostalCode()">Update Postal Code</button>
            <button onclick="deletePostalCode()">Delete Postal Code</button>
            <button onclick="clearOutput()">Clear</button>
            <div id="output"></div>
        </div>
    </div>
    <script>
        function sendRequest(method, url, data = null, callback) {
            var xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        var responseData = JSON.parse(xhr.responseText);
                        callback(responseData);
                    } else {
                        console.error('Request failed with status', xhr.status);
                    }
                }
            };
            xhr.send(JSON.stringify(data));
        }

        function handleResponse(responseData) {
            var outputDiv = document.getElementById('output');
            outputDiv.innerHTML = ''; // Pulisci il contenuto precedente

            if (responseData.message) {
                // Se la risposta contiene solo un messaggio
                outputDiv.textContent = responseData.message;
            } else {
                // Se la risposta contiene dati da visualizzare
                var table = document.createElement('table');
                var headerRow = table.insertRow();
                for (var key in responseData[0]) {
                    if (responseData[0].hasOwnProperty(key)) {
                        var headerCell = headerRow.insertCell();
                        headerCell.textContent = key;
                    }
                }

                // Aggiungi righe di dati alla tabella
                responseData.forEach(function(rowData) {
                    var row = table.insertRow();
                    for (var key in rowData) {
                        if (rowData.hasOwnProperty(key)) {
                            var cell = row.insertCell();
                            cell.textContent = rowData[key];
                        }
                    }
                });

                // Aggiungi la tabella al div di output
                outputDiv.appendChild(table);
            }
        }

        function clearOutput() {
            document.getElementById('output').innerHTML = '';
        }

        function getAll() {
            sendRequest('GET', '/wsphp/', null, handleResponse);
        }

        function getByPostalCode() {
            var postalCode = prompt('Enter Postal Code:');
            if (postalCode !== null && postalCode.trim() !== '') {
                sendRequest('GET', '/wsphp/CAP/' + postalCode.trim(), null, handleResponse);
            }
        }

        function getByCity() {
            var city = prompt('Enter City:');
            if (city !== null && city.trim() !== '') {
                sendRequest('GET', '/wsphp/Comune/' + city.trim(), null, handleResponse);
            }
        }

        function addPostalCode() {
            var postalCode = prompt('Enter Postal Code:');
            var city = prompt('Enter City:');
            if (postalCode !== null && postalCode.trim() !== '' && city !== null && city.trim() !== "") {
                var data = { "CodicePostale": postalCode.trim(), "Comune": city.trim() };
                sendRequest('POST', '/wsphp/ADD', data, handleResponse);
            }
        }

        function updatePostalCode() {
            var postalCode = prompt('Enter Postal Code to Update:');
            var city = prompt('Enter New City:');
            if (postalCode !== null && postalCode.trim() !== '' && city !== null && city.trim() !== "") {
                var data = { "Comune": city.trim() };
                sendRequest('PUT', '/wsphp/EDIT/' + postalCode.trim(), data, handleResponse);
            }
        }

        function deletePostalCode() {
            var postalCode = prompt('Enter Postal Code to Delete:');
            if (postalCode !== null && postalCode.trim() !== "") {
                sendRequest('DELETE', '/wsphp/DEL/' + postalCode.trim(), null, handleResponse);
            }
        }
    </script>
</body>
</html>
