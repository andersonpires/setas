<?php
$pageTitle = $pageTitle ?? 'Família';
$currentPage = $currentPage ?? 'familia';
$codigoFamilia = $codigoFamilia ?? '';
$membros = $membros ?? [];

ob_start();
?>
<div class="card">
  <h4 class="card-title-benef">Membros da Família - Código <?= htmlspecialchars($codigoFamilia) ?: '—' ?></h4>
  <?php if (empty($membros)): ?>
    <p class="card-desc">Nenhum membro encontrado para este código familiar. Acesse a página Beneficiário e realize uma busca para obter o link de acesso.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-familia">
        <thead>
          <tr>
            <th>CPF</th>
            <th>Nome</th>
            <th>Vale Gás Federal</th>
            <th>Vale Gás Estadual</th>
            <th>Aluguel Social</th>
            <th>Cartão CE Sem Fome</th>
            <th>Programa Criança Feliz</th>
            <th>Cartão Mais Infância</th>
            <th>Bolsa Família</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($membros as $m): ?>
          <tr>
            <td data-label="CPF"><?= htmlspecialchars($m['cpf']) ?></td>
            <td data-label="Nome"><?= htmlspecialchars($m['nome']) ?></td>
            <td data-label="Vale Gás Federal"><span class="benef-bene-valor <?= $m['possui_vale_gas_federal'] ? 'sim' : 'nao' ?>"><?= $m['possui_vale_gas_federal'] ? 'Sim' : 'Não' ?></span></td>
            <td data-label="Vale Gás Estadual"><span class="benef-bene-valor <?= $m['possui_vale_gas_estadual'] ? 'sim' : 'nao' ?>"><?= $m['possui_vale_gas_estadual'] ? 'Sim' : 'Não' ?></span></td>
            <td data-label="Aluguel Social"><span class="benef-bene-valor <?= $m['possui_aluguel_social'] ? 'sim' : 'nao' ?>"><?= $m['possui_aluguel_social'] ? 'Sim' : 'Não' ?></span></td>
            <td data-label="Cartão CE Sem Fome"><span class="benef-bene-valor <?= $m['possui_cartao_ce_sem_fome'] ? 'sim' : 'nao' ?>"><?= $m['possui_cartao_ce_sem_fome'] ? 'Sim' : 'Não' ?></span></td>
            <td data-label="Programa Criança Feliz"><span class="benef-bene-valor <?= $m['possui_prog_crianca_feliz'] ? 'sim' : 'nao' ?>"><?= $m['possui_prog_crianca_feliz'] ? 'Sim' : 'Não' ?></span></td>
            <td data-label="Cartão Mais Infância"><span class="benef-bene-valor <?= $m['possui_cartao_mais_infancia'] ? 'sim' : 'nao' ?>"><?= $m['possui_cartao_mais_infancia'] ? 'Sim' : 'Não' ?></span></td>
            <td data-label="Bolsa Família"><span class="benef-bene-valor <?= $m['possui_bolsa_familia'] ? 'sim' : 'nao' ?>"><?= $m['possui_bolsa_familia'] ? 'Sim' : 'Não' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require BASE_PATH . '/app/template/layout.php';