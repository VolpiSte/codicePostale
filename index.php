<?php
// Ottieni il metodo della richiesta
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Ottieni il tipo di contenuto della richiesta
$contentType = $_SERVER['CONTENT_TYPE'];

// Ottieni il tipo di contenuto accettato
$acceptType = $_SERVER['HTTP_ACCEPT'];

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
        $response = handleGetRequest();
        break;
    case 'POST':
        // Gestisci la richiesta POST
        $response = handlePostRequest($requestData);
        break;
    case 'PUT':
        // Gestisci la richiesta PUT
        $response = handlePutRequest($requestData);
        break;
    case 'DELETE':
        // Gestisci la richiesta DELETE
        $response = handleDeleteRequest($requestData);
        break;
    default:
        // Metodo di richiesta non valido
        http_response_code(405);
        echo 'Metodo di richiesta non valido';
        break;
}

// Formatta la risposta in base al tipo di contenuto accettato
if ($acceptType === 'application/json') {
    header('Content-Type: application/json');
    echo json_encode($response);
} elseif ($acceptType === 'application/xml') {
    header('Content-Type: application/xml');
    echo xml_encode($response); 
} else {
    // Tipo di contenuto non accettato
    http_response_code(406);
    echo 'Tipo di contenuto non accettato';
    exit;
}

    // Funzione per convertire un array in formato XML
    function xml_encode($data) {
        $xml = new SimpleXMLElement('<root/>');
        array_to_xml($data, $xml);
        return $xml->asXML();
    }

    // Funzione ricorsiva per convertire un array in formato XML
    function array_to_xml($data, &$xml) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml->addChild($key);
                array_to_xml($value, $subnode);
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    function handleGetRequest() {
        // Crea una connessione al database
        $conn = new mysqli('localhost', 'root', '', 'codicipostali');

        // Controlla la connessione
        if ($conn->connect_error) {
            die('Connessione fallita: ' . $conn->connect_error);
        }

        // Estrai il codice postale dal percorso dell'URL
        $urlPath = explode('/', $_SERVER['REQUEST_URI']);
        $codicePostale = $urlPath[3];

        // Query SQL per ottenere i dati
        $query = "SELECT * FROM CodiciPostali";
        if ($codicePostale !== '') {
            $codicePostale = $conn->real_escape_string($codicePostale); // Prevenire SQL Injection
            $query .= " WHERE Codice = '$codicePostale'";
        }

        // Esegui la query
        $result = $conn->query($query);

        $response = [];

        if ($result->num_rows > 0) {
            // Stampa i dati
            while ($row = $result->fetch_assoc()) {
                $response[] = $row;
            }
        } else {
            $response = ['message' => 'Nessun risultato'];
        }

        // Chiudi la connessione
        $conn->close();

        return $response;
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
            $responseData = http_response_code(201);
            ;
        } else {
            $responseData = ['status' => 'errore', 'message' => 'Errore: ' . $query . '<br>' . $conn->error];
        }

        // Chiudi la connessione
        $conn->close();

        return $responseData;
    } else {
        // Dati non validi
        http_response_code(400);
        return ['status' => 'errore', 'message' => 'Dati non validi'];
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
        return ['status' => 'errore', 'message' => 'Terzo parametro non presente nell\'URI'];
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
            $responseData = http_response_code(201);
        } else {
            $responseData = ['status' => 'errore', 'message' => 'Errore: ' . $query . '<br>' . $conn->error];
        }

        // Chiudi la connessione
        $conn->close();

        return $responseData;
    } else {
        // Dati non validi
        http_response_code(400);
        return ['status' => 'errore', 'message' => 'Dati non validi'];
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
            $responseData = http_response_code(201);
        } else {
            $responseData = ['status' => 'errore', 'message' => 'Errore: ' . $query . '<br>' . $conn->error];
        }

        // Chiudi la connessione
        $conn->close();

        return $responseData;
    } else {
        // Dati non validi
        http_response_code(400);
        return ['status' => 'errore', 'message' => 'Dati non validi'];
    }
}
?>