<?php
/**
 * admin/item-delete.php ― 商品削除（POSTのみ受け付ける）
 */
require_once __DIR__ . '/../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: item-list.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

// 画像ファイルも一緒に削除する
$stmt = $pdo->prepare('SELECT image FROM items WHERE id = ?');
$stmt->execute([$id]);
$image = $stmt->fetchColumn();
if ($image) {
    $path = __DIR__ . '/../uploads/items/' . $image;
    if (is_file($path)) {
        unlink($path);
    }
}

// TODO（初級）: items テーブルから該当行を DELETE する（必ずプレースホルダを使うこと）

header('Location: item-list.php');
exit;
