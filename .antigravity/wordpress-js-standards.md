Voici une spécification complète au format Markdown basée sur les **WordPress JavaScript Coding Standards (WPCS)**. Ce document est conçu pour être utilisé par ton éditeur IA pour corriger ton code et le rendre 100% conforme.

# Spécification : Standards de Codage JavaScript WordPress (WPCS)

## 1. Formatage et Structure

### 1.1 Indentation (La règle d'or)

* **Utiliser des tabulations réelles (`\t`)** pour l'indentation au début de chaque ligne.
* Les **espaces** ne sont autorisés que pour l'alignement à l'intérieur d'une ligne ou dans les blocs de documentation.
* Toute fonction, même si elle enveloppe tout le fichier (closure), doit avoir son corps indenté d'une tabulation.

### 1.2 Accolades et Blocs

* **Toujours utiliser des accolades** pour les structures `if`, `else`, `for`, `while`, et `try`, même pour une seule ligne.
* L'ouverture de l'accolade `{` doit se trouver sur la **même ligne** que la condition ou la définition de la fonction.
* La fermeture de l'accolade `}` doit se trouver sur une **nouvelle ligne** immédiatement après la dernière instruction du bloc.

### 1.3 Espacement

* **Opérateurs :** Toujours mettre un espace avant et après les opérateurs logiques (`&&`, `||`), de comparaison (`===`, `!==`), et d'affectation (`=`).
* **Parenthèses :** Mettre des espaces à l'intérieur des parenthèses pour les structures de contrôle (ex: `if ( condition )`).
* **Semicolons :** Toujours terminer les instructions par un point-virgule `;`.

## 2. Conventions de Nommage

* **Variables et Fonctions :** Utiliser exclusivement le **camelCase** commençant par une minuscule (ex: `myVariableName`).
* **Classes et Constructeurs :** Utiliser le **PascalCase** (ex: `MyClassName`).
* **Constantes :** Utiliser des majuscules avec des underscores (ex: `MY_CONSTANT`).
* **Fichiers :** Les noms de fichiers doivent être en minuscules et les mots séparés par des traits d'union (ex: `zen-engine.js`).

## 3. Meilleures Pratiques et Sécurité

* **Utilisation de `var`, `let`, `const` :** Pour le code moderne (ES2015+), privilégier `const` et `let` plutôt que `var`.
* **Globales :** Éviter de polluer l'espace global. Envelopper les scripts dans des closures ou utiliser des objets de configuration (comme `zenadminConfig`).
* **Shorthand :** Utiliser la syntaxe raccourcie pour les méthodes d'objets (ex: `init() {}` au lieu de `init: function() {}`).

## 4. Guide de Correction (Erreurs spécifiques)

### 4.1 Enchaînement de méthodes (Chaining)

Si l'enchaînement est trop long, chaque appel doit être sur une nouvelle ligne :

```javascript
// CORRECT
document.querySelector( '#my-id' )
	.addEventListener( 'click', ( e ) => {
		// Code
	} );

```

### 4.2 Messages et Toasts

Les chaînes de texte longues dans les fonctions doivent être isolées :

```javascript
// CORRECT
ZenAdminToast.success(
	'Session blocks cleared. Reloading...'
);

```

## 5. Déclarations de Globales (Linter)

Pour éviter les erreurs `no-undef`, déclarer les globales WordPress en haut du fichier :

```javascript
/* global ZenAdminToast, zenadminConfig, jQuery, confirm, sessionStorage */

```