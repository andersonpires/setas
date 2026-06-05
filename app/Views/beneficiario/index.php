<?php
$pageTitle = $pageTitle ?? 'Beneficiário';
$currentPage = $currentPage ?? 'beneficiario';
$subtitle = $subtitle ?? 'Consulta de benefícios por CPF';

ob_start();
?>
<div class="card card-beneficiario-busca">
  <h4 class="card-title-benef">Consultar Beneficiário</h4>
  <p class="card-desc">Informe o CPF para buscar benefícios e dados do beneficiário em todas as bases.</p>
  <form id="formBeneficiario" class="form-inline-benef">
    <div class="form-group">
      <label for="cpf_beneficiario">CPF</label>
      <input type="text" id="cpf_beneficiario" name="cpf" placeholder="000.000.000-00" maxlength="14" required
             inputmode="numeric" autocomplete="off">
      <span id="cpfStatus" class="cpf-status"></span>
    </div>
    <button type="submit" class="btn btn-primary">Buscar</button>
  </form>
</div>

<div id="resultadoBeneficiario" class="resultado-beneficiario" style="display:none;"></div>
<div id="erroBeneficiario" class="erro-benef" style="display:none;"></div>

<?php
$content = ob_get_clean();
$baseUrl = addslashes(BASE_URL);
$scripts = '
<script>
(function() {
  const BASE_URL = "' . $baseUrl . '";
  const form = document.getElementById("formBeneficiario");
  const resultado = document.getElementById("resultadoBeneficiario");
  const erro = document.getElementById("erroBeneficiario");
  const cpfInput = document.getElementById("cpf_beneficiario");
  const cpfStatus = document.getElementById("cpfStatus");

  function formatarCpf(v) {
    v = v.replace(/\D/g, "");
    return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
  }
  cpfInput.addEventListener("input", function() {
    this.value = formatarCpf(this.value);
  });

  form.addEventListener("submit", function(e) {
    e.preventDefault();
    resultado.style.display = "none";
    erro.style.display = "none";
    const cpf = cpfInput.value.replace(/\D/g, "");
    if (cpf.length < 11) {
      cpfStatus.textContent = "CPF inválido (mín. 11 dígitos)";
      cpfStatus.className = "cpf-status invalido";
      return;
    }
    cpfStatus.textContent = "";
    if (window.showLoadingOverlay) window.showLoadingOverlay();
    const formData = new FormData();
    formData.append("cpf", cpfInput.value);

    fetch("' . BASE_URL . 'beneficiario/buscar", {
      method: "POST",
      body: formData,
      headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(r => r.json())
    .then(res => {
      if (window.hideLoadingOverlay) window.hideLoadingOverlay();
      if (res.ok) {
        resultado.innerHTML = renderResultado(res.dados);
        resultado.style.display = "block";
      } else {
        erro.textContent = res.msg || "Erro ao buscar.";
        erro.style.display = "block";
      }
    })
    .catch(err => {
      if (window.hideLoadingOverlay) window.hideLoadingOverlay();
      erro.textContent = "Erro na requisição. Tente novamente.";
      erro.style.display = "block";
    });
  });

  function simNao(v) { return v ? "Sim" : "Não"; }
  function fmtData(d) { return d ? d.split("-").reverse().join("/") : "—"; }
  function fmtMoeda(v) {
    if (v === null || v === undefined || v === "") return "—";
    return "R$ " + parseFloat(v).toLocaleString("pt-BR", {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function renderResultado(d) {
    const membros = (d.membros_familia && d.membros_familia.length) ? d.membros_familia.join(", ") : "—";
    const cpfParam = (d.cpf_raw || d.cpf || "").replace(/\D/g, "");
    const pdfUrl = BASE_URL + "beneficiario/pdf?cpf=" + encodeURIComponent(cpfParam);
    return `
<div class="benef-resultado">
  <div class="benef-resultado-header">
    <h4 class="benef-resultado-titulo">Dados do Beneficiário</h4>
    <a href="${pdfUrl}" target="_blank" class="btn btn-danger benef-btn-pdf">Gerar PDF</a>
  </div>
  <div class="benef-grid">
    <div class="benef-item"><span class="benef-label">CPF</span><span class="benef-valor">${d.cpf || "—"}</span></div>
    <div class="benef-item"><span class="benef-label">Nome</span><span class="benef-valor">${d.nome || "—"}</span></div>
    <div class="benef-item"><span class="benef-label">NIS</span><span class="benef-valor">${d.nis || "—"}</span></div>
    <div class="benef-item"><span class="benef-label">Data de nascimento</span><span class="benef-valor">${fmtData(d.dt_nascimento)}</span></div>
    <div class="benef-item"><span class="benef-label">Sexo</span><span class="benef-valor">${d.sexo || "—"}</span></div>
    <div class="benef-item benef-item-full"><span class="benef-label">Endereço</span><span class="benef-valor">${d.endereco || "—"}</span></div>
    <div class="benef-item"><span class="benef-label">Renda familiar média</span><span class="benef-valor">${fmtMoeda(d.renda_media)}</span></div>
    <div class="benef-item"><span class="benef-label">Renda total</span><span class="benef-valor">${fmtMoeda(d.renda_total)}</span></div>
    <div class="benef-item"><span class="benef-label">Data do cadastro</span><span class="benef-valor">${fmtData(d.data_cadastro)}</span></div>
    <div class="benef-item"><span class="benef-label">Última atualização</span><span class="benef-valor">${fmtData(d.data_atualizacao)}</span></div>
  </div>
  <h4 class="benef-resultado-titulo benef-mt">Código familiar e membros</h4>
  <div class="benef-grid">
    <div class="benef-item"><span class="benef-label">Código familiar</span><span class="benef-valor">${d.codigo_familiar || "—"}</span></div>
    <div class="benef-item benef-item-full"><span class="benef-label">Outros membros da família</span><span class="benef-valor">${membros}</span>${(d.membros_familia && d.membros_familia.length > 0 && d.codigo_familiar) ? " <a href=\"" + BASE_URL + "familia?codigo=" + encodeURIComponent(d.codigo_familiar) + "\" class=\"link-familia\" target=\"_self\">(Ver dados dos demais familiares)</a>" : ""}</div>
  </div>
  <h4 class="benef-resultado-titulo benef-mt">Benefícios</h4>
  <div class="benef-beneficios">
    <div class="benef-bene-item"><span class="benef-bene-label">Vale Gás Federal</span><span class="benef-bene-valor ${d.possui_vale_gas_federal ? "sim" : "nao"}">${simNao(d.possui_vale_gas_federal)}</span></div>
    <div class="benef-bene-item"><span class="benef-bene-label">Vale Gás Estadual</span><span class="benef-bene-valor ${d.possui_vale_gas_estadual ? "sim" : "nao"}">${simNao(d.possui_vale_gas_estadual)}</span></div>
    <div class="benef-bene-item"><span class="benef-bene-label">Aluguel Social</span><span class="benef-bene-valor ${d.possui_aluguel_social ? "sim" : "nao"}">${simNao(d.possui_aluguel_social)}</span></div>
    <div class="benef-bene-item"><span class="benef-bene-label">Cartão Ceará Sem Fome</span><span class="benef-bene-valor ${d.possui_cartao_ce_sem_fome ? "sim" : "nao"}">${simNao(d.possui_cartao_ce_sem_fome)}</span></div>
    <div class="benef-bene-item"><span class="benef-bene-label">Programa Criança Feliz</span><span class="benef-bene-valor ${d.possui_prog_crianca_feliz ? "sim" : "nao"}">${simNao(d.possui_prog_crianca_feliz)}</span></div>
    <div class="benef-bene-item"><span class="benef-bene-label">Cartão Mais Infância</span><span class="benef-bene-valor ${d.possui_cartao_mais_infancia ? "sim" : "nao"}">${simNao(d.possui_cartao_mais_infancia)}</span></div>
    <div class="benef-bene-item"><span class="benef-bene-label">Bolsa Família</span><span class="benef-bene-valor ${d.possui_bolsa_familia ? "sim" : "nao"}">${simNao(d.possui_bolsa_familia)}</span></div>
  </div>
</div>`;
  }
})();
</script>';
require BASE_PATH . '/app/template/layout.php';
