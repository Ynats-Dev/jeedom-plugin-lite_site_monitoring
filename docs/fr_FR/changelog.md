---
layout: default
lang: fr_FR
---

# Changelog Lite Site Monitoring

# 24/11/2026 - 1.0.0

- Fréquence de contrôle réglable par équipement (expression cron, 5 minutes par défaut)
- Sites contrôlés en parallèle, une seule requête par contrôle, délais maximaux de 10 s (connexion) et 20 s (requête)
- Redirections suivies ; succès si le code HTTP final est 2xx ou 3xx, ou fait partie des codes attendus configurés
- Mot-clé attendu optionnel dans la page
- Nouvel essai immédiat en cas d'échec et nombre d'échecs consécutifs avant alerte réglable
- Sans accès à Internet, les sites ne sont plus passés hors ligne : leur dernier état est conservé
- Option « Site interne » pour les sites du réseau local
- Nouvelles commandes : Code HTTP, Erreur, Expiration SSL, Accès Internet
- Temps numériques en millisecondes, historisables ; l'ancien historique de ces commandes, en secondes, est vidé à la mise à jour
- « Latence » correspond au temps total de la requête (l'ancienne valeur additionnait des temps cumulés)
- Nouveau widget (désactivable), voyant d'état sur la page du plugin et résumé du dernier contrôle dans l'équipement
- Validation de l'URL à l'enregistrement
- Interface en français, anglais, allemand, espagnol et italien
- Correction d'une faille : l'URL n'est plus transmise à une commande système
- Nettoyage du code et suppression des fichiers inutilisés

# 17/06/2021 - Version bêta

- Lancement de la bêta
- Fonctionnalités « OnLine/OffLine » et « Latence »
