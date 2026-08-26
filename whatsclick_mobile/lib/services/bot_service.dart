// BotService — Regroupement logique des méthodes bot d'ApiService.
// Les méthodes ci-dessous sont actuellement dans ApiService et accessibles
// via ApiService().methodName(). Ce fichier documente leur appartenance
// sémantique en vue d'une migration progressive.
//
// Méthodes de ce service :
  //   - createBotReply
  //   - deleteBotReply
  //   - deleteCannedReply
  //   - fetchAiSettings
  //   - fetchBotActionSupportData
  //   - fetchBotReplies
  //   - fetchCannedReplies
  //   - fetchQuickReplies
  //   - saveAiSettings
  //   - saveCannedReply
  //   - toggleBotReply
  //   - toggleBotReplyStatus
  //   - updateBotReply
//
// Pour utiliser ces méthodes, appelez directement : ApiService().methodName()
// Aucun changement n'est nécessaire dans les écrans existants.

export 'api_service.dart' show ApiService;
