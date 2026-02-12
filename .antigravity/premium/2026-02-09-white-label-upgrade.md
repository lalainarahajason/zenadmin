Voici la spécification technique complète et définitive pour le module **ZenAdmin White Label (Pro)**. Ce document fusionne toutes les fonctionnalités de personnalisation, de discrétion et de sécurité discutées.

---

# Specification: ZenAdmin White Label Module (Complete Edition)

## 1. Project Overview

Le module **White Label** est une extension "Pro" de ZenAdmin permettant aux agences de rebrander totalement l'interface WordPress et de masquer l'existence même du plugin ZenAdmin pour offrir une expérience "Logiciel sur mesure" à leurs clients.

## 2. Data Structure & Persistence

Tous les réglages doivent être stockés dans une seule option `wp_options` nommée `zenadmin_white_label` sous forme de tableau associatif sérialisé (JSON).

---

## 3. Detailed Fields & Features

### Section A: Identité du Plugin (Global Rebranding)

* **`wl_plugin_name`** (String) : Remplace "ZenAdmin" dans la liste des extensions et le menu.
* **`wl_plugin_desc`** (Textarea) : Remplace la description du plugin.
* **`wl_agency_name`** (String) : Nom de l'agence (remplace l'auteur du plugin).
* **`wl_agency_url`** (URL) : Lien vers le site de l'agence.
* **`wl_menu_icon`** (Media/SVG) : Icône personnalisée pour l'entrée du menu latéral.
* **`wl_stealth_mode`** (Boolean) : Si activé, le plugin est invisible dans `plugins.php` pour tous les rôles sauf `Super Admin`.

### Section B: Page de Connexion (Login Branding)

* **`wl_login_logo`** (Media URL) : Image remplaçant le logo WordPress (via hook `login_enqueue_scripts`).
* **`wl_login_logo_url`** (URL) : Lien du logo redirigeant vers le site de l'agence.
* **`wl_login_bg_color`** (Hex Color) : Couleur de fond de la page de connexion.
* **`wl_login_btn_color`** (Hex Color) : Couleur du bouton de soumission et des accents.

### Section C: Interface & Workspace (Admin Clean-up)

* **`wl_footer_text`** (HTML/String) : Texte personnalisé à gauche dans le pied de page (filtre `admin_footer_text`).
* **`wl_hide_wp_version`** (Boolean) : Masque la version de WordPress en bas à droite (filtre `update_footer`).
* **`wl_hide_wp_logo`** (Boolean) : Supprime le logo WordPress dans la barre d'administration (Admin Bar).
* **`wl_hide_updates`** (Boolean) : Masque les bulles de notifications de mise à jour (Core/Plugins) pour les rôles ciblés.

### Section D: Tableau de Bord (Dashboard Control)

* **`wl_dashboard_reset`** (Boolean) : Supprime tous les widgets natifs de WordPress via `wp_dashboard_setup`.
* **`wl_welcome_title`** (String) : Titre du widget de support personnalisé de l'agence.
* **`wl_welcome_content`** (WYSIWYG/HTML) : Contenu (liens de support, vidéos, coordonnées) du widget de bienvenue.

### Section E: Accès & Sécurité (Hard Blocking)

* **`wl_hard_block_pages`** (Array/Textarea) : Liste des slugs de fichiers à bloquer (ex: `options-general.php`, `tools.php`).
* **`wl_redirect_dest`** (URL) : Destination de redirection en cas de tentative d'accès à une page bloquée.
* **`wl_applied_roles`** (Array) : Liste des rôles qui subissent ces restrictions (ex: `editor`, `author`). **L'administrateur principal doit pouvoir être exclu.**

---

## 4. Technical Logic & Hooks

### 4.1. Stealth & Metadata (PHP)

Utiliser le filtre `all_plugins` pour modifier l'affichage des informations du plugin :

```php
add_filter('all_plugins', 'zenadmin_apply_white_label_metadata');

```

### 4.2. Hard Blocking (Security)

Utiliser le hook `admin_init` pour intercepter l'accès aux pages interdites :

```php
add_action('admin_init', function() {
    global $pagenow;
    // Si page dans wl_hard_block_pages ET rôle dans wl_applied_roles
    // Alors wp_redirect(wl_redirect_dest)
});

```

### 4.3. Login Overrides (CSS)

Injecter les styles via `login_head` pour remplacer le logo et les couleurs :

```php
add_action('login_head', 'zenadmin_apply_login_branding');

```

---

## 5. Security Guardrails

* **Emergency Kill-Switch :** Le module doit vérifier l'existence de la constante `define('ZENADMIN_WHITE_LABEL', false);` dans le `wp-config.php`. Si elle est à `false`, tous les réglages de Marque Blanche sont ignorés pour permettre un dépannage.
* **Capability Check :** Seuls les utilisateurs ayant la capacité `manage_options` (ou `manage_network` en multisite) peuvent modifier ces champs.
* **Sanitization :** Chaque champ HTML (`wl_welcome_content`, `wl_footer_text`) doit être passé par `wp_kses_post()` avant affichage.

---

## 6. UI/UX Standard

L'interface de réglages doit être construite avec les classes WordPress natives (`.form-table`, `.regular-text`). Les onglets de navigation doivent permettre de séparer les sections A, B, C, D et E pour éviter de surcharger l'utilisateur.