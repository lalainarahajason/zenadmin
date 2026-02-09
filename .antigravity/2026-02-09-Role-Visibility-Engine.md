# Spec Update: Role-Based Visibility Engine (RBVE)
**Date :** 09 Février 2026  
**Status :** Finalized  
**Goal :** Allow selective interface cleaning based on user roles.

---

## 1. Functional Logic (Multi-Role Selection)

ZenAdmin permet de choisir **plusieurs rôles** pour chaque élément bloqué, offrant un contrôle granulaire sur qui voit quoi.

### Comportement par défaut :
- Lors du blocage, **tous les rôles sont pré-cochés** (= masqué pour tous).
- L'utilisateur **décoche** les rôles qui doivent **voir** l'élément.

### Exemple pratique :
| Élément | Masqué pour | Visible pour |
|---------|-------------|--------------|
| Pub Yoast | Editor, Author, Subscriber | Administrator |
| Rappel Abo | Subscriber | Administrator, Editor, Author |

---

## 2. Technical Implementation

### 2.1 Backend (`class-core.php`)

**Envoyer les rôles disponibles au JS :**
```php
global $wp_roles;
$roles_list = wp_list_pluck( $wp_roles->roles, 'name' );
// Résultat: ['administrator' => 'Administrator', 'editor' => 'Editor', ...]
```

**Stockage (wp_options) :**
```php
$blacklist[$hash] = [
    'selector'   => '.notice-yoast',
    'label'      => 'Pub Yoast',
    'hidden_for' => ['editor', 'author', 'subscriber'],
    'created'    => time(),
];
```

**Injection conditionnelle :**
```php
$user = wp_get_current_user();
$user_roles = (array) $user->roles;

foreach ($blacklist as $entry) {
    if ( array_intersect( $user_roles, $entry['hidden_for'] ) ) {
        $selectors[] = $entry['selector'];
    }
}
```

### 2.2 Frontend (`zen-modal.js`)

**UI : Liste de checkboxes dynamique**
```
Masquer pour :
[x] Administrator
[x] Editor
[x] Author
[x] Subscriber
```

L'utilisateur décoche "Administrator" → l'Admin verra toujours l'élément.

### 2.3 AJAX Payload (`zen-engine.js`)

```javascript
{
    action: 'zenadmin_save_block',
    selector: '.notice-yoast',
    label: 'Pub Yoast',
    hidden_for: ['editor', 'author', 'subscriber']
}
```

---

## 3. Migration

Les anciens blocs (sans `hidden_for`) sont traités comme `global` (masqué pour tous).