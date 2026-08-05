<?php

/**
 * WhatsJet / WhatsClick - Structure des Permissions Utilisateurs et Membres d'Équipe
 *-----------------------------------------------------------------------------*/

return [
    'administrative' => [
        'title' => __tr('Administration & Équipe'),
        'description' => __tr('Accès général à la configuration du compte, la gestion d\'équipe, les abonnements et le journal système'),
        'permissions' => [
            'manage_team_members' => [
                'title' => __tr('Gérer l\'Équipe (Ajouter, Modifier, Supprimer des Membres)'),
                'description' => __tr('Autoriser la création, modification et suppression des membres de l\'équipe'),
            ],
            'manage_subscriptions' => [
                'title' => __tr('Gérer les Abonnements & Factures'),
                'description' => __tr('Autoriser la souscription aux plans, la gestion du compte et l\'accès aux factures'),
            ],
            'manage_settings' => [
                'title' => __tr('Gérer les Paramètres & Clés API'),
                'description' => __tr('Autoriser l\'accès aux configurations WhatsApp Cloud API, Webhooks et intégrations'),
            ],
        ],
    ],
    'manage_contacts' => [
        'title' => __tr('Gestion des Contacts & Groupes'),
        'description' => __tr('Autoriser/Refuser l\'accès à la gestion des contacts, groupes et champs personnalisés'),
        'permissions' => [
            'import_contacts' => [
                'title' => __tr('Importer des Contacts'),
                'description' => __tr('Autoriser l\'importation de contacts via fichier CSV/Excel'),
            ],
            'export_contacts' => [
                'title' => __tr('Exporter des Contacts'),
                'description' => __tr('Autoriser l\'exportation de la base de contacts'),
            ],
            'delete_contacts' => [
                'title' => __tr('Supprimer des Contacts'),
                'description' => __tr('Autoriser la suppression définitive de contacts'),
            ],
            'add_edit_contacts' => [
                'title' => __tr('Ajouter / Modifier des Contacts'),
                'description' => __tr('Autoriser l\'ajout et la mise à jour des fiches contacts'),
            ],
            'add_edit_delete_custom_contact_fields' => [
                'title' => __tr('Gérer les Champs Personnalisés'),
                'description' => __tr('Autoriser la création, modification et suppression de champs sur-mesure'),
            ],
            'add_edit_delete_archive_group' => [
                'title' => __tr('Gérer les Groupes de Contacts'),
                'description' => __tr('Autoriser l\'ajout, modification, suppression et archivage de groupes'),
            ],
        ],
    ],
    'manage_campaigns' => [
        'title' => __tr('Gestion des Campagnes & Audiences'),
        'description' => __tr('Autoriser/Refuser la création, planification et exécution des campagnes de messages'),
        'permissions' => [
            'create_edit_campaigns' => [
                'title' => __tr('Créer & Lancer des Campagnes (Modèle & Message Libre)'),
                'description' => __tr('Autoriser la création et le lancement de nouvelles campagnes WhatsApp'),
            ],
            'delete_campaigns' => [
                'title' => __tr('Supprimer & Archiver des Campagnes'),
                'description' => __tr('Autoriser la suppression et l\'archivage des campagnes exécutées'),
            ],
            'manage_campaign_audiences' => [
                'title' => __tr('Gérer les Audiences de Campagne'),
                'description' => __tr('Autoriser la création et modification d\'audiences ciblées'),
            ],
            'manage_drip_campaigns' => [
                'title' => __tr('Gérer les Campagnes Goutte-à-Goutte (Drip)'),
                'description' => __tr('Autoriser la création et la configuration des campagnes automatisées séquentielles'),
            ],
        ],
    ],
    'messaging' => [
        'title' => __tr('Messagerie & Live Chat'),
        'description' => __tr('Autoriser/Refuser l\'accès aux discussions WhatsApp en direct et à la gestion des conversations'),
        'permissions' => [
            'delete_chat_history' => [
                'title' => __tr('Supprimer des Conversations / Historique'),
                'description' => __tr('Autoriser la suppression de l\'historique des échanges avec les clients'),
            ],
        ],
    ],
    'manage_templates' => [
        'title' => __tr('Modèles de Messages (Meta Templates)'),
        'description' => __tr('Autoriser/Refuser la création, synchronisation et gestion des modèles approuvés Meta'),
        'permissions' => [
            'add_edit_templates' => [
                'title' => __tr('Créer / Modifier des Modèles'),
                'description' => __tr('Autoriser la soumission de nouveaux modèles à la validation Meta'),
            ],
            'delete_templates' => [
                'title' => __tr('Supprimer des Modèles'),
                'description' => __tr('Autoriser la suppression des modèles de messages enregistrés'),
            ],
        ],
    ],
    'assigned_chats_only' => [
        'title' => __tr('Restreindre aux Chats Attribués'),
        'description' => __tr('Limiter l\'utilisateur uniquement aux conversations qui lui sont directement assignées'),
    ],
    'hide_contact_phone_numbers' => [
        'title' => __tr('Masquer les Numéros de Téléphone'),
        'description' => __tr('Masquer les numéros de téléphone des contacts pour cet utilisateur'),
        'default' => false
    ],
    'hide_contact_emails' => [
        'title' => __tr('Masquer les Adresses E-mail'),
        'description' => __tr('Masquer les adresses e-mail des contacts pour cet utilisateur'),
        'default' => false
    ],
    'manage_bot_replies' => [
        'title' => __tr('Chatbots & Automatisations (Flows)'),
        'description' => __tr('Autoriser/Refuser la configuration des réponses automatiques du Bot et des flux conversationnels'),
        'permissions' => [
            'add_edit_bot_replies' => [
                'title' => __tr('Créer / Modifier des Réponses Automatiques'),
                'description' => __tr('Autoriser la configuration des mots-clés et réponses Bot'),
            ],
            'delete_bot_replies' => [
                'title' => __tr('Supprimer des Réponses Automatiques'),
                'description' => __tr('Autoriser la suppression de réponses Bot configurées'),
            ],
            'add_edit_bot_flows' => [
                'title' => __tr('Créer / Modifier des Flux (Bot Flows)'),
                'description' => __tr('Autoriser la création de flux de discussion intelligents'),
            ],
            'delete_bot_flows' => [
                'title' => __tr('Supprimer des Flux Bot'),
                'description' => __tr('Autoriser la suppression de flux de discussion'),
            ],
            'manage_bot_flow_builder' => [
                'title' => __tr('Accès au Constructeur Visuel (Flow Builder)'),
                'description' => __tr('Autoriser l\'utilisation du créateur visuel de scénarios de chat'),
            ],
        ],
    ],
    'manage_orders' => [
        'title' => __tr('Gestion des Commandes WhatsApp'),
        'description' => __tr('Autoriser/Refuser l\'accès à la consultation, la création et la gestion des commandes'),
        'permissions' => [
            'add_edit_orders' => [
                'title' => __tr('Créer & Modifier des Commandes / Statuts'),
                'description' => __tr('Autoriser la création de commandes et le changement de statut'),
            ],
            'delete_orders' => [
                'title' => __tr('Supprimer des Commandes'),
                'description' => __tr('Autoriser la suppression définitive de commandes'),
            ],
        ],
    ],
    'manage_support_tickets' => [
        'title' => __tr('Support Client & Tickets'),
        'description' => __tr('Autoriser/Refuser l\'accès à la création et au traitement des tickets de support'),
        'permissions' => [
            'create_tickets' => [
                'title' => __tr('Créer des Tickets de Support'),
                'description' => __tr('Autoriser l\'ouverture de demandes d\'assistance'),
            ],
            'reply_tickets' => [
                'title' => __tr('Répondre aux Tickets'),
                'description' => __tr('Autoriser l\'envoi de messages sur les tickets existants'),
            ],
        ],
    ],
];
