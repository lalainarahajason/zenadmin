# Plan d'Implémentation: ZenAdmin v1.1 - Stabilization & Security Patch

## Vue d'Ensemble

**Objectif**: Transformer le MVP en produit production-ready en corrigeant les failles de sécurité, stabilisant le moteur de sélection et améliorant l'UX.

**Priorité**: CRITIQUE - Bloquant pour la release

**Estimation**: 6-8 heures de développement + 2 heures de tests

---

## Phase 1: Sécurité Critique (Priorité: HAUTE)

### 1.1 Refonte du Hard Blocking
**Fichier**: [`class-core.php`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/includes/class-core.php)

#### Tâches
- [ ] Remplacer la logique `strpos()` par une comparaison stricte d'URL
- [ ] Implémenter le parsing d'URL avec `parse_url()` et comparaison de `$pagenow`
- [ ] Ajouter la vérification des paramètres GET pour les URLs avec query strings
- [ ] Renforcer la whitelist hardcodée (ZenAdmin settings + dashboard)
- [ ] Ajouter des tests unitaires pour les cas limites

**Complexité**: Moyenne  
**Temps estimé**: 2h

#### Algorithme proposé
```php
public function enforce_hard_blocks() {
    global $pagenow;
    
    // Safety checks
    if ( wp_doing_ajax() || $this->is_safe_mode() ) return;
    
    // Whitelist hardcodée
    if ( isset( $_GET['page'] ) && 'zenadmin' === $_GET['page'] ) return;
    if ( 'index.php' === $pagenow && ! $this->is_dashboard_blocked() ) return;
    
    $blacklist = get_option( 'zenadmin_blacklist', array() );
    $user_roles = wp_get_current_user()->roles;
    
    foreach ( $blacklist as $entry ) {
        if ( empty( $entry['hard_block'] ) || empty( $entry['target_url'] ) ) continue;
        
        // Vérifier les rôles
        if ( ! empty( $entry['hidden_for'] ) && ! array_intersect( $user_roles, $entry['hidden_for'] ) ) continue;
        
        // Parser l'URL cible
        $parsed = parse_url( $entry['target_url'] );
        $target_file = basename( $parsed['path'] ?? '' );
        
        // Comparer le fichier
        if ( $target_file !== $pagenow ) continue;
        
        // Comparer les paramètres GET si présents
        if ( ! empty( $parsed['query'] ) ) {
            parse_str( $parsed['query'], $target_params );
            foreach ( $target_params as $key => $value ) {
                if ( ! isset( $_GET[$key] ) || $_GET[$key] !== $value ) {
                    continue 2; // Skip cette règle
                }
            }
        }
        
        // BLOCK
        wp_safe_redirect( admin_url( 'index.php?zenadmin_blocked=1' ) );
        exit;
    }
}
```

---

### 1.2 Validation Stricte des Rôles
**Fichier**: [`class-core.php`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/includes/class-core.php)

#### Tâches
- [ ] Remplacer `sanitize_key()` par validation via `$wp_roles->is_role()`
- [ ] Ajouter un filtre pour nettoyer les rôles invalides avant sauvegarde
- [ ] Mettre à jour `ajax_save_block()` et `ajax_update_block_roles()`

**Complexité**: Faible  
**Temps estimé**: 30min

---

### 1.3 Verrouillage Client (ZENADMIN_LOCK_SETTINGS)
**Fichiers**: [`class-core.php`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/includes/class-core.php), [`class-settings.php`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/includes/class-settings.php)

#### Tâches
- [ ] Ajouter la constante `ZENADMIN_LOCK_SETTINGS` dans la documentation
- [ ] Masquer les boutons Delete/Edit dans `render_blocks_tab()` si verrouillé
- [ ] Bloquer les requêtes AJAX de modification avec message d'erreur
- [ ] Afficher une notice admin si verrouillé

**Complexité**: Faible  
**Temps estimé**: 45min

---

## Phase 2: Stabilité du Moteur de Sélection (Priorité: HAUTE)

### 2.1 Ciblage Chirurgical des Menus
**Fichier**: [`zen-engine.js`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/assets/zen-engine.js)

#### Tâches
- [ ] Modifier `generateSelector()` pour détecter les `li.wp-has-submenu`
- [ ] Forcer la sélection de l'ancre `<a>` au lieu du `<li>` parent
- [ ] Ajouter un avertissement dans le modal si parent détecté
- [ ] Tester sur différents types de menus (top-level, submenu, custom)

**Complexité**: Moyenne  
**Temps estimé**: 1h30

