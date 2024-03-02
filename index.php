<?php
// Ottieni il metodo della richiesta
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Ottieni il tipo di contenuto della richiesta
$contentType = $_SERVER['CONTENT_TYPE'];

// Leggi il corpo della richiesta
$requestData = file_get_contents('php://input');

// Determina il formato dei dati (JSON o XML)
if ($contentType === 'application/json') {
    $requestData = json_decode($requestData, true);
} elseif ($contentType === 'application/xml') {
    $requestData = simplexml_load_string($requestData);
} else {
    // Tipo di contenuto non valido
    http_response_code(415);
    echo 'Tipo di contenuto non valido';
    exit;
}

// Elabora la richiesta in base al metodo della richiesta
switch ($requestMethod) {
    case 'GET':
        // Gestisci la richiesta GET
        handleGetRequest();
        break;
    case 'POST':
        // Gestisci la richiesta POST
        handlePostRequest($requestData);
        break;
    case 'PUT':
        // Gestisci la richiesta PUT
        handlePutRequest($requestData);
        break;
    case 'DELETE':
        // Gestisci la richiesta DELETE
        handleDeleteRequest($requestData);
        break;
    default:
        // Metodo di richiesta non valido
        http_response_code(405);
        echo 'Metodo di richiesta non valido';
        break;
}

// Funzione per gestire la richiesta GET
function handleGetRequest() {
    // Crea una connessione al database
    $conn = new mysqli('localhost', 'root', '', 'codicipostali');

    // Controlla la connessione
    if ($conn->connect_error) {
        die('Connessione fallita: ' . $conn->connect_error);
    }

    // Query SQL per ottenere i dati
    $query = "SELECT * FROM CodiciPostali";

    // Esegui la query
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        // Stampa i dati
        while ($row = $result->fetch_assoc()) {
            echo 'CodicePostale: ' . $row['CodicePostale'] . ', Comune: ' . $row['Comune'] . '<br>';
        }
    } else {
        echo 'Nessun risultato';
    }

    // Chiudi la connessione
    $conn->close();
}

// Funzione per gestire la richiesta POST
function handlePostRequest($requestData) {
    // Convalida i dati
    if (isset($requestData['CodicePostale']) && isset($requestData['Comune'])) {
        // Inserisci i dati nel database
        $codicePostale = $requestData['CodicePostale'];
        $comune = $requestData['Comune'];

        // Crea una connessione al database
        $conn = new mysqli('localhost', 'root', '', 'codicipostali');

        // Controlla la connessione
        if ($conn->connect_error) {
            die('Connessione fallita: ' . $conn->connect_error);
        }

        // Query SQL per inserire i dati
        $query = "INSERT INTO CodiciPostali (CodicePostale, Comune) VALUES ('$codicePostale', '$comune')";

        // Esegui la query
        if ($conn->query($query) === TRUE) {
            // Imposta i dati di risposta
            $responseData = ['status' => 'successo'];
            echo json_encode($responseData);
        } else {
            echo 'Errore: ' . $query . '<br>' . $conn->error;
            exit;
        }

        // Chiudi la connessione
        $conn->close();
    } else {
        // Dati non validi
        http_response_code(400);
        echo 'Dati non validi';
        exit;
    }
}

// Funzione per gestire la richiesta PUT
function handlePutRequest($requestData) {
    // Ottieni l'URI e dividilo in parti
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uriParts = explode( '/', $uri );

    // Verifica che il terzo parametro sia presente
    if (!isset($uriParts[2])) {
        http_response_code(400);
        echo 'Terzo parametro non presente nell\'URI ERRORE';
        exit;
    }

    // Usa il terzo parametro come "codicePostale"
    $codicePostale = $uriParts[2];

    // Convalida i dati
    if (isset($requestData['Comune'])) {
        // Aggiorna i dati nel database
        $comune = $requestData['Comune'];

        // Crea una connessione al database
        $conn = new mysqli('localhost', 'root', '', 'codicipostali');

        // Controlla la connessione
        if ($conn->connect_error) {
            die('Connessione fallita: ' . $conn->connect_error);
        }

        // Query SQL per aggiornare i dati
        $query = "UPDATE CodiciPostali SET Comune = '$comune' WHERE CodicePostale = '$codicePostale'";

        // Esegui la query
        if ($conn->query($query) === TRUE) {
            // Imposta i dati di risposta
            $responseData = ['status' => 'successo'];
            echo json_encode($responseData);
        } else {
            echo 'Errore: ' . $query . '<br>' . $conn->error;
            exit;
        }

        // Chiudi la connessione
        $conn->close();
    } else {
        // Dati non validi
        http_response_code(400);
        echo 'Dati non validi';
        exit;
    }
}

// Funzione per gestire la richiesta DELETE
function handleDeleteRequest($requestData) {
    // Convalida i dati
    if (isset($requestData['CodicePostale'])) {
        // Elimina i dati dal database
        $codicePostale = $requestData['CodicePostale'];

        // Crea una connessione al database
        $conn = new mysqli('localhost', 'root', '', 'codicipostali');

        // Controlla la connessione
        if ($conn->connect_error) {
            die('Connessione fallita: ' . $conn->connect_error);
        }

        // Query SQL per eliminare i dati
        $query = "DELETE FROM CodiciPostali WHERE CodicePostale = '$codicePostale'";

        // Esegui la query
        if ($conn->query($query) === TRUE) {
            // Imposta i dati di risposta
            $responseData = ['status' => 'successo'];
            echo json_encode($responseData);
        } else {
            echo 'Errore: ' . $query . '<br>' . $conn->error;
            exit;
        }

        // Chiudi la connessione
        $conn->close();
    } else {
        // Dati non validi
        http_response_code(400);
        echo 'Dati non validi';
        exit;
    }
}
?>