<?php
/**
 * RecuperacaoSenhaModel - Tokens para troca de senha
 */

require_once BASE_PATH . '/app/Core/Database.php';

class RecuperacaoSenhaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function gerarToken(int $colaboradorId): string
    {
        $this->invalidarTokensAtivos($colaboradorId);

        $token = bin2hex(random_bytes(32));
        $minutes = defined('RECUP_SENHA_TOKEN_MINUTES') ? (int) RECUP_SENHA_TOKEN_MINUTES : 30;
        $expiraEm = (new DateTimeImmutable())->modify(sprintf('+%d minutes', max(1, $minutes)));

        $stmt = $this->pdo->prepare("
            INSERT INTO recuperacao_senha (colaborador_id, token, expira_em, usado)
            VALUES (:colaborador_id, :token, :expira_em, 0)
        ");

        $stmt->execute([
            'colaborador_id' => $colaboradorId,
            'token' => $token,
            'expira_em' => $expiraEm->format('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function buscarTokenValido(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT rs.*, c.email, c.nome
            FROM recuperacao_senha rs
            JOIN colaboradores c ON c.id = rs.colaborador_id
            WHERE rs.token = :token
              AND rs.usado = 0
              AND rs.expira_em > NOW()
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function marcarComoUsado(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    private function invalidarTokensAtivos(int $colaboradorId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE recuperacao_senha
            SET usado = 1
            WHERE colaborador_id = :colaborador_id AND usado = 0
        ");
        $stmt->execute(['colaborador_id' => $colaboradorId]);
    }
}

