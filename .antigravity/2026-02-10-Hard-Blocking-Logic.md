# Spec Update: Soft Hide vs Hard Blocking
**Date :** 10 Février 2026  
**Goal :** Ensure that blocking a menu can also restrict access to the underlying page.

## 1. Détection des Menus
Le moteur `zen-engine.js` doit identifier si l'élément cliqué est un item du menu d'administration (`#adminmenu a`).

## 2. Option "Restrict Access" (Pro Feature)
Dans le modal de confirmation, si l'élément est un lien de menu, ajouter une case à cocher :
* **[ ] Bloquer l'accès à la page (Hard Block)**
    * *Si coché :* ZenAdmin doit enregistrer l'URL de destination.
    * *Action PHP :* Utiliser le hook `admin_init` pour vérifier l'URL courante. Si l'URL correspond à un élément "Hard Blocked" pour le rôle de l'utilisateur, rediriger vers le tableau de bord avec un message d'erreur.

## 3. Risque de "Lockout"
**Avertissement critique :** Si l'utilisateur fait un "Hard Block" sur la page de réglages de ZenAdmin ou sur le tableau de bord, il se bloque lui-même.
* **Correction :** Interdire le "Hard Block" sur les URLs contenues dans la `Whitelist Hardcoded` (AS2).