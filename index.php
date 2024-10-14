<?php
function listOrSearch($dir, $off, $lim, $key = '') {
    if (!is_dir($dir)) return [];
    $items = array_diff(scandir($dir), ['.', '..']);
    $res = [];
    $count = 0;
    foreach ($items as $item) {
        $path = "$dir/$item";
        if (is_dir($path) || pathinfo($item, PATHINFO_EXTENSION) === 'ipa') {
            if (!$key || stripos($item, $key) !== false) {
                if ($count >= $off && $count < $off + $lim) {
                    $res[] = ['name' => $item, 'path' => $path, 'isDir' => is_dir($path), 'size' => is_file($path) ? filesize($path) : 0];
                }
                $count++;
            }
            if (is_dir($path) && $key) {
                $res = array_merge($res, listOrSearch($path, 0, PHP_INT_MAX, $key));
            }
        }
    }
    return $res;
}
function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0;$size >= 1024 && $i < count($units) - 1;$i++) {
        $size/= 1024;
    }
    return round($size, 2) . ' ' . $units[$i];
}
if (isset($_GET['download']) && file_exists($file = urldecode($_GET['download']))) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}
$key = $_GET['keyword']??'';
$path = $_GET['path']??'.';
$page = max(1, (int)($_GET['page']??1));
$lim = 10;
$off = ($page - 1) * $lim;
if (!is_dir($path)) $path = '.';
$items = listOrSearch($path, $off, $lim, $key);
$total = count(listOrSearch($path, 0, PHP_INT_MAX, $key));
$pages = ceil($total / $lim);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPAHost</title>
    <style>
        body { background-color: #121212; color: #e1e1e1; font-family: Arial, sans-serif; padding: 20px; }
        h1, .go-up, .no-results, .pagination { text-align: center; }
        form { margin: 20px auto; display: flex; justify-content: center; gap: 10px; }
        input[type="text"] { padding: 10px; border: 1px solid #444; border-radius: 5px; background-color: #1f1f1f; color: #e1e1e1; width: 90%; max-width: 600px; }
        button { padding: 10px 20px; background-color: #238636; border: none; border-radius: 5px; color: #ffffff; cursor: pointer; }
        ul { list-style: none; padding: 0; text-align: center; }
        li { margin: 10px 0; }
        a { color: #58a6ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h1>IPAHost</h1>
<p style="text-align: center; color: #e1e1e1;">Safe and secure IPA library</p>

<form method="get">
    <input type="text" name="keyword" placeholder="Search IPAs..." value="<?=htmlspecialchars($key); ?>">
    <button type="submit">Go</button>
</form>

<div class="go-up">
    <?php if ($path !== '.' && $path !== '/'): ?>
        <a href="?path=<?=urlencode(dirname($path)); ?>">&#x2B06; Go Up</a>
    <?php
endif; ?>
</div>

<ul>
    <?php if (count($items) > 0): ?>
        <?php foreach ($items as $item): ?>
            <li>
                <a href="?<?=$item['isDir'] ? 'path=' : 'download=' ?><?=urlencode($item['path']); ?>"><?=htmlspecialchars($item['name']); ?></a>
                <?php if (!$item['isDir']) echo ' - ' . formatSize($item['size']); ?>
            </li>
        <?php
    endforeach; ?>
    <?php
else: ?>
        <p class="no-results">No files or directories found</p>
    <?php
endif; ?>
</ul>

<?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?path=<?=urlencode($path); ?>&page=<?=$page - 1; ?>">&#x25C0; Previous</a>
        <?php
    endif; ?>
        <?php for ($i = 1;$i <= $pages;$i++): ?>
            <a href="?path=<?=urlencode($path); ?>&page=<?=$i; ?>" style="<?=$i === $page ? 'font-weight:bold;' : ''; ?>"><?=$i; ?></a>
        <?php
    endfor; ?>
        <?php if ($page < $pages): ?>
            <a href="?path=<?=urlencode($path); ?>&page=<?=$page + 1; ?>">Next &#x25B6;</a>
        <?php
    endif; ?>
    </div>
<?php
endif; ?>

</body>
</html>