<?php
    #################################
    ############ main.php ###########
    #################################
    // gestisce l'interazione con l'utente

    require "conf.php";


	// ottenimento updates
	$update = file_get_contents("php://input");
    $update = json_decode($update, TRUE);

    $comandi = "/iscriviti - \xF0\x9F\x93\xA9 Iscriviti alla newsletter\n/disiscriviti - \xE2\x9D\x8C Disiscriviti dalla newsletter\n/ultime - \xF0\x9F\x95\x93 Visualizza le notizie più recenti\n/info - \xE2\x84\xB9 Informazioni utili";

    // controlla se l'Update è un messaggio
    if($update['message'] != null)
    {

        // ottenimento chatId mittente e messaggio
        $chatId = $update['message']['chat']['id'];
        $name = $update['message']['chat']['first_name'];
        $user = $update['message']['chat']['username'];
        $userId = $update['message']['from']['id'];
        $messageId = $update['message']['message_id'];
        $text = strtolower($update['message']['text']);

        # LOG messaggio ricevuto
        writeLog("in", $text, $user."-uid".$userId);

        // elabora risposta
        switch($text){
        	case "/start":
            	// avvio
                $message = "Benvenuto, ".$name."! \xF0\x9F\x98\x8A\nResta aggiornato sulle notizie dell'istituto!\n\nComandi utili:\n".$comandi;
            	sendMessage($chatId, $message);
            	break;
            case "/iscriviti":
                // iscrizione alla newsletter
                $message = "Vuoi iscriverti alla newsletter e rimanere aggiornato sulle novità dell' istituto?";
                // creazione inline keyboard
                $btnSi = array('text'=>'Iscrivimi', 'callback_data'=>'iscrivi');
                $btnNo = array('text'=>'Annulla', 'callback_data'=>'annulla');
                $rigaBtn = array($btnSi, $btnNo);
                $keyboard = array('inline_keyboard'=>array($rigaBtn));

                sendMessage($chatId, $message, "reply_markup=".json_encode($keyboard));
                break;
            case "/disiscriviti":
                // disiscrizione dalla newsletter
                $message = "Sei sicuro di voler rinunciare agli aggiornamenti?";

                // creazione inline keyboard
                $btnSi = array('text'=>'Disiscrivimi', 'callback_data'=>'disiscrivi');
                $btnNo = array('text'=>'Annulla', 'callback_data'=>'annulla');
                $rigaBtn = array($btnSi, $btnNo);
                $keyboard = array('inline_keyboard'=>array($rigaBtn));

                sendMessage($chatId, $message, "reply_markup=".json_encode($keyboard));
                break;
            case "/ultime":
                // visualizzazione notizie recenti
                $message = "Quante notizie vuoi ricevere?";

                // creazione inline keyboard
                $btnUno = array('text'=>"\x31\xE2\x83\xA3", 'callback_data'=>'ultime_1');
                $btnTre = array('text'=>"\x33\xE2\x83\xA3", 'callback_data'=>'ultime_3');
                $btnCinque = array('text'=>"\x35\xE2\x83\xA3", 'callback_data'=>'ultime_5');
                $btnAnnulla = array('text'=>'Annulla', 'callback_data'=>'annulla');
                $rigaUno = array($btnUno, $btnTre, $btnCinque);
                $rigaDue = array($btnAnnulla);
                $keyboard = array('inline_keyboard'=>array($rigaUno, $rigaDue));

                sendMessage($chatId, $message, "reply_markup=".json_encode($keyboard));
                break;
            case "/info":
            	// informazioni bot
                $message = "*Rimani aggiornato sulle notizie dell'ITI V.E.M.!*\n\nIl bot permette di leggere le notizie dell'istituto più recenti e riceverle periodicamente iscrivendosi alla newsletter.\nViene sfruttato l'aggregatore RSS fornito dalla scuola.";

                // creazione inline keyboard
                $btnScuola = array('text'=>"\xF0\x9F\x8F\xA1+Sito+scuola", 'url'=>'https://www.itismarzotto.it');
                $btnFeed = array('text'=>"\xF0\x9F\x93\xA3+Feed+RSS", 'url'=>'https://www.itismarzotto.it/wp/feed-rss/');
                $btnSorgente = array('text'=>"\xF0\x9F\x93\x9C+Codice+sorgente+bot", 'url'=>$urlsource);
                $btnSviluppatore = array('text'=>"\xF0\x9F\x93\xA7+Contatta+lo+sviluppatore", 'url'=>$tmedev);
                $keyboard = array('inline_keyboard'=>array(array($btnScuola), array($btnFeed), array($btnSorgente), array($btnSviluppatore)));

                sendMessage($chatId, $message, array("disable_web_page_preview=true","parse_mode=Markdown", "reply_markup=".json_encode($keyboard)));
                break;
            default:
            	// default
            	sendMessage($chatId, "Comando non riconosciuto.\n\nProva uno dei seguenti comandi:\n".$comandi);
                break;
        }
    }
    else if($update['callback_query']!=null)
    {
        // se l'Update è una callback_query

        $query = $update['callback_query'];
        $queryId = $query['id'];
        $data = $query['data'];
        $user = $query['message']['chat']['username'];
        $messageId = $query['message']['message_id'];
        $userId = $query['from']['id'];
        $chatId = $query['message']['chat']['id'];

        # LOG
        writeLog("in", $data, $user."-uid".$userId);

        // elabora risposta
        switch($data){
            case "iscrivi":
                // inserimento utente in database
                if(aggiungiUser($userId))
                {
                    $text = "Sei stato iscritto alla newsletter!";
                    $newText = "Operazione eseguita con successo.\nD'ora in poi riceverai periodicamente gli aggiornamenti!";
                }
                else {
                    $text = "Sei già iscritto alla newsletter!";
                    $newText = "Operazione annullata.\nSei già iscritto alla newsletter!";
                }
                $url = $website."/answerCallbackQuery?callback_query_id=".$queryId."&text=".urlencode($text);
                editMessage($chatId, $messageId, $newText);
                break;
            case "disiscrivi":
                if(rimuoviUser($userId))
                {
                    $text = "Sei stato rimosso dalla newsletter";
                    $newText = "Sei stato rimosso dalla newsletter.\nPuoi reiscriverti in qualunque momento attraverso il comando /iscriviti !";
                }
                else
                {
                    $text = "Non sei ancora iscritto alla newsletter!";
                    $newText = "Operazione annullata\nNon sei ancora iscritto alla newsletter!";
                }
                $url = $website."/answerCallbackQuery?callback_query_id=".$queryId."&text=".urlencode($text);
                editMessage($chatId, $messageId, $newText);
                break;
            case "annulla":
                // annulla operazione
                $text = "Operazione annullata";
                $newText = "Operazione annullata";
                $url = $website."/answerCallbackQuery?callback_query_id=".$queryId."&text=".urlencode($text);
                editMessage($chatId, $messageId, $newText);
                break;
            case "ultime_1":
            case "ultime_3":
            case "ultime_5":
                // visualizzazione ultime $n notizie, $n ricavato ricercando attraverso una regular expression (ricerca pattern)
                preg_match('#(\d+)$#', $data, $n);
                $message = ultimeNotizie($n[0]);
                sendMessage($chatId, $message, array("disable_web_page_preview=true", "parse_mode=HTML"));

                $newText = "Operazione eseguita";
                $url = $website."/answerCallbackQuery?callback_query_id=".$queryId;
                break;
            default:
                $text = "Qualcosa è andato storto";
                $url = $website."/answerCallbackQuery?callback_query_id=".$queryId."&show_alert=true&text=".urlencode($text);;
                break;
        }
        // invio /answerCallbackQuery
        try{
            file_get_contents($url);
        }catch (Exception $e){
            writeLog("err", "main.php - Eccezione di file_get_contents - answerCallbackQuery: ".$e->getMessage());
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
        }catch (Exception $e){
            # LOG errore
            writeLog("err", "main.php - Eccezione di file_get_contents in sendMessage(): ".$e->getMessage());
        }

        # LOG risposta
        writeLog("out", $message);
    }

    // modifica messaggio
    function editMessage($chat_id, $message_id, $newText)
    {
        $url = $GLOBALS[website]."/editMessageText?chat_id=".$chat_id."&message_id=".$message_id."&text=".urlencode($newText);
        try{
            file_get_contents($url);
        }catch (Exception $e){
            # LOG errore
            writeLog("err", "main.php - Eccezione di file_get_contents in editMessage(): ".$e->getMessage());
        }
    }

    // aggiunge utente al database se non è presente
    function aggiungiUser($userId)
    {
        // accesso al database
        $mysqli = new mysqli($GLOBALS["dbhost"], $GLOBALS["dbuser"], $GLOBALS["dbpass"], $GLOBALS["dbname"]);

        // se la connessione non avviene
        if (!$mysqli)
        {
            # LOG errore
            writeLog("err", "main.php - Errore connessione db in aggiungiUser(): ".$mysqli->connect_err);
            die();
        }

        $mysqli->set_charset('utf8');

        // controlla se l'utente è già presente
        $presente = $mysqli->query("    SELECT user_id FROM utenti
                                        WHERE user_id='$userId'
                                        LIMIT 1");

        // se l'esecuzione della query non va a buon fine
        if(!$presente)
        {
            # LOG errore
            writeLog("err", "main.php - Errore query controllo presenza user in aggiungiUser(): $mysqli->errno - $mysqli->error");
        }

        // se è già presente ritorna false, altrimenti aggiungilo
        if($presente->num_rows > 0) return false;
        else $result = $mysqli->query(" INSERT INTO utenti
                                        VALUES('$userId', NULL)");

        if(!$result)
        {
            writeLog("err", "main.php - Errore query inserimento utente: $mysqli->errno - $mysqli->error");
        }

        # LOG inserimento utente
        writeLog("ser", "+ Inserito uid".$userId);

        $mysqli->close();

        return true;
    }

    // rimuove utente dal database se è presente
    function rimuoviUser($userId)
    {
        // accesso al database
        $mysqli = new mysqli($GLOBALS["dbhost"], $GLOBALS["dbuser"], $GLOBALS["dbpass"], $GLOBALS["dbname"]);

        if (!$mysqli)
        {
            # LOG errore
            writeLog("err", "main.php - Errore connessione db in rimuoviUser(): ".$mysqli->connect_err);
            die();
        }

        $mysqli->set_charset('utf8');

        // controlla se l'utente è già presente
        $presente = $mysqli->query("    SELECT user_id
                                        FROM utenti
                                        WHERE user_id='$userId'
                                        LIMIT 1");

        if(!$presente)
        {
            # LOG errore
            writeLog("err", "main.php - Errore query controllo presenza user in rimuoviUser(): $mysqli->errno - $mysqli->error");
            die();
        }

        // se non è presente ritorna false, altrimenti lo rimuove
        if($presente->num_rows < 1) return false;
        else $result = $mysqli->query(" DELETE FROM utenti
                                        WHERE user_id='$userId'");

        if(!$result)
        {
            # LOG errore
            writeLog("err", "main.php - Errore query rimozione utente in rimuoviUser(): $mysqli->errno - $mysqli->error");
            die();
        }

        # LOG rimozione utente
        writeLog("ser", "- Rimosso uid".$userId);

        $mysqli->close();

        return true;
    }

    // invia le ultime n notizie
    function ultimeNotizie($n)
    {
   		// collegamento al db
        $mysqli = new mysqli($GLOBALS["dbhost"], $GLOBALS["dbuser"], $GLOBALS["dbpass"], $GLOBALS["dbname"]);
        $mysqli->set_charset('utf8');

        if (!$mysqli)
        {
            # LOG errore
            writeLog("err", "main.php - Errore connessione db in ultimeNotizie(): ".$mysqli->connect_err);
            die();
        }

        // ottenimento ultime $n notizie
        $results = $mysqli->query(" SELECT * FROM articoli
                                    ORDER BY data DESC
                                    LIMIT $n");

        if(!$results)
        {
            # LOG errore
            writeLog("err", "main.php - Errore query prelievo notizie in ultimeNotizie(): $mysqli->errno - $mysqli->error");
            die();
        }

        if($results->num_rows < 1) sendMessage($chatId, "Nessuna notizia presente nel database.");

        // riempimento array $articoli[] con le notizie
    	while($articolo = $results->fetch_assoc()) $articoli[] = $articolo;

		$i=1;
        $message = "";
		foreach($articoli as $articolo)
		{
            // emoji del numero dell'articolo stampato
            $emoji = "\x3".$i."\xE2\x83\xA3";

			// invia articolo all'$utente
			setlocale(LC_ALL, 'it_IT.UTF-8'); // imposta locale in italiano per stampare data, alternativa date('d-m-Y H:i:s', strtotime($articolo['data']))
			$message .= $emoji." <b>".$articolo['titolo']."</b>\n<i>".strftime('%A %e %B %G, %H:%M', strtotime($articolo['data']))."</i>\n\n".$articolo['descrizione']."\n\n".'<a href="'.$articolo['link'].'">'."\xF0\x9F\x94\x97 Link articolo</a>\n\n\n\n";
			$i++;
		}

		// chiusura connessione al database
	    $mysqli->close();

        return $message;
    }


?>
