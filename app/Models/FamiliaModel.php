<?php
/**
 * FamiliaModel - Lista membros da família e benefícios de cada um
 */

require_once BASE_PATH . '/app/Core/Database.php';

class FamiliaModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Retorna todos os membros da família (cpf, nome) e status de cada benefício
     * @return array[] Lista de membros com possui_* para cada benefício
     */
    public function buscarMembrosComBeneficios(string $codigoFamilia): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT bcf.cpf, COALESCE(b.nome, bcf.nome) AS nome
            FROM beneficiario_cod_familia bcf
            LEFT JOIN beneficiario b ON b.cpf = bcf.cpf
            WHERE bcf.codigo_familia = ?
            ORDER BY nome
        ");
        $stmt->execute([$codigoFamilia]);
        $membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($membros as $m) {
            $cpf = $m['cpf'];
            $cpfNorm = $this->normalizarCpf($cpf);
            $benef = $this->buscarBeneficiario($cpfNorm);

            $resultado[] = [
                'cpf' => $this->formatarCpf($cpfNorm),
                'nome' => $m['nome'] ?? '—',
                'possui_vale_gas_federal' => $this->cpfExisteEmTabela($cpfNorm, 'vale_gas_federal'),
                'possui_vale_gas_estadual' => $this->cpfExisteEmTabela($cpfNorm, 'vale_gas_ce'),
                'possui_aluguel_social' => $this->cpfExisteEmTabela($cpfNorm, 'aluguel_social'),
                'possui_cartao_ce_sem_fome' => $this->cpfExisteEmTabela($cpfNorm, 'cartao_ce_sem_fome'),
                'possui_prog_crianca_feliz' => $this->cpfExisteEmTabela($cpfNorm, 'prog_crianca_feliz'),
                'possui_cartao_mais_infancia' => $this->cpfExisteEmTabela($cpfNorm, 'cartao_mais_infancia'),
                'possui_bolsa_familia' => $benef !== null && isset($benef['pbf']) && $benef['pbf'] !== null && trim((string)$benef['pbf']) !== '',
            ];
        }

        return $resultado;
    }

    public static function normalizarCpf(string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', trim($cpf));
        return str_pad($cpf, 11, '0', STR_PAD_RIGHT);
    }

    private function buscarBeneficiario(string $cpfNorm): ?array
    {
        $stmt = $this->pdo->prepare("SELECT pbf FROM beneficiario WHERE cpf = ? LIMIT 1");
        $stmt->execute([$cpfNorm]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function cpfExisteEmTabela(string $cpfNorm, string $tabela): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$tabela} WHERE cpf = ? LIMIT 1");
        $stmt->execute([$cpfNorm]);
        return (bool) $stmt->fetch();
    }

    private function formatarCpf(string $cpf): string
    {
        if (strlen($cpf) !== 11) return $cpf;
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }
}
