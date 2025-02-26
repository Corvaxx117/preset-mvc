<?php

namespace App\Model;

use App\Core\Database;
use PDO;

abstract class BaseModel
{
    protected PDO $pdo;
    protected string $table;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Récupérer tous les enregistrements.
     * Exemple : SELECT * FROM table
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->getTableName()}");
        return $stmt->fetchAll();
    }

    /**
     * Trouver un enregistrement unique selon des critères.
     * Exemple : SELECT * FROM table WHERE column = value LIMIT 1
     * @return array|null
     */
    public function findOneBy(array $criteria): ?array
    {
        $conditions = $this->buildConditions($criteria);
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$conditions} LIMIT 1");
        $stmt->execute($criteria);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Trouver plusieurs enregistrements selon des critères.
     * Exemple : SELECT * FROM table WHERE column = value
     * @return array
     */
    public function findBy(array $criteria): array
    {
        $conditions = $this->buildConditions($criteria);
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$conditions}");
        $stmt->execute($criteria);
        return $stmt->fetchAll();
    }

    /**
     * Crée une nouvelle entrée dans la table.
     * Exemple : INSERT INTO table (column1, column2) VALUES (:column1, :column2)
     */
    public function create(array $data): bool
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_map(fn($key) => ":$key", array_keys($data)));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Met à jour un enregistrement en fonction de son ID.
     * Exemple : UPDATE table SET column1 = value1, column2 = value2 WHERE id = :id
     */
    public function update(int $id, array $criteria): bool
    {
        // Ajout de l'ID aux paramètres
        $criteria['id'] = $id;
        $setClause = $this->buildSetClause($criteria);
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($criteria);
    }

    /**
     * Supprime des entrées selon des critères donnés.
     * Exemple : DELETE FROM table WHERE column = value
     */
    public function delete(array $criteria): bool
    {
        $conditions = $this->buildConditions($criteria);
        $sql = "DELETE FROM {$this->table} WHERE {$conditions}";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($criteria);
    }

    /**
     * Fonction utilitaire pour construire les conditions SQL.
     * 
     * @param array $criteria Tableau associatif clé => valeur
     * @return string Clause WHERE SQL générée (ex: email = :email AND lastname = :lastname)
     */
    private function buildConditions(array $criteria): string
    {
        // array_map applique une fonction à chaque élément du tableau
        return implode(' AND ', array_map(fn($key) => "$key = :$key", array_keys($criteria)));
        // resultat : email = :email AND lastname = :lastname
    }

    /**
     * Fonction utilitaire pour construire la clause SET pour une requête UPDATE.
     * 
     * @param array $data Tableau associatif clé => valeur
     * @return string Clause SET SQL générée (ex: "column1 = :column1, column2 = :column2")
     */
    private function buildSetClause(array $data): string
    {
        return implode(', ', array_map(fn($key) => "$key = :$key", array_keys($data)));
    }

    /**
     * Chaque modèle enfant doit définir le nom de la table associée.
     * @return string Nom de la table
     */
    protected function getTableName(): string
    {
        return $this->table;
    }

    /**
     * Récupérer des enregistrements avec statistiques et tri dynamique.
     * 
     * @param string $orderBy Colonne de tri (par défaut: 'date_creation')
     * @param string $direction Ordre de tri ('ASC' ou 'DESC')
     * @param array $stats Colonnes de statistiques supplémentaires (ex: ['views' => 'COUNT(views)'])
     * @return array Résultats triés avec statistiques
     */
    public function findAllWithStats(string $orderBy = 'date_creation', string $direction = 'DESC', array $stats = []): array
    {
        // Liste des colonnes autorisées pour le tri
        $allowedColumns = array_merge(['id', 'title', 'date_creation'], array_keys($stats));
        if (!in_array($orderBy, $allowedColumns)) {
            $orderBy = 'date_creation';
        }

        // Sécurisation du tri
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        // Construction de la requête SQL
        $statsSql = "";
        if (!empty($stats)) {
            $statsParts = [];
            foreach ($stats as $alias => $formula) {
                $statsParts[] = "$formula AS $alias";
            }
            $statsSql = ", " . implode(", ", $statsParts);
        }

        $sql = "
        SELECT a.* {$statsSql}
        FROM {$this->getTableName()} a
        ORDER BY {$orderBy} {$direction}
    ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Incremente le nombre de vues d'un enregistrement.
     * 
     * @param int $id Identifiant de l'enregistrement
     * @return bool true si la mise à jour a fonctionné, false sinon
     */
    public function incrementViews(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET views = views + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
