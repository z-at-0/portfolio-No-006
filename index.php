<?php
// ==========================================
// portfolio-No-006: GitHub API Search App (PHP版)
// ==========================================

$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

$userData = null;
$repoData = [];
$rateLimit = null;

function fetchGitHubCached($url, $expire = 600) {
    $cacheDir = './cache/';
    if (!is_dir($cacheDir)) { mkdir($cacheDir, 0777, true); }
    if (rand(1, 10) === 1) {
        $files = glob($cacheDir . 'cache_*.json');
        foreach ($files as $file) {
            if (time() - filemtime($file) > 86400) { unlink($file); }
        }
    }
    $cacheFile = $cacheDir . 'cache_' . md5($url) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $expire)) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    $options = ['http' => ['method' => 'GET', 'header' => ['User-Agent: PHP-GitHub-App-V3']]];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        file_put_contents($cacheFile, $response);
        return json_decode($response, true);
    }
    return null;
}

if ($username) {
    $userData = fetchGitHubCached("https://api.github.com/users/{$username}");
    if ($userData) {
        $perPage = 5; 
        $repoData = fetchGitHubCached("https://api.github.com/users/{$username}/repos?per_page={$perPage}&page={$page}&sort=updated");
        if ($repoData && is_array($repoData)) {
            usort($repoData, fn($a, $b) => $b['stargazers_count'] <=> $a['stargazers_count']);
        }
    }
    $rateRes = @file_get_contents("https://api.github.com/rate_limit", false, stream_context_create([
        'http' => ['method' => 'GET', 'header' => ['User-Agent: PHP-GitHub-App-V3']]
    ]));
    $rateLimit = json_decode($rateRes, true);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>portfolio-No-006 PHP GitHub API App</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; color: #333; background-color: #f9f9f9; }
        .container { background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { border-left: 5px solid #28a745; padding-left: 15px; margin-bottom: 25px; }
        .search-form { margin-bottom: 20px; }
        .search-form input { padding: 10px; width: 250px; border: 1px solid #ddd; border-radius: 4px; }
        .search-form button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .rate-info { font-size: 0.85em; color: #666; background: #eee; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .profile { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding: 15px; background: #f0fff4; border-radius: 8px; }
        .profile img { border-radius: 50%; border: 2px solid #fff; }
        .repo-item { border: 1px solid #eee; border-radius: 8px; padding: 20px; margin-bottom: 15px; background: #fff; }
        .repo-name { font-size: 1.1em; font-weight: bold; color: #28a745; text-decoration: none; }
        .commit-box { font-size: 0.85em; background: #fcfcfc; padding: 12px; margin-top: 15px; border-left: 4px solid #28a745; }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 20px; margin-top: 30px; }
        .btn { padding: 8px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .page-num { font-weight: bold; color: #666; }
    </style>
</head>
<body>

<div class="container">
    <h2>portfolio-No-006 PHP GitHub API App</h2>

    <div class="search-form">
        <form action="" method="GET">
            <input type="text" name="username" placeholder="GitHubユーザー名を入力" value="<?php echo $username; ?>" required>
            <input type="hidden" name="page" value="1">
            <button type="submit">検索</button>
        </form>
    </div>

    <?php if ($rateLimit): ?>
        <div class="rate-info">
            API残り回数: <strong><?php echo $rateLimit['rate']['remaining']; ?></strong> / <?php echo $rateLimit['rate']['limit']; ?>
            <span style="margin-left: 10px; color: #28a745;">(キャッシュ機能：有効)</span>
        </div>
    <?php endif; ?>

    <hr>

    <?php if ($userData): ?>
        <div class="profile">
            <img src="<?php echo $userData['avatar_url']; ?>" width="70" height="70" alt="avatar">
            <div>
                <h3 style="margin: 0;"><?php echo htmlspecialchars($userData['login']); ?></h3>
                <p style="margin: 5px 0 0; font-size: 0.9em;"><?php echo htmlspecialchars($userData['bio'] ?? "自己紹介なし"); ?></p>
            </div>
        </div>

        <div id="repos">
            <?php if (empty($repoData)): ?>
                <p>これ以上のリポジトリはありません。</p>
                <?php if ($page > 1): ?>
                    <div class="pagination"><a href="?username=<?php echo urlencode($username); ?>&page=<?php echo $page - 1; ?>" class="btn">← 前のページへ戻る</a></div>
                <?php endif; ?>
            <?php else: ?>
                <h4 style="margin-bottom: 15px;">リポジトリ一覧（スター順）</h4>
                
                <?php foreach ($repoData as $repo): ?>
                    <div class="repo-item">
                        <span>⭐ <?php echo $repo['stargazers_count']; ?></span> - 
                        <a href="<?php echo $repo['html_url']; ?>" target="_blank" class="repo-name">
                            <?php echo htmlspecialchars($repo['name']); ?>
                        </a>
                        <p style="margin: 5px 0; font-size: 0.9em; color: #666;"><?php echo htmlspecialchars($repo['description'] ?? '説明なし'); ?></p>

                        <div class="commit-box">
                            <strong>最近のコミット:</strong><br>
                            <?php
                            $commits = fetchGitHubCached("https://api.github.com/repos/{$username}/{$repo['name']}/commits?per_page=3");
                            if ($commits && is_array($commits)):
                                foreach ($commits as $c):
                                    echo "・ " . htmlspecialchars(mb_strimwidth($c['commit']['message'], 0, 60, "...")) . "<br>";
                                endforeach;
                            else: echo "コミット情報なし"; endif;
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?username=<?php echo urlencode($username); ?>&page=<?php echo $page - 1; ?>" class="btn">← 前へ</a>
                    <?php endif; ?>

                    <?php if ($page > 1 && count($repoData) === 5): ?>
                        <span class="page-num">Page <?php echo $page; ?></span>
                    <?php endif; ?>

                    <?php if (count($repoData) === 5): ?>
                        <a href="?username=<?php echo urlencode($username); ?>&page=<?php echo $page + 1; ?>" class="btn">次へ →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($username): ?>
        <p style="color: #dc3545;">ユーザーが見つかりませんでした。</p>
    <?php endif; ?>
</div>

</body>
</html>