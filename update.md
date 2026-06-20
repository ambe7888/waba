# Changelog (Mises à jour)

Ce fichier liste l'historique des modifications apportées au projet.

## [Validé] - Nouvel Assistant de Création de Templates (Wizard UI)
- **Interface par Étapes (Wizard)** : Remplacement de l'ancienne page complexe par un assistant clair et guidé (4 à 6 étapes selon le type).
- **Aperçu Mobile en Temps Réel** : Ajout d'une prévisualisation dynamique type smartphone affichant instantanément le rendu final du template.
- **Support Avancé des Carrousels** : 
  - Gestion indépendante des cartes du carrousel avec sélection de médias modernisée.
  - Configuration des boutons d'action (URL, Téléphone, Quick Reply) propre à **chaque carte** (jusqu'à 2 boutons par carte), respectant l'architecture Meta.
- **Design Moderne & Premium** : Adoption du code couleur de la marque (Teal), utilisation d'Alpine.js pour une réactivité parfaite sans rechargement, animations fluides et interfaces épurées.


## [Validé] - Redesign du bloc d'informations du contact (SaaS CRM Moderne)
- **Structure en Cartes Épurées** : Chaque section d'informations (Profil, À propos, Paramètres et Assignation, Étiquettes, Notes) est organisée sous forme de carte individuelle blanche avec des angles légèrement arrondis (`12px`) et une ombre très fine.
- **Redesign du Modal des Étiquettes (Amélioré)** : Refonte complète de la fenêtre popup de gestion des étiquettes pour lever toute confusion sur les couleurs :
  - **Sélecteurs de Couleur Distincts** : Les entrées pour la couleur du texte et celle du fond sont disposées côte à côte avec des explications spécifiques sous chaque champ (ex: "Couleur de la police" / "Couleur d'arrière-plan"). Les champs affichent dynamiquement la valeur hexadécimale à côté de la palette de couleur.
  - **Aperçu en Temps Réel** : Ajout d'un bloc d'aperçu dynamique de l'étiquette ("Aperçu en direct") qui s'actualise en temps réel à chaque saisie de titre ou changement de couleur, aussi bien pour la création que pour la modification d'étiquettes existantes.
  - **Interface Premium** : Rangement des étiquettes existantes dans des cartes blanches avec ombres douces et boutons circulaires pour sauvegarder ou supprimer de manière intuitive.
- **Boutons d'Action Circulaires** : Pour une interaction plus instinctive, les boutons d'action secondaires (ajouter une étiquette `+` et modifier les remarques `pencil`) ont été stylisés sous forme de boutons ronds personnalisés (`.lw-crm-btn-round`) avec effets de survol et de zoom.
- **Section Étiquettes** : Renommage de "Labels / Tags" en "Étiquettes" pour une meilleure adéquation avec le public francophone, et remplacement de l'icône d'engrenage par un bouton d'ajout `+` très clair.
- **Fond de page sobre** : Passage du fond gris uniforme à une nuance ardoise claire (`#f8fafc`) qui améliore les contrastes et rend le panneau élégant.
- **Avatar moderne à coins arrondis** : Remplacement de l'ancien avatar circulaire par un carré aux angles adoucis (`border-radius: 16px`) utilisant un dégradé professionnel émeraude/teal (`linear-gradient(135deg, #0f766e, #0d9488)`).
- **iOS-style Toggles** : Les interrupteurs (toggles) des bots (AI Bot, Reply Bot) ont été remplacés par des switchs ultra-fluides style Apple iOS.
- **Accentuation Visuelle** : Ajout d'une fine bordure verticale colorée (`border-left: 3px solid #0d9488`) sur la gauche de chaque en-tête de section, et intégration d'icônes à fond grisé doux dans les lignes d'information.

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
