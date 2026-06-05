<?php
/**
 * Limpa todas as tabelas que foram populadas via importação de arquivos CSV.
 * Ordem: tabelas dependentes primeiro (FK para beneficiario/codigo_familia), depois beneficiario, depois codigo_familia.
 *
 * Execute: php limpar_tabelas_csv.php
 * Ou acesse: http://localhost/setas-web/database/limpar_tabelas_csv.php
 */

require_once dirname(__DIR__) . '/config/config.php';

echo "<pre style='font-family:monospace;'>";
echo "=== Limpeza das tabelas originadas de CSV ===\n\n";

$tabelasOrdem = [
    'beneficiario_cod_familia',  // FK: beneficiario, codigo_familia
    'vale_gas_federal',          // FK: beneficiario
    'vale_gas_ce',               // FK: beneficiario
    'prog_crianca_feliz',        // FK: beneficiario
    'cartao_mais_infancia',      // FK: beneficiario
    'cartao_ce_sem_fome',        // FK: beneficiario
    'aluguel_social',            // FK: beneficiario (opcional)
    'beneficiario',
    'codigo_familia',
];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tabelasOrdem as $tabela) {
        $pdo->exec("TRUNCATE TABLE `{$tabela}`");
        echo "[OK] TRUNCATE {$tabela}\n";
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    echo "\n=== Limpeza concluída com sucesso! ===\n\n";
    echo "--- LISTA PARA REIMPORTAR (uma a uma) ---\n\n";

    $lista = [
        ['tabela' => 'codigo_familia',           'arquivo' => 'bolsa_familia.csv',           'script' => 'import_codigo_familia_bolsa_familia.php'],
        ['tabela' => 'beneficiario',             'arquivo' => 'bolsa_familia.csv',           'script' => 'import_beneficiario_bolsa_familia.php'],
        ['tabela' => 'beneficiario_cod_familia', 'arquivo' => 'bolsa_familia.csv',           'script' => 'import_beneficiario_cod_familia_bolsa_familia.php'],
        ['tabela' => 'vale_gas_federal',          'arquivo' => 'vale_gas_federal.CSV',        'script' => 'import_vale_gas_federal.php'],
        ['tabela' => 'vale_gas_ce',              'arquivo' => 'vale_gas_ce.CSV',             'script' => 'import_vale_gas_ce.php'],
        ['tabela' => 'prog_crianca_feliz',       'arquivo' => 'prog_crianca_feliz.CSV',      'script' => 'import_prog_crianca_feliz.php'],
        ['tabela' => 'cartao_mais_infancia',     'arquivo' => 'cartao_mais_infancia.CSV',    'script' => 'import_cartao_mais_infancia.php'],
        ['tabela' => 'cartao_ce_sem_fome',       'arquivo' => 'cartao_ce_sem_fome.CSV',      'script' => 'import_cartao_ce_sem_fome.php'],
        ['tabela' => 'aluguel_social',           'arquivo' => 'aluguel_social.CSV',           'script' => 'import_aluguel_social.php'],
    ];

    foreach ($lista as $i => $item) {
        $n = $i + 1;
        echo "{$n}. Tabela: {$item['tabela']}\n";
        echo "   Arquivo: !Suporte/base_dados/{$item['arquivo']}\n";
        echo "   Script:  database/{$item['script']}\n\n";
    }

    echo "Ordem recomendada de execução dos imports: 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9\n";
    echo "</pre>";

} catch (PDOException $e) {
    echo "[ERRO] " . $e->getMessage() . "\n";
    echo "</pre>";
}
