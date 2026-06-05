<?php
/**
 * ColaboradorModel - Acesso a colaboradores
 */

require_once BASE_PATH . '/app/Core/Database.php';

class ColaboradorModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function buscarPorCpf(string $cpf): ?array
    {
        $cpf = preg_replace('/\D/', '', $cpf);
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.nome as permissao_nome
            FROM colaboradores c
            LEFT JOIN permissoes p ON p.id = c.permissao_id
            WHERE c.cpf = :cpf
        ");
        $stmt->execute(['cpf' => $cpf]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function validarTokenSessao(string $token): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.nome, p.nome as permissao_nome
            FROM colaborador_sessao s
            JOIN colaboradores c ON c.id = s.colaborador_id
            LEFT JOIN permissoes p ON p.id = c.permissao_id
            WHERE s.token = :token AND s.expira_em > NOW() AND c.ativo = 1
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function salvarTokenSessao(int $colaboradorId, string $token, int $expiraTimestamp): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO colaborador_sessao (colaborador_id, token, expira_em)
            VALUES (:colaborador_id, :token, FROM_UNIXTIME(:expira))
        ");
        $stmt->execute([
            'colaborador_id' => $colaboradorId,
            'token' => $token,
            'expira' => $expiraTimestamp,
        ]);
    }

    public function removerTokenSessao(string $token): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM colaborador_sessao WHERE token = :token");
        $stmt->execute(['token' => $token]);
    }

    /**
     * Total de colaboradores excluindo superadministrador (para dashboard).
     */
    public function countExcludingSuperadmin(): int
    {
        $stmt = $this->pdo->query("
            SELECT COUNT(*) FROM colaboradores c
            INNER JOIN permissoes p ON p.id = c.permissao_id
            WHERE p.nome != 'superadministrador'
        ");
        return (int) $stmt->fetchColumn();
    }

    public function listarTodos(bool $incluirInativos = false): array
    {
        $sql = "
            SELECT c.*, p.nome as permissao_nome
            FROM colaboradores c
            LEFT JOIN permissoes p ON p.id = c.permissao_id
        ";
        if (!$incluirInativos) {
            $sql .= " WHERE c.ativo = 1";
        }
        $sql .= " ORDER BY c.nome";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, p.nome as permissao_nome
            FROM colaboradores c
            LEFT JOIN permissoes p ON p.id = c.permissao_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function salvar(array $dados): int
    {
        $cpf = preg_replace('/\D/', '', $dados['cpf'] ?? '');
        $cpf = str_pad($cpf, 11, '0', STR_PAD_RIGHT);

        if (!cpf_valido($cpf)) {
            throw new InvalidArgumentException('CPF inválido.');
        }
        if ($this->buscarPorCpf($cpf)) {
            throw new InvalidArgumentException('Já existe colaborador cadastrado com este CPF.');
        }
        $stmt = $this->pdo->prepare("SELECT 1 FROM colaboradores WHERE email = ?");
        $stmt->execute([$dados['email']]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('Já existe colaborador cadastrado com este e-mail.');
        }

        $senha = $dados['senha'] ?? substr($cpf, 0, 6);
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO colaboradores (nome, cpf, email, senha, dt_nascimento, permissao_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($dados['nome'] ?? ''),
            $cpf,
            trim($dados['email'] ?? ''),
            $senhaHash,
            $dados['dt_nascimento'] ?? null,
            (int)($dados['permissao_id'] ?? 0),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function atualizar(int $id, array $dados): void
    {
        $colab = $this->buscarPorId($id);
        if (!$colab) {
            throw new InvalidArgumentException('Colaborador não encontrado.');
        }

        $cpf = preg_replace('/\D/', '', $dados['cpf'] ?? $colab['cpf'] ?? '');
        $cpf = str_pad($cpf, 11, '0', STR_PAD_RIGHT);

        if (!cpf_valido($cpf)) {
            throw new InvalidArgumentException('CPF inválido.');
        }
        $outro = $this->buscarPorCpf($cpf);
        if ($outro && (int)$outro['id'] !== (int)$id) {
            throw new InvalidArgumentException('Já existe outro colaborador com este CPF.');
        }

        $stmt = $this->pdo->prepare("SELECT id FROM colaboradores WHERE email = ? AND id != ?");
        $stmt->execute([$dados['email'] ?? '', $id]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('Já existe outro colaborador com este e-mail.');
        }

        if (!empty($dados['nova_senha'])) {
            $senhaHash = password_hash($dados['nova_senha'], PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                UPDATE colaboradores SET nome = ?, cpf = ?, email = ?, senha = ?, dt_nascimento = ?, permissao_id = ? WHERE id = ?
            ");
            $stmt->execute([
                trim($dados['nome'] ?? $colab['nome']),
                $cpf,
                trim($dados['email'] ?? $colab['email']),
                $senhaHash,
                $dados['dt_nascimento'] ?? $colab['dt_nascimento'],
                (int)($dados['permissao_id'] ?? $colab['permissao_id']),
                $id,
            ]);
        } else {
            $stmt = $this->pdo->prepare("
                UPDATE colaboradores SET nome = ?, cpf = ?, email = ?, dt_nascimento = ?, permissao_id = ? WHERE id = ?
            ");
            $stmt->execute([
                trim($dados['nome'] ?? $colab['nome']),
                $cpf,
                trim($dados['email'] ?? $colab['email']),
                $dados['dt_nascimento'] ?? $colab['dt_nascimento'],
                (int)($dados['permissao_id'] ?? $colab['permissao_id']),
                $id,
            ]);
        }
    }

    public function atualizarPerfil(int $id, array $dados, ?string $foto = null): void
    {
        $colab = $this->buscarPorId($id);
        if (!$colab) {
            throw new InvalidArgumentException('Colaborador não encontrado.');
        }

        $email = trim($dados['email'] ?? $colab['email'] ?? '');
        $stmt = $this->pdo->prepare("SELECT id FROM colaboradores WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('Já existe outro colaborador com este e-mail.');
        }

        $campos = [
            'nome' => trim($dados['nome'] ?? $colab['nome']),
            'email' => $email,
            'dt_nascimento' => $dados['dt_nascimento'] ?? $colab['dt_nascimento'],
        ];

        if (!empty($dados['nova_senha'])) {
            $campos['senha'] = password_hash($dados['nova_senha'], PASSWORD_DEFAULT);
        }

        if ($foto !== null) {
            $campos['foto'] = $foto;
        }

        $setParts = [];
        $values = [];
        foreach ($campos as $campo => $valor) {
            $setParts[] = "{$campo} = ?";
            $values[] = $valor;
        }
        $values[] = $id;

        $sql = "UPDATE colaboradores SET " . implode(', ', $setParts) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }

    public function atualizarSenha(int $id, string $novaSenha): void
    {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            UPDATE colaboradores
            SET senha = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$senhaHash, $id]);
    }

    public function ativar(int $id): void
    {
        $this->pdo->prepare("UPDATE colaboradores SET ativo = 1 WHERE id = ?")->execute([$id]);
    }

    public function inativar(int $id): void
    {
        $this->pdo->prepare("UPDATE colaboradores SET ativo = 0 WHERE id = ?")->execute([$id]);
    }
}
