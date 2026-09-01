const String baseUrl = 'https://whats-click.com/'; // Production server URL
const String baseApiUrl = '${baseUrl}api/';

// Version de l'application
const String version = '1.0.28';

// Polling configuration
const int pollingIntervalSeconds = 3;
const int aggressivePollingIntervalMs = 800;
const int aggressivePollingMaxCount = 8;

const Map configItems = {
  'appTitle': 'WhatsClick',
  'default_language_code': 'fr',
  'services': {
    'pusher': {
      'apiKey': '1aaea6dc705a38d4816c',
      'cluster': 'mt1',
      'encrypted': true,
    }
  }
};
