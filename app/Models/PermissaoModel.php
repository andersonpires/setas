<?php
/**
 * PermissaoModel - Acesso a permissões e funcionalidades vinculadas
 */

require_once BASE_PATH . '/app/Core/Database.php';

class PermissaoModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function listarTodas(): array
    {
        return $this->pdo->query("
            SELECT id, nome, created_at
            FROM permissoes
            ORDER BY nome
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permissoes WHERE id = ?");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function buscarPorNome(string $nome, ?int $excluirId = null): ?array
    {
        if ($excluirId) {
            $stmt = $this->pdo->prepare("SELECT * FROM permissoes WHERE nome = ? AND id != ?");
            $stmt->execute([$nome, $excluirId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM permissoes WHERE nome = ?");
            $stmt->execute([$nome]);
        }
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function salvar(string $nome): int
    {
        $nome = trim($nome);
        if (strtolower($nome) === 'superadministrador') {
            throw new InvalidArgumentException('Não é permitido cadastrar permissão com esse nome.');
        }
        if ($this->buscarPorNome($nome)) {
            throw new InvalidArgumentException('Já existe uma permissão com este nome.');
        }
        $stmt = $this->pdo->prepare("INSERT INTO permissoes (nome) VALUES (?)");
        $stmt->execute([$nome]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, string $nome): void
    {
        $permissao = $this->buscarPorId($id);
        if (!$permissao) {
            throw new InvalidArgumentException('Permissão não encontrada.');
        }
        if ($permissao['nome'] === 'superadministrador') {
            throw new InvalidArgumentException('Não é permitido alterar a permissão superadministrador.');
        }
        $nome = trim($nome);
        if (strtolower($nome) === 'superadministrador') {
            throw new InvalidArgumentException('Não é permitido usar esse nome.');
        }
        if ($this->buscarPorNome($nome, $id)) {
            throw new InvalidArgumentException('Já existe outra permissão com este nome.');
        }
        $stmt = $this->pdo->prepare("UPDATE permissoes SET nome = ? WHERE id = ?");
        $stmt->execute([$nome, $id]);
    }

    public function getFuncionalidadesVinculadas(int $permissaoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT funcionalidade_id FROM permissao_funcionalidade WHERE permissao_id = ?
        ");
        $stmt->execute([$permissaoId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function vincularFuncionalidades(int $permissaoId, array $funcionalidadeIds): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM permissao_funcionalidade WHERE permissao_id = ?");
            $stmt->execute([$permissaoId]);
            if (!empty($funcionalidadeIds)) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO permissao_funcionalidade (permissao_id, funcionalidade_id) VALUES (?, ?)
                ");
                foreach ($funcionalidadeIds as $fid) {
                    $stmt->execute([$permissaoId, (int)$fid]);
                }
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): void
    {
        $permissao = $this->buscarPorId($id);
        if (!$permissao) {
            throw new InvalidArgumentException('Permissão não encontrada.');
        }
        if ($permissao['nome'] === 'superadministrador') {
            throw new InvalidArgumentException('Não é permitido excluir a permissão superadministrador.');
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM colaboradores WHERE permissao_id = ?");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new InvalidArgumentException('Não é possível excluir: existem colaboradores com esta permissão.');
        }
        $this->pdo->prepare("DELETE FROM permissao_funcionalidade WHERE permissao_id = ?")->execute([$id]);
        $this->pdo->prepare("DELETE FROM permissoes WHERE id = ?")->execute([$id]);
    }
}
