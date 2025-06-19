<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../utils/response.php';

try {
    $stmt = $pdo->query("SELECT i.*, c.nome AS cidade_nome
                         FROM imoveis i
                         JOIN cidades c ON i.cidade_id = c.id
                         ORDER BY i.criado_em DESC
                         LIMIT 50");
    $imoveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse($imoveis);
} catch (Exception $e) {
    jsonResponse(["error" => $e->getMessage()], 500);
}
?>
