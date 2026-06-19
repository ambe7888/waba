---
description: Instructions pour l'Agent Copilot - Projet Agence Marketing Digital
---

# Instructions pour l'Agent Copilot - Projet Agence Marketing Digital

Ce projet est le site web d'une agence de marketing digital. Il utilise une architecture MVC en PHP natif. 

## Bonnes Pratiques de Développement pour ce Projet
1. **Architecture MVC :** Respecter strictement la séparation des responsabilités entre les Modèles (requêtes SQL), les Vues (HTML/CSS) et les Contrôleurs (logique métier).
2. **Intégrations Tierces plutôt que développement local :**
   - **Emailing :** Utiliser des APIs (ex: SendGrid, Brevo) pour l'envoi d'emails transactionnels et marketing en masse, au lieu de configurer un SMTP local complexe.
   - **Trafic / Analytics :** Intégrer Google Analytics, Plausible ou l'API de Google Analytics pour remonter les données dans le dashboard d'administration.
   - **Chatbot / IA :** Intégrer l'API d'OpenAI ou de DeepSeek pour propulser un chatbot intelligent et personnalisé.
3. **Sécurité :**
   - Toutes les requêtes en base de données doivent utiliser des requêtes préparées (PDO) pour éviter les injections SQL.
   - Sécuriser l'accès à la route `/admin` et vérifier l'authentification et les autorisations (sessions).
   - Nettoyer les entrées des formulaires (`htmlspecialchars`, validation).
4. **Clean Code :** 
   - Garder les méthodes des contrôleurs courtes.
   - Commenter la logique métier complexe (tunnels de vente, CRM).

## Fonctionnalités Principales (Roadmap)
- Interface d'administration.
- Gestion des services (CRUD).
- Tunnels de vente (Vues dynamiques étapes par étapes).
- CRM : Capture de leads via des formulaires frontend et stockage sécurisé.
- Création et gestion d'articles de blog (CRUD).
- Intégration de chatbot live.
- Suivi du trafic et statistiques des pages.

