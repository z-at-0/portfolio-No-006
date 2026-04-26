<?php
// --- 設定と関数定義 ---
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '';
$userData = null;
$repoData = [];
$rateLimit = null;

// GitHub APIを叩く共通関数
function fetchGitHub($url) {
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: PHP-App-Client' // GitHub APIにはUser-Agentが必須
            ]
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    return $response ? json_decode($response, true) : null;
}

// ユーザー名が入力されていたら実行
if ($username) {
    // 1. ユーザー情報取得
    $userData = fetchGitHub("https://api.github.com/users/{$username}");
    
    // 2. リポジトリ一覧取得
    if ($userData) {
        $repoData = fetchGitHub("https://api.github.com/users/{$username}/repos?per_page=10&sort=updated");
        // スター数順にソート
        usort($repoData, fn($a, $b) => $b['stargazers_count'] <=> $a['stargazers_count']);
    }

    // 3. レート制限の取得
    $rateLimit = fetchGitHub("https://api.github.com/rate_limit");
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>portfolio-No-005 PHP GitHub API App</title>
    <style>
        .repo-item { border: 1px solid #ccc; margin: 5px; padding: 10px; }
        .commit-box { font-size: 0.9em; background: #f9f9f9; padding: 5px; margin-top: 5px; }
    </style>
</head>
<body>

<h2>portfolio-No-005 PHP GitHub API App</h2>

<form action="" method="GET">
    <input name="username" placeholder="GitHubユーザー名" value="<?php echo $username; ?>">
    <button type="submit">検索</button>
</form>

<?php if ($rateLimit): ?>
    <div id="rate">API残り回数: <?php echo $rateLimit['rate']['remaining']; ?> / <?php echo $rateLimit['rate']['limit']; ?></div>
<?php endif; ?>

<hr>

<?php if ($userData): ?>
    <div id="profile">
        <h3><?php echo $userData['login']; ?></h3>
        <img src="<?php echo $userData['avatar_url']; ?>" width="100" style="border-radius: 50%;">
        <p><?php echo $userData['bio'] ?? "自己紹介なし"; ?></p>
    </div>

    <div id="repos">
        <h4>リポジトリ一覧（スター順）</h4>
        <?php foreach ($repoData as $repo): ?>
            <div class="repo-item">
                <strong>⭐ <?php echo $repo['stargazers_count']; ?></strong> - 
                <a href="<?php echo $repo['html_url']; ?>" target="_blank"><?php echo $repo['name']; ?></a>
                
                <div class="commit-box">
                    <strong>最近のコミット:</strong><br>
                    <?php
                    $commits = fetchGitHub("https://api.github.com/repos/{$username}/{$repo['name']}/commits?per_page=3");
                    if ($commits):
                        foreach ($commits as $c):
                            echo "- " . htmlspecialchars(mb_strimwidth($c['commit']['message'], 0, 50, "...")) . "<br>";
                        endforeach;
                    else:
                        echo "コミット情報なし";
                    endif;
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($username): ?>
    <p>ユーザーが見つかりませんでした。</p>
<?php endif; ?>

</body>
</html>