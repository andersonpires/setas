<?php
$pageTitle = 'Dashboard';
$currentPage = 'home';
$subtitle = 'Página inicial do sistema';
$colaboradorNome = $colaboradorNome ?? 'Usuário';
$ultimoAcesso = $ultimoAcesso ?? null;
$totalColaboradores = $totalColaboradores ?? 0;
$totalBeneficiarios = $totalBeneficiarios ?? 0;

$saudacao = 'Olá, ' . htmlspecialchars($colaboradorNome) . '.';
if ($ultimoAcesso && !empty($ultimoAcesso['data_hora'])) {
    $dt = date('d/m/Y', strtotime($ultimoAcesso['data_hora']));
    $hora = date('H', strtotime($ultimoAcesso['data_hora'])) . 'h' . date('i', strtotime($ultimoAcesso['data_hora']));
    $saudacao .= ' Seu último acesso foi em ' . $dt . ' às ' . $hora . '.';
}

ob_start();
?>
<p class="page-saudacao"><?= $saudacao ?></p>
<div class="card">
  <h3 style="margin-top:0;">Bem-vindo ao SETAS-WEB</h3>
  <p>Sistema de acompanhamento de beneficiários e colaboradores.</p>
</div>
<div class="row">
  <div class="col-md-4">
    <div class="card">
      <h4 style="margin-top:0;color:var(--color-primary);">Colaboradores</h4>
      <p id="totalColaboradores"><?= (int)$totalColaboradores ?></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <h4 style="margin-top:0;color:var(--color-primary);">Beneficiários</h4>
      <p id="totalBeneficiarios"><?= (int)$totalBeneficiarios ?></p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <h4 style="margin-top:0;color:var(--color-primary);">Relatórios</h4>
      <p>Acesse em breve</p>
    </div>
  </div>
</div>
<div class="card">
  <h4 style="margin-top:0;">Gráfico de Atividades</h4>
  <canvas id="chartDashboard" height="100"></canvas>
</div>
<?php
$content = ob_get_clean();
$scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  new Chart(document.getElementById("chartDashboard"), {
    type: "bar",
    data: {
      labels: ["Jan","Fev","Mar","Abr","Mai","Jun"],
      datasets: [{
        label: "Acessos",
        data: [12, 19, 8, 15, 22, 18],
        backgroundColor: "rgba(63, 159, 110, 0.6)",
        borderColor: "#3F9F6E",
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
});
</script>';
require BASE_PATH . '/app/template/layout.php';
?>
