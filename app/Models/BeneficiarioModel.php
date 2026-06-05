<?php
/**
 * BeneficiarioModel - Busca dados do beneficiário em todas as tabelas
 */

require_once BASE_PATH . '/app/Core/Database.php';

class BeneficiarioModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Normaliza CPF: apenas números, zeros à direita até 11 caracteres
     */
    public static function normalizarCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', trim($cpf));
        return str_pad($cpf, 11, '0', STR_PAD_RIGHT);
    }

    /**
     * Busca todos os dados do beneficiário por CPF
     * @return array|null Dados consolidados ou null se não encontrado em nenhuma tabela
     */
    public function buscarPorCpf(string $cpf): ?array
    {
        $cpfNorm = self::normalizarCpf($cpf);

        $benef = $this->buscarBeneficiario($cpfNorm);
        $aluguel = $this->buscarAluguelSocial($cpfNorm);

        if (!$benef && !$aluguel) {
            return null;
        }

        $dados = [
            'cpf' => $this->formatarCpf($cpfNorm),
            'cpf_raw' => $cpfNorm,
            'nome' => $benef['nome'] ?? $aluguel['nome'] ?? '—',
            'nis' => $benef['nis'] ?? $aluguel['nis'] ?? '—',
            'dt_nascimento' => $benef['dt_nascimento'] ?? null,
            'sexo' => $benef['sexo'] ?? '—',
            'endereco' => $this->montarEndereco($benef ?? $aluguel),
            'renda_media' => $benef['renda_media'] ?? null,
            'renda_total' => $benef['renda_total'] ?? null,
            'data_cadastro' => $benef['data_cadastro'] ?? null,
            'data_atualizacao' => $benef['data_atualizacao'] ?? null,
            'codigo_familiar' => $this->buscarCodigoFamiliarPorCpf($cpfNorm),
            'membros_familia' => $this->buscarMembrosFamiliaPorCpf($cpfNorm),
            'possui_vale_gas_federal' => $this->cpfExisteEmTabela($cpfNorm, 'vale_gas_federal'),
            'possui_vale_gas_estadual' => $this->cpfExisteEmTabela($cpfNorm, 'vale_gas_ce'),
            'possui_aluguel_social' => $this->cpfExisteEmTabela($cpfNorm, 'aluguel_social'),
            'possui_cartao_ce_sem_fome' => $this->cpfExisteEmTabela($cpfNorm, 'cartao_ce_sem_fome'),
            'possui_prog_crianca_feliz' => $this->cpfExisteEmTabela($cpfNorm, 'prog_crianca_feliz'),
            'possui_cartao_mais_infancia' => $this->cpfExisteEmTabela($cpfNorm, 'cartao_mais_infancia'),
            'possui_bolsa_familia' => $benef !== null && isset($benef['pbf']) && $benef['pbf'] !== null && trim((string)$benef['pbf']) !== '',
        ];

        return $dados;
    }

    private function buscarBeneficiario(string $cpfNorm): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id_beneficiario, cpf, nis, nome, pbf, dt_nascimento, sexo, tipo_logradouro, logradouro,
                   localidade, municipio, renda_media, renda_total, data_cadastro, data_atualizacao
            FROM beneficiario WHERE cpf = ? ORDER BY id_beneficiario ASC LIMIT 1
        ");
        $stmt->execute([$cpfNorm]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /** Verifica se o CPF existe na tabela (por coluna cpf) */
    private function cpfExisteEmTabela(string $cpfNorm, string $tabela): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$tabela} WHERE cpf = ? LIMIT 1");
        $stmt->execute([$cpfNorm]);
        return (bool) $stmt->fetch();
    }

    private function buscarAluguelSocial(string $cpfNorm): ?array
    {
        $stmt = $this->pdo->prepare("SELECT cpf, nis, nome FROM aluguel_social WHERE cpf = ? LIMIT 1");
        $stmt->execute([$cpfNorm]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function montarEndereco(array $r): string
    {
        $partes = [];
        if (!empty($r['tipo_logradouro']) || !empty($r['logradouro'])) {
            $partes[] = trim(($r['tipo_logradouro'] ?? '') . ' ' . ($r['logradouro'] ?? ''));
        }
        if (!empty($r['endereco'])) {
            $partes[] = $r['endereco'];
        }
        if (!empty($r['localidade'])) $partes[] = $r['localidade'];
        if (!empty($r['municipio'])) $partes[] = $r['municipio'];
        return !empty($partes) ? implode(', ', array_filter($partes)) : '—';
    }

    private function buscarCodigoFamiliarPorCpf(string $cpfNorm): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT codigo_familia FROM beneficiario_cod_familia WHERE cpf = ? LIMIT 1
        ");
        $stmt->execute([$cpfNorm]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ? $r['codigo_familia'] : null;
    }

    private function buscarMembrosFamiliaPorCpf(string $cpfNorm): array
    {
        $stmt = $this->pdo->prepare("
            SELECT codigo_familia FROM beneficiario_cod_familia WHERE cpf = ?
        ");
        $stmt->execute([$cpfNorm]);
        $codigos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($codigos)) return [];

        $placeholders = implode(',', array_fill(0, count($codigos), '?'));
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT b.nome FROM beneficiario_cod_familia bcf
            JOIN beneficiario b ON b.cpf = bcf.cpf
            WHERE bcf.codigo_familia IN ($placeholders) AND bcf.cpf != ?
        ");
        $stmt->execute(array_merge($codigos, [$cpfNorm]));
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function formatarCpf(string $cpf): string
    {
        if (strlen($cpf) !== 11) return $cpf;
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    /**
     * Total de beneficiários para dashboard: CPFs distintos (exceto 00000000000) + qtd de registros com CPF 00000000000.
     * Usa a view v_todos_cpfs (todas as tabelas com campo cpf).
     */
    public function getTotalBeneficiariosDashboard(): int
    {
        try {
            $stmt = $this->pdo->query("
                SELECT
                    (SELECT COUNT(DISTINCT cpf) FROM v_todos_cpfs WHERE cpf != '00000000000')
                    + (SELECT COUNT(*) FROM v_todos_cpfs WHERE cpf = '00000000000')
                AS total
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($row['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
}
