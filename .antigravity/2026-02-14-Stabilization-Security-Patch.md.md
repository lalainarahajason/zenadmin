# Specification: ZenAdmin — Stabilization & Security Patch (v1.1)

**Date :** 14 Février 2026
**Priorité :** CRITIQUE (Bloquant pour la release)
**Objectif :** Corriger les failles de sécurité (Hard Block), stabiliser le moteur de sélection (Menus) et améliorer l'UX (Preview) pour transformer le MVP en produit production-ready.

## 1. Refonte du "Hard Blocking" (Sécurité & Performance)

### Problème

L'implémentation actuelle utilise `strpos` sur `$_SERVER['REQUEST_URI']`.

* **Risque :** Faux positifs (ex: bloquer `settings.php` bloque aussi `settings.php?page=ok`).
* **Performance :** Boucle sur toute la blacklist à chaque chargement de page admin.

### Spécifications Techniques (`class-core.php`)

#### 1.1 Séparation des Règles

Au lieu d'itérer sur toute la `blacklist`, le plugin doit extraire les règles "Hard Block" lors de la sauvegarde et les stocker (ou les filtrer efficacement).

#### 1.2 Logique de Comparaison Stricte

Remplacer la détection par URI brute par une analyse des composants de l'URL WordPress.

**Algorithme `enforce_hard_blocks` :**

1. Récupérer le tableau des règles.
2. Pour chaque règle marquée `hard_block = true` :
* **Parsing :** Décomposer l'URL cible (ex: `options-general.php?page=my-plugin`).
* **Check Fichier :** Comparer avec la globale `$pagenow`.
* **Check Paramètres :** Si l'URL cible a des paramètres (ex: `page`, `tab`), vérifier qu'ils sont présents et identiques dans `$_GET`.
* *Exemple :*
* Règle : `admin.php?page=elementor`
* Visite : `admin.php?page=elementor-connect` -> **PASS** (Ne pas bloquer).
* Visite : `admin.php?page=elementor` -> **BLOCK**.





#### 1.3 Whitelist Hardcodée (Safety Net)

Interdire strictement le Hard Block sur :

* `options-general.php?page=zenadmin` (Réglages du plugin)
* `index.php` (Dashboard, sauf si configuré explicitement via White Label)

---

## 2. Robustesse du Ciblage Menu (UX & Structure)

### Problème

Cibler un `<li>` parent dans `#adminmenu` (ex: `li.wp-has-submenu`) masque tous les enfants, rendant le menu inaccessible involontairement.

### Spécifications Techniques (`zen-engine.js`)

#### 2.1 Règle de Non-Propagation (Surgical Targeting)

Si l'utilisateur clique sur un élément dans `#adminmenu` :

1. **Détection Parent :** Vérifier si l'élément cliqué (ou son parent) est un `li.wp-has-submenu`.
2. **Forçage de l'Ancre :** Le moteur ne doit **jamais** sélectionner le `li` parent. Il doit automatiquement descendre et sélectionner la balise `<a>` correspondante (le lien principal du menu).
* *Sélecteur généré :* `#adminmenu li#menu-posts a.menu-top` (au lieu de `#adminmenu li#menu-posts`).



#### 2.2 Avertissement Interactif

Si le moteur détecte que le sélecteur va masquer un conteneur parent (`.wp-has-submenu`, `.wrap`, `.wp-list-table`), le modal doit afficher un avertissement jaune :

> "⚠️ Attention : Vous ciblez un élément parent. Cela masquera tout son contenu (sous-menus ou réglages)."

---

## 3. Amélioration UX : Preview & Copywriting

### Problème

Le bouton "Block Forever" est anxiogène. L'utilisateur ne voit pas le résultat avant de s'engager.

### Spécifications Techniques (`zen-modal.js`)

#### 3.1 Mode Aperçu (Preview)

Ajouter un bouton **"Preview"** (icône œil) dans le modal.

* **Action :** Applique temporairement `display: none !important` via style inline sur l'élément ciblé dans le DOM actuel.
* **Toggle :** Un second clic réaffiche l'élément.
* **Annulation :** Si l'utilisateur clique sur "Annuler", le style inline doit être retiré.

#### 3.2 Wording

* Remplacer "Block Forever" par **"Hide Element"** (Masquer l'élément).
* Remplacer "Session Only" par **"Hide temporarily (Reload to reset)"**.

---

## 4. Sécurité des Rôles & Données

### Problème

`sanitize_key` corrompt les rôles personnalisés (ex: rôles avec majuscules ou caractères spéciaux créés par d'autres plugins). Faible vérification des droits.

### Spécifications Techniques (`class-core.php`)

#### 4.1 Validation Stricte des Rôles

Dans `ajax_save_block` et `ajax_update_block_roles` :

* Ne pas utiliser `sanitize_key` aveuglément.
* **Validation :**
```php
global $wp_roles;
if ( ! $wp_roles->is_role( $role_slug ) ) {
    // Ignorer ou rejeter
}

```

#### 4.2 Verrouillage des Réglages (Client Proofing)

Ajouter le support de la constante `ZENADMIN_LOCK_SETTINGS`.

* Si `true` :
* Masquer les boutons "Delete", "Edit Roles" et le formulaire d'ajout manuel.
* Afficher une notice : *"Les réglages ZenAdmin sont verrouillés par l'administrateur."*
* Refuser toute requête AJAX de modification (`save`, `delete`).

## 5. Détection Gutenberg (React/FSE)

### Problème

Le DOM de l'éditeur de blocs est dynamique. Les sélecteurs CSS statiques sont instables.

### Spécifications Techniques (`zen-engine.js`)

#### 5.1 Détection de Contexte

Au chargement de `Engine.init()` :

* Vérifier si `document.body.classList.contains('block-editor-page')` ou `.iframe-editor`.

#### 5.2 Avertissement "Experimental"

Si l'utilisateur active le Zen Mode dans ce contexte :

* Afficher un Toast (Notification) : *"ZenAdmin : Le masquage dans l'éditeur de blocs est expérimental et peut être annulé par WordPress."*

## 6. Code Quality & Standards

### Problème

Autoloader archaïque et structure hybride.

### Spécifications Techniques

#### 6.1 Modernisation (`zenadmin.php`)

* Nettoyer la fonction `spl_autoload_register`.
* Standardiser le nommage des fichiers si nécessaire pour correspondre strictement au standard WP (`class-nom-classe.php`).

#### 6.2 Sécurisation AJAX (IDOR)

* S'assurer que `check_ajax_referer` est présent sur **toutes** les actions.
* Vérifier `current_user_can('manage_options')` systématiquement.