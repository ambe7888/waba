---
description: Sécurité des Applications Web - Projet Agence Marketing Digital
---



---

# Sécurité des Applications Web – Projet Agence Marketing Digital (version complétée)

## OWASP Top 10 2025 – Les 10 Risques de Sécurité les plus Critiques

### 1. **Broken Access Control**
Authentification et autorisation mal gérées.

**Risques :**
- Utilisateurs accèdent aux ressources qu’ils ne devraient pas.
- Accès aux routes `/admin` sans vérification de rôle.
- Accès aux données d’autres utilisateurs (escalade horizontale).
- Escalade verticale via modification de paramètres (mass assignment).

**Protection :**
```php
// ✅ BON - Vérifier l'authentification ET l'autorisation
public function editService($id) {
    if (!Auth::isLoggedIn()) {
        redirect('/login');
    }
    
    if (!Auth::hasRole('admin')) {
        http_response_code(403);
        die('Accès refusé');
    }
    
    $service = $this->serviceModel->getById($id);
    if (!$service) {
        http_response_code(404);
        return;
    }
    
    // Vérifier aussi que l'utilisateur est bien propriétaire si rôle = 'editor'
    if (Auth::getRole() === 'editor' && $service['user_id'] !== Auth::getId()) {
        http_response_code(403);
        die('Accès refusé');
    }
    
    return view('services/edit', ['service' => $service]);
}

// ❌ MAUVAIS - Pas de vérification
public function editService($id) {
    $service = $this->serviceModel->getById($id);
    return view('services/edit', ['service' => $service]);
}
```

**Protection contre le mass assignment :**
```php
// ✅ BON - N'accepter que les champs autorisés
$allowed = ['title', 'description', 'price'];
$data = array_intersect_key($_POST, array_flip($allowed));
$this->serviceModel->update($id, $data);

// ❌ MAUVAIS - Accepter tous les champs envoyés par le client
$this->serviceModel->update($id, $_POST); // un attaquant peut définir 'is_admin' => 1
```

### 2. **Cryptographic Failures**
Données sensibles mal protégées.

**Risques :**
- Mots de passe stockés en clair.
- Absence de HTTPS.
- Secrets (clés API, tokens) en dur dans le code.
- Données sensibles en cache navigateur ou serveur.

**Protection :**
```php
// ✅ BON - Mots de passe hashés avec bcrypt (cost >= 10)
$hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 10]);

if (password_verify($_POST['password'], $user['password'])) {
    // Connexion réussie
}

// ✅ BON - HTTPS obligatoire avec redirection
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

// ✅ BON - Secrets dans des variables d'environnement (fichier .env non versionné)
$apiKey = $_ENV['OPENAI_API_KEY'];

// ❌ MAUVAIS - Mot de passe en clair
$password = $_POST['password']; // Stockage direct
```

### 3. **Injection Attacks (SQL, XSS, Command)**
Insertion malveillante de code ou de commandes.

**Risques :**
- Injections SQL (vol/suppression de données).
- Cross-Site Scripting (XSS) – vol de session, défiguration.
- Injection de commandes système.
- Injection dans les en-têtes HTTP (header injection).

**Protection SQL :**
```php
// ✅ BON - Requêtes préparées avec PDO
$stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND active = ?");
$stmt->execute([$email, true]);
$user = $stmt->fetch();

// ❌ MAUVAIS - Injection SQL
$query = "SELECT * FROM users WHERE email = '" . $_GET['email'] . "'";
```

**Protection XSS :**
```php
// ✅ BON - Échappement des sorties HTML
<h3><?php echo htmlspecialchars($service['title'], ENT_QUOTES, 'UTF-8'); ?></h3>

// Pour JavaScript
<script>
  const data = <?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

// ❌ MAUVAIS - Pas d'échappement
<h3><?php echo $service['title']; ?></h3> <!-- Dangereux ! -->
```

### 4. **Insecure Design**
Architecture de sécurité insuffisante.

**Risques :**
- Validation uniquement côté client.
- Absence de rate limiting (brute force, déni de service).
- Gestion de session non sécurisée.
- Flux métiers non validés (ex. réinitialisation de mot de passe).

**Protection :**
```php
// ✅ BON - Validation côté serveur OBLIGATOIRE
public function createLead($data) {
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new ValidationException('Email invalide');
    }
    
    // Rate limiting
    if (RateLimiter::isLimited($_SERVER['REMOTE_ADDR'], 'login', 5, 300)) {
        http_response_code(429);
        die('Trop de tentatives. Réessayez dans 5 minutes.');
    }
    // ...
}

// ✅ BON - Configuration sécurisée des sessions
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict'
]);
```

### 5. **Security Misconfiguration**
Mauvaise configuration du serveur ou de l’application.

**Risques :**
- Messages d’erreur détaillés exposés aux utilisateurs.
- En-têtes de sécurité HTTP absents.
- Dépendances obsolètes.
- Fichiers sensibles accessibles (.env, .git, composer.json, logs).
- Méthodes HTTP inutiles activées (PUT, DELETE, TRACE).

**Protection :**
```php
// ✅ BON - En-têtes de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' https://trusted-cdn.com; style-src \'self\' \'unsafe-inline\'; img-src * data:;');

// ✅ BON - Masquer les erreurs en production
if (($_ENV['DEBUG'] ?? false) === false) {
    error_reporting(0);
    ini_set('display_errors', 0);
    error_log($errorMessage, 3, '/var/log/app.log');
}
```

**Configuration serveur (Apache) :**
```apache
# Désactiver l'indexation des répertoires
Options -Indexes

# Bloquer l'accès aux fichiers sensibles
<FilesMatch "(^\.env|composer\.(json|lock)|\.git|\.htaccess)">
    Require all denied
</FilesMatch>

# Limiter les méthodes HTTP
<LimitExcept GET POST HEAD>
    Deny from all
</LimitExcept>
```

