<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../utils/response.php';

$method = $_SERVER['REQUEST_METHOD'];

// Para GET: ?tipo=interesses ou ?tipo=imoveis
$tipo = $_GET['tipo'] ?? '';

if ($method === 'GET') {
    if ($tipo === 'interesses') {
        // Listar pessoas interessadas em dividir
        try {
            $stmt = $pdo->query("SELECT d.*, u.nome, c.nome as cidade_nome
                                 FROM interesse_divisao d
                                 JOIN users u ON d.user_id = u.id
                                 JOIN cidades c ON d.cidade_id = c.id
                                 ORDER BY d.criado_em DESC
                                 LIMIT 50");
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse($dados);
        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    } elseif ($tipo === 'imoveis') {
        // Listar imóveis para dividir
        try {
            $stmt = $pdo->query("SELECT i.*, u.nome as dono_nome, c.nome as cidade_nome
                                 FROM imoveis_divisao i
                                 JOIN users u ON i.user_id = u.id
                                 JOIN cidades c ON i.cidade_id = c.id
                                 ORDER BY i.criado_em DESC
                                 LIMIT 50");
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse($dados);
        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    } else {
        jsonResponse(['error' => 'Parâmetro tipo inválido ou ausente'], 400);
    }

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($tipo === 'interesses') {
        // Cadastrar interesse em dividir
        $required = ['user_id', 'cidade_id', 'orcamento_maximo'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Campo obrigatório ausente: $field"], 400);
            }
        }
        $preferencias = $input['preferencias'] ?? '';

        try {
            $stmt = $pdo->prepare("INSERT INTO interesse_divisao (user_id, cidade_id, orcamento_maximo, preferencias) VALUES (:user_id, :cidade_id, :orcamento_maximo, :preferencias)");
            $stmt->execute([
                ':user_id' => $input['user_id'],
                ':cidade_id' => $input['cidade_id'],
                ':orcamento_maximo' => $input['orcamento_maximo'],
                ':preferencias' => $preferencias
            ]);
            jsonResponse(['success' => true, 'message' => 'Interesse cadastrado com sucesso', 'id' => $pdo->lastInsertId()], 201);
        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }

    } elseif ($tipo === 'imoveis') {
        // Cadastrar imóvel para dividir
        $required = ['user_id', 'cidade_id', 'titulo', 'categoria', 'localizacao', 'detalhes', 'valor_aluguel'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Campo obrigatório ausente: $field"], 400);
            }
        }
        $valor_caucao = $input['valor_caucao'] ?? 0;
        $quarto_disponivel = isset($input['quarto_disponivel']) ? (bool)$input['quarto_disponivel'] : true;

        try {
            $stmt = $pdo->prepare("INSERT INTO imoveis_divisao (user_id, cidade_id, titulo, categoria, localizacao, detalhes, valor_aluguel, valor_caucao, quarto_disponivel) VALUES (:user_id, :cidade_id, :titulo, :categoria, :localizacao, :detalhes, :valor_aluguel, :valor_caucao, :quarto_disponivel)");
            $stmt->execute([
                ':user_id' => $input['user_id'],
                ':cidade_id' => $input['cidade_id'],
                ':titulo' => $input['titulo'],
                ':categoria' => $input['categoria'],
                ':localizacao' => $input['localizacao'],
                ':detalhes' => $input['detalhes'],
                ':valor_aluguel' => $input['valor_aluguel'],
                ':valor_caucao' => $valor_caucao,
                ':quarto_disponivel' => $quarto_disponivel
            ]);
            jsonResponse(['success' => true, 'message' => 'Imóvel para dividir cadastrado com sucesso', 'id' => $pdo->lastInsertId()], 201);
        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }

    } else {
        jsonResponse(['error' => 'Parâmetro tipo inválido ou ausente'], 400);
    }

} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
?>
