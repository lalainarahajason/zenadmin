# Specification: ZenAdmin — White Label Module (Pro)

## 1. Objectif du Module

Permettre aux agences et freelances d'effacer l'identité de ZenAdmin et de WordPress pour proposer une interface cohérente, personnalisée et sécurisée à leurs clients finaux.

## 2. Identité du Plugin (Rebranding)

* **Plugin Metadata Overwrite :** Utiliser le filtre `all_plugins` pour modifier dynamiquement le Nom, l'Auteur et l'URL du plugin dans la liste des extensions.
* **Menu Identity :**
* **Label :** Possibilité de renommer l'entrée "ZenAdmin" dans le menu latéral.
* **Icon :** Champ d'upload pour remplacer l'icône lotus par un SVG personnalisé ou un Dashicon.


* **Stealth Mode :** Option pour masquer totalement le plugin de la liste `plugins.php` pour tous les utilisateurs n'ayant pas une capacité spécifique (`manage_network` ou via une constante de sécurité).

## 3. Login Customizer (Branding de Connexion)

Le module doit injecter du CSS et des scripts sur la page `wp-login.php` via le hook `login_enqueue_scripts`.

* **Logo Override :**
* Remplacer le logo WordPress par une image personnalisée.
* Ajuster `background-size` et `height/width` pour s'adapter au format de l'agence.


* **Logo Link & Title :** Modifier l'URL du logo (rediriger vers le site de l'agence) et l'attribut `title` (nom de l'agence) via les filtres `login_headerurl` et `login_headertext`.
* **Design :**
* Couleur d'arrière-plan ou image de fond plein écran.
* Couleur personnalisée pour le bouton "Se connecter" (Primary Color).



## 4. Administration & Footer (Workspace Branding)

* **Footer Text :** Remplacer le texte de gauche ("Merci de créer avec WordPress") via le filtre `admin_footer_text`.
* **Version Hidden :** Supprimer la version de WordPress à droite via le filtre `update_footer`.
* **Admin Bar Cleanup :**
* Supprimer le logo WordPress (menu "About") dans la barre d'outils.
* Masquer le nœud de mise à jour (updates) pour les rôles non-administrateurs.



## 5. Hard Blocking Logic (Restriction d'Accès)

C'est la différence majeure entre le masquage CSS et la sécurité.

* **Menu Removal (PHP) :** Utiliser `remove_menu_page()` et `remove_submenu_page()` au lieu de simplement cacher en CSS.
* **Access Lockdown :**
* Si une page est "Hard Blocked", l'accès direct via l'URL (`wp-admin/options-general.php`) doit être intercepté via le hook `admin_init`.
* **Action :** Rediriger l'utilisateur vers `admin.url( 'index.php' )` avec une notice d'erreur si ses droits ou son rôle ne lui permettent pas d'accéder à la page masquée.



## 6. Dashboard Customization

* **Widget Remover :** Option pour désactiver tous les widgets par défaut (Brouillon rapide, Nouvelles, Activité) via `wp_dashboard_setup`.
* **Agency Welcome Widget :**
* Zone de texte supportant l'HTML et les Shortcodes.
* Affichage forcé en haut de colonne pour servir de "Centre de Support" (Coordonnées, liens utiles).



## 7. Configuration & Sécurité (Guardrails)

* **Visibility Scope :** Tous les réglages de la Marque Blanche ne s'appliquent qu'aux rôles sélectionnés (ex: masquer pour `Editor`, mais laisser visible pour `Administrator`).
* **Emergency Kill-Switch :**
* Si `define('ZENADMIN_WHITE_LABEL', false);` est ajouté au `wp-config.php`, tous les rebrandings sont annulés et le nom "ZenAdmin" réapparaît.
* Cela garantit que l'administrateur technique peut toujours retrouver ses outils en cas de mauvaise configuration.



## 8. Data Schema (JSON Extension)

```json
{
  "white_label": {
    "enabled": true,
    "plugin_name": "Agency Manager",
    "agency_url": "https://agence.com",
    "login_logo": "url_to_image",
    "hide_from_list": true,
    "footer_text": "Support par Mon Agence",
    "hard_block_list": [
      "options-general.php",
      "tools.php"
    ]
  }
}

```

---

**Souhaites-tu que je transforme cette spec en une liste de tâches (To-Do List) prioritaires pour ton IA de développement ?**