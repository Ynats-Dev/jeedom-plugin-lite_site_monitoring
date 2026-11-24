---
layout: default
lang: it_IT
---

# Registro delle modifiche di Lite Site Monitoring

# 24/11/2026 - 1.0.0

- Frequenza di controllo configurabile per dispositivo (espressione cron, ogni 5 minuti per impostazione predefinita)
- Siti controllati in parallelo, una sola richiesta per controllo, limiti di 10 s (connessione) e 20 s (richiesta)
- I reindirizzamenti vengono seguiti; successo se il codice HTTP finale è 2xx o 3xx, o fa parte dei codici attesi configurati
- Parola chiave attesa facoltativa nella pagina
- Nuovo tentativo immediato in caso di errore e numero configurabile di errori consecutivi prima dell'allarme
- Senza accesso a Internet, i siti non vengono più segnati offline: il loro ultimo stato viene mantenuto
- Opzione «Sito interno» per i siti della rete locale
- Nuovi comandi: Codice HTTP, Errore, Scadenza SSL, Accesso a Internet
- Tempi numerici in millisecondi, storicizzabili; il vecchio storico di questi comandi, in secondi, viene svuotato durante l'aggiornamento
- «Latenza» corrisponde al tempo totale della richiesta (il vecchio valore sommava tempi cumulativi)
- Nuovo widget (disattivabile), spia di stato nella pagina del plugin e riepilogo dell'ultimo controllo nel dispositivo
- Validazione dell'URL al salvataggio
- Interfaccia in francese, inglese, tedesco, spagnolo e italiano
- Correzione di sicurezza: l'URL non viene più passato a un comando di sistema
- Pulizia del codice e rimozione dei file inutilizzati

# 17/06/2021 - Versione beta

- Lancio della beta
- Funzioni «OnLine/OffLine» e «Latenza»
