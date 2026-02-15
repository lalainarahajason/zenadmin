Voici la spécification technique complète et détaillée pour l'implémentation du **Customizer de Couleurs d'Administration** et la mise à jour de l'onglet 
---

# Specification: ZenAdmin — Admin Branding & Help System (v1.2)

## 1. Objectif du Module

Étendre les capacités de la **Marque Blanche** pour inclure le "skinning" de l'interface d'administration WordPress et intégrer un système d'aide complet pour l'utilisateur final et l'agence.

## 2. Admin Color Customizer (White Label Pro)

### 2.1 Nouveaux Champs de Réglages (`includes/class-settings.php`)

Ajouter les champs suivants dans la section "White Label" :

* **`wl_admin_primary`** (Color Picker) : Remplace la couleur de base de WordPress (bleu par défaut).
* **`wl_admin_sidebar_bg`** (Color Picker) : Définit la couleur de fond du menu latéral.
* **`wl_admin_accent`** (Color Picker) : Définit la couleur de l'élément de menu actif et des notifications.

### 2.2 Logique d'Injection CSS (`includes/class-white-label.php`)

Créer une méthode `inject_admin_colors()` appelée sur le hook `admin_head`.

**Sélecteurs cibles prioritaires :**

* **Barre Latérale :** `#adminmenu, #adminmenu .wp-submenu, #adminmenuback, #adminmenuwrap`.
* **Élément Actif :** `#adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, #adminmenu .wp-submenu li.current a`.
* **Boutons & Liens :** `.wp-core-ui .button-primary`, `a`.

**Exemple de calcul de contraste (IA Logic) :**
Si `wl_admin_sidebar_bg` est clair (luminance > 0.6), forcer les textes de menu (`#adminmenu a`) en noir (#1d2327). Sinon, les garder en blanc.

---

## 3. Système de Documentation Interne

### 3.1 Architecture de l'Onglet `Documentation`

L'onglet doit être structuré en deux colonnes : une barre latérale de navigation par ancres et une zone de contenu.

### 3.2 Sections de Contenu (Data Map)

#### A. Le Zen Mode (Sélection Visuelle)

* **Activation :** Expliquer l'usage du bouton "Toggle Zen Mode" dans la barre d'outils.
* **Sélecteurs :** Préciser que ZenAdmin utilise une stratégie de ciblage par URL (HREF) pour éviter que les éléments ne réapparaissent après une mise à jour de plugin.

#### B. Sécurité & Urgence

* **Safe Mode :** Expliquer l'accès de secours via `?zenadmin_safe_mode=1` ou via l'onglet Help pour restaurer l'accès en cas de blocage critique.
* **Kill Switch :** Documenter l'usage de `define('ZENADMIN_DISABLE', true);` dans le fichier `wp-config.php`.

#### C. Marque Blanche & Couleurs (Pro)

* **Rebranding :** Guide pour masquer ZenAdmin de la liste des plugins (Stealth Mode) et personnaliser l'écran de connexion.
* **Hard Blocking :** Expliquer comment interdire l'accès réel à une page PHP, et non pas seulement la masquer visuellement.

---

## 4. Schéma de Données Mis à Jour (JSON)

```json
{
  "white_label": {
    "enabled": true,
    "colors": {
      "admin_primary": "#6e58ff",
      "admin_sidebar_bg": "#1d2327",
      "admin_accent": "#6e58ff"
    },
    "login": {
      "logo": "url",
      "bg_color": "#f1f1f1"
    },
    "stealth_mode": true
  }
}

```