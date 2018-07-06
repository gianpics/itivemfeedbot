<?php
    #################################
	########## saturno.php ##########
    #################################
	// scarica ed inserisce nel database le notizie

    require 'conf.php';

    # LOG esecuzione script
    writeLog("ser", "Eseguito saturno.php");

    $url = 'https://www.itismarzotto.it/wp/feed/';
	// ottenimento feed
	$feeds = simplexml_load_file($url);

    // accesso al database
    $mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    if (!$mysqli)
    {
        # LOG errore
        writeLog("err", "saturno.php - Errore connessione db: ".$mysqli->connect_err);
        die();
    }

    $mysqli->set_charset('utf8');

    // ottenimento notizia più recente presente nel database
    $results = $mysqli->query(' SELECT * FROM articoli
                                ORDER BY data DESC 
				LIMIT 1');
    if(!$results)
    {
        # LOG errore
        writeLog("err", "saturno.php - Errore query ottenimento notizia più recente: $mysqli->errno - $mysqli->error");
        die();
    }

    $record = $results->fetch_assoc();

    // minDate è la data della notizia più recente presente nel database
    // se nessuna notizia è presente viene assegnato il valore minimo di date (UNIX time)
    if($results->num_rows > 0) $minDate = $record['data'];
    else $minDate = date('Y-m-d H:i:s', 0);

    // ottieni array di articoli
    $items = $feeds->channel->item;

    // gli articoli vengono processati in ordine cronologico discendente, dalla più recente alla meno recente
    // $dataIns memorizzerà la data della notizia meno recente da inviare
    $dataIns;


    // inserisce nel database le notizie più recenti dell'ultima già presente
    for($i=0; $i<count($items); $i++)
    {
        $data = date('Y-m-d H:i:s', strtotime(strip_tags($items[$i]->pubDate)));
        // se l'articolo da inserire è più vecchio dell'ultimo articolo già presente nel db allora termina il ciclo
        if ($data<=$minDate) break;
        $titolo = strip_tags($items[$i]->title);
        $descrizione = strip_tags($items[$i]->description, '<a>');
        $link = $items[$i]->link;

        $dataIns = $data;

        // inserisci dati articolo nel database
        $result = $mysqli->query("  INSERT INTO articoli
                                    VALUES(NULL,'$titolo','$descrizione','$link','$data')");
        if(!$result)
        {
            # LOG errore
            writeLog("err", "saturno.php - Errore query inserimento articoli in database: $mysqli->errno - $mysqli->error");
            die();
        }
    }

    // rimozione articoli in eccesso se ne sono stati inseriti di nuovi
    if($dataIns!=NULL)
    {
        # LOG inserimento nuovi articoli
        writeLog("ser", "+ Inseriti nuovi articoli");

        $result = $mysqli->query("  SELECT data
                                    FROM articoli
                                    ORDER BY data DESC 
				    LIMIT 10,1");
        if(!$result)
        {
            # LOG errore
            writeLog("err", "saturno.php - Errore query controllo numero di articoli presenti per eliminazione: $mysqli->errno - $mysqli->error");
            die();
        }

        if($result->num_rows > 0)
        {
        	$record = $result->fetch_assoc();
            $data = $record["data"];

            $result = $mysqli->query("  DELETE FROM articoli
                                        WHERE data<='$data'");
        	if(!$result)
            {
                # LOG errore
                writeLog("err", "saturno.php - Errore query eliminazione articoli in eccesso: $mysqli->errno - $mysqli->error");
                die();
            }
    	}
    }


    #### mercurio.php ####
    // esecuzione di mercurio.php per inviare agli utenti le nuove notizie se ne sono state inserite
    if($dataIns!=NULL)
    {
        try{
            require 'mercurio.php';
        }catch(Exception $e){
            # LOG errore
            writeLog("err", "saturno.php - Impossibile includere mercurio.php: ".$e->getMessage());
        }
    }

    // chiusura connessione al database
    $mysqli->close();

?>
