---
name: wordpress-js-standards 
description: Aligne le code JavaScript sur les standards officiels de WordPress. À utiliser pour corriger les erreurs de linting (indentation, accolades, espaces) et assurer la conformité avec l'écosystème WordPress.
---

# WordPress JavaScript Standards (WPCS)

Ce skill transforme le code JavaScript pour respecter les conventions de formatage et de structure exigées par WordPress.

## When to use this skill

* Utiliser ce skill lors de la détection d'erreurs de type `prettier/prettier`, `curly`, `object-shorthand`, ou `no-undef` liées aux globales WordPress.
* Utile pour nettoyer les fichiers `assets/zen-engine.js` et `assets/zen-modal.js` avant une release ou l'intégration de services tiers.
* Indispensable pour garantir la lisibilité et la maintenance du code par d'autres développeurs de l'écosystème WordPress.

## How to use it

### 1. Indentation et Espacement

* **Tabulations obligatoires :** Remplacer systématiquement les espaces d'indentation par des tabulations réelles (`\t`).
* **Espaces dans les parenthèses :** Ajouter un espace après la parenthèse ouvrante et avant la parenthèse fermante dans les structures de contrôle et les appels de fonctions complexes.
* *Exemple :* `if ( condition ) { ... }`.


* **Opérateurs :** Insérer un espace de chaque côté des opérateurs binaires (`===`, `&&`, `=`, etc.).

### 2. Structures de Contrôle

* **Accolades systématiques :** Toujours utiliser des accolades `{ }` pour les blocs `if`, `else`, `for`, `while`, même pour une seule instruction.
* **Placement :** L'accolade ouvrante doit être sur la même ligne que l'instruction de contrôle.

### 3. Syntaxe et Nommage

* **Raccourcis de méthode :** Convertir les définitions `key: function() { ... }` en syntaxe raccourcie ES6 `key() { ... }`.
* **camelCase :** Utiliser le `camelCase` pour les variables et fonctions, sauf pour les constructeurs qui utilisent le `PascalCase`.
* **Points-virgules :** Terminer chaque instruction par un point-virgule `;`.

### 4. Gestion des Globales

Ajouter systématiquement la déclaration suivante au sommet des fichiers pour informer le linter des variables globales disponibles dans WordPress :

```javascript
/* global ZenAdminToast, zenadminConfig, jQuery, confirm, sessionStorage */

```

### 5. Conventions de Formatage Spécifiques

* **Chaining :** Si plusieurs méthodes sont enchaînées, placer chaque méthode sur une nouvelle ligne précédée d'une tabulation.
* **Chaînes longues :** Isoler les messages de texte (comme les Toasts) sur leur propre ligne avec une indentation profonde pour la clarté.

---

Tu peux maintenant enregistrer ce fichier sous le nom `wordpress-js-standards.md` dans ton dossier de skills Antigravity. Pour l'utiliser, demande simplement à ton éditeur : *"Applique le skill wordpress-js-standards sur mes fichiers JS."*