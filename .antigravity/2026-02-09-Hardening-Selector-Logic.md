# Spec Update: ZenAdmin Hardening & Logic Refinement
**Date :** 09 Février 2026  
**Status :** Urgent / Critical  
**Reference :** AS1.1, AS2, AS4

## 1. Problématique : Fragilité du Ciblage
Le système actuel repose trop sur la structure DOM (`nth-of-type`), ce qui est une erreur stratégique majeure dans l'écosystème WordPress où les notices sont injectées dynamiquement par AJAX ou des hooks tiers.

## 2. Modifications de la Hiérarchie des Sélecteurs (AS1.1)

Le moteur `zen-engine.js` doit abandonner le ciblage positionnel pur au profit d'un ciblage sémantique et par attributs.

### Nouvel Ordre de Priorité (Strict) :
1.  **ID Unique Filtré :** Un ID qui ne contient pas de suite numérique aléatoire (ex: `#wp-admin-bar-my-account` OK, `#el-12345` REJETÉ).
2.  **HREF Surgical Target :** Si l'élément est un `<a>` ou contient un `<a>`, extraire le paramètre `page` ou `action` de l'URL. 
    * *Exemple :* `a[href*="page=bad-plugin-upsell"]`
3.  **Combinaison de Classes Spécifiques :** Minimum deux classes si l'une d'elles est générique (ex: `.notice.my-plugin-specific-class`).
4.  **Attribute Partial Match :** Ciblage par `src`, `data-id`, ou `name`.
5.  **Fallback Structurel (Dernier recours) :** Uniquement si combiné à un parent stable (ex: `#wpbody-content .wrap > div:nth-of-type(2)`).


## 3. Sécurité : Le "Kill Switch" d'Urgence (AS2)

Le "Safe Mode" via URL (`?zenadmin_safe_mode=1`) est insuffisant si l'interface est totalement brisée.

**Correction :** * Ajout d'une constante de secours à vérifier dès le `plugins_loaded` :
    ```php
    if ( defined( 'ZENADMIN_DISABLE' ) && ZENADMIN_DISABLE ) {
        return; // Sortie immédiate, aucun script, aucun style.
    }
    ```
* **Action :** Documenter cette constante dans l'onglet "Troubleshooting" de la page de réglages.

## 4. Levée des Limites Arbitraires (Section 12)

La limite de 50 sélecteurs est un frein à l'usage "Power User". 
* **Nouvelle limite :** 200 sélecteurs. 
* **Optimisation :** Pour éviter de charger un gros objet en JS sur chaque page, les blocs "Session-Only" (`sessionStorage`) ne doivent être synchronisés avec la DB que si l'utilisateur coche explicitement "Permanent".

## 5. Mécanisme de Prévention des Conflits (AS5)

Avant de sauvegarder un sélecteur, le script JS doit effectuer un "Specificity Check" :
* **Scan de l'Admin :** Si `document.querySelectorAll(newSelector).length > 1`, le modal doit afficher un avertissement : *"Attention : Ce sélecteur masque [X] éléments. Voulez-vous être plus spécifique ?"*
* **Intersection :** Si le sélecteur ciblé est déjà enfant d'un élément masqué, bloquer l'action pour éviter les doublons inutiles dans la base de données.

## 6. Accessibilité du Modal (UX Upgrade)

Le modal ne doit pas simplement être "WP Style", il doit être conforme **WCAG 2.1**.
* **Focus Trap :** Implémenter un script de capture de focus (le Tab ne doit pas sortir du modal tant qu'il est ouvert).
* **Esc Key :** Fermeture systématique sur la touche Echap.
* **Aria-Labels :** Ajout systématique sur les champs d'input du label personnalisé.

## 7. Plan d'Action Immédiat

1.  **Refactor `zen-engine.js`** pour intégrer la logique de détection de lien (HREF).
2.  **Mise à jour de `class-core.php`** pour inclure la constante `ZENADMIN_DISABLE`.
3.  **Nettoyage de `wp_options`** : Créer une fonction de migration pour convertir les anciens sélecteurs `nth-of-type` fragiles en sélecteurs plus robustes si possible.