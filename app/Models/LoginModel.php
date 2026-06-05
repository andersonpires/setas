<?php
/**
 * LoginModel - Registro de acessos ao sistema
 */

require_once BASE_PATH . '/app/Core/Database.php';

class LoginModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Registra um novo login do colaborador.
     */
    public function registrar(int $colaboradorId): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO logins (colaborador_id, data_hora) VALUES (?, NOW())");
        $stmt->execute([$colaboradorId]);
    }

    /**
     * Busca o penúltimo acesso (o anterior ao atual) do colaborador.
     * @return array|null ['data_hora' => 'Y-m-d H:i:s'] ou null se não houver
     */
    public function buscarUltimoAcessoAnterior(int $colaboradorId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT data_hora FROM logins
            WHERE colaborador_id = ?
            ORDER BY data_hora DESC
            LIMIT 1 OFFSET 1
        ");
        $stmt->execute([$colaboradorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
