<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../utils/response.php';

$acao = $_GET['acao'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

switch ($acao) {
    case 'cadastrar':
        // Campos obrigatórios
        $required = ['nome', 'email', 'senha'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Campo obrigatório ausente: $field"], 400);
            }
        }

        // Verificar se e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $input['email']]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'E-mail já cadastrado'], 400);
        }

        // Inserir usuário com senha hash
        $hashSenha = password_hash($input['senha'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nome, email, telefone, senha) VALUES (:nome, :email, :telefone, :senha)");
        $stmt->execute([
            ':nome' => $input['nome'],
            ':email' => $input['email'],
            ':telefone' => $input['telefone'] ?? '',
            ':senha' => $hashSenha
        ]);

        jsonResponse(['success' => true, 'message' => 'Usuário cadastrado com sucesso!', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'login':
        $required = ['email', 'senha'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Campo obrigatório ausente: $field"], 400);
            }
        }

        // Buscar usuário pelo e-mail
        $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM users WHERE email = :email");
        $stmt->execute([':email' => $input['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($input['senha'], $user['senha'])) {
            jsonResponse(['error' => 'E-mail ou senha incorretos'], 401);
        }

        // Login bem-sucedido - retornar dados do usuário (sem senha)
        unset($user['senha']);
        jsonResponse(['success' => true, 'user' => $user]);
        break;

    default:
        jsonResponse(['error' => 'Ação inválida'], 400);
}
?>
