# Spec Update: Parent Menu Preservation Logic (Bug Fix)
**Date :** 09 Février 2026  
**Status :** Critical Fix  
**Bug Reference :** BF001 - "Hidden child hides parent menu"

## 1. Analyse du Problème
Lorsqu'un utilisateur sélectionne un élément dans un sous-menu (ex: `Apparence > Éditeur`), le sélecteur généré peut involontairement cibler une structure qui impacte le conteneur parent (`li.wp-has-submenu`). Si le parent est masqué, l'accès à tous les autres sous-menus légitimes est perdu pour ce rôle.

## 2. Correction de la Stratégie de Ciblage (Surgical Targeting)

Le moteur `zen-engine.js` doit appliquer une règle de **Non-Propagation** pour les menus d'administration WordPress.

### A. Identification du Contexte Menu
Si l'élément cible se trouve à l'intérieur de `#adminmenu` :
1. **Interdiction du Parent :** Le moteur ne doit jamais générer un sélecteur qui cible un `li` ayant la classe `.wp-has-submenu` si l'utilisateur a cliqué sur un sous-élément.
2. **Ciblage de l'Ancre (`<a>`) :** Au lieu de masquer le `<li>` (qui peut porter des styles de structure), cibler l'élément `<a>` spécifique à l'intérieur.
   * *Sélecteur recommandé :* `#adminmenu a[href="admin.php?page=plugin-slug"]`
   * *Action CSS :* Appliquer `display: none !important` sur l'ancre uniquement ou réduire la hauteur du `li` à 0.



## 3. Heuristique de "Sécurité Structurelle"

Avant de valider un sélecteur dans le menu admin :
* **Check :** Est-ce que le sélecteur contient `.wp-submenu` ?
* **Validation :** Si oui, s'assurer que le sélecteur est assez spécifique pour ne pas remonter au `.wp-menu-separator` ou au `.menu-top`.
* **Avertissement :** Si l'utilisateur tente de cacher le **dernier** élément visible d'un sous-menu, ZenAdmin doit l'avertir : *"Masquer ce dernier élément rendra le menu parent invisible pour ce rôle."*

## 4. CSS de Compensation

Pour éviter que le menu parent ne paraisse "vide" ou "cassé" lorsqu'un sous-menu est masqué, injecter ce correctif de base dans le bloc `<style>` de ZenAdmin :

```css
#adminmenu li.wp-has-submenu.zenadmin-child-hidden {
    display: block !important; /* Force la visibilité du parent même si WP pense qu'il est vide */
}