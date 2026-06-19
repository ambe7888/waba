---
description: # MVC & Clean Code Standards - Projet Agence Marketing Digital
---


---

# Guide Complet du Clean Code (PHP)

## 1. Introduction

Le *Clean Code* désigne un ensemble de pratiques visant à produire un code **lisible, maintenable, évolutif et fiable**. Il ne s’agit pas seulement de faire fonctionner le programme, mais de le rendre compréhensible par d’autres (et par vous-même dans six mois).

Ce guide couvre :
- Architecture MVC saine
- Règles de nommage, fonctions, commentaires
- DRY, KISS, YAGNI
- Principes SOLID
- Gestion des erreurs et sécurité
- Refactoring et métriques
- Bonnes pratiques PHP spécifiques (PSR, environnement, tests)

---

## 2. Architecture MVC (Modèle-Vue-Contrôleur)

L’architecture MVC sépare les responsabilités en trois couches distinctes.

### 2.1 Modèle (Model)

**Responsabilités :**
- Accès aux données (requêtes SQL préparées)
- Logique métier liée aux données
- Validation et nettoyage avant insertion

**Bonnes pratiques :**
```php
class UserModel {
    private $db;
    public function __construct(PDO $db) { $this->db = $db; }
    
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function create(array $data): bool {
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email invalide');
        }
        $stmt = $this->db->prepare("INSERT INTO users (email, name) VALUES (?, ?)");
        return $stmt->execute([$data['email'], $data['name']]);
    }
}
```

**À éviter :**  
- Écrire des `echo` ou du HTML  
- Concaténer des chaînes pour les requêtes SQL (risque d’injection)

### 2.2 Vue (View)

**Responsabilités :**  
- Afficher les données reçues du contrôleur  
- Contenir uniquement du HTML et des boucles simples  

**Bonnes pratiques :** toujours échapper les sorties avec `htmlspecialchars`.

```php
<!-- view/user/profile.php -->
<h1><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h1>
```

**À éviter :**  
- Logique métier, calculs complexes, requêtes SQL dans la vue  
- Accès direct à `$_GET` ou `$_POST`

### 2.3 Contrôleur (Controller)

**Responsabilités :**  
- Recevoir la requête HTTP  
- Valider les entrées utilisateur  
- Appeler les modèles / services  
- Transmettre les données à la vue  
- Gérer les redirections et codes HTTP  

```php
class UserController {
    public function show(int $id) {
        $user = $this->userModel->findById($id);
        if (!$user) {
            http_response_code(404);
            return view('errors/404');
        }
        return view('user/profile', ['user' => $user]);
    }
}
```

**À éviter :**  
- Mélanger du SQL ou du HTML  
- Logique métier complexe (déléguer au modèle ou à un service)

---

## 3. Principes fondamentaux du Clean Code

### 3.1 Noms significatifs (intention-révélateur)

| Catégorie                         | ✅ Bon                                | ❌ Mauvais       |
|-----------------------------------|---------------------------------------|------------------|
| Variable                          | `$invoiceTotal`, `$isActive`          | `$t`, `$flag`    |
| Fonction                          | `calculateTotalPrice($items)`         | `calc($x)`       |
| Classe                            | `CustomerRepository`, `EmailService`  | `Helper`, `Util` |
| Constante                         | `const MAX_LOGIN_ATTEMPTS = 5;`       | `5` (magic number) |

### 3.2 Fonctions courtes et mono-responsabilité

Une fonction doit faire **une seule chose** et la faire bien. Ne dépassez pas 20 lignes.

```php
// ❌ trop de responsabilités
function processOrder($order) {
    // validation
    if ($order->total <= 0) throw new Exception();
    // calcul taxes
    $tax = $order->total * 0.2;
    // insertion DB
    $db->insert('orders', ...);
    // envoi email
    mail(...);
}

// ✅ découpage
function validateOrder(Order $order) { ... }
function calculateTax(Order $order) { ... }
function saveOrder(Order $order) { ... }
function sendOrderConfirmation(Order $order) { ... }
```

