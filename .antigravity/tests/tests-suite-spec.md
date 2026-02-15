Voici la spécification technique complète pour l'implémentation de la suite de tests automatisés pour **ZenAdmin**. Ce document est structuré pour permettre à ton éditeur IA de configurer l'infrastructure de test et de générer les premiers tests critiques afin de garantir l'absence de régression avant l'intégration de Freemius.

---

# Specification: ZenAdmin — Testing Suite Infrastructure (v1.3)

## 1. Objectif

Mettre en place un environnement de tests automatisés (Unitaires et Intégration) couvrant les fonctionnalités critiques du plugin pour sécuriser les futurs développements et l'intégration de services tiers (Freemius).

## 2. Infrastructure de Test

### 2.1 Backend (PHP)

* **Outil :** PHPUnit (intégré via `wp-browser` ou l'infrastructure de test native WP).
* **Emplacement :** `/tests/phpunit/`.
* **Dépendances :** Mockery (pour simuler les objets WordPress comme `WP_User` ou `wpdb`).

### 2.2 Frontend (JS)

* **Outil :** Jest.
* **Emplacement :** `/tests/js/`.
* **Cible :** `assets/zen-engine.js` et `assets/zen-modal.js`.

### 2.3 Environnement Local (Intégration)

* **Outil :** `wp-env` (Docker).
* **Usage :** Lancer un environnement WordPress éphémère pour les tests d'intégration (E2E/Integration) sans polluer l'installation locale.

---

## 3. Qualité, CI/CD & Structure

### 3.1 Qualité de Code (Linting)

* **PHPCS :** Standards `WordPress-Core` et `WordPress-Extra`.
* **ESLint :** Configuration standard WordPress (`@wordpress/eslint-plugin`).
* **Objectif :** Bloquer les commits si le code ne respecte pas les standards WP.

### 3.2 Structure des Dossiers

* `/tests/phpunit/unit/` : Tests unitaires purs (Mockery, rapides).
* `/tests/phpunit/integration/` : Tests nécessitant WP chargé (lents, via `wp-env`).
* `/tests/js/` : Tests Jest.

### 3.3 CI/CD (GitHub Actions)

* **Workflow :** `.github/workflows/test.yml`
* **Triggers :** `push` sur `main` et `pull_request`.
* **Jobs :**
    1. **Linting :** PHPCS + ESLint.
    2. **Testing :** PHPUnit + Jest.
    3. **Coverage :** Génération du rapport de couverture (Cible : 100% sur `class-portability.php`).

---

## 4. Scénarios de Tests Prioritaires (Logiciel)

### 4.1 Moteur de Sélection (JS)

L'IA doit tester les fonctions de génération de sélecteurs dans `zen-engine.js`.

* **Test de Stabilité :** Vérifier que `generateSelector` rejette les IDs avec plus de 3 chiffres (IDs dynamiques).
* **HREF Strategy :** Simuler un clic sur un lien `<a>` et vérifier que le sélecteur produit contient `a[href*="page=...]`.
* **Non-Propagation :** Vérifier que le clic sur un sous-menu WordPress ne retourne jamais le sélecteur du `<li>` parent.

### 4.2 Role-Based Visibility Engine (PHP)

L'IA doit tester la logique dans `class-core.php`.

* **Validation des Rôles :** Envoyer un rôle inexistant à `validate_roles()` et vérifier qu'il est supprimé du tableau.
* **Injection Conditionnelle :** Simuler un utilisateur avec le rôle `editor` et vérifier que `inject_styles()` n'inclut que les sélecteurs où `hidden_for` contient `editor`.

### 4.3 Hard Blocking & Whitelist (PHP)

Tester la sécurité dans `enforce_hard_blocks()`.

* **Protection Anti-Verrouillage :** Vérifier que la fonction retourne immédiatement si la page courante est `zenadmin` ou `index.php`.
* **Redirection Stricte :** Simuler une visite sur une URL bloquée et vérifier l'appel à `wp_safe_redirect`.

### 4.4 Portability Engine (JSON)

Tester l'intégrité des données dans `class-portability.php`.

* **Sanitization Import :** Soumettre un JSON contenant un champ `label` avec des balises `<script>` et vérifier qu'il est nettoyé par `sanitize_text_field`.
* **Hash Integrity :** Vérifier que l'import recalcule correctement les hashs MD5 des sélecteurs pour éviter les doublons.

---

## 5. Configuration de l'Éditeur IA

### 5.1 Fichiers à générer

1. **`phpunit.xml.dist` :** Configuration de base pour PHPUnit (incluant les suites Unit/Integration).
2. **`package.json` :** Scripts `test:js`, `lint:php`, `lint:js`.
3. **`.github/workflows/test.yml` :** Workflow CI/CD.
4. **`tests/phpunit/unit/test-core.php` :** Tests unitaires Core.
5. **`tests/phpunit/integration/test-portability.php` :** Tests intégration Import/Export.

### 5.2 Exemple de Test Unitaire (IA Roadmap)

```php
/**
 * Test que la whitelist protège bien la page de réglages.
 */
public function test_settings_page_is_whitelisted() {
    $_GET['page'] = 'zenadmin';
    $core = new \ZenAdmin\Core();
    // On vérifie que la redirection ne s'exécute jamais sur cette page
    $this->assertNull($core->enforce_hard_blocks());
}

```

---

## 6. Checklist

* [ ] Créer la structure `/tests/` (unit/integration).
* [ ] Configurer `wp-env` et `.github/workflows/`.
* [ ] Configurer PHPCS et ESLint.
* [ ] Configurer un environnement "Mock" pour ne pas affecter la base de données réelle lors des tests unitaires.
* [ ] Générer une suite de tests couvrant 100% du fichier `class-portability.php` (car c'est le point d'entrée de données externes).
* [ ] Vérifier que les tests échouent si `ZENADMIN_DISABLE` est à `true` (Kill Switch test).