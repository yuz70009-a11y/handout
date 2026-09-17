<?php
function handleImageUpload(array $file, string $destDir): array
{
    $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize     = 2 * 1024 * 1024; // 2MB

    // ファイルが送られていない場合（画像なしでOKなフォーム用）
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => null, 'error' => null];
    }

    // PHPレベルのエラー
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'アップロードに失敗しました'];
    }

    // TODO（中級）: サイズチェック
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'filename' => null, 'error' => 'ファイルサイズが大きすぎます（最大2MB）'];
    }

    // TODO（中級）: 拡張子チェック
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'filename' => null, 'error' => '許可されていない拡張子です'];
    }

    // TODO（上級）: MIMEタイプチェック（実体チェック）
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMime, true)) {
        return ['ok' => false, 'filename' => null, 'error' => '画像ファイルではありません（MIMEチェックNG）'];
    }

    // TODO（上級）: ランダムファイル名生成（元の名前は信用しない）
    $randomName = bin2hex(random_bytes(16)) . '.' . $ext;

    // 保存先ディレクトリがなければ作成
    if (!is_dir($destDir)) {
        mkdir($destDir, 0777, true);
    }

    $savePath = rtrim($destDir, '/') . '/' . $randomName;

    // TODO: move_uploaded_file() で保存
    if (!move_uploaded_file($file['tmp_name'], $savePath)) {
        return ['ok' => false, 'filename' => null, 'error' => 'ファイルの保存に失敗しました'];
    }

    return ['ok' => true, 'filename' => $randomName, 'error' => null];
}
