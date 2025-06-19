<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../utils/response.php';

// Permite apenas POST para upload e GET para listar imagens de um imóvel
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Listar imagens de um imóvel (imovel_id via query string)
    $imovel_id = isset($_GET['imovel_id']) ? (int)$_GET['imovel_id'] : 0;
    if ($imovel_id <= 0) {
        jsonResponse(['error' => 'Parâmetro imovel_id obrigatório'], 400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id, tipo, url FROM imagens WHERE imovel_id = :imovel_id");
        $stmt->execute([':imovel_id' => $imovel_id]);
        $imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse($imagens);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }
    
} elseif ($method === 'POST') {
    // Upload de imagem/vídeo
    // Espera multipart/form-data com campos: imovel_id, tipo ('imagem' ou 'video'), arquivo 'file'

    if (!isset($_POST['imovel_id']) || !isset($_FILES['file'])) {
        jsonResponse(['error' => 'Parâmetros imovel_id e arquivo são obrigatórios'], 400);
    }

    $imovel_id = (int)$_POST['imovel_id'];
    $tipo = $_POST['tipo'] ?? 'imagem';
    $allowedTypes = ['imagem', 'video'];
    if (!in_array($tipo, $allowedTypes)) {
        jsonResponse(['error' => 'Tipo inválido, deve ser imagem ou video'], 400);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Erro no upload do arquivo'], 400);
    }

    // Validar extensão (simplificado)
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $validImageExt = ['jpg', 'jpeg', 'png', 'gif'];
    $validVideoExt = ['mp4', 'avi', 'mov', 'mkv'];

    if ($tipo === 'imagem' && !in_array($ext, $validImageExt)) {
        jsonResponse(['error' => 'Extensão de imagem inválida'], 400);
    }
    if ($tipo === 'video' && !in_array($ext, $validVideoExt)) {
        jsonResponse(['error' => 'Extensão de vídeo inválida'], 400);
    }

    // Pasta para salvar uploads (crie essa pasta no servidor)
    $uploadDir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Nome do arquivo: timestamp + original name para evitar conflitos
    $filename = time() . '_' . basename($file['name']);
    $targetFile = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        jsonResponse(['error' => 'Falha ao mover arquivo'], 500);
    }

    // Salvar URL no banco (assumindo que /public/uploads/ está acessível em URL)
    $url = '/uploads/' . $filename;

    try {
        $stmt = $pdo->prepare("INSERT INTO imagens (imovel_id, tipo, url) VALUES (:imovel_id, :tipo, :url)");
        $stmt->execute([
            ':imovel_id' => $imovel_id,
            ':tipo' => $tipo,
            ':url' => $url
        ]);
        jsonResponse(['success' => true, 'url' => $url], 201);
    } catch (Exception $e) {
        jsonResponse(['error' => $e->getMessage()], 500);
    }

} else {
    jsonResponse(['error' => 'Método não permitido'], 405);
}
?>
