# Spec Premium: Multisite & Portability Engine
**Date :** 09 Février 2026  
**Status :** High-Value Feature  
**Goal :** Allow agencies to deploy ZenAdmin configurations across multiple sites instantly.

---

## 1. Gestion Multisite (Network-Wide)

L'objectif est de permettre à un super-admin de nettoyer tout son réseau de sites en une seule action.

### Fonctionnalités :
* **Network Sync :** Une option dans le panneau "Network Settings" pour propager la liste de blocage à tous les sites du réseau.
* **Inheritance (Héritage) :** Les sites enfants héritent des règles globales, mais les admins locaux peuvent ajouter leurs propres règles (sauf si le super-admin verrouille la configuration).
* **Global CSS Injection :** Injection du style via un seul appel au niveau réseau pour optimiser les performances de la base de données.

---

## 2. Système d'Export / Import (JSON Strategy)

Pour les sites qui ne sont pas sur un réseau multisite, ZenAdmin doit permettre de copier-coller une "intelligence de blocage".

### Mécanisme :
* **Export :** Génération d'un fichier `.json` contenant :
    * Les sélecteurs (IDs, classes, hrefs).
    * Les labels.
    * Les réglages de visibilité (scopes/rôles).
* **Import :** * Téléchargement du fichier ou simple "Copier/Coller" d'un bloc de code JSON.
    * **Merge vs Overwrite :** L'utilisateur choisit s'il veut ajouter les nouveaux blocs à sa liste actuelle ou tout écraser.

---

## 3. Templates de Communauté (Cloud-Lite)

Au lieu d'une plateforme externe, ZenAdmin peut interroger une simple API (ou un fichier distant sur GitHub) pour récupérer des templates mis à jour sans quitter l'admin.

* **Auto-Update des Templates :** Si Yoast ou Elementor change ses classes CSS, ZenAdmin met à jour automatiquement les templates intégrés.
* **One-Click Cleanup :** Un bouton "Nettoyer mon Admin" qui applique les 20 blocs les plus populaires de la communauté.

---

## 4. Analyse de Rentabilité (Le coût d'opportunité)

Sans ces fonctions, ton utilisateur passe 10 minutes par site à configurer ses blocages. Avec l'import/multisite, il passe à 10 secondes.
* **Valeur perçue :** Tu vends du **temps**. 
* **Cible :** Agences Web et Freelances (ceux qui ont un budget pour les outils).

---

## 5. Sécurité de l'Importation

Pour éviter l'injection de code malveillant via le JSON :
* **Validation du Schéma :** Vérifier que chaque clé du JSON correspond aux attentes (id, selector, label, scope).
* **Sanitization Post-Import :** Passer chaque sélecteur importé dans la fonction de nettoyage `zenadmin_validate_selector` avant de l'enregistrer en base de données.