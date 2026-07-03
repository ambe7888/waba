# Implémentation du Module E-commerce & Vente Directe

Ce document détaille le plan technique pour implémenter l'Option 2 : **Intégration du Catalogue WhatsApp** et **Intégration native WooCommerce** pour les relances automatiques.

## Open Questions
1. Pour l'intégration WooCommerce, souhaitez-vous utiliser le système de Webhooks natif de WooCommerce (Le vendeur copiera une URL fournie par la plateforme dans les réglages de son site) ?
2. Souhaitez-vous que cette fonctionnalité "E-commerce" soit disponible pour tous les abonnements ou réservée à un Plan Premium ?

## Proposed Changes

### 1. Support du Catalogue WhatsApp (Interactive Product Messages)
Ajout de la capacité d'envoyer des fiches produits interactives via l'API WhatsApp Cloud.
#### `app/Yantrana/Components/WhatsAppService/Controllers/WhatsAppServiceController.php`
- Ajouter le support des types `product`, `product_list`, et `catalog_message` dans la méthode d'envoi de messages interactifs.
#### `app/Yantrana/Components/WhatsAppService/WhatsAppServiceEngine.php`
- Mapper les nouveaux payloads interactifs vers l'API de Meta (structure de l'objet `action` avec `catalog_id` et `product_retailer_id`).
#### `resources/views/whatsapp-service/chat-box.blade.php` (ou équivalent)
- Ajouter une option "Envoyer un Produit/Catalogue" dans la boîte de dialogue du Chat en direct.

### 2. API & Relances WooCommerce
Création d'un système qui écoute les événements d'une boutique WooCommerce et déclenche des messages WhatsApp.
#### `app/Yantrana/Components/ECommerce/Controllers/WooCommerceWebhookController.php`
- Création d'un nouveau contrôleur pour recevoir et traiter les requêtes POST provenant de WooCommerce (ex: événement `order.created`, `order.updated`).
#### `routes/api.php`
- Ajout de la route d'API publique : `/webhook/woocommerce/{vendor_uid}`.
#### `resources/views/configuration/settings.blade.php` (Configuration Vendeur)
- Ajout d'un onglet "E-commerce" dans les paramètres du vendeur pour qu'il puisse :
  1. Voir son URL Webhook unique à copier dans WooCommerce.
  2. Sélectionner le Template WhatsApp (modèle validé) à envoyer lors d'une "Nouvelle Commande".
  3. Sélectionner le Template WhatsApp à envoyer lors d'un "Panier Abandonné".
