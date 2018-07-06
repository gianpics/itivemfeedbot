<?php

    // token telegram API
    $botToken = "123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11";

    // url API Telegram
    $website = "https://api.telegram.org/bot".$botToken;

    // url source code
    $urlsrouce = "https://github.com/gianpics/itivemfeedbot";

    // uid e link telegram sviluppatore da contattare
    $uiddev = 12345;
    $tmedev = "t.me/xyz";

    $fconf = "path/to/log/file";

    // max articoli in database
    $maxArt = 5;    

    // info database
    $dbhost = 'host';
    $dbuser = 'user';
    $dbpass = 'password';
    $dbname = 'database';


    // scrivi log su log.html
    function writeLog($type, $message, $source=null)
    {
        $f = fopen($GLOBALS['fconf'], "a");

        // in - messaggio in entrata, out - risposta, ser - log di servizio, err - errore
        switch($type){
            case "in":
                // messaggio in entrata
                $text = '<span style="color:blue">← ['.date('G:i:s d/m/y')."](".$source."): ".$message."</span><br>\n";
                break;
            case "out":
                // messaggio in uscita
                $text = '<span style="color:#5c8a8a">→ ['.date('G:i:s d/m/y')."]: ".$message."</span><br>\n";
                break;
            case "ser":
                // log di servizio
                $text = '<span style="color:#248f24">● ['.date('G:i:s d/m/y')."]: ".$message."</span><br>\n";
                break;
            case "err":
                // log errore
                $text = '<span style="color:red">⚠ ['.date('G:i:s d/m/y')."]: ".$message."</span><br>\n";

                // avviso allo sviluppatore
                $url = "https://api.telegram.org/bot".$GLOBALS['botToken']."/sendMessage?chat_id=".$GLOBALS['uiddev']."&text=".urlencode("\xE2\x9A\xA0 ".$message);
                file_get_contents($url);
                break;
        }

        fwrite($f, $text);

        fclose($f);
    }
?>
