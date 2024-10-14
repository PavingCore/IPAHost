<?php
$baseDir = realpath('./files');
if ($baseDir === false) {
    die("Base directory is not valid.");
}
function securePath($relativePath, $baseDir) {
    $fullPath = realpath($baseDir . '/' . $relativePath);
    if ($fullPath === false || strpos($fullPath, $baseDir) !== 0) {
        return false;
    }
    return $fullPath;
}
function listOrSearch($relativePath, $off, $lim, $key = '') {
    global $baseDir;
    $dir = securePath($relativePath, $baseDir);
    if ($dir === false || !is_dir($dir)) return [];
    $items = array_diff(scandir($dir), ['.', '..']);
    $res = [];
    $count = 0;
    foreach ($items as $item) {
        $itemPath = "$relativePath/$item";
        $fullPath = "$dir/$item";
        if (is_dir($fullPath) || pathinfo($item, PATHINFO_EXTENSION) === 'ipa') {
            if (!$key || stripos($item, $key) !== false) {
                if ($count >= $off && $count < $off + $lim) {
                    $res[] = ['name' => htmlspecialchars($item, ENT_QUOTES, 'UTF-8'), 'path' => urlencode(ltrim($itemPath, './')), 'isDir' => is_dir($fullPath) ];
                }
                $count++;
            }
        }
    }
    return $res;
}
if (isset($_GET['download'])) {
    $relativeFile = urldecode($_GET['download']);
    $filePath = securePath($relativeFile, $baseDir);
    if ($filePath !== false && is_file($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        die('Invalid file path.');
    }
}
$key = htmlspecialchars($_GET['keyword']??'', ENT_QUOTES, 'UTF-8');
$relativePath = $_GET['path']??'';
$path = securePath($relativePath, $baseDir) ? : $baseDir;
$page = max(1, (int)($_GET['page']??1));
$lim = 10;
$off = ($page - 1) * $lim;
$items = listOrSearch($relativePath, $off, $lim, $key);
$total = count(listOrSearch($relativePath, 0, PHP_INT_MAX, $key));
$pages = ceil($total / $lim);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPAHost</title>
    <style>
        body { background-color: #121212; color: #e1e1e1; font-family: sans-serif; margin: 0; padding: 20px; text-align: center; }
        input[type="text"], button { padding: 8px; border: none; border-radius: 3px; margin: 5px; }
        input[type="text"] { width: 60%; background-color: #1f1f1f; color: #e1e1e1; }
        button { background-color: #238636; color: white; cursor: pointer; }
        ul { list-style: none; padding: 0; }
        li { margin: 5px 0; }
        a { color: #58a6ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'none';">
</head>
<body>

<h1>IPAHost</h1>

<form method="get">
    <input type="text" name="keyword" placeholder="Search IPAs..." value="<?=htmlspecialchars($key); ?>">
    <button type="submit">Search</button>
</form>

<div class="go-up">
    <?php
if ($relativePath !== '' && realpath($baseDir . '/' . $relativePath) !== $baseDir): ?>
        <a href="?path=<?=urlencode(dirname($relativePath)); ?>">&#x2B06; Go Up</a>
    <?php
endif; ?>
</div>

<ul>
    <?php if (count($items) > 0): ?>
        <?php foreach ($items as $item): ?>
            <li>
                <a href="?<?=$item['isDir'] ? 'path=' : 'download=' ?><?=$item['path']; ?>"><?=htmlspecialchars($item['name']); ?></a>
            </li>
        <?php
    endforeach; ?>
    <?php
else: ?>
        <li>No results found</li>
    <?php
endif; ?>
</ul>

<?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?path=<?=urlencode($relativePath); ?>&page=<?=$page - 1; ?>">&#x25C0; Previous</a>
        <?php
    endif; ?>
        <?php for ($i = 1;$i <= $pages;$i++): ?>
            <a href="?path=<?=urlencode($relativePath); ?>&page=<?=$i; ?>" style="<?=$i === $page ? 'font-weight:bold;' : ''; ?>"><?=$i; ?></a>
        <?php
    endfor; ?>
        <?php if ($page < $pages): ?>
            <a href="?path=<?=urlencode($relativePath); ?>&page=<?=$page + 1; ?>">Next &#x25B6;</a>
        <?php
    endif; ?>
    </div>
<?php
endif; ?>

</body>
</html>
