# ConnectMySQLDB

`ConnectMySQLDB` est une classe PHP pour gérer les interactions avec une base de données MySQL en utilisant PDO. Elle offre des méthodes pratiques pour créer, lire, mettre à jour et supprimer des enregistrements, ainsi que pour exécuter des requêtes personnalisées et des procédures stockées.

## Installation

1. Clonez ce dépôt ou téléchargez le fichier `ConnectMySQLDB.php`.
2. Incluez la classe dans votre projet PHP :

    ```php
    require_once 'ConnectMySQLDB.php';
    ```

## Utilisation

### Initialisation

Créez une instance de `ConnectMySQLDB` en fournissant les détails de connexion à la base de données :

```php
$db = new ConnectMySQLDB('localhost', 'my_database', 'username', 'password');
```

### Méthodes Disponibles

#### `create($table, $data)`

Insère un nouvel enregistrement dans une table spécifiée.

**Paramètres :**
- `$table` (string) : Le nom de la table.
- `$data` (array) : Un tableau associatif des colonnes et de leurs valeurs.

**Exemple :**

```php
$data = [
    'name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'age' => 30,
    'country' => 'USA'
];
$insertResult = $db->create('users', $data);
echo $insertResult ? 'Insert successful' : 'Insert failed';
```

#### `read($table, $conditions = [], $orderBy = '')`

Lit les enregistrements d'une table avec des conditions optionnelles et un ordre de tri.

**Paramètres :**
- `$table` (string) : Le nom de la table.
- `$conditions` (array) : Un tableau associatif des conditions (optionnel).
- `$orderBy` (string) : Une clause ORDER BY (optionnel).

**Exemple :**

```php
$conditions = ['age' => ['>', 25]];
$users = $db->read('users', $conditions);
print_r($users);
```

#### `update($table, $data, $conditions)`

Met à jour les enregistrements d'une table selon les conditions spécifiées.

**Paramètres :**
- `$table` (string) : Le nom de la table.
- `$data` (array) : Un tableau associatif des colonnes et de leurs nouvelles valeurs.
- `$conditions` (array) : Un tableau associatif des conditions.

**Exemple :**

```php
$data = ['email' => 'newemail@example.com'];
$conditions = ['id' => 1];
$updateResult = $db->update('users', $data, $conditions);
echo $updateResult ? 'Update successful' : 'Update failed';
```

#### `delete($table, $conditions)`

Supprime les enregistrements d'une table selon les conditions spécifiées.

**Paramètres :**
- `$table` (string) : Le nom de la table.
- `$conditions` (array) : Un tableau associatif des conditions.

**Exemple :**

```php
$conditions = ['id' => 1];
$deleteResult = $db->delete('users', $conditions);
echo $deleteResult ? 'Delete successful' : 'Delete failed';
```

#### `executeCustomQuery($query, $params = [])`

Exécute une requête SQL personnalisée.

**Paramètres :**
- `$query` (string) : La requête SQL.
- `$params` (array) : Un tableau associatif des paramètres de la requête (optionnel).

**Exemple :**

```php
$query = "SELECT * FROM users WHERE age > :age";
$params = [':age' => 25];
$results = $db->executeCustomQuery($query, $params);
print_r($results);
```

#### `customSelect($tables, $conditions = [], $joins = [], $orderBy = '')`

Exécute une requête SELECT personnalisée avec des jointures.

**Paramètres :**
- `$tables` (array) : Un tableau des noms de tables.
- `$conditions` (array) : Un tableau associatif des conditions (optionnel).
- `$joins` (array) : Un tableau des clauses JOIN (optionnel).
- `$orderBy` (string) : Une clause ORDER BY (optionnel).

**Exemple :**

```php
$tables = ['users u'];
$joins = ['JOIN orders o ON u.id = o.user_id'];
$conditions = ['u.age' => ['>', 25], 'o.status' => 'pending'];
$orderBy = 'u.name ASC';

$results = $db->customSelect($tables, $conditions, $joins, $orderBy);
print_r($results);
```

#### `executeStoredProcedure($procedureName, $params = [])`

Exécute une procédure stockée sans résultats retournés.

**Paramètres :**
- `$procedureName` (string) : Le nom de la procédure stockée.
- `$params` (array) : Un tableau des paramètres de la procédure (optionnel).

**Exemple :**

```php
$procedureName = 'update_user_status';
$params = [1, 'active'];
$db->executeStoredProcedure($procedureName, $params);
```

#### `executeStoredProcedureWithResults($procedureName, $params = [])`

Exécute une procédure stockée et retourne les résultats.

**Paramètres :**
- `$procedureName` (string) : Le nom de la procédure stockée.
- `$params` (array) : Un tableau des paramètres de la procédure (optionnel).

**Exemple :**

```php
$procedureName = 'get_user_details';
$params = [1];
$results = $db->executeStoredProcedureWithResults($procedureName, $params);
print_r($results);
```

#### `lastInsertID()`

Retourne l'ID du dernier enregistrement inséré.

**Exemple :**

```php
$lastId = $db->lastInsertID();
echo "Last Inserted ID: " . $lastId;
```

### Gestion des Erreurs

Les erreurs sont journalisées en utilisant `error_log`. Assurez-vous que votre environnement PHP est configuré pour gérer correctement les journaux d'erreurs.

## Contributions

Les contributions sont les bienvenues. Veuillez soumettre une pull request ou ouvrir une issue pour discuter de changements majeurs.

## License

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.
