<?php

return [
    'free' => [ // only one plan, do not change this key
        'id' => 'free', // do not change this id
        'enabled' => true,
        'title' => 'Free',
        'trial_days' => 0, // not in use as free plan
        'features' => [
            'contacts' => [
                'description' => __tr('Contacts'),
                'limit' => 2, // 0 for none, -1 for unlimited
            ],
            'campaigns' => [
                'limit_duration' => 'monthly',
                'limit_duration_title' => __tr('Per Month'),
                'description' => __tr('Campaigns'),
                'limit' => 10, // 0 for none, -1 for unlimited
            ],
            'drip_campaigns' => [
                'description' => __tr('Drip Campaigns (Automated Sequences)'),
                'limit' => 1, // 0 for none, -1 for unlimited
            ],
            'bot_replies' => [
                'description' => __tr('Bot Replies'),
                'limit' => 10, // 0 for none, -1 for unlimited
            ],
            'bot_flows' => [
                'description' => __tr('Bot Flows'),
                'limit' => 5, // 0 for none, -1 for unlimited
            ],
            'contact_custom_fields' => [
                'description' => __tr('Contact Custom Fields'),
                'limit' => 2, // 0 for none, -1 for unlimited
            ],
            'system_users' => [
                'description' => __tr('Team Members/Agents'),
                'limit' => 0, // 0 for none, -1 for unlimited
            ],
            'ai_chat_bot' => [
                'type' => 'switch', // on or off
                'description' => __tr('AI Chat Bot'),
                'limit' => 1, // 0 for none, 1 for enable
            ],
            'ai_credits' => [
                'description' => __tr('AI Credits / Month'),
                'limit' => 10, // 10 free credits
            ],
            'api_access' => [
                'type' => 'switch', // on or off
                'description' => __tr('API and Webhook Access'),
                'limit' => 1, // 0 for none, 1 for enable
            ],
            'ecommerce_catalog' => [
                'type' => 'switch', // on or off
                'description' => __tr('E-commerce et Catalogue'),
                'limit' => 0, // 0 for none, 1 for enable
            ],
        ],
    ],
    'paid' => [ // do not change this key
        'plan_1' => [
            'id' => 'plan_1',
            'enabled' => true,
            'popular' => false, // set plan as popular
            'title' => 'Plan de base',
            'trial_days' => 0,
            'features' => [
                'contacts' => [
                    'description' => __tr('Contacts'),
                    'limit' => 1000, // 0 for none, -1 for unlimited
                ],
                'campaigns' => [
                    'limit_duration' => 'monthly',
                    'limit_duration_title' => __tr('Per Month'),
                    'description' => __tr('Campaigns'),
                    'limit' => 10000, // 0 for none, -1 for unlimited
                ],
                'drip_campaigns' => [
                    'description' => __tr('Drip Campaigns (Automated Sequences)'),
                    'limit' => 5, // 0 for none, -1 for unlimited
                ],
                'bot_replies' => [
                    'description' => __tr('Bot Replies'),
                    'limit' => 50, // 0 for none, -1 for unlimited
                ],
                'bot_flows' => [
                    'description' => __tr('Bot Flows'),
                    'limit' => 20, // 0 for none, -1 for unlimited
                ],
                'contact_custom_fields' => [
                    'description' => __tr('Contact Custom Fields'),
                    'limit' => 10, // 0 for none, -1 for unlimited
                ],
                'system_users' => [
                    'description' => __tr('Team Members/Agents'),
                    'limit' => 2, // 0 for none, -1 for unlimited
                ],
                'ai_chat_bot' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('AI Chat Bot'),
                    'limit' => 1, // 0 for none and 1 for enable
                ],
                'ai_credits' => [
                    'description' => __tr('AI Credits / Month'),
                    'limit' => 500,
                ],
                'api_access' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('API and Webhook Access'),
                    'limit' => 1, // 0 for none, 1 for enable
                ],
                'ecommerce_catalog' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('E-commerce et Catalogue'),
                    'limit' => 0, // 0 for none, 1 for enable
                ],
            ],
            'charges' => [
                'monthly' => [
                    'title' => __tr('monthly'),
                    'enabled' => true,
                    'price_id' => '',
                    'charge' => 5000,
                ],
                'yearly' => [
                    'title' => __tr('yearly'),
                    'enabled' => true,
                    'price_id' => '',
                    'charge' => 50000,
                ],
            ],
        ],
        'plan_2' => [
            'id' => 'plan_2',
            'enabled' => true,
            'popular' => true, // set plan as popular
            'title' => 'Plan Intermédiaire',
            'trial_days' => 0,
            'features' => [
                'contacts' => [
                    'description' => __tr('Contacts'),
                    'limit' => 5000, // 0 for none, -1 for unlimited
                ],
                'campaigns' => [
                    'limit_duration' => 'monthly',
                    'limit_duration_title' => __tr('Per Month'),
                    'description' => __tr('Campaigns'),
                    'limit' => 50000, // 0 for none, -1 for unlimited
                ],
                'drip_campaigns' => [
                    'description' => __tr('Drip Campaigns (Automated Sequences)'),
                    'limit' => 20, // 0 for none, -1 for unlimited
                ],
                'bot_replies' => [
                    'description' => __tr('Bot Replies'),
                    'limit' => 200, // 0 for none, -1 for unlimited
                ],
                'bot_flows' => [
                    'description' => __tr('Bot Flows'),
                    'limit' => 100, // 0 for none, -1 for unlimited
                ],
                'contact_custom_fields' => [
                    'description' => __tr('Contact Custom Fields'),
                    'limit' => 50, // 0 for none, -1 for unlimited
                ],
                'system_users' => [
                    'description' => __tr('Team Members/Agents'),
                    'limit' => 10, // 0 for none, -1 for unlimited
                ],
                'ai_chat_bot' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('AI Chat Bot'),
                    'limit' => 1, // 0 for none, 1 for enable
                ],
                'ai_credits' => [
                    'description' => __tr('AI Credits / Month'),
                    'limit' => 2000,
                ],
                'api_access' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('API and Webhook Access'),
                    'limit' => 1, // 0 for none, 1 for enable
                ],
                'ecommerce_catalog' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('E-commerce et Catalogue'),
                    'limit' => 0, // 0 for none, 1 for enable
                ],
            ],
            'charges' => [
                'monthly' => [
                    'title' => __tr('monthly'),
                    'enabled' => true,
                    'price_id' => '',
                    'charge' => 10000,
                ],
                'yearly' => [
                    'title' => __tr('yearly'),
                    'enabled' => true,
                    'price_id' => '',
                    'charge' => 100000,
                ],
            ],
        ],
        'plan_3' => [
            'id' => 'plan_3',
            'enabled' => true,
            'popular' => false, // set plan as popular
            'title' => 'Plan Commerce Pro',
            'trial_days' => 0,
            'features' => [
                'contacts' => [
                    'description' => __tr('Contacts'),
                    'limit' => -1, // 0 for none, -1 for unlimited
                ],
                'campaigns' => [
                    'limit_duration' => 'monthly',
                    'limit_duration_title' => __tr('Per Month'),
                    'description' => __tr('Campaigns'),
                    'limit' => -1, // 0 for none, -1 for unlimited
                ],
                'drip_campaigns' => [
                    'description' => __tr('Drip Campaigns (Automated Sequences)'),
                    'limit' => -1, // 0 for none, -1 for unlimited
                ],
                'bot_replies' => [
                    'description' => __tr('Bot Replies'),
                    'limit' => -1, // 0 for none, -1 for unlimited
                ],
                'bot_flows' => [
                    'description' => __tr('Bot Flows'),
                    'limit' => -1, // 0 for none, -1 for unlimited
                ],
                'contact_custom_fields' => [
                    'description' => __tr('Contact Custom Fields'),
                    'limit' => -1, // 0 for none, -1 for unlimited
                ],
                'system_users' => [
                    'description' => __tr('Team Members/Agents'),
                    'limit' => -1, // 0 for none, -1 for unlimited
                ],
                'ai_chat_bot' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('AI Chat Bot'),
                    'limit' => 1, // 0 for none, 1 for enable
                ],
                'ai_credits' => [
                    'description' => __tr('AI Credits / Month'),
                    'limit' => -1, // Unlimited
                ],
                'api_access' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('API and Webhook Access'),
                    'limit' => 1, // 0 for none, 1 for enable
                ],
                'ecommerce_catalog' => [
                    'type' => 'switch', // on or off
                    'description' => __tr('E-commerce et Catalogue'),
                    'limit' => 1, // 0 for none, 1 for enable
                ],
            ],
            'charges' => [
                'monthly' => [
                    'title' => __tr('monthly'),
                    'enabled' => true,
                    'price_id' => '',
                    'charge' => 15000,
                ],
                'yearly' => [
                    'title' => __tr('yearly'),
                    'enabled' => true,
                    'price_id' => '',
                    'charge' => 150000,
                ],
            ],
        ],
    ],
];
