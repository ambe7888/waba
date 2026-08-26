// AdminService — Regroupement logique des méthodes admin d'ApiService.
// Les méthodes ci-dessous sont actuellement dans ApiService et accessibles
// via ApiService().methodName(). Ce fichier documente leur appartenance
// sémantique en vue d'une migration progressive.
//
// Méthodes de ce service :
  //   - cancelReminder
  //   - checkForUpdate
  //   - createAgent
  //   - deleteAgent
  //   - fetchAgentDetail
  //   - fetchAgents
  //   - fetchDashboardStats
  //   - fetchNotifications
  //   - fetchResources
  //   - fetchSubscriptionHistory
  //   - markNotificationsAsRead
  //   - storeReminder
  //   - toggleAgentStatus
  //   - updateAgent
//
// Pour utiliser ces méthodes, appelez directement : ApiService().methodName()
// Aucun changement n'est nécessaire dans les écrans existants.

export 'api_service.dart' show ApiService;