### 6. **Vulnerable and Outdated Components**
Utilisation de bibliothèques/frameworks obsolètes.

**Protection :**
```bash
# Vérifier les dépendances vulnérables
composer audit
npm audit

# Mettre à jour régulièrement
composer update
npm update

# Automatiser avec Dependabot / Snyk dans la CI/CD
```

### 7. **Identification and Authentication Failures**
Authentification faible ou cassée.

**Risques :**
- Mots de passe par défaut non changés.
- Absence d’authentification multi-facteurs (2FA).
- Sessions mal gérées (fixation, expiration).
- Énumération d’utilisateurs possible.
- Flux de réinitialisation de mot de passe non sécurisé.

**Protection – Login et 2FA :**
```php
// ✅ BON - Authentification multi-facteurs
if (!password_verify($_POST['password'], $user['password'])) {
    // Message générique, sans préciser si l'email existe
    die('Identifiants invalides');
}

// Éviter l'énumération : toujours simuler un temps de traitement constant
// En cas d'email inexistant, on peut hasher un faux mot de passe avec le même coût
if (!$user) {
    password_verify('dummy', '$2y$10$...'); // consommation de temps identique
    die('Identifiants invalides');
}

// 2FA
$code = random_int(100000, 999999);
$this->mailer->send($user['email'], 'Code 2FA: ' . $code);
```

**Protection – Réinitialisation de mot de passe :**
```php
// ✅ BON - Token unique, haché, avec expiration
public function requestReset($email) {
    // Message homogène, même si l'email n'existe pas
    $message = "Si un compte existe, un lien de réinitialisation a été envoyé.";
    $user = $this->userModel->getByEmail($email);
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);
        // Stocker hashedToken + expiration (15 min) en base
        $this->userModel->storeResetToken($user['id'], $hashedToken, time() + 900);
        // Envoyer le lien avec le token brut (pas le hashé)
        $this->mailer->send($email, 'Réinitialisation : https://site.com/reset?token=' . $token);
    }
    die($message);
}

public function resetPassword($token, $newPassword) {
    $hashedToken = hash('sha256', $token);
    $user = $this->userModel->getByResetToken($hashedToken);
    if (!$user || $user['token_expiry'] < time()) {
        die('Lien invalide ou expiré.');
    }
    // Mettre à jour le mot de passe et supprimer le token
    $this->userModel->updatePassword($user['id'], password_hash($newPassword, PASSWORD_BCRYPT));
    $this->userModel->clearResetToken($user['id']);
    // Régénérer l'ID de session pour éviter la fixation
    session_regenerate_id(true);
}
```

### 8. **Software and Data Integrity Failures**
Intégrité du logiciel ou des données compromise.

**Risques :**
- Mises à jour malveillantes.
- Données corrompues en transit ou au stockage.
- Utilisation de CDN tiers sans vérification d’intégrité.

**Protection :**
```php
// ✅ BON - Vérifier l'intégrité des téléchargements
$checksum = hash('sha256', file_get_contents($file));
if ($checksum !== $_ENV['EXPECTED_CHECKSUM']) {
    die('Fichier corrompu ou modifié');
}

// Utilisation d'un integrity hash pour les ressources CDN
// <script src="https://cdn.example.com/lib.js" integrity="sha384-..."></script>
```

### 9. **Logging and Monitoring Failures**
Manque de logs et de surveillance.

**Risques :**
- Attaques non détectées.
- Impossibilité de tracer une intrusion.
- Pas d’alertes sur les événements anormaux.

**Protection :**
```php
// ✅ BON - Logs d'audit structurés
function log_action($action, $user_id, $details) {
    $log = [
        'timestamp' => date('Y-m-d\TH:i:sP'),
        'action' => $action,
        'user_id' => $user_id,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    error_log(json_encode($log), 3, '/var/log/audit.log');
}

// Exemples
log_action('login_success', $user['id'], 'Login depuis IP ' . $_SERVER['REMOTE_ADDR']);
log_action('password_reset_request', 0, 'Email: ' . $email); // user_id 0 si non identifié
log_action('admin_lead_export', Auth::getId(), 'Export de tous les leads');

// Surveiller les logs avec un outil (ex. ELK, Graylog) et configurer des alertes
```

### 10. **Server-Side Request Forgery (SSRF)**
Le serveur effectue des requêtes vers des ressources internes non autorisées.

**Risques :**
- Accès à des services internes (bases de données, metadata cloud).
- Contournement de pare-feu via le serveur web.

**Protection :**
```php
// ✅ BON - Valider et restreindre les URLs cibles
function isSafeUrl($url) {
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host'])) return false;
    
    // Bloquer les adresses locales et privées
    $host = $parsed['host'];
    if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'])) return false;
    if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false; // IP privée ou réservée
    }
    
    // Optionnel : restreindre les schémas à http/https
    if (!in_array($parsed['scheme'] ?? 'https', ['http', 'https'])) return false;
    
    return true;
}

// Validation des redirections avec la même logique
if (isset($_GET['redirect']) && isSafeUrl($_GET['redirect'])) {
    header('Location: ' . $_GET['redirect']);
    exit;
}
```

---

## Pratiques de Sécurité Complémentaires – Spécifiques Agence Marketing

### 1. **Gestion Sécurisée des Fichiers Uploadés**
Les agences reçoivent des visuels, briefs, logos, etc. Un upload mal maîtrisé peut compromettre le serveur.

```php
// ✅ BON - Upload sécurisé d'images
function uploadImage($file) {
    $maxSize = 5 * 1024 * 1024; // 5 Mo
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('Er