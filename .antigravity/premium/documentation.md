Voici une spécification détaillée pour l'implémentation de l'onglet **Documentation** au sein de l'interface d'administration de ZenAdmin.

---

# Specification: ZenAdmin — Documentation Tab (Pro/Admin)

## 1. Objectif du Module

Fournir une ressource centralisée, accessible et visuelle pour éduquer les administrateurs et les agences sur l'utilisation optimale de ZenAdmin, réduisant ainsi le besoin de support technique externe.

## 2. Accès et Sécurité

* **Capacité requise :** `manage_options` (identique aux autres onglets de gestion).
* **Visibilité :** Visible uniquement via l'onglet `help` ou un nouvel onglet dédié `documentation` dans la page des réglages `ZenAdmin`.
* **Kill Switch :** Si `ZENADMIN_LOCK_SETTINGS` est actif, la documentation reste accessible mais les liens vers les outils de modification sont masqués.

## 3. Structure de l'Interface (UI)

L'interface doit respecter les standards natifs de WordPress pour une intégration fluide.

### 3.1. Navigation Latérale (Sidebar Layout)

Utiliser une mise en page avec une barre latérale de navigation pour parcourir les sections :

* **Getting Started :** Activation du Zen Mode et premier blocage.
* **Advanced Selection :** Comprendre la stratégie HREF et les IDs.
* **Role Management :** Configurer la visibilité sélective.
* **White Label :** Guide de personnalisation pour les agences.
* **Troubleshooting :** Safe Mode et Kill Switch.

### 3.2. Composants Visuels

* **Alertes :** Utiliser les classes `.notice.notice-info` pour les conseils de pro.
* **Code Snippets :** Blocs `<code>` pour illustrer les sélecteurs générés.
* **Dashicons :** Utilisation d'icônes pour faciliter la lecture (ex: `dashicons-visibility`, `dashicons-shield`).

---

## 4. Contenu Détaillé (Data Map)

### 4.1. Guide du "Zen Mode"

* **Activation :** Expliquer l'utilisation du bouton dans la barre d'administration.
* **Interaction :** Décrire l'effet du survol (bordure violette) et du clic pour ouvrir le modal.
* **Preview :** Comment tester un masquage avant validation sans recharger la page.

### 4.2. Stratégies de Ciblage (AS1.1)

Expliquer pourquoi ZenAdmin est plus robuste que ses concurrents :

* **HREF Strategy :** Ciblage des liens pour une stabilité maximale lors des mises à jour de plugins tiers.
* **Hard Blocking :** Distinction entre le masquage CSS et la restriction d'accès aux fichiers PHP.

### 4.3. Sécurité et Urgence (AS2)

* **Safe Mode :** Expliquer l'accès via l'URL `?zenadmin_safe_mode=1` en cas de blocage accidentel du menu.
* **Constantes PHP :** Documenter l'usage de `ZENADMIN_DISABLE` et `ZENADMIN_LOCK_SETTINGS` dans le fichier `wp-config.php`.

---

## 5. Implémentation Technique

### 5.1. Fichier `class-settings.php`

Ajouter le nouvel onglet dans la méthode `render_page()` et créer la méthode `render_documentation_tab()`.

### 5.2. Localisation (I18N)

Toutes les chaînes de caractères de la documentation doivent être passées par les fonctions de traduction `__()` ou `_e()` avec le domaine `zenadmin`.

---

## 6. Checklist pour l'Éditeur IA

* [ ] Créer la fonction `render_documentation_tab()` dans `includes/class-settings.php`.
* [ ] Ajouter l'onglet "Documentation" dans la navigation `nav-tab-wrapper`.
* [ ] Intégrer des ancres de liens pour un accès direct aux sections (ex: `tab=documentation#safemode`).
* [ ] Inclure un widget "Support Agence" si le White Label est activé.