#### Code proposé
```javascript
// Dans generateSelector(), après la détection admin menu
const adminMenuLi = el.closest('#adminmenu li');
if (adminMenuLi) {
    // Vérifier si c'est un parent avec sous-menus
    if (adminMenuLi.classList.contains('wp-has-submenu')) {
        // Forcer la sélection de l'ancre principale
        const mainLink = adminMenuLi.querySelector('a.menu-top');
        if (mainLink) {
            const href = mainLink.getAttribute('href');
            if (href && href !== '#') {
                return `#adminmenu a.menu-top[href="${href}"]`;
            }
        }
    }
    // ... reste de la logique existante
}
```

---

### 2.2 Avertissement Interactif
**Fichier**: [`zen-modal.js`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/assets/zen-modal.js)

#### Tâches
- [ ] Ajouter une fonction de détection de conteneur parent
- [ ] Afficher un bandeau jaune dans le modal si détecté
- [ ] Permettre à l'utilisateur de continuer ou annuler

**Complexité**: Faible  
**Temps estimé**: 30min

---

## Phase 3: Amélioration UX (Priorité: MOYENNE)

### 3.1 Mode Preview
**Fichier**: [`zen-modal.js`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/assets/zen-modal.js)

#### Tâches
- [ ] Ajouter un bouton "Preview" (icône œil) dans le modal
- [ ] Implémenter le toggle de `display: none` temporaire
- [ ] Nettoyer le style inline si annulation
- [ ] Ajouter une animation de transition

**Complexité**: Moyenne  
**Temps estimé**: 1h

---

### 3.2 Amélioration du Wording
**Fichiers**: [`class-core.php`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/includes/class-core.php), [`zen-modal.js`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/assets/zen-modal.js)

#### Tâches
- [ ] Remplacer "Block Forever" → "Hide Element"
- [ ] Remplacer "Session Only" → "Hide temporarily (Reload to reset)"
- [ ] Mettre à jour toutes les traductions (i18n)

**Complexité**: Faible  
**Temps estimé**: 15min

---

## Phase 4: Compatibilité & Détection (Priorité: BASSE)

### 4.1 Détection Gutenberg
**Fichier**: [`zen-engine.js`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/assets/zen-engine.js)

#### Tâches
- [ ] Détecter `body.block-editor-page` dans `init()`
- [ ] Afficher un toast d'avertissement si contexte Gutenberg
- [ ] Documenter les limitations dans le README

**Complexité**: Faible  
**Temps estimé**: 30min

---

## Phase 5: Code Quality (Priorité: BASSE)

### 5.1 Modernisation Autoloader
**Fichier**: [`zenadmin.php`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/zenadmin.php)

#### Tâches
- [ ] Nettoyer la fonction `spl_autoload_register`
- [ ] Vérifier la cohérence des noms de fichiers
- [ ] Ajouter des commentaires de documentation

**Complexité**: Faible  
**Temps estimé**: 30min

---

### 5.2 Audit Sécurité AJAX
**Fichier**: [`class-core.php`](file:///Users/rahajason/Documents/haja/wp/ZenAdmin/includes/class-core.php)

#### Tâches
- [ ] Vérifier `check_ajax_referer()` sur toutes les actions AJAX
- [ ] Vérifier `current_user_can('manage_options')` systématiquement
- [ ] Ajouter des logs de sécurité si `ZENADMIN_DEBUG`

**Complexité**: Faible  
**Temps estimé**: 30min

---

## Stratégie d'Implémentation

### Ordre Recommandé

1. **Sprint 1 (Sécurité)** - 3h
   - 1.1 Refonte Hard Blocking
   - 1.2 Validation Rôles
   - 1.3 Verrouillage Client

2. **Sprint 2 (Stabilité)** - 2h
   - 2.1 Ciblage Menus
   - 2.2 Avertissements

3. **Sprint 3 (UX)** - 1h15
   - 3.1 Mode Preview
   - 3.2 Wording

4. **Sprint 4 (Polish)** - 1h30
   - 4.1 Gutenberg
   - 5.1 Autoloader
   - 5.2 Audit AJAX

### Tests à Effectuer

#### Tests Fonctionnels
- [ ] Hard Block avec URLs complexes (query strings multiples)
- [ ] Hard Block ne bloque pas les pages whitelistées
- [ ] Ciblage menu ne cache pas les sous-menus
- [ ] Preview fonctionne et se nettoie correctement
- [ ] Verrouillage bloque bien les modifications

#### Tests de Sécurité
- [ ] Impossible de bloquer ZenAdmin settings
- [ ] Rôles invalides sont rejetés
- [ ] AJAX requiert nonce valide
- [ ] Utilisateurs non-admin ne peuvent pas modifier

#### Tests de Compatibilité
- [ ] WordPress 6.4+
- [ ] PHP 8.0+
- [ ] Gutenberg editor
- [ ] Classic editor

---

## Checklist de Release

- [ ] Tous les tests passent
- [ ] Documentation mise à jour
- [ ] CHANGELOG.md mis à jour
- [ ] Version bump à 1.1.0
- [ ] Tag Git créé
- [ ] Build de production testé
- [ ] Déploiement sur environnement de staging

---

## Notes Importantes

> [!WARNING]
> La refonte du Hard Blocking est **critique** - elle corrige une faille de sécurité majeure. À prioriser absolument.

> [!IMPORTANT]
> Le ciblage des menus doit être testé manuellement sur plusieurs configurations WordPress (menus custom, plugins tiers).

> [!TIP]
> Le mode Preview peut être implémenté de manière incrémentale - commencer par un simple toggle, puis ajouter les animations.
