# Changelog (Mises à jour)

Ce fichier liste l'historique des modifications apportées au projet.

## [Validé] - Recherche avancée type WhatsApp (Global & Intra-chat)
- **Recherche Globale (Contacts)** : La barre de recherche filtre désormais les contacts en incluant le contenu de tous leurs messages. L'extrait exact du message trouvé s'affiche sous le contact avec le mot-clé en surbrillance.
- **Recherche Intra-chat** : Ajout d'une icône "Loupe" dans l'en-tête d'une discussion pour chercher des mots au sein de la conversation active, filtrant instantanément les messages non pertinents et surlignant les mots trouvés.

## [Validé] - Format de l'heure des messages
- L'heure des messages dans l'interface de discussion s'affiche désormais au format 24H avec la date complète (ex: `samedi 13 juin 2026 17:18:46`).
- Le modèle `WhatsAppMessageLogModel.php` a été mis à jour avec un accesseur `getFormattedMessageTime24hAttribute()`.
- La vue `chat.blade.php` a été ajustée pour utiliser cette nouvelle valeur.

## [Validé] - Fixation et esthétique du champ de saisie
- Transformation du champ de saisie en une bannière fixe en bas de l'écran avec fond blanc et bords légèrement arrondis.
- Bouton d'envoi rendu carré avec des bords légèrement arrondis.
- Résolution du problème de débordement (overflow) du champ de saisie sur ordinateur (desktop).
- Conservation de l'affichage adaptatif et visible du champ de saisie sur mobile.
