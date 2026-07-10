# Plan d'Implémentation - Corrections et Fonctionnalités (App & Web)

## 1. Notes rapide non fonctionnel
**Problème :** L'enregistrement ou la récupération des notes rapides sur l'application mobile ne fonctionne pas.
**Analyse :** 
L'API `/vendor/whatsapp/contact/chat/update-notes` est appelée. Côté backend (`ContactEngine::processUpdateNotes`), la mise à jour de la colonne JSON `__data` pourrait écraser les autres données ou ne pas être fusionnée correctement. Sur mobile, les notes sont récupérées via `/vendor/contacts/{uid}/get-update-data`.
**Action :** Vérifier le backend `ContactEngine.php` ligne 1855 pour s'assurer que `contact_notes` est bien fusionné ou utiliser la syntaxe `__data->contact_notes` pour Laravel.

## 2. Groupe de contacts non fonctionnel
**Problème :** Sur l'application, l'assignation des groupes de contacts ne fonctionne pas.
**Analyse :** 
L'API Laravel valide `contacts_uid` et `groups_uid` mais la logique `processAssignGroupsToSelectedContacts` lit `selected_groups` et `selected_contacts` à moins qu'il s'agisse d'une requête API externe marquée par un header `x-external-api-request`.
**Action :** Dans `api_service.dart` (`assignGroupsToContact`), envoyer les deux jeux de clés (`contacts_uid`/`selected_contacts` et `groups_uid`/`selected_groups`) dans le payload pour satisfaire à la fois la validation et le traitement.

## 3. Ajouter Déconnexion avec confirmation
**Problème :** La déconnexion est directe sans confirmation.
**Action :** Dans `home_screen.dart` et `account_screen.dart`, ajouter une boîte de dialogue (AlertDialog) pour confirmer la déconnexion avant d'appeler `ApiService().logout()`.

## 4. Liste des Réponses du bot non visible dans les paramètres
**Problème :** L'écran "Réponses du bot" ne s'affiche pas ou liste vide.
**Analyse :** Dans `bot_replies_screen.dart` et l'API `vendor/bot-replies-management/list`.
**Action :** Vérifier l'intégration de la ListView et l'appel API.

## 5. Créateur de campagne avec "Audience" (Feature majeure Web & App)
**Problème :** Actuellement, une campagne cible un ou plusieurs "Groupes". Le client souhaite pouvoir créer une "Audience" qui regroupe : Groupes + Contacts individuels + Étiquettes.
**Analyse :**
Ceci nécessite un refactoring majeur :
- Création d'une table `audiences` (id, title, vendor_id, rules_json, created_at, updated_at).
- Création d'une page Web "Audiences" (CRUD) pour gérer ces listes.
- Le créateur de campagne (Web et Mobile) doit sélectionner une "Audience" unique au lieu de multiples groupes.
- Modification du moteur d'envoi de campagne (`CampaignEngine.php`) pour résoudre l'audience en une liste unique de contacts (en fusionnant les groupes, les étiquettes et les contacts individuels sélectionnés).
**Action :** Exécuter une migration de base de données, ajouter le contrôleur/modèle/vues pour Audiences, et mettre à jour le créateur de campagne sur l'application mobile et le web.

## 6. Afficher 3 lignes sur le tableau des statistiques des étiquettes
**Problème :** J'ai réduit à 1 ligne dans le précédent correctif.
**Action :** Rétablir les 3 lignes dans `dashboard_screen.dart` (mobile).

## 7. Le bouton œil non fonctionnel sur la table étiquettes
**Problème :** Sur le tableau de bord (mobile), le bouton œil à côté des statistiques d'une étiquette est inactif.
**Action :** Corriger la méthode `_viewLabeledContacts` dans `dashboard_screen.dart` pour qu'elle redirige correctement vers `ContactsScreen` avec le bon filtre, et vérifier que `ContactsScreen` gère bien ce filtre.

## 8. Liste de tous les agents dans le dashboard admin et table étiquettes
**Problème :** La sélection d'un agent dans le filtre du tableau de bord admin ne filtre peut-être pas correctement les statistiques d'étiquettes, ou la liste des agents est incomplète.
**Action :** Vérifier comment le backend (`DashboardEngine::prepareVendorDashboardData`) gère le paramètre `agent_id` et si la liste `agents` remonte tous les membres de l'équipe (vendor users).

## 9. Bouton rafraîchir en haut à droite de discussion ne fonctionne plus
**Problème :** Dans `chat_box_screen.dart`, le bouton de rafraîchissement manuel est cassé.
**Action :** Reconnecter le bouton à la fonction `_loadChatHistory(reset: true)`.

## 10. Optimisez la recherche
**Problème :** La recherche est lente ou inefficace (elle ne s'effectue actuellement qu'en local sur les contacts chargés).
**Analyse :** Pour optimiser, il faut envoyer la requête de recherche au serveur car l'API `/vendor/contact/contacts-data` accepte déjà le paramètre `search`.
**Action :** Dans `home_screen.dart`, ajouter un appel API à chaque frappe avec un léger délai (Debounce de 500ms) en passant le paramètre `search` à `fetchContacts`.

## 11. Ordre de listage des messages à changer
**Problème :** Les dernières réponses (nouveaux messages) ne sont pas en tête de liste.
**Analyse :** Le tri actuel de `contacts-data` dans `ContactRepository::getVendorContactsWithUnreadDetails` ne comporte aucun `orderBy`.
**Action :** Modifier `ContactRepository.php` (ligne 574) pour ajouter un tri `orderByDesc` basé sur le `messaged_at` le plus récent via une sous-requête sur la table `whatsapp_message_logs`.

---

**Souhaitez-vous que je procède à l'exécution de ce plan ?**
