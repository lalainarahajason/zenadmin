# Spec Update: Role-Based Visibility Engine (RBVE)
**Date :** 09 Février 2026  
**Status :** Finalized  
**Goal :** Allow selective interface cleaning based on user capabilities.

---

## 1. Functional Logic (The "Who sees What")

Plutôt que de cibler des rôles rigides (Admin, Editor), ZenAdmin s'appuie sur les **Capabilities** WordPress pour assurer une compatibilité avec les plugins de gestion de membres (User Role Editor, etc.).

### Scopes de visibilité disponibles :
1.  **Global (Everywhere) :** L'élément est masqué pour TOUS les utilisateurs, y compris l'Administrateur.
2.  **Protect Admin (Exclude Admin) :** L'élément est masqué pour tout le monde, **sauf** pour les utilisateurs ayant la capacité `manage_options`. 
    * *Usage :* Cacher les pubs Yoast/Elementor pour le client, mais les garder visibles pour le dev.
3.  **Low Privileges Only :** L'élément est masqué uniquement pour les utilisateurs qui ne peuvent pas éditer les pages des autres (`edit_others_posts`).
    * *Usage :* Nettoyer l'interface pour les Auteurs et contributeurs externes.

---

## 2. Technical Implementation (PHP)

### Validation des droits lors de l'injection :
Dans `includes/class-core.php`, la méthode de rendu doit comparer la "capability" requise stockée avec celle de l'utilisateur actuel.


```php
/**
 * Vérifie si un sélecteur doit être injecté pour l'utilisateur actuel.
 * * @param string $visibility_level Le niveau choisi (global, exclude_admin, low_privilege).
 * @return bool True si on doit masquer l'élément.
 */
function zenadmin_should_hide_for_current_user( $visibility_level ) {
    switch ( $visibility_level ) {
        case 'exclude_admin':
            return ! current_user_can( 'manage_options' );
        
        case 'low_privilege':
            return ! current_user_can( 'edit_others_posts' );
            
        case 'global':
        default:
            return true;
    }
}