### 3.3 DRY (Don’t Repeat Yourself)

Factorisez le code dupliqué.

```php
// ❌ répétition
function createUser($data) { $sanitized = htmlspecialchars($data['name']); ... }
function updateUser($data) { $sanitized = htmlspecialchars($data['name']); ... }

// ✅ factorisation
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

### 3.4 KISS (Keep It Simple, Stupid) et YAGNI (You Aren’t Gonna Need It)

- **KISS** : privilégiez la solution la plus simple qui fonctionne.  
- **YAGNI** : n’ajoutez pas de fonctionnalités “au cas où”. Le code inutilisé complique la maintenance.

### 3.5 Commentaires : le “pourquoi”, pas le “quoi”

```php
// ❌ redondant
// incrémente i
$i++;

// ✅ utile : explique une bizarrerie métier
// Les leads du dimanche sont moins convertis (étude marketing 2024)
if (date('w') == 0) { $this->applySundayStrategy($lead); }
```

Préférez des noms clairs plutôt que des commentaires.

---

## 4. Principes SOLID (piliers de la conception objet)

| Principe | Description | Exemple PHP |
|----------|-------------|--------------|
| **S** Single Responsibility | Une classe n’a qu’une raison de changer. | `UserRepository` (accès DB) ≠ `EmailSender` |
| **O** Open/Closed | Ouvert à l’extension, fermé à la modification. | Utiliser des interfaces : `PaymentInterface` implémentée par `CreditCardPayment`, `PayPalPayment` |
| **L** Liskov Substitution | Une sous-classe doit pouvoir remplacer sa classe parente sans altérer le comportement. | Ne pas renforcer les préconditions ou affaiblir les postconditions. |
| **I** Interface Segregation | Préférer plusieurs petites interfaces qu’une grosse. | `Flyable` et `Swimmable` plutôt que `Animal` avec des méthodes vides. |
| **D** Dependency Inversion | Dépendre d’abstractions (interfaces), pas d’implémentations concrètes. | `new Mailer(new SmtpTransport())` (injection) plutôt que `new Mailer()` qui crée son transport. |

**Exemple d’injection de dépendances :**
```php
interface NotificationService {
    public function send(string $to, string $message): void;
}

class EmailNotification implements NotificationService { ... }
class SmsNotification implements NotificationService { ... }

class UserService {
    public function __construct(private NotificationService $notifier) {}
    public function register($user) { ... $this->notifier->send(...); }
}
```

---

## 5. Gestion des erreurs et sécurité

### 5.1 Exceptions personnalisées

```php
class NotFoundException extends Exception {}
class ValidationException extends Exception {}

