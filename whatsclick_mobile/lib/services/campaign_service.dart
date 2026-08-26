// CampaignService — Regroupement logique des méthodes campaign d'ApiService.
// Les méthodes ci-dessous sont actuellement dans ApiService et accessibles
// via ApiService().methodName(). Ce fichier documente leur appartenance
// sémantique en vue d'une migration progressive.
//
// Méthodes de ce service :
  //   - archiveCampaign
  //   - createAudience
  //   - createCampaign
  //   - createDripCampaign
  //   - createTemplate
  //   - deleteDripCampaign
  //   - deleteDripCampaignStep
  //   - deleteTemplate
  //   - fetchAllLabels
  //   - fetchAllTemplates
  //   - fetchAudiences
  //   - fetchCampaignContacts
  //   - fetchCampaignDashboard
  //   - fetchCampaigns
  //   - fetchDripCampaignDetail
  //   - fetchDripCampaigns
  //   - fetchEligible24hContacts
  //   - fetchNonTemplateMessagePresets
  //   - fetchTemplates
  //   - fetchWhatsAppEmbeddedSignupUrl
  //   - scheduleCampaign
  //   - storeDripCampaignStep
  //   - syncTemplates
  //   - toggleDripCampaign
  //   - updateDripCampaignStep
//
// Pour utiliser ces méthodes, appelez directement : ApiService().methodName()
// Aucun changement n'est nécessaire dans les écrans existants.

export 'api_service.dart' show ApiService;
