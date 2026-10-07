<?php
/**
 * items.php ― 商品一覧・キーワード検索・カテゴリ絞り込み・ページネーション
 *
 * 初級：一覧表示 → すでに動きます
 * 中級：LIKE検索・カテゴリ絞り込み・0件時の表示 → TODOを埋めてください
 * 上級：検索条件を保ったままのページネーション → TODOを埋めてください
 */
require_once __DIR__ . '/../includes/config.php';

// ---- ① 検索条件の取得 ----------------------------------------------------
// TODO: $_GET から keyword（検索キーワード）と category（カテゴリ）を取得する
$keyword  = $_GET['keyword'] ?? '';
$category = $_GET['category'] ?? '';
// 商品の並び替え条件を取得
$sort = $_GET['sort'] ?? 'newest';

// 許可する並び替え条件
$allowedSorts = ['newest', 'price_asc', 'price_desc'];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}

// TODO（上級）: $_GET から page（ページ番号）を取得する。1未満なら1にする。
$page    = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page    = max(1, $page);
$perPage = 9;

// ---- ② WHERE句を動的に組み立てる（必ずプレースホルダを使うこと）------------
// TODO（中級）: $keyword が空でなければ "name LIKE :keyword" 相当の条件を追加する
//   ヒント：LIKE検索では % と _ がワイルドカードとして特別な意味を持つため、
//   ユーザー入力にこれらの文字が含まれていた場合はエスケープしてから渡すこと。
// TODO（中級）: $category が空でなければ "category = :category" 相当の条件を追加する
$conditions = [];
$params     = [];
if ($keyword !== '') {
    $conditions[] = 'name LIKE :keyword';
    $params[':keyword'] = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';
}
if ($category !== '') {
    $conditions[] = 'category = :category';
    $params[':category'] = $category;
}
$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ---- ③ 該当件数を取得し、総ページ数を計算する（上級）------------------------
// TODO: COUNT(*) で件数を取得し、$perPage で割って総ページ数を求める
// TODO: $page が総ページ数を超えていたら、総ページ数に丸める（末尾ページ対策）
$stmt = $pdo->prepare("SELECT COUNT(*) FROM items {$whereSql}");
$stmt->execute($params);
$totalCount = (int) $stmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// ---- ④ 商品一覧を取得する（初級）--------------------------------------------
// 並び替え条件を設定
$orderBy = match ($sort) {
    'price_asc'  => 'price ASC',
    'price_desc' => 'price DESC',
    default      => 'created_at DESC',
};
$sql = "SELECT id, name, price, image, stock, category
        FROM items
        {$whereSql}
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$categories = $pdo->query('SELECT DISTINCT category FROM items WHERE category IS NOT NULL ORDER BY category')
                   ->fetchAll(PDO::FETCH_COLUMN);

// TODO（上級）: ページ送りリンクで検索条件を保持するためのクエリ文字列を組み立てる関数を作る
function buildQuery(array $overrides = []): string
{
    // ヒント：$_GET の keyword / category / page を土台に、$overrides で上書きする
    $query = array_merge($_GET, $overrides);
    return '?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>商品一覧 ― Tsuchi-to-Hi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header><a href="index.php">Tsuchi-to-Hi</a></header>
    <main>
        <h1>商品一覧</h1>

        <form class="search-form" method="get" action="items.php">
            <input type="text" name="keyword" placeholder="商品名で検索"
                   value="<?= htmlspecialchars($keyword) ?>">
            <select name="category">
                <option value="">すべてのカテゴリ</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $c === $category ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="sort">
    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
        新着順
    </option>
    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>
        価格が安い順
    </option>
    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>
        価格が高い順
    </option>
</select>
            <button type="submit">検索</button>
        </form>

        <?php if (empty($items)): ?>
            <!-- TODO（中級）: 該当0件の時に、ユーザーに分かりやすいメッセージを表示する -->
            <p class="empty-state"><!-- TODO --></p>
        <?php else: ?>
            <div class="item-grid">
                <?php foreach ($items as $item): ?>
                    <a class="item-card" href="item.php?id=<?= (int) $item['id'] ?>" style="text-decoration:none; color:inherit;">
                        <img src="<?= $item['image'] ? 'uploads/items/' . htmlspecialchars($item['image']) : 'https://placehold.co/220x140' ?>" alt="">
                        <div class="name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="price">¥<?= number_format($item['price']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- TODO（上級）: ページネーションのUIを作る（前へ／各ページ番号／次へ）。
                 buildQuery() を使って検索条件を保ったままリンクを組み立てること -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= buildQuery(['page' => $page - 1]) ?>">前へ</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= buildQuery(['page' => $i]) ?>"
                       <?= $i === $page ? 'class="current"' : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= buildQuery(['page' => $page + 1]) ?>">次へ</a>
                <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