try {
    $user = $userRepo->find($id) ?? throw new NotFoundException();
} catch (NotFoundException $e) {
    http_response_code(404);
    echo $e->getMessage();
}
```

### 5.2 Sécurité impérative

- **Requêtes préparées** (PDO) systématiquement  
- **Échappement des sorties** : `htmlspecialchars(...)`  
- **Validation stricte** des entrées (type, longueur, format)  
- **Ne jamais faire confiance à `$_GET`, `$_POST`, `$_COOKIE`**

### 5.3 Logging (pas de `var_dump` en production)

Utilisez Monolog ou un système structuré. Logguez les erreurs, pas les variables utilisateur sensibles.

---

## 6. Refactoring : techniques et métriques

### 6.1 Signes de code à refactoriser (“code smells”)

- Méthode trop longue (> 20 lignes)  
- Trop de paramètres (> 3)  
- Classes “god object” qui font tout  
- Duplication (DRY violé)  
- Commentaires “TODO” ou “FIXME” persistants

### 6.2 Techniques de refactoring courantes

| Technique                 | Avant                                          | Après                                                            |
|---------------------------|------------------------------------------------|------------------------------------------------------------------|
| **Extract Method**        | 30 lignes dans une fonction                    | plusieurs petites fonctions                                     |
| **Replace Temp with Query** | `$base = $price * 0.8; $total = $base + $shipping;` | `function getBasePrice() { return $this->price * 0.8; }` |
| **Introduce Parameter Object** | `function createUser($name, $email, $age, $country)` | `function createUser(UserDto $dto)` |
| **Split Loop**             | une boucle qui fait deux choses différentes    | deux boucles séparées                                           |

### 6.3 Métriques de complexité

- **Complexité cyclomatique** : nombre de chemins indépendants. Idéal ≤ 10.  
  Outils : PHPMD, PHPStan, Psalm.  
- **Couverture de tests** : viser 80% pour les parties critiques.

---

## 7. Bonnes pratiques spécifiques PHP

### 7.1 Conventions PSR (PHP Standard Recommendations)

| PSR    | Contenu                                      | Outil d’auto-vérification |
|--------|----------------------------------------------|----------------------------|
| PSR-1  | Règles de base (fichiers, encodage, etc.)    | phpcs                      |
| PSR-12 | Style de codage (indentation, accolades, etc.) | php-cs-fixer              |
| PSR-4  | Autoloading (namespace → répertoire)          | Composer                   |

### 7.2 Documentation avec phpDoc

```php
/**
 * Calcule le montant total avec remise.
 *
 * @param float $price Prix unitaire
 * @param int $quantity Quantité
 * @param float $discount Remise en pourcentage (0-100)
 * @return float Total après remise
 * @throws InvalidArgumentException Si discount <0 ou >100
 */
function calculateTotal(float $price, int $quantity, float $discount): float { ... }
```

### 7.3 Variables d’environnement (pas de config en dur)

Utilisez `getenv()` ou `$_ENV` avec un fichier `.env` (via `vlucas/phpdotenv`).

```php
$dbHost = getenv('DB_HOST') ?: 'localhost'; // valeur par défaut
```

**Ne jamais commiter** les secrets (API keys, mots de passe) dans le code source.

### 7.4 Structure de projet recommandée

```
project/
├── src/
│   ├── Controller/
│   ├── Model/
│   ├── Service/
│   ├── Repository/
│   ├── View/
│   └── Helper/
├── public/
│   └── index.php
├── config/
│   └── config.php
├── tests/
├── .env
├── composer.json
└── README.md
```

---

## 8. Testabilité et tests automatiques

Un code propre est **testable**. Évitez les dépendances cachées (singletons, appels statiques).

**Exemple testable :**
```php
class PriceCalculator {
    public function __construct(private TaxCalculator $taxCalculator) {}
    public function compute($price) { ... }
}
// Dans le test : new PriceCalculator($mockTaxCalculator)
```

Types de tests :
- **Unitaires** (PHPUnit) : valider une méthode isolée  
- **Intégration** : base de données, API externes  
- **Fonctionnels** : parcours utilisateur (ex. Symfony Panther)

---

## 9. Checklist de qualité Clean Code (à passer avant livraison)

- [ ] Les noms de variables/fonctions/classes sont explicites  
- [ ] Aucune fonction ne dépasse 20 lignes  
- [ ] Aucune classe n’a plus de 200 lignes (sauf exception justifiée)  
- [ ] DRY respecté : pas de duplication >3 lignes  
- [ ] Toutes les requêtes SQL sont préparées (PDO)  
- [ ] Toutes les sorties HTML sont échappées  
- [ ] Les constantes magiques sont remplacées par des constantes nommées  
- [ ] Les exceptions sont capturées et gérées (pas de `die()` ou `exit`)  
- [ ] Le code respecte PSR-12 (vérifié avec php-cs-fixer)  
- [ ] Aucun commentaire redondant ; les “pourquoi” sont expliqués  
- [ ] Les dépendances sont injectées (pas de `new` à l’intérieur des classes)  
- [ ] Des tests unitaires couvrent au moins les parties critiques  
- [ ] Le fichier `.env` n’est pas versionné (seul `.env.example` l’est)  
- [ ] Le code passe l’analyse statique (PHPStan niveau 5 ou plus)

---

## 10. Ressources et outils

| Outil      