<?php
	#################################
	########## mercurio.php #########
	#################################
	// invia le ultime notizie agli utenti iscritti alla newsletter presenti nel database


	# LOG esecuzione script
	writeLog("ser", "Eseguito mercurio.php");

	// ottenimento data
	if(!isset($dataIns))
	{
		# LOG errore
		writeLog("err", 'mercurio.php - Parametro $dataIns non valorizzato');
		die();
	}


    if (!$mysqli)
	{
        # LOG errore
        writeLog("err", "mercurio.php - Connessione a db assente: ".$mysqli->connect_err);
        die();
    }

    $mysqli->set_charset('utf8');

    // ottenimento lista di utenti da contattare
    $utenti = $mysqli->query('	SELECT user_id
								FROM utenti');
    if(!$utenti)
	{
        # LOG errore
        writeLog("err", "mercurio.php - Errore query ottenimento lista utenti: $mysqli->errno - $mysqli->error");
        die();
    }

	// se non ci sono utenti da contattare termina lo script
	if($utenti->num_rows<1) die("ok");

	// ottenimento notizie da inviare
	$results = $mysqli->query("	SELECT * FROM articoli
								WHERE data >= '$dataIns'
								ORDER BY data DESC");
    if(!$results)
	{
        # LOG errore
        writeLog("err", "mercurio.php - Errore query ottenimento articoli da inviare: $mysqli->errno - $mysqli->error");
        die();
    }

	// riempimento array $articoli[] con le notizie
	while($articolo = $results->fetch_assoc()) $articoli[] = $articolo;

    // imposta locale in italiano per stampare data, alternativa date('d-m-Y H:i:s', strtotime($articolo['data']))
	setlocale(LC_ALL, 'it_IT.UTF-8');


	// cicla tutti gli utenti
	while($utente = $utenti->fetch_assoc())
	{
        // cicla tutte le notizie
    	foreach($articoli as $articolo)
    	{
    		$message = "\xF0\x9F\x94\xB9 <b>".$articolo['titolo']."</b>\n<i>".strftime('%A %e %B %G, %H:%M', strtotime($articolo['data']))."</i>\n\n".$articolo['descrizione']."\n\n".'<a href="'.$articolo['link'].'">'."\xF0\x9F\x94\x97 Link articolo</a>\n";

            // invio notizie
    		sendMessage($utente['user_id'], $message, "parse_mode=HTML");
        }
	}


	// invia messaggio - $args sono argomenti ulteriori e opzionali, possono essere passati come array
    function sendMessage($chatId, $message, $args=null)
    {
  		$url = $GLOBALS['website']."/sendMessage?chat_id=".$chatId."&text=".urlencode($message);

        // aggiunta eventuali argomenti
		if($args!=null)
		{
			if (is_array($args)) foreach($args as $arg) $url.="&".$arg;
			else $url.="&".$args;
		}

        // esecuzione
		try{
			file_get_contents($url);
			// anti spam, attesa 100'000 microsecondi
			usleep(100000);
        }catch (Exception $e){
			# LOG errore
            writeLog("err", "mercurio.php - Eccezione di file_get_contents in sendMessage(): ".$e->getMessage());
        }
    }

?>
