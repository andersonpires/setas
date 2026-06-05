<?php
/**
 * Validação de CPF - dígitos verificadores
 * Algoritmo conforme: https://www.campuscode.com.br/conteudos/o-calculo-do-digito-verificador-do-cpf-e-do-cnpj
 *
 * Uso: require_once BASE_PATH . '/app/Helpers/cpf.php';
 *      if (cpf_valido('123.456.789-09')) { ... }
 */

/**
 * Verifica se o CPF é válido (dígitos verificadores corretos).
 * Aceita CPF com ou sem formatação (123.456.789-09 ou 12345678909).
 * Rejeita CPFs com todos os dígitos iguais (111.111.111-11, 000.000.000-00, etc).
 *
 * @param string $cpf CPF a validar
 * @return bool true se válido, false caso contrário
 */
function cpf_valido(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf);

    if (strlen($cpf) !== 11) {
        return false;
    }

    // Rejeita CPFs com todos os dígitos iguais (válidos pelo algoritmo mas inexistentes)
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Validação do primeiro dígito verificador (pesos 10 a 2)
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += (int) $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : (11 - $resto);
    if ((int) $cpf[9] !== $dv1) {
        return false;
    }

    // Validação do segundo dígito verificador (pesos 11 a 2)
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += (int) $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : (11 - $resto);
    if ((int) $cpf[10] !== $dv2) {
        return false;
    }

    return true;
}
