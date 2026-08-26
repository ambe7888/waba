// ShopService — Regroupement logique des méthodes shop d'ApiService.
// Les méthodes ci-dessous sont actuellement dans ApiService et accessibles
// via ApiService().methodName(). Ce fichier documente leur appartenance
// sémantique en vue d'une migration progressive.
//
// Méthodes de ce service :
  //   - addCategory
  //   - addProduct
  //   - createManualOrder
  //   - deleteCategory
  //   - deleteOrder
  //   - deleteProduct
  //   - fetchAllOrders
  //   - fetchCategories
  //   - fetchContactOrders
  //   - fetchProducts
  //   - fetchShopSettings
  //   - saveShopSettings
  //   - syncProducts
  //   - updateOrderStatus
//
// Pour utiliser ces méthodes, appelez directement : ApiService().methodName()
// Aucun changement n'est nécessaire dans les écrans existants.

export 'api_service.dart' show ApiService;
