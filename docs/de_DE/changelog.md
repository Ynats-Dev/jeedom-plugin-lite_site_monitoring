---
layout: default
lang: de_DE
---

# Änderungsprotokoll Lite Site Monitoring

# 24/11/2026 - 1.0.0

- Prüfhäufigkeit pro Gerät einstellbar (Cron-Ausdruck, standardmäßig alle 5 Minuten)
- Websites werden parallel geprüft, eine einzige Anfrage pro Prüfung, Zeitlimits von 10 s (Verbindung) und 20 s (Anfrage)
- Weiterleitungen werden verfolgt; Erfolg, wenn der endgültige HTTP-Code 2xx oder 3xx ist oder zu den konfigurierten erwarteten Codes gehört
- Optionales erwartetes Schlüsselwort auf der Seite
- Sofortiger neuer Versuch bei Fehlschlag und einstellbare Anzahl aufeinanderfolgender Fehlschläge vor Alarm
- Ohne Internetzugang werden Websites nicht mehr als offline markiert: Ihr letzter Zustand bleibt erhalten
- Option „Interne Website“ für Websites im lokalen Netzwerk
- Neue Befehle: HTTP-Code, Fehler, SSL-Ablauf, Internetzugang
- Numerische Zeiten in Millisekunden, protokollierbar; der alte Verlauf dieser Befehle in Sekunden wird beim Update geleert
- „Latenz“ entspricht der Gesamtdauer der Anfrage (der alte Wert addierte kumulierte Zeiten)
- Neues Widget (abschaltbar), Statusleuchte auf der Plugin-Seite und Zusammenfassung der letzten Prüfung im Gerät
- Prüfung der URL beim Speichern
- Oberfläche auf Französisch, Englisch, Deutsch, Spanisch und Italienisch
- Sicherheitskorrektur: Die URL wird nicht mehr an einen Systembefehl übergeben
- Code-Bereinigung und Entfernung ungenutzter Dateien

# 17/06/2021 - Betaversion

- Start der Beta
- Funktionen „OnLine/OffLine“ und „Latenz“
