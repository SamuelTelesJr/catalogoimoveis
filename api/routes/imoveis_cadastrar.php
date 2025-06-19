<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../utils/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(["error" => "Método não permitido"], 405);
}

// Obter dados JSON enviados
$input = json_decode(file_get_contents('php://input'), true);

// Validar campos obrigatórios
$required = ['user_id', 'cidade_id', 'titulo', 'categoria', 'localizacao', 'detalhes', 'valor_aluguel'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(["error" => "Campo obrigatório ausente: $field"], 400);
    }
}

// Definir campos opcionais
$valor_caucao = isset($input['valor_caucao']) ? $input['valor_caucao'] : 0;
$pode_dividir = isset($input['pode_dividir']) ? (int)$input['pode_dividir'] : 0;
$max_parcelas = isset($input['max_parcelas']) ? (int)$input['max_parcelas'] : 1;

try {
    $stmt = $pdo->prepare("INSERT INTO imoveis 
        (user_id, cidade_id, titulo, categoria, localizacao, detalhes, valor_aluguel, valor_caucao, pode_dividir, max_parcelas)
        VALUES (:user_id, :cidade_id, :titulo, :categoria, :localizacao, :detalhes, :valor_aluguel, :valor_caucao, :pode_dividir, :max_parcelas)");

    $stmt->execute([
        ':user_id' => $input['user_id'],
        ':cidade_id' => $input['cidade_id'],
        ':titulo' => $input['titulo'],
        ':categoria' => $input['categoria'],
        ':localizacao' => $input['localizacao'],
        ':detalhes' => $input['detalhes'],
        ':valor_aluguel' => $input['valor_aluguel'],
        ':valor_caucao' => $valor_caucao,
        ':pode_dividir' => $pode_dividir,
        ':max_parcelas' => $max_parcelas
    ]);

    jsonResponse(["success" => true, "message" => "Imóvel cadastrado com sucesso!", "id" => $pdo->lastInsertId()], 201);

} catch (PDOException $e) {
    jsonResponse(["error" => $e->getMessage()], 500);
}
?>
