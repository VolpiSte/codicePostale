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
    
        // Estrai il parametro dal percorso dell'URL
        $urlPath = explode('/', $_SERVER['REQUEST_URI']);
        $parametro = $urlPath[2] ?? null;
        $valore = $urlPath[3] ?? null;
    
        // Controllo sul secondo parametro e preparazione della query SQL
        if ($parametro === 'CAP' && !empty($valore)) {
            $valore = $conn->real_escape_string($valore); // Prevenire SQL Injection
            $query = "SELECT Comune FROM CodiciPostali WHERE CodicePostale = '$valore'";
        } elseif ($parametro === 'Comune' && !empty($valore)) {
            $valore = $conn->real_escape_string($valore); // Prevenire SQL Injection
            $query = "SELECT CodicePostale FROM CodiciPostali WHERE Comune = '$valore'";
        } elseif (empty($parametro)) {
            $query = "SELECT * FROM CodiciPostali";
        } else {
            // URL non valido
            http_response_code(400);
            return ['status' => 'errore', 'message' => 'Errore: URL non valido'];
        }
    
        // Esegui la query
        $result = $conn->query($query);
    
        if ($result) {
            if ($result->num_rows > 0) {
                // Stampa i dati
                while ($row = $result->fetch_assoc()) {
                    $response[] = $row;
                }
            } else {
                // Nessun risultato trovato
                
                return ['status' => 'errore', 'message' => 'Nessun risultato trovato'];
                http_response_code(404);
            }
        } else {
            // Errore nella query
            http_response_code(500);
            return ['status' => 'errore', 'message' => 'Errore nella query'];
        }
    
        // Chiudi la connessione
        $conn->close();
    
        return $response;
    }
    
    
    function handlePostRequest($requestData) {
        // Verifica il percorso dell'URL
        $urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($urlPath !== '/wsphp/ADD') {
            http_response_code(400);
            return ['status' => 'errore', 'message' => 'URL non valido'];
        }
    
        // Convalida i dati
        if (isset($requestData['CodicePostale']) && isset($requestData['Comune'])) {
            // Crea una connessione al database
            $conn = new mysqli('localhost', 'root', '', 'codicipostali');
    
            // Controlla la connessione
            if ($conn->connect_error) {
                die('Connessione fallita: ' . $conn->connect_error);
            }
    
            // Verifica se il codice postale esiste già nel database
            $codicePostaleEsiste = false;
            $query_check = "SELECT CodicePostale FROM CodiciPostali WHERE CodicePostale = ?";
            $stmt_check = $conn->prepare($query_check);
            $stmt_check->bind_param("s", $requestData['CodicePostale']);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $codicePostaleEsiste = true;
            }
            $stmt_check->close();
    
            if ($codicePostaleEsiste) {
                return ['status' => 'errore', 'message' => 'Il codice postale specificato esiste già nel database'];
                http_response_code(409);
            }
    
            // Prepara l'istruzione SQL con i segnaposto (?)
            $query = "INSERT INTO CodiciPostali (CodicePostale, Comune) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
    
            // Associa i parametri e imposta i tipi dei dati
            $stmt->bind_param("ss", $requestData['CodicePostale'], $requestData['Comune']);
    
            // Esegui l'istruzione preparata
            if ($stmt->execute()) {
                http_response_code(200);
                $responseData = ['status' => 'successo', 'message' => 'Dato aggiunto con successo'];
            } else {
                $responseData = ['status' => 'errore', 'message' => 'Errore nell\'esecuzione dell\'istruzione preparata'];
            }
    
            // Chiudi lo statement e la connessione
            $stmt->close();
            $conn->close();
    
            return $responseData;
        } else {
            // Dati non validi
            http_response_code(400);
            return ['status' => 'errore', 'message' => 'Dati non validi'];
        }
    }
    
    
    function handlePutRequest($requestData) {
        // Ottieni l'URI e dividilo in parti
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uriParts = explode('/', $uri);
    
        // Verifica che il terzo parametro sia "EDIT" e che il quarto parametro sia presente
        if ($uriParts[2] !== 'EDIT' || !isset($uriParts[3])) {
            http_response_code(400);
            return ['status' => 'errore', 'message' => 'URL non valido'];
        }
    
        // Usa il quarto parametro come "codicePostale"
        $codicePostale = $uriParts[3];
    
        // Convalida i dati
        if (isset($requestData['Comune'])) {
            // Crea una connessione al database
            $conn = new mysqli('localhost', 'root', '', 'codicipostali');
    
            // Controlla la connessione
            if ($conn->connect_error) {
                die('Connessione fallita: ' . $conn->connect_error);
            }
    
            // Verifica se il codice postale esiste nel database
            $query_check = "SELECT * FROM CodiciPostali WHERE CodicePostale = ?";
            $stmt_check = $conn->prepare($query_check);
            $stmt_check->bind_param("s", $codicePostale);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
    
            if ($result_check->num_rows === 0) {
                // Codice postale non trovato, restituisci un errore
                
                return ['status' => 'errore', 'message' => 'Il codice postale specificato non esiste'];
                http_response_code(404);
            }
    
            // Prepara l'istruzione SQL per l'aggiornamento
            $query = "UPDATE CodiciPostali SET Comune = ? WHERE CodicePostale = ?";
            $stmt = $conn->prepare($query);
    
            // Associa i parametri e imposta i tipi dei dati
            $stmt->bind_param("ss", $comune, $codicePostale);
    
            // Imposta il valore del parametro
            $comune = $requestData['Comune'];
    
            // Esegui l'istruzione preparata per l'aggiornamento
            if ($stmt->execute()) {
                // Imposta i dati di risposta
                http_response_code(200);
                $responseData = ['status' => 'successo', 'message' => 'Dato aggiornato con successo'];
    
            } else {
                $responseData = ['status' => 'errore', 'message' => 'Errore nell\'esecuzione dell\'istruzione preparata'];
            }
    
            // Chiudi lo statement e la connessione
            $stmt_check->close();
            $stmt->close();
            $conn->close();
    
            return $responseData;
        } else {
            // Dati non validi
            http_response_code(400);
            return ['status' => 'errore', 'message' => 'Dati non validi'];
        }
    }
    
    
    function handleDeleteRequest() {
        // Ottieni l'URI e dividilo in parti
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uriParts = explode('/', $uri);
    
        // Verifica che il terzo parametro sia "DEL" e che il quarto parametro sia presente
        if ($uriParts[2] !== 'DEL' || !isset($uriParts[3])) {
            http_response_code(400);
            return ['status' => 'errore', 'message' => 'URL non valido'];
        }
    
        // Usa il quarto parametro come "codicePostale"
        $codicePostale = $uriParts[3];
    
        // Crea una connessione al database
        $conn = new mysqli('localhost', 'root', '', 'codicipostali');
    
        // Controlla la connessione
        if ($conn->connect_error) {
            die('Connessione fallita: ' . $conn->connect_error);
        }
    
        // Verifica se il codice postale esiste nel database
        $query_check = "SELECT * FROM CodiciPostali WHERE CodicePostale = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("s", $codicePostale);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
    
        if ($result_check->num_rows === 0) {
            // Codice postale non trovato, restituisci un errore
            
            return ['status' => 'errore', 'message' => 'Il codice postale specificato non esiste'];
            http_response_code(404);
        }
    
        // Prepara l'istruzione SQL per l'eliminazione
        $query = "DELETE FROM CodiciPostali WHERE CodicePostale = ?";
        $stmt = $conn->prepare($query);
    
        // Associa il parametro e imposta il tipo di dato
        $stmt->bind_param("s", $codicePostale);
    
        // Esegui l'istruzione preparata per l'eliminazione
        if ($stmt->execute()) {
            // Imposta i dati di risposta
            http_response_code(200);
            $responseData = ['status' => 'successo', 'message' => 'Dato eliminato con successo'];
        } else {
            $responseData = ['status' => 'errore', 'message' => 'Errore nell\'esecuzione dell\'istruzione preparata'];
        }
    
        // Chiudi lo statement e la connessione
        $stmt_check->close();
        $stmt->close();
        $conn->close();
    
        return $responseData;
    }
    