<?php
// File: core/BaseModel.php

class BaseModel
{
  /** @var PDO */
  protected $pdo;

  protected ?int $organizationId = null;

  /** @var string Table name (must be set by child models) */
  protected $tableName;

  /** @var string Primary key column name */
  protected $primaryKey = 'id';

  // add these properties in BaseModel
  protected $orgFilterColumn = 'auto';          // default
  protected $orgFilterCandidates = ['tenant_id', 'organization_id', 'org_id', 'account_id']; // auto-detect order
  private   $orgFilterResolved = false;

  protected function resolveOrgFilterColumn(): void
  {
    if ($this->orgFilterResolved || !$this->tableName) return;

    // Respect explicit concrete names
    if ($this->orgFilterColumn && !in_array($this->orgFilterColumn, ['auto', 'organization_id'], true)) {
      $this->orgFilterResolved = true;
      return;
    }

    $candidates = ($this->orgFilterColumn === 'auto' || !$this->orgFilterColumn)
      ? $this->orgFilterCandidates
      : array_unique([$this->orgFilterColumn, ...$this->orgFilterCandidates]);

    foreach ($candidates as $col) {
      $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$this->tableName} LIKE ?");
      $stmt->execute([$col]);
      if ($stmt->fetch()) {
        $this->orgFilterColumn = 'tenant_id';
        $this->orgFilterResolved = true;
        return;
      }
    }

    // Final fallback so we never append "alias. = ?"
    $this->orgFilterColumn = 'tenant_id';
    $this->orgFilterResolved = true;
  }


  /**
   * @param PDO $pdo
   */
  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
    // This is where you would typically set it based on session/auth
    $this->organizationId = \Auth::tenantId();
  }

  /**
   * Determine the organization ID to filter queries by.
   * Provider admins (role 'admin', organization_type 'provider') bypass the filter (return null).
   *
   * @return int|null
   */
  // Prefer Auth; fall back to session if present. Never index $_SESSION directly.
  protected function getCurrentOrganizationIdForFilter(): ?int
  {
    if (class_exists('Auth') && method_exists('Auth', 'getOrganizationId')) {
      $id = Auth::getOrganizationId();
      if ($id) return (int)$id;
    }
    return isset($_SESSION['tenant_id']) ? (int)$_SESSION['tenant_id'] : null;
  }

  /**
   * Append an organization filter WHERE clause if applicable.
   * $alias lets you target an aliased table (e.g., 'u' => 'u.organization_id').
   *
   * @param string $sql
   * @param array  $params
   * @param string $alias
   * @return array{sql:string, params:array}
   */
  // core/BaseModel.php (The method likely looks something like this)

  protected function applyOrganizationFilter(string $sql, array $params = [], string $alias = ''): array
  {
    $tenantId = \Auth::tenantId();
    $userRole = \Auth::getRole();
    $orgType  = method_exists('Auth', 'getOrganizationType')
      ? Auth::getOrganizationType()
      : ($_SESSION['organization_type'] ?? null);

    $isProviderAdmin = ($userRole === 'admin' && $orgType === 'provider');

    if ($isProviderAdmin || empty($tenantId)) {
      return ['sql' => $sql, 'params' => $params];
    }

    // 3. Define the qualified column name and placeholder
    // The filter column will be 't.tenant_id', 'p.tenant_id', etc.
    $filterCol = $alias ? "{$alias}.tenant_id" : "tenant_id";
    $placeholder = ':tenantFilterId'; // Using a unique name for guaranteed safety

    // 4. Define the tenant WHERE clause fragment.
    $tenantClause = " {$filterCol} = {$placeholder} ";

    // 5. Insert the clause into the SQL query
    // Use a case-insensitive check for WHERE
    if (preg_match('/\sWHERE\s/i', $sql)) {
      $sql .= " AND " . $tenantClause;
    } else {
      $sql .= " WHERE " . $tenantClause;
    }

    // 6. Safely add the parameter to the associative array.
    $params[$placeholder] = $tenantId;

    return ['sql' => $sql, 'params' => $params];
  }

  /* Get all rows (with optional limit/offset), applying org filter.
   *
   * @param int|null $limit
   * @param int|null $offset
   * @return array
   */
  public function getAll(?int $limit = null, ?int $offset = null): array
  {
    if (!$this->tableName) {
      throw new Exception("Table name not set for model.");
    }

    $sql = "SELECT * FROM {$this->tableName}";
    $params = [];

    ['sql' => $sql, 'params' => $params] = $this->applyOrganizationFilter($sql, $params);

    if ($limit !== null) {
      $sql .= " LIMIT ?";
      $params[] = $limit;
      if ($offset !== null) {
        $sql .= " OFFSET ?";
        $params[] = $offset;
      }
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Get a single row by primary key, applying org filter.
   *
   * @param int $id
   * @return array|null
   */
  public function getById(int $id): ?array
  {
    if (!$this->tableName) {
      throw new Exception("Table name not set for model.");
    }

    $sql = "SELECT * FROM {$this->tableName} WHERE {$this->primaryKey} = ?";
    $params = [$id];

    ['sql' => $sql, 'params' => $params] = $this->applyOrganizationFilter($sql, $params);

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
  }

  /**
   * Delete a row by primary key, applying org filter and verifying existence first.
   *
   * @param int $id
   * @return bool
   * @throws Exception if the table name is missing or row not accessible
   */
  public function delete(int $id): bool
  {
    if (!$this->tableName) {
      throw new Exception("Table name not set for model.");
    }

    // Verify row exists under current org scope
    $existing = $this->getById($id);
    if (!$existing) {
      throw new Exception("Record not found or not accessible for deletion.");
    }

    $sql = "DELETE FROM {$this->tableName} WHERE {$this->primaryKey} = ?";
    $params = [$id];

    // Apply org filter again for defense-in-depth
    ['sql' => $sql, 'params' => $params] = $this->applyOrganizationFilter($sql, $params);

    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute($params);
  }
}
