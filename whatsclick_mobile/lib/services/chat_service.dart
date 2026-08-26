// ChatService — Regroupement logique des méthodes chat d'ApiService.
// Les méthodes ci-dessous sont actuellement dans ApiService et accessibles
// via ApiService().methodName(). Ce fichier documente leur appartenance
// sémantique en vue d'une migration progressive.
//
// Méthodes de ce service :
  //   - fetchMessages
  //   - fetchOlderMessages
  //   - fetchUnreadCounts
  //   - sendMediaMessage
  //   - sendMessage
  //   - sendProductMessage
  //   - sendQuickReply
  //   - sendTemplateMessage
  //   - uploadTempMedia
//
// Pour utiliser ces méthodes, appelez directement : ApiService().methodName()
// Aucun changement n'est nécessaire dans les écrans existants.

export 'api_service.dart' show ApiService;
