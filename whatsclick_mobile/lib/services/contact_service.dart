// ContactService — Regroupement logique des méthodes contact d'ApiService.
// Les méthodes ci-dessous sont actuellement dans ApiService et accessibles
// via ApiService().methodName(). Ce fichier documente leur appartenance
// sémantique en vue d'une migration progressive.
//
// Méthodes de ce service :
  //   - assignContactLabels
  //   - assignContactUser
  //   - assignGroupsToContact
  //   - blockContact
  //   - createContact
  //   - createContactGroup
  //   - createContactLabel
  //   - deleteContact
  //   - deleteContactGroup
  //   - fetchAllContactsSimple
  //   - fetchContactDetails
  //   - fetchContactGroups
  //   - fetchContactLabelsWithCounts
  //   - fetchContacts
  //   - fetchGroupContacts
  //   - fetchLabelsAndAgents
  //   - fetchSimpleContactsList
  //   - unassignGroupsFromContact
  //   - unblockContact
  //   - updateContact
  //   - updateContactDetails
  //   - updateContactNotes
//
// Pour utiliser ces méthodes, appelez directement : ApiService().methodName()
// Aucun changement n'est nécessaire dans les écrans existants.

export 'api_service.dart' show ApiService;
