# itivemfeedbot

L' __ITI VE Marzotto Feed Bot__ è un bot Telegram che permette agli utenti di iscriversi ad una newsletter in modo da essere notificati quando una nuova notizia viene pubblicata sul sito dell'istituto.
Il sistema memorizza quindi in un database gli identificativi degli utenti che hanno richiesto l'iscrizione e a cadenza oraria controlla se sono stati pubblicati nuovi articoli scaricando i feed RSS messi a disposizione dalla scuola.
In caso affermativo questi vengono inseriti nel database per poi essere inviati agli utenti abbonati.
E' possibile disiscriversi dal servizio in qualsiasi momento e le ultime notizie possono essere visualizzate anche manualmente attraverso un apposito comando.

Il bot si compone principalmente di tre script fondamentali sviluppati in PHP:
- main.php: gestisce l'interazione con l'utente e si occupa di inserire e rimuovere gli utenti dal database.
- saturno.php: viene eseguito automaticamente a cadenza oraria da un cron job e si occupa di scaricare i feed RSS, inserire i nuovi articoli nel database e rimuovere quelli obsoleti.
- mercurio.php: viene avviato da saturno.php se sono state inserite nuove notizie nel database e si occupa di inviarle agli utenti iscritti al servizio.

L'accesso al database avviene tramite l'estensione MySQLi.
Vengono utilizzate una tabella utenti contenente l'id univoco degli utenti iscritti al servizio assieme alla data di inserimento e una tabella articoli che associa ad ogni articolo un codice univoco oltre a memorizzarne gli elementi quali titolo, descrizione, link e data di pubblicazione.
Segue lo schema logico del database:

- UTENTI(__user_id__: bigint, data: timestamp)
- ARTICOLI(__id__: int, titolo: text, descrizione: text, link: text, data: datetime)