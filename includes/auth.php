<?php

/*
 * ============================================================
 * AUTENTICAÇÃO E CONTROLE DE ACESSO - MATHPLAY
 * ============================================================
 *
 * Este arquivo deve ser incluído nas páginas que precisam
 * de autenticação.
 *
 * Exemplos:
 *
 * Página do aluno:
 * require_once '../includes/auth.php';
 * protegerPagina('aluno');
 *
 * Página do professor:
 * require_once '../includes/auth.php';
 * protegerPagina('professor');
 *
 * Página que pode ser acessada por professor ou admin:
 * require_once '../includes/auth.php';
 * protegerPagina(['professor', 'admin']);
 *
 */


/*
 * Inicia a sessão somente se ela ainda não estiver iniciada.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
 * ============================================================
 * VERIFICAR SE O USUÁRIO ESTÁ LOGADO
 * ============================================================
 */

function usuarioLogado(): bool
{
    return isset($_SESSION['usuario_id']);
}


/*
 * ============================================================
 * EXIGIR LOGIN
 * ============================================================
 *
 * Se não estiver logado, volta para a página de login.
 */

function exigirLogin(): void
{
    if (!usuarioLogado()) {

        header('Location: ../login.php');
        exit;
    }
}


/*
 * ============================================================
 * PEGAR ID DO USUÁRIO LOGADO
 * ============================================================
 */

function usuarioId(): ?int
{
    if (!usuarioLogado()) {
        return null;
    }

    return (int) $_SESSION['usuario_id'];
}


/*
 * ============================================================
 * PEGAR TIPO DO USUÁRIO
 * ============================================================
 */

function tipoUsuario(): ?string
{
    return $_SESSION['tipo'] ?? null;
}


/*
 * ============================================================
 * PEGAR NOME DO USUÁRIO
 * ============================================================
 */

function nomeUsuario(): string
{
    return $_SESSION['nome'] ?? '';
}


/*
 * ============================================================
 * VERIFICAR PERMISSÃO
 * ============================================================
 */

function usuarioTemPermissao($tiposPermitidos): bool
{
    if (!usuarioLogado()) {
        return false;
    }

    if (!is_array($tiposPermitidos)) {
        $tiposPermitidos = [$tiposPermitidos];
    }

    return in_array(
        tipoUsuario(),
        $tiposPermitidos,
        true
    );
}


/*
 * ============================================================
 * PROTEGER PÁGINA
 * ============================================================
 *
 * Exemplos:
 *
 * protegerPagina('aluno');
 *
 * protegerPagina('professor');
 *
 * protegerPagina(['professor', 'admin']);
 *
 */

function protegerPagina($tiposPermitidos): void
{
    exigirLogin();

    if (!usuarioTemPermissao($tiposPermitidos)) {

        /*
         * Se o usuário estiver logado mas não possuir
         * permissão, enviamos para o ambiente correto.
         */

        if (tipoUsuario() === 'aluno') {

            header('Location: ../aluno/dashboard.php');
            exit;

        }

        if (
            tipoUsuario() === 'professor' ||
            tipoUsuario() === 'admin'
        ) {

            header('Location: ../professor/dashboard.php');
            exit;

        }


        /*
         * Caso não seja possível identificar o tipo,
         * encerramos a sessão e voltamos para o login.
         */

        session_unset();
        session_destroy();

        header('Location: ../login.php');
        exit;
    }
}


/*
 * ============================================================
 * VERIFICAR SE É ALUNO
 * ============================================================
 */

function ehAluno(): bool
{
    return tipoUsuario() === 'aluno';
}


/*
 * ============================================================
 * VERIFICAR SE É PROFESSOR
 * ============================================================
 */

function ehProfessor(): bool
{
    return tipoUsuario() === 'professor';
}


/*
 * ============================================================
 * VERIFICAR SE É ADMIN
 * ============================================================
 */

function ehAdmin(): bool
{
    return tipoUsuario() === 'admin';
}


/*
 * ============================================================
 * VERIFICAR SE É PROFESSOR OU ADMIN
 * ============================================================
 */

function ehProfessorOuAdmin(): bool
{
    return in_array(
        tipoUsuario(),
        ['professor', 'admin'],
        true
    );
}