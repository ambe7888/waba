const String baseUrl = 'https://wb.4adev.com/'; // Production server URL
const String baseApiUrl = '${baseUrl}api/';

const String publicKey = '''-----BEGIN PUBLIC KEY-----
MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAPJwwNa//eaQYxkNsAODohg38azVtalE
h7Lw4wxlBrbDONgYaebgscpjPRloeL0kj4aLI462lcQGVAxhyh8JijsCAwEAAQ==
-----END PUBLIC KEY-----''';

const bool debug = true;
const String version = '1.0.2';

// Polling configuration
const int pollingIntervalSeconds = 5;
const int aggressivePollingIntervalMs = 600;
const int aggressivePollingMaxCount = 8;

const Map configItems = {
  'debug': debug,
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
