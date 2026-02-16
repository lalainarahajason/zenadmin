# Spécification : Correction des standards JS WordPress (WPCS)

## 1. Objectif
Nettoyer le fichier `assets/zen-engine.js` pour éliminer les erreurs de linting (ESLint + Prettier) en appliquant les standards de codage officiels de WordPress.

## 2. Formatage Global (Prettier WordPress)
- **Indentation :** Remplacer tous les espaces par des tabulations réelles (`↹`). Chaque niveau d'imbrication doit correspondre à une tabulation.
- **Retours à la ligne (Line Breaks) :** - Séparer les chaînes de caractères longues sur plusieurs lignes si nécessaire.
    - Pour les chaînes de méthodes (chaining), placer chaque méthode sur une nouvelle ligne (ex: `.querySelector()` puis `.addEventListener()`).
    - Les arguments longs dans les fonctions (comme les sélecteurs d'ID de la barre d'admin) doivent être isolés sur leur propre ligne avec l'indentation adéquate.

## 3. Syntaxe ES6 et Meilleures Pratiques
- **Method Shorthand :** Convertir toutes les définitions de méthodes d'objets `key: function() {}` en syntaxe raccourcie `key() {}`.
- **Accolades (Curly) :** Ajouter systématiquement des accolades `{ }` pour toutes les conditions `if`, même lorsqu'elles tiennent sur une seule ligne.
- **Espacement :** Supprimer les espaces superflus à l'intérieur des parenthèses simples (ex: `(e)` au lieu de `( e )`) pour correspondre au formatage attendu par le linter.

## 4. Gestion des Globales
- **Déclarations :** Supprimer les déclarations manuelles de `sessionStorage`, `confirm`, et `jQuery`. Ces variables sont reconnues comme des globales intégrées par l'environnement WordPress.
- **Exceptions :** Si l'erreur `no-alert` persiste pour `confirm()`, ajouter un commentaire de désactivation locale `// eslint-disable-line no-alert`.

## 5. Corrections Spécifiques (Extraits ciblés)
- **Lignes 38 & 47 :** Placer les sélecteurs de la barre d'admin sur une nouvelle ligne indentée.
- **Ligne 49 :** Découper la ligne pour que `.querySelector('a')` et `.addEventListener()` soient sur des lignes distinctes.
- **Ligne 52 :** Reformater l'appel `ZenAdminToast.success` pour isoler le message de texte sur une nouvelle ligne avec une indentation de tabulation profonde (niveau 7 suggéré par le log).

## 6. Validation
Une fois les modifications appliquées, le fichier ne doit plus présenter d'erreurs de type `prettier/prettier`, `object-shorthand` ou `curly`.