<?php
/**
 * FuncionalidadeModel - Acesso às funcionalidades (páginas do sistema)
 */

require_once BASE_PATH . '/app/Core/Database.php';

class FuncionalidadeModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function listarTodas(bool $apenasAtivas = false): array
    {
        $sql = "SELECT id, nome, rota, ativo, created_at FROM funcionalidades";
        if ($apenasAtivas) {
            $sql .= " WHERE ativo = 1";
        }
        $sql .= " ORDER BY nome";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM funcionalidades WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function buscarPorRota(string $rota, ?int $excluirId = null): ?array
    {
        $rota = trim($rota);
        if ($excluirId) {
            $stmt = $this->pdo->prepare("SELECT * FROM funcionalidades WHERE rota = ? AND id != ?");
            $stmt->execute([$rota, $excluirId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM funcionalidades WHERE rota = ?");
            $stmt->execute([$rota]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function salvar(string $nome, string $rota, bool $ativo = true): int
    {
        $nome = trim($nome);
        $rota = trim($rota);
        if ($this->buscarPorRota($rota)) {
            throw new InvalidArgumentException('Já existe uma funcionalidade com esta rota.');
        }
        $stmt = $this->pdo->prepare("INSERT INTO funcionalidades (nome, rota, ativo) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $rota, $ativo ? 1 : 0]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, string $nome, string $rota, bool $ativo = true): void
    {
        $f = $this->buscarPorId($id);
        if (!$f) {
            throw new InvalidArgumentException('Funcionalidade não encontrada.');
        }
        $nome = trim($nome);
        $rota = trim($rota);
        if ($this->buscarPorRota($rota, $id)) {
            throw new InvalidArgumentException('Já existe outra funcionalidade com esta rota.');
        }
        $stmt = $this->pdo->prepare("UPDATE funcionalidades SET nome = ?, rota = ?, ativo = ? WHERE id = ?");
        $stmt->execute([$nome, $rota, $ativo ? 1 : 0, $id]);
    }

    public function excluir(int $id): void
    {
        $this->pdo->prepare("DELETE FROM permissao_funcionalidade WHERE funcionalidade_id = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM funcionalidades WHERE id = ?")->execute([$id]);
    }
}